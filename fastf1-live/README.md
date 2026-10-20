# PitMetric F1 Live: private FastF1 bridge

This is a **separate Python service**. Never install its dependencies into the running Laravel/Nixpacks container. It runs FastF1's live SignalR client and parses incoming packets into a rolling snapshot; FastF1's own recorder does **not** process live data during the session.

## Coolify
1. Create a **second application** from this repository, Dockerfile at fastf1-live/Dockerfile, build context fastf1-live. Connect it to PitMetric's private network.
2. Set PITMETRIC_F1_TOKEN to a strong random shared secret (same value in Laravel), FASTF1_LIVE_ENABLED=true, FASTF1_DATA_DIR=/data. Do not expose the Python service on a public domain.
3. Mount its own persistent volume at /data for the FastF1 cache and raw captures. Its raw live files can be large; configure disk monitoring/retention.
4. Set in the Laravel Coolify service PITMETRIC_F1_URL=http://<internal-python-service>:8080 and PITMETRIC_F1_TOKEN=<same-secret>. Deploy Python service first.
5. Open /studio/f1-live as an authorized update editor. The page reports 'offline' if the Python service is absent; the rest of PitMetric is unaffected.

## F1 TV authentication required for full live telemetry

FastF1 3.7+ uses the newer F1 SignalR Core endpoint. The full live feed generally requires a valid **F1 TV Access/Pro/Premium** entitlement and authentication; a shared secret between PHP and the Python bridge **does not grant upstream F1 TV access**.

- Authenticate with a legitimate F1 TV account using FastF1's supported authentication flow (for example, FastF1 Companion on a trusted computer). Never add login credentials or session tokens to GitHub, Laravel .env or JavaScript.
- Securely copy the resulting FastF1 token file `f1auth.json` into the **private persistent Python volume** at `/data/auth/fastf1/f1auth.json`. This path is used because the bridge container sets `XDG_DATA_HOME=/data/auth`.
- Verify file permissions and renew the F1 TV token whenever it expires. The live client will try to authenticate using this file. Without a valid token, Formula 1 may return no or limited live data even if the bridge itself reports healthy.
- If the required subscription or authentication is unavailable, use the historical FastF1 analysis after data is published. The UI must not pretend that cached history is live.

## Accuracy and limits
- Current live stream is dependent on Formula 1's SignalR endpoint and access rules; anonymous feeds can be partial or unavailable. Live data is not guaranteed, and this bridge explicitly marks stale data offline.
- Available channels include speed, RPM, gear, throttle, brake flag and DRS, live positions, lap/sector timing, gaps, tyre stints, weather, flags and race-control messages **when transmitted by the upstream**. Raw JSON is available for advanced inspection. No fictional fuel, ERS state, tyre temperature or aerodynamic load.
- Historical analysis loads FastF1's session and returns a sampled lap with car/position telemetry. Recent sessions may become available only after processing.
- This service uses the original FastF1 recorder for streaming and a custom decoder of channel updates, because the FastF1 recorder itself cannot analyze data during a session.
- Internally protected with X-PitMetric-Token. PHP endpoints are separately protected by auth, email-editor Gate and throttling.
- Do not operate multiple replicas of this service: each replica opens its own stream.

## Mandatory pre-deploy: KEEP POST IMAGES
PitMetric stores uploaded post images under storage/app/public/updates on the Laravel **public disk**. Git commits cannot persist files stored inside an ephemeral Coolify application container.

**Before merging/deploying**, back up the current /app/storage/app/public/updates directory from the *existing* live container and its DB. Configure a Coolify persistent storage volume mounted at **/app/storage/app/public**, copy the existing media into that volume, and verify ownership/read permissions. Only then deploy, and confirm old post images load at their existing /updates/{slug}/media URLs. A new empty volume will NOT magically contain the old media. Do not change the app's document root, storage symlink or deployment command. This branch intentionally does not touch uploaded content.

The production mount is an infrastructure setting and **cannot be installed via a GitHub commit alone**.
