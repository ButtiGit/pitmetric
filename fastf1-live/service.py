"""Private FastF1 bridge for PitMetric. One process per service; never expose publicly."""
import base64
import hmac
import json
import logging
import math
import os
import threading
import time
import zlib
from collections import defaultdict, deque
from concurrent.futures import ThreadPoolExecutor
from contextlib import asynccontextmanager
from copy import deepcopy
from datetime import datetime, timezone
from functools import lru_cache
from pathlib import Path
from typing import Any

import fastf1
from fastapi import FastAPI, Header, HTTPException, Query
from fastf1.livetiming.client import SignalRClient

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("pitmetric.fastf1")
DATA_DIR = Path(os.getenv("FASTF1_DATA_DIR", "/data"))
DATA_DIR.mkdir(parents=True, exist_ok=True)
(DATA_DIR / "cache").mkdir(parents=True, exist_ok=True)
fastf1.Cache.configure(cache_dir=str(DATA_DIR / "cache"))
TOKEN = os.getenv("PITMETRIC_F1_TOKEN", "").strip()
LIVE_ENABLED = os.getenv("FASTF1_LIVE_ENABLED", "true").lower() in ("1", "true", "yes")
lock = threading.RLock()
history_executor = ThreadPoolExecutor(max_workers=2, thread_name_prefix="fastf1-history")
history_lock = threading.RLock()
history_jobs: dict[str, Any] = {}
live: dict[str, Any] = {
    "connected": False, "last_message": 0.0, "last_timing": 0.0, "session": {}, "session_status": {},
    "track": {}, "lap_count": {}, "weather": {}, "drivers": {}, "timing": {},
    "stints": {}, "stats": {}, "cars": {}, "positions": {},
    "messages": deque(maxlen=100), "samples": defaultdict(lambda: deque(maxlen=900)),
    "error": None, "channels": set(),
}

def require_token(authorization: str | None) -> None:
    if not TOKEN:
        raise HTTPException(503, "FastF1 bridge is not configured")
    if not authorization or not hmac.compare_digest(authorization, TOKEN):
        raise HTTPException(401, "Invalid bridge token")

def merge(old: dict, new: dict) -> dict:
    for key, value in new.items():
        if isinstance(value, dict) and isinstance(old.get(key), dict):
            merge(old[key], value)
        else:
            old[key] = value
    return old

def decode_packed(payload: Any) -> dict:
    if isinstance(payload, dict):
        return payload
    if not isinstance(payload, str):
        return {}
    packed = base64.b64decode(payload)
    for mode in (-zlib.MAX_WBITS, zlib.MAX_WBITS):
        try:
            result = json.loads(zlib.decompress(packed, mode))
            return result if isinstance(result, dict) else {}
        except (ValueError, zlib.error, UnicodeDecodeError):
            pass
    return {}

CHANNELS = {"0": "rpm", "2": "speed", "3": "gear", "4": "throttle", "5": "brake", "45": "drs"}

def update_feed(topic: str, data: Any, timestamp: str = "") -> None:
    if topic.endswith(".z"):
        try:
            data = decode_packed(data)
        except Exception:
            logger.exception("Unable to decode telemetry %s", topic)
            return
    if not isinstance(data, dict):
        return

    with lock:
        live["last_message"] = time.time()
        live["channels"].add(topic)
        has_timing = (topic == "TimingData" and bool(data.get("Lines")))
        has_cars = (topic == "CarData.z" and any(entry.get("Cars") for entry in data.get("Entries", []) if isinstance(entry, dict)))
        has_positions = (topic == "Position.z" and any(entry.get("Entries") for entry in data.get("Position", []) if isinstance(entry, dict)))
        if has_timing or has_cars or has_positions:
            live["last_timing"] = time.time()
        if topic == "SessionInfo":
            previous = live["session"].get("Key")
            current = data.get("Key")
            if previous is not None and current is not None and previous != current:
                for key in ("session", "session_status", "drivers", "track", "weather", "lap_count",
                            "timing", "stints", "cars", "positions", "stats"):
                    live[key] = {}
                live["last_timing"] = 0.0
                live["samples"] = defaultdict(lambda: deque(maxlen=900))
                live["messages"].clear()
            merge(live["session"], data)
        elif topic == "SessionStatus":
            merge(live["session_status"], data)
        elif topic == "DriverList":
            merge(live["drivers"], data)
        elif topic == "TimingData":
            merge(live["timing"], data.get("Lines", {}))
        elif topic == "TimingAppData":
            merge(live["stints"], data.get("Lines", {}))
        elif topic == "TimingStats":
            merge(live["stats"], data.get("Lines", {}))
        elif topic == "WeatherData":
            merge(live["weather"], data)
        elif topic == "LapCount":
            merge(live["lap_count"], data)
        elif topic == "TrackStatus":
            merge(live["track"], data)
        elif topic == "RaceControlMessages":
            messages = data.get("Messages", [])
            if isinstance(messages, dict):
                messages = list(messages.values())
            for message in messages if isinstance(messages, list) else []:
                if isinstance(message, dict) and message not in live["messages"]:
                    live["messages"].append(message)
        elif topic == "Position.z":
            for entry in data.get("Position", []):
                if not isinstance(entry, dict):
                    continue
                for number, pos in entry.get("Entries", {}).items():
                    if isinstance(pos, dict):
                        merge(live["positions"].setdefault(str(number), {}), {
                            "x": pos.get("X"), "y": pos.get("Y"), "z": pos.get("Z"),
                            "status": pos.get("Status"), "utc": entry.get("Timestamp", timestamp),
                        })
        elif topic == "CarData.z":
            for entry in data.get("Entries", []):
                if not isinstance(entry, dict):
                    continue
                utc = entry.get("Utc", timestamp)
                for number, content in entry.get("Cars", {}).items():
                    if not isinstance(content, dict):
                        continue
                    number = str(number)
                    values = {CHANNELS[str(key)]: value
                              for key, value in content.get("Channels", {}).items()
                              if str(key) in CHANNELS}
                    values["utc"] = utc
                    merge(live["cars"].setdefault(number, {}), values)
                    if any(name in values for name in ("speed", "rpm", "gear")):
                        live["samples"][number].append(dict(live["cars"][number]))

class PitMetricRecorder(SignalRClient):
    """FastF1 provides transport/recording; we decode incoming packets for live UI."""
    def _on_message(self, message: Any) -> None:
        super()._on_message(message)
        if isinstance(message, list) and len(message) >= 2 and isinstance(message[0], str):
            update_feed(message[0], message[1], str(message[2]) if len(message) > 2 else "")
        elif hasattr(message, "result") and isinstance(message.result, dict):
            for topic, raw in message.result.items():
                try:
                    decoded = json.loads(raw) if isinstance(raw, str) and not topic.endswith(".z") else raw
                    update_feed(topic, decoded)
                except (TypeError, ValueError):
                    logger.debug("Unrecognized initial payload for %s", topic)

def record_forever() -> None:
    while True:
        filename = DATA_DIR / ("live-" + datetime.now(timezone.utc).strftime("%Y%m%d") + ".txt")
        recorder = PitMetricRecorder(filename=str(filename), filemode="a", timeout=90)
        try:
            with lock:
                live["error"] = None
            recorder.start()
        except Exception as exc:
            logger.warning("FastF1 stream disconnected: %s", exc)
            with lock:
                live["error"] = type(exc).__name__
        finally:
            with lock:
                live["connected"] = False
            try:
                if recorder._connection:
                    recorder._connection.stop()
                if recorder._output_file and not recorder._output_file.closed:
                    recorder._output_file.close()
            except Exception:
                pass
        time.sleep(30)

@asynccontextmanager
async def lifespan(app: FastAPI):
    if TOKEN and LIVE_ENABLED:
        threading.Thread(target=record_forever, daemon=True, name="fastf1-stream").start()
    yield

app = FastAPI(title="PitMetric FastF1 private bridge", docs_url=None, redoc_url=None,
              openapi_url=None, lifespan=lifespan)

@app.get("/health")
def health() -> dict:
    with lock:
        return {"ok": True, "configured": bool(TOKEN), "last_message": live["last_message"],
                "version": fastf1.__version__}

@app.get("/api/live")
def snapshot(x_pitmetric_token: str | None = Header(default=None),
             drivers: str = Query(default="", max_length=100, pattern=r"^(\d{1,3}(,\d{1,3}){0,2})?$")) -> dict:
    require_token(x_pitmetric_token)
    selected = set(drivers.split(",")) if drivers else set()
    with lock:
        age = round(time.time() - live["last_message"]) if live["last_message"] else None
        timing_age = round(time.time() - live["last_timing"]) if live["last_timing"] else None
        session_state = str(live["session_status"].get("Status", "")).lower()
        active = (timing_age is not None and timing_age < 40
                  and session_state not in ("finished", "finalised", "inactive", "aborted"))
        available = list(live["drivers"]) or list(live["timing"])
        numbers = available
        history_numbers = [n for n in numbers if n in selected][:3]
        return deepcopy({
            "source": "FastF1 SignalR live", "live": active, "age_seconds": age,
            "telemetry_age_seconds": timing_age,
            "error": live["error"] if not active else None,
            "session": live["session"].copy(), "session_status": live["session_status"].copy(),
            "weather": live["weather"].copy(), "track": live["track"].copy(),
            "lap_count": live["lap_count"].copy(), "channels": sorted(live["channels"]),
            "drivers": {
                n: {
                    **(live["drivers"].get(n) or {}), "number": n,
                    "timing": live["timing"].get(n, {}), "stints": live["stints"].get(n, {}),
                    "stats": live["stats"].get(n, {}), "car": live["cars"].get(n, {}),
                    "position": live["positions"].get(n, {}),
                } for n in numbers
            },
            "history": {n: list(live["samples"][n])[-240:] for n in history_numbers},
            "race_control": list(live["messages"])[-35:],
        })

@lru_cache(maxsize=5)
def schedule(year: int) -> list[dict]:
    frame = fastf1.get_event_schedule(year, include_testing=False)
    rows = []
    for _, item in frame.iterrows():
        if int(item.get("RoundNumber", 0)) <= 0:
            continue
        sessions = []
        for n in range(1, 6):
            title, day = item.get(f"Session{n}"), item.get(f"Session{n}DateUtc")
            if isinstance(title, str) and title:
                sessions.append({"name": title, "date_utc": day.isoformat() if hasattr(day, "isoformat") and not str(day).startswith("NaT") else None})
        rows.append({"round": int(item["RoundNumber"]), "name": str(item["EventName"]),
                     "country": str(item["Country"]), "sessions": sessions})
    return rows

@app.get("/api/events")
def events(year: int = Query(default=datetime.now(timezone.utc).year, ge=2018, le=2100),
           x_pitmetric_token: str | None = Header(default=None)) -> dict:
    require_token(x_pitmetric_token)
    return {"year": year, "events": schedule(year)}

def safe_number(value: Any) -> float | int | None:
    try:
        f = float(value)
        if math.isfinite(f):
            return round(f, 3)
    except (TypeError, ValueError):
        pass
    return None

def sample_lap(lap, max_points: int = 350) -> tuple[list[dict], list[dict]]:
    car = lap.get_car_data().add_distance()
    if car.empty:
        return [], []
    position = lap.get_pos_data()
    step = max(1, math.ceil(len(car) / max_points))
    points = []
    for _, row in car.iloc[::step].iterrows():
        points.append({
            "t": round(row["Time"].total_seconds(), 3) if hasattr(row["Time"], "total_seconds") else None,
            "speed": safe_number(row.get("Speed")), "rpm": safe_number(row.get("RPM")),
            "gear": safe_number(row.get("nGear")), "throttle": safe_number(row.get("Throttle")),
            "brake": int(bool(row.get("Brake"))) if row.get("Brake") is not None else None,
            "drs": safe_number(row.get("DRS")), "distance": safe_number(row.get("Distance"))
        })
    # Position samples have their own time base and are returned separately to avoid false alignment.
    if position is not None and not position.empty:
        pos_step = max(1, math.ceil(len(position) / max_points))
        coords = [{"t": round(row["Time"].total_seconds(), 3),
                   "x": safe_number(row.get("X")), "y": safe_number(row.get("Y")),
                   "z": safe_number(row.get("Z")), "status": str(row.get("Status", ""))}
                  for _, row in position.iloc[::pos_step].iterrows()]
    else:
        coords = []
    return points, coords

@app.get("/api/history")
def history(year: int = Query(ge=2018, le=2100),
            round: int = Query(ge=1, le=30),
            session: str = Query(pattern=r"^(FP1|FP2|FP3|Q|SQ|S|R)$"),
            drivers: str = Query(max_length=30, pattern=r"^[A-Za-z0-9]{1,3}(,[A-Za-z0-9]{1,3}){0,2}$"),
            lap: str = Query(default="fastest", pattern=r"^(fastest|latest|[1-9][0-9]?)$"),
            x_pitmetric_token: str | None = Header(default=None)) -> dict:
    require_token(x_pitmetric_token)
    requested = [d.strip().upper() for d in drivers.split(",") if d.strip()][:3]
    if not requested:
        raise HTTPException(422, "Select at least one driver")
    key = f"{year}:{round}:{session}:{','.join(requested)}:{lap}"
    with history_lock:
        if key not in history_jobs:
            # Bound retained session data: never accumulate an unbounded FastF1 archive.
            for old_key in list(history_jobs):
                if len(history_jobs) < 5:
                    break
                if history_jobs[old_key].done():
                    del history_jobs[old_key]
            if len(history_jobs) >= 5:
                raise HTTPException(503, "Too many historical requests in progress; retry shortly")
            history_jobs[key] = history_executor.submit(build_history, year, round, session, requested, lap)
        future = history_jobs[key]
    if not future.done():
        return {"pending": True, "message": "FastF1 is processing the session"}
    try:
        return future.result()
    except Exception:
        with history_lock:
            history_jobs.pop(key, None)
        raise


def build_history(year: int, round: int, session: str, requested: list[str], lap: str) -> dict:
    try:
        race = fastf1.get_session(year, round, session)
        race.load(laps=True, telemetry=True, weather=True, messages=True)
        result = {}
        for code in requested:
            laps = race.laps.pick_drivers(code)
            if laps.empty:
                result[code] = {"error": "Driver or laps unavailable"}
                continue
            laps = laps[laps["LapTime"].notna()]
            if laps.empty:
                result[code] = {"error": "No timed laps available"}
                continue
            selected = (laps.pick_fastest() if lap == "fastest" else
                        laps.sort_values("LapNumber").iloc[-1] if lap == "latest" else
                        laps[laps["LapNumber"] == int(lap)].iloc[-1] if not laps[laps["LapNumber"] == int(lap)].empty else None)
            if selected is None or selected.empty:
                result[code] = {"error": "Lap unavailable"}
                continue
            telemetry, positions = sample_lap(selected)
            result[code] = {
                "driver": code, "lap": int(selected["LapNumber"]), "compound": str(selected.get("Compound", "")),
                "lap_seconds": safe_number(selected["LapTime"].total_seconds()),
                "available_laps": [int(n) for n in laps["LapNumber"].dropna().tolist()],
                "telemetry": telemetry, "position": positions,
            }
        return {"source": "FastF1 archive", "live": False, "year": year, "round": round,
                "event": str(race.event["EventName"]), "session": session, "drivers": result}
    except HTTPException:
        raise
    except Exception as exc:
        logger.warning("FastF1 archive unavailable: %s", exc)
        raise HTTPException(503, "Historical data not yet available or FastF1 upstream unavailable") from exc
