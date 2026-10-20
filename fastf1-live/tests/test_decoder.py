"""Smoke tests for parser and deep merge; run with python -m unittest discover -s tests."""
import base64
import json
import os
import tempfile
import unittest
import zlib

os.environ.setdefault("FASTF1_DATA_DIR", tempfile.mkdtemp(prefix="pitmetric-fastf1-"))
from service import decode_packed, live, update_feed  # noqa: E402

class TestLiveDecoder(unittest.TestCase):
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
        update_feed("Position.z", {"Entries": [{"Cars": {
            "44": {"Position": {"X": 120, "Y": 10, "Z": 4}, "Status": "OnTrack"}
        }}]})
        update_feed("TimingData", {"Lines": {"44": {"Position": "2", "LastLapTime": {"Value": "1:31.200"}}}})
        update_feed("TimingData", {"Lines": {"44": {"GapToLeader": "+1.200"}}})
        self.assertEqual(live["positions"]["44"]["x"], 120)
        self.assertEqual(live["timing"]["44"]["LastLapTime"]["Value"], "1:31.200")
        self.assertEqual(live["timing"]["44"]["GapToLeader"], "+1.200")

if __name__ == "__main__":
    unittest.main()
