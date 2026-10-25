"""Smoke tests for parser and deep merge; run with python -m unittest discover -s tests."""
import base64
import json
import os
import tempfile
import unittest
import zlib
from collections import defaultdict, deque
from io import StringIO
from unittest.mock import patch

os.environ.setdefault("FASTF1_DATA_DIR", tempfile.mkdtemp(prefix="pitmetric-fastf1-"))
os.environ["FASTF1_LIVE_ENABLED"] = "false"
import service  # noqa: E402
from fastapi.testclient import TestClient  # noqa: E402
from service import decode_packed, live, update_feed  # noqa: E402

class TestLiveDecoder(unittest.TestCase):
    def setUp(self):
        for key in ("session", "session_status", "drivers", "timing", "cars", "positions", "stints", "stats"):
            live[key] = {}
        live["last_message"] = live["last_timing"] = 0.0
        live["samples"] = defaultdict(lambda: deque(maxlen=900))

    def test_zipped_car_channels(self):
        packet = {"Entries": [{"Utc": "2026-10-20T15:00:00Z", "Cars": {
            "44": {"Channels": {"0": 12000, "2": 299, "3": 8, "4": 100, "5": 0, "45": 10}}
        }}]}
        compressed = zlib.compress(json.dumps(packet).encode())
        raw_deflate = compressed[2:-4]
        update_feed("CarData.z", base64.b64encode(raw_deflate).decode())
        self.assertEqual(live["cars"]["44"]["speed"], 299)
        self.assertEqual(live["cars"]["44"]["rpm"], 12000)
        self.assertEqual(len(live["samples"]["44"]), 1)

    def test_position_and_timing(self):
        update_feed("Position.z", {"Position": [{"Timestamp": "2026-10-25T15:00:00Z", "Entries": {
            "44": {"X": 120, "Y": 10, "Z": 4, "Status": "OnTrack"}
        }}]})
        update_feed("TimingData", {"Lines": {"44": {"Position": "2", "LastLapTime": {"Value": "1:31.200"}}}})
        update_feed("TimingData", {"Lines": {"44": {"GapToLeader": "+1.200"}}})
        self.assertEqual(live["positions"]["44"]["x"], 120)
        self.assertEqual(live["timing"]["44"]["LastLapTime"]["Value"], "1:31.200")
        self.assertEqual(live["timing"]["44"]["GapToLeader"], "+1.200")

    def test_snapshot_returns_samples_for_selected_drivers_not_first_three(self):
        update_feed("DriverList", {n: {"Tla": n} for n in ("1", "4", "16", "44")})
        live["samples"]["44"].append({"speed": 299})
        with patch.object(service, "TOKEN", "test-secret"):
            result = service.snapshot(x_pitmetric_token="test-secret", drivers="44")
        self.assertEqual(list(result["history"]), ["44"])
        self.assertEqual(result["history"]["44"][0]["speed"], 299)
        self.assertEqual(len(result["drivers"]), 4)

    def test_stale_or_finished_session_is_never_live(self):
        update_feed("TimingData", {"Lines": {"44": {"Position": "1"}}})
        with patch.object(service, "TOKEN", "test-secret"):
            self.assertTrue(service.snapshot(x_pitmetric_token="test-secret", drivers="")["live"])
            update_feed("SessionStatus", {"Status": "Finished"})
            self.assertFalse(service.snapshot(x_pitmetric_token="test-secret", drivers="")["live"])
            live["session_status"] = {}
            live["last_timing"] -= 60
            self.assertFalse(service.snapshot(x_pitmetric_token="test-secret", drivers="")["live"])

    def test_empty_timing_packets_do_not_mark_the_feed_live(self):
        update_feed("TimingData", {"Lines": {}})
        update_feed("CarData.z", {"Entries": []})
        update_feed("Position.z", {"Position": []})
        with patch.object(service, "TOKEN", "test-secret"):
            self.assertFalse(service.snapshot(x_pitmetric_token="test-secret", drivers="")["live"])

    def test_new_session_clears_previous_drivers_and_live_state(self):
        update_feed("SessionInfo", {"Key": 1})
        update_feed("DriverList", {"44": {"Tla": "HAM"}})
        update_feed("TimingData", {"Lines": {"44": {"Position": "1"}}})
        update_feed("SessionInfo", {"Key": 2})
        self.assertEqual(live["drivers"], {})
        self.assertEqual(live["timing"], {})
        self.assertEqual(live["last_timing"], 0)

    def test_initial_signalr_compressed_payload_is_decoded(self):
        packet = {"Entries": [{"Cars": {"44": {"Channels": {"2": 301}}}}]}
        compressed = base64.b64encode(zlib.compress(json.dumps(packet).encode())[2:-4]).decode()
        from signalrcore.messages.completion_message import CompletionMessage
        recorder = service.PitMetricRecorder(filename="unused")
        recorder._output_file = StringIO()
        recorder._on_message(CompletionMessage(invocation_id="1", result={"CarData.z": compressed}, error=None))
        self.assertEqual(live["cars"]["44"]["speed"], 301)

    def test_signalr_transport_supports_fastf1_auth_and_subscriptions(self):
        import inspect
        from signalrcore.hub_connection_builder import HubConnectionBuilder
        connection = HubConnectionBuilder().with_url("wss://example.test/signalrcore", options={
            "verify_ssl": True, "access_token_factory": lambda: "test-token", "headers": {},
        }).build()
        self.assertTrue(connection.verify_ssl)
        self.assertIn("on_invocation", inspect.signature(connection.send).parameters)

    def test_private_endpoints_require_token_and_validate_requests(self):
        client = TestClient(service.app)
        with patch.object(service, "TOKEN", "test-secret"):
            for path in ("/api/live", "/api/events?year=2026", "/api/history?year=2026&round=1&session=R&drivers=VER"):
                self.assertEqual(client.get(path).status_code, 401)
                self.assertEqual(client.get(path, headers={"X-PitMetric-Token": "wrong"}).status_code, 401)
            self.assertEqual(client.get("/api/live", headers={"X-PitMetric-Token": "test-secret"}).status_code, 200)
            self.assertEqual(client.get("/api/live?drivers=1,2,3,4", headers={"X-PitMetric-Token": "test-secret"}).status_code, 422)
        with patch.object(service, "TOKEN", ""):
            self.assertEqual(client.get("/api/live").status_code, 503)

if __name__ == "__main__":
    unittest.main()
