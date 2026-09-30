<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <title>Open Wheel Career 26 · PitMetric</title>
    <style>
        :root {
            --bg: #07090a;
            --panel: #0e1214;
            --panel-2: #13191c;
            --panel-3: #182024;
            --line: #273034;
            --line-strong: #39464b;
            --text: #f2f5f5;
            --muted: #8c9a9f;
            --lime: #c6ff2e;
            --cyan: #55e6ff;
            --orange: #ff9d45;
            --red: #ff5a64;
            --green: #62e6a6;
            --yellow: #ffd35c;
            --shadow: 0 24px 70px rgba(0, 0, 0, .34);
            --radius: 18px;
        }

        * { box-sizing: border-box; }
        html { background: var(--bg); }
        body {
            margin: 0;
            min-height: 100vh;
            overflow-x: hidden;
            background:
                radial-gradient(circle at 18% 0%, rgba(198, 255, 46, .08), transparent 24rem),
                radial-gradient(circle at 88% 18%, rgba(85, 230, 255, .07), transparent 26rem),
                var(--bg);
            color: var(--text);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        button, input, select { font: inherit; }
        button { color: inherit; }
        button:focus-visible, input:focus-visible, select:focus-visible {
            outline: 2px solid var(--lime);
            outline-offset: 2px;
        }

        .shell { min-height: 100vh; }
        .topbar {
            position: sticky;
            top: 0;
            z-index: 30;
            height: 68px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 0 24px;
            background: rgba(7, 9, 10, .87);
            backdrop-filter: blur(18px);
            border-bottom: 1px solid rgba(255,255,255,.08);
        }
        .brand { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .brand-mark {
            width: 34px; height: 34px; border-radius: 10px;
            display: grid; place-items: center;
            background: var(--lime); color: #091000; font-weight: 1000;
            box-shadow: 0 0 28px rgba(198,255,46,.18);
        }
        .brand-copy { min-width: 0; }
        .brand-title { font-size: 14px; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; white-space: nowrap; }
        .brand-sub { color: var(--muted); font-size: 11px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .top-actions { display: flex; align-items: center; gap: 8px; }
        .status-pill, .chip {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 7px 10px; border-radius: 999px;
            border: 1px solid var(--line); background: rgba(255,255,255,.025);
            color: var(--muted); font-size: 11px; font-weight: 800;
        }
        .status-dot { width: 7px; height: 7px; border-radius: 999px; background: var(--green); box-shadow: 0 0 12px var(--green); }

        .btn {
            border: 1px solid var(--line);
            background: var(--panel-2);
            border-radius: 11px;
            min-height: 40px;
            padding: 0 14px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 850;
            transition: transform .15s ease, border-color .15s ease, background .15s ease;
        }
        .btn:hover { transform: translateY(-1px); border-color: var(--line-strong); background: var(--panel-3); }
        .btn-primary { background: var(--lime); border-color: var(--lime); color: #081000; }
        .btn-primary:hover { background: #d3ff59; border-color: #d3ff59; }
        .btn-danger { color: #ff98a0; border-color: rgba(255,90,100,.28); background: rgba(255,90,100,.08); }
        .btn-ghost { background: transparent; }
        .btn-small { min-height: 32px; padding: 0 10px; font-size: 11px; }
        .btn[disabled] { opacity: .42; cursor: not-allowed; transform: none; }

        .layout {
            display: grid;
            grid-template-columns: 264px minmax(0, 1fr) 326px;
            gap: 14px;
            max-width: 1660px;
            margin: 0 auto;
            padding: 14px;
        }
        .column { min-width: 0; display: flex; flex-direction: column; gap: 14px; }
        .card {
            background: linear-gradient(180deg, rgba(19,25,28,.96), rgba(13,17,19,.98));
            border: 1px solid var(--line);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        .card-pad { padding: 16px; }
        .card-head {
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
            padding: 15px 16px; border-bottom: 1px solid var(--line);
        }
        .eyebrow { color: var(--lime); font-size: 10px; font-weight: 950; letter-spacing: .16em; text-transform: uppercase; }
        .title { font-size: 15px; font-weight: 900; margin: 3px 0 0; }
        .muted { color: var(--muted); }
        .tiny { font-size: 10px; }
        .small { font-size: 12px; }
        .hidden { display: none !important; }

        .identity {
            padding: 18px;
            background:
                linear-gradient(135deg, rgba(198,255,46,.07), transparent 52%),
                var(--panel);
        }
        .avatar {
            width: 48px; height: 48px; border-radius: 14px; display: grid; place-items: center;
            background: linear-gradient(145deg, #263034, #111719);
            border: 1px solid var(--line-strong);
            font-size: 16px; font-weight: 950;
        }
        .identity-row { display: flex; gap: 12px; align-items: center; }
        .identity-name { font-size: 16px; font-weight: 950; }
        .identity-role { color: var(--muted); font-size: 11px; margin-top: 3px; }
        .overall-block { margin-top: 18px; display: grid; grid-template-columns: auto 1fr; gap: 12px; align-items: center; }
        .overall-ring {
            --score: 70;
            width: 58px; height: 58px; border-radius: 50%; position: relative;
            display: grid; place-items: center;
            background: conic-gradient(var(--lime) calc(var(--score) * 1%), #253033 0);
        }
        .overall-ring::after { content: ''; position: absolute; inset: 5px; background: #101517; border-radius: inherit; }
        .overall-ring span { z-index: 1; font-size: 17px; font-weight: 1000; }
        .metric-line { display: flex; justify-content: space-between; gap: 12px; margin: 6px 0; color: var(--muted); font-size: 11px; }
        .metric-line strong { color: var(--text); }

        .skill-list { display: grid; gap: 11px; }
        .skill-row { display: grid; grid-template-columns: minmax(76px, 1fr) 32px; gap: 10px; align-items: center; }
        .skill-top { display: flex; justify-content: space-between; gap: 8px; margin-bottom: 5px; font-size: 10px; font-weight: 800; }
        .bar { height: 5px; border-radius: 99px; overflow: hidden; background: #252e31; }
        .bar > span { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #91c91e, var(--lime)); }
        .delta { color: var(--green); font-size: 9px; font-weight: 900; text-align: right; }

        .relationship { display: grid; gap: 12px; }
        .relationship-row { display: grid; grid-template-columns: 34px 1fr 28px; align-items: center; gap: 9px; }
        .relationship-icon { width: 34px; height: 34px; border: 1px solid var(--line); border-radius: 10px; display: grid; place-items: center; font-size: 13px; }
        .relationship-label { font-size: 10px; color: var(--muted); margin-bottom: 5px; }
        .relationship-score { font-size: 10px; font-weight: 900; text-align: right; }

        .hero {
            min-height: 215px;
            position: relative;
            padding: 22px;
            display: flex;
            align-items: flex-end;
            overflow: hidden;
            background:
                linear-gradient(90deg, rgba(8,11,12,.96) 0%, rgba(8,11,12,.76) 42%, rgba(8,11,12,.24) 75%),
                radial-gradient(circle at 80% 28%, rgba(85,230,255,.22), transparent 35%),
                linear-gradient(125deg, #0d1417, #1d272b 65%, #111719);
        }
        .hero::after {
            content: ''; position: absolute; width: 340px; height: 112px; right: -28px; top: 52px;
            border: 12px solid rgba(255,255,255,.07); border-left-width: 28px; transform: skewX(-18deg) rotate(-4deg);
            border-radius: 55% 20% 36% 40%; filter: blur(.2px);
        }
        .hero-copy { position: relative; z-index: 2; max-width: 580px; }
        .hero h1 { margin: 6px 0 8px; font-size: clamp(24px, 4vw, 42px); line-height: .96; letter-spacing: -.045em; }
        .hero p { margin: 0; color: #aab6b9; font-size: 12px; line-height: 1.55; max-width: 560px; }
        .hero-meta { display: flex; flex-wrap: wrap; gap: 7px; margin-top: 14px; }
        .hero-meta .chip { color: #cdd5d7; background: rgba(0,0,0,.2); }

        .session-strip { display: flex; overflow-x: auto; scrollbar-width: thin; border-bottom: 1px solid var(--line); }
        .session-tab {
            min-width: 112px; flex: 1; padding: 13px 12px; border: 0; border-right: 1px solid var(--line);
            background: transparent; color: var(--muted); text-align: left; cursor: default;
        }
        .session-tab:last-child { border-right: 0; }
        .session-tab small { display: block; font-size: 9px; text-transform: uppercase; letter-spacing: .09em; }
        .session-tab strong { display: block; margin-top: 4px; color: inherit; font-size: 11px; }
        .session-tab.is-done { color: var(--green); }
        .session-tab.is-current { background: rgba(198,255,46,.08); color: var(--lime); box-shadow: inset 0 -2px var(--lime); }

        .track-wrap { padding: 14px; }
        .track-toolbar { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 10px; }
        .track-title { display: flex; align-items: baseline; gap: 9px; }
        .track-title h2 { margin: 0; font-size: 16px; }
        .track-title span { color: var(--muted); font-size: 10px; }
        .speed-group { display: flex; gap: 4px; }
        .speed-btn { border: 1px solid var(--line); background: transparent; color: var(--muted); border-radius: 8px; padding: 5px 8px; font-size: 10px; font-weight: 900; cursor: pointer; }
        .speed-btn.active { color: #071000; background: var(--lime); border-color: var(--lime); }
        .circuit-stage {
            position: relative;
            min-height: 430px;
            border: 1px solid var(--line);
            border-radius: 15px;
            overflow: hidden;
            background:
                linear-gradient(rgba(255,255,255,.018) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.018) 1px, transparent 1px),
                #0a0e10;
            background-size: 28px 28px;
        }
        .circuit-stage svg { display: block; width: 100%; height: 100%; min-height: 430px; }
        .track-shadow { fill: none; stroke: #020304; stroke-width: 34; stroke-linecap: round; stroke-linejoin: round; }
        .track-road { fill: none; stroke: #384145; stroke-width: 26; stroke-linecap: round; stroke-linejoin: round; }
        .track-edge { fill: none; stroke: rgba(255,255,255,.14); stroke-width: 1.5; stroke-dasharray: 4 8; }
        .track-line { fill: none; stroke: rgba(198,255,46,.72); stroke-width: 2; stroke-dasharray: 5 8; }
        .car-dot text { font-size: 8px; font-weight: 1000; fill: #050708; text-anchor: middle; dominant-baseline: central; pointer-events: none; }
        .car-dot.player .car-body { filter: drop-shadow(0 0 8px rgba(198,255,46,.8)); }
        .car-body { transition: fill .2s ease; }
        .start-line { stroke: #fff; stroke-width: 5; stroke-dasharray: 5 4; }
        .track-overlay {
            position: absolute; inset: 14px 14px auto auto; min-width: 150px;
            border: 1px solid var(--line); background: rgba(7,10,11,.85); backdrop-filter: blur(9px); border-radius: 12px; padding: 10px;
        }
        .track-overlay .big { font-size: 21px; font-weight: 1000; }
        .race-controls { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-top: 10px; }
        .control {
            min-height: 58px; border: 1px solid var(--line); background: #111719; border-radius: 12px; padding: 9px;
            text-align: left; cursor: pointer;
        }
        .control:hover { border-color: var(--line-strong); }
        .control.active { border-color: rgba(198,255,46,.52); background: rgba(198,255,46,.075); }
        .control small { display: block; color: var(--muted); font-size: 9px; text-transform: uppercase; }
        .control strong { display: block; font-size: 11px; margin-top: 5px; }

        .timing { width: 100%; border-collapse: collapse; font-size: 10px; }
        .timing th { color: var(--muted); font-size: 9px; text-transform: uppercase; letter-spacing: .08em; text-align: left; padding: 8px 9px; border-bottom: 1px solid var(--line); }
        .timing td { padding: 8px 9px; border-bottom: 1px solid rgba(255,255,255,.04); }
        .timing tr:last-child td { border-bottom: 0; }
        .timing .you { background: rgba(198,255,46,.07); }
        .team-swatch { display: inline-block; width: 6px; height: 13px; border-radius: 3px; vertical-align: middle; margin-right: 7px; }
        .pos { width: 26px; font-weight: 1000; font-size: 12px; }
        .tyre { width: 20px; height: 20px; border: 2px solid; border-radius: 50%; display: inline-grid; place-items: center; font-size: 7px; font-weight: 1000; }
        .tyre.soft { border-color: #ff5a64; color: #ff5a64; }
        .tyre.medium { border-color: #ffd35c; color: #ffd35c; }
        .tyre.hard { border-color: #e8edef; color: #e8edef; }
        .tyre.inter { border-color: #64e48b; color: #64e48b; }
        .tyre.wet { border-color: #5aa3ff; color: #5aa3ff; }

        .telemetry-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
        .telemetry-item { padding: 11px; border: 1px solid var(--line); border-radius: 11px; background: rgba(255,255,255,.018); }
        .telemetry-item small { display: block; color: var(--muted); font-size: 8px; letter-spacing: .08em; text-transform: uppercase; }
        .telemetry-item strong { display: block; margin-top: 5px; font-size: 14px; }

        .radio-log { height: 246px; overflow-y: auto; padding: 12px; display: flex; flex-direction: column; gap: 9px; }
        .radio-msg { border-left: 2px solid var(--line-strong); padding: 5px 0 5px 10px; font-size: 11px; line-height: 1.45; color: #c5ced0; }
        .radio-msg strong { color: var(--text); display: block; font-size: 9px; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 2px; }
        .radio-msg.team { border-color: var(--cyan); }
        .radio-msg.you { border-color: var(--lime); }
        .radio-msg.alert { border-color: var(--orange); }
        .radio-choices { display: grid; gap: 7px; padding: 0 12px 12px; }
        .radio-choice { border: 1px solid var(--line); background: var(--panel-2); border-radius: 10px; padding: 9px 10px; text-align: left; cursor: pointer; font-size: 10px; }
        .radio-choice:hover { border-color: var(--lime); }

        .strategy-grid { display: grid; gap: 9px; }
        .strategy-row { display: grid; grid-template-columns: 1fr auto; align-items: center; gap: 10px; border: 1px solid var(--line); border-radius: 11px; padding: 10px; }
        .strategy-row strong { font-size: 11px; }
        .strategy-row small { display: block; color: var(--muted); font-size: 9px; margin-top: 3px; }
        .select {
            height: 34px; background: #0b1012; border: 1px solid var(--line); color: var(--text); border-radius: 9px; padding: 0 8px; font-size: 10px;
        }
        .forecast { display: grid; grid-template-columns: repeat(5, 1fr); gap: 5px; margin-top: 10px; }
        .weather-cell { border: 1px solid var(--line); border-radius: 9px; padding: 7px 4px; text-align: center; }
        .weather-cell span { display: block; font-size: 13px; }
        .weather-cell small { color: var(--muted); font-size: 8px; }

        .event-feed { max-height: 230px; overflow-y: auto; }
        .feed-item { display: grid; grid-template-columns: 38px 1fr; gap: 9px; padding: 9px 12px; border-bottom: 1px solid rgba(255,255,255,.045); font-size: 10px; }
        .feed-item:last-child { border-bottom: 0; }
        .feed-lap { color: var(--muted); font-weight: 900; }
        .feed-item.overtake { color: #d8ff77; }
        .feed-item.incident { color: #ff979e; }
        .feed-item.pit { color: #84eaff; }
        .feed-item.safety { color: #ffe17d; }

        .calendar-list { display: flex; overflow-x: auto; gap: 8px; padding: 12px; scrollbar-width: thin; }
        .round-card { min-width: 144px; border: 1px solid var(--line); border-radius: 12px; padding: 10px; background: rgba(255,255,255,.018); cursor: default; }
        .round-card.current { border-color: rgba(198,255,46,.5); background: rgba(198,255,46,.065); }
        .round-card.done { opacity: .58; }
        .round-no { color: var(--muted); font-size: 8px; text-transform: uppercase; letter-spacing: .12em; }
        .round-name { font-size: 11px; font-weight: 900; margin-top: 5px; }
        .round-date { color: var(--muted); font-size: 9px; margin-top: 5px; }
        .sprint-label { color: var(--orange); font-size: 8px; font-weight: 950; margin-top: 8px; }

        .empty-state { padding: 22px; text-align: center; color: var(--muted); font-size: 11px; }
        .weekend-action { padding: 14px; display: flex; gap: 9px; align-items: center; justify-content: space-between; flex-wrap: wrap; }
        .weekend-action-copy strong { display: block; font-size: 12px; }
        .weekend-action-copy span { display: block; margin-top: 3px; color: var(--muted); font-size: 10px; }

        .overlay {
            position: fixed; inset: 0; z-index: 100; display: grid; place-items: center; padding: 18px;
            background: rgba(2,4,5,.84); backdrop-filter: blur(16px);
        }
        .wizard {
            width: min(100%, 1060px); max-height: calc(100vh - 36px); overflow-y: auto;
            background: #0d1214; border: 1px solid var(--line-strong); border-radius: 24px; box-shadow: 0 30px 100px rgba(0,0,0,.62);
        }
        .wizard-head { padding: 22px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; gap: 18px; align-items: flex-start; }
        .wizard-head h1 { margin: 6px 0 4px; font-size: clamp(24px, 4vw, 42px); letter-spacing: -.045em; line-height: 1; }
        .wizard-head p { color: var(--muted); margin: 7px 0 0; font-size: 11px; max-width: 640px; line-height: 1.5; }
        .wizard-body { padding: 22px; }
        .stepper { display: flex; gap: 6px; }
        .step-dot { width: 27px; height: 5px; border-radius: 99px; background: #283034; }
        .step-dot.active { background: var(--lime); }
        .step-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .choice-card { border: 1px solid var(--line); border-radius: 16px; background: rgba(255,255,255,.018); padding: 16px; cursor: pointer; transition: .18s ease; }
        .choice-card:hover, .choice-card.selected { border-color: rgba(198,255,46,.55); background: rgba(198,255,46,.055); }
        .choice-card .choice-icon { width: 38px; height: 38px; border-radius: 11px; border: 1px solid var(--line); display: grid; place-items: center; margin-bottom: 12px; }
        .choice-card h3 { margin: 0; font-size: 14px; }
        .choice-card p { margin: 7px 0 0; color: var(--muted); font-size: 10px; line-height: 1.5; }
        .field-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 11px; margin-top: 18px; }
        .field label { display: block; font-size: 9px; color: var(--muted); text-transform: uppercase; letter-spacing: .1em; margin-bottom: 6px; font-weight: 800; }
        .input { width: 100%; height: 42px; border: 1px solid var(--line); border-radius: 10px; background: #090d0e; color: var(--text); padding: 0 11px; }
        .skill-builder { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 9px; margin-top: 14px; }
        .skill-build-row { border: 1px solid var(--line); border-radius: 12px; padding: 11px; }
        .skill-build-top { display: flex; justify-content: space-between; align-items: center; gap: 8px; }
        .skill-build-name strong { display: block; font-size: 11px; }
        .skill-build-name small { color: var(--muted); font-size: 8px; }
        .skill-stepper { display: flex; align-items: center; gap: 7px; }
        .step-btn { width: 27px; height: 27px; border-radius: 8px; border: 1px solid var(--line); background: #151b1e; cursor: pointer; }
        .skill-value { min-width: 22px; text-align: center; font-size: 11px; font-weight: 950; }
        .budget { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 14px; padding: 12px; border-radius: 12px; background: rgba(198,255,46,.065); border: 1px solid rgba(198,255,46,.22); }
        .budget strong { font-size: 12px; }
        .budget span { color: var(--lime); font-weight: 1000; }
        .wizard-footer { padding: 15px 22px 22px; display: flex; justify-content: flex-end; gap: 8px; }

        .offers { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 15px; }
        .offer { border: 1px solid var(--line); border-radius: 16px; padding: 14px; background: rgba(255,255,255,.018); }
        .offer-top { display: flex; justify-content: space-between; gap: 10px; align-items: flex-start; }
        .offer-team { font-size: 13px; font-weight: 950; }
        .offer-series { color: var(--muted); font-size: 9px; margin-top: 3px; }
        .offer-score { font-size: 17px; font-weight: 1000; color: var(--lime); }
        .offer-details { display: grid; gap: 6px; margin: 12px 0; }
        .offer-line { display: flex; justify-content: space-between; gap: 8px; color: var(--muted); font-size: 9px; }
        .offer-line strong { color: var(--text); }

        .modal {
            position: fixed; z-index: 90; inset: 0; display: grid; place-items: center; padding: 20px;
            background: rgba(1,3,4,.72); backdrop-filter: blur(10px);
        }
        .modal-box { width: min(100%, 580px); border: 1px solid var(--line-strong); background: #0e1416; border-radius: 20px; box-shadow: var(--shadow); overflow: hidden; }
        .modal-head { padding: 18px; border-bottom: 1px solid var(--line); }
        .modal-head h2 { margin: 4px 0 0; font-size: 20px; }
        .modal-body { padding: 18px; }
        .result-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
        .result-box { border: 1px solid var(--line); border-radius: 12px; padding: 11px; text-align: center; }
        .result-box small { display: block; color: var(--muted); font-size: 8px; text-transform: uppercase; }
        .result-box strong { display: block; margin-top: 4px; font-size: 18px; }
        .growth-list { display: grid; gap: 7px; margin-top: 14px; }
        .growth-row { display: flex; justify-content: space-between; gap: 12px; padding: 8px 10px; border-radius: 9px; background: rgba(255,255,255,.025); font-size: 10px; }
        .growth-row span:last-child { color: var(--green); font-weight: 900; }

        .toast { position: fixed; z-index: 150; right: 18px; bottom: 18px; max-width: 350px; background: #161e21; border: 1px solid var(--line-strong); border-radius: 13px; padding: 12px 14px; box-shadow: var(--shadow); font-size: 11px; transform: translateY(20px); opacity: 0; pointer-events: none; transition: .2s ease; }
        .toast.show { transform: translateY(0); opacity: 1; }

        @media (max-width: 1180px) {
            .layout { grid-template-columns: 224px minmax(0, 1fr); }
            .right-column { grid-column: 1 / -1; display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); }
            .right-column .card { min-width: 0; }
            .radio-log { height: 210px; }
        }
        @media (max-width: 820px) {
            .topbar { padding: 0 12px; }
            .status-pill { display: none; }
            .layout { grid-template-columns: 1fr; padding: 9px; }
            .left-column { display: grid; grid-template-columns: 1fr 1fr; }
            .hero { min-height: 190px; }
            .right-column { grid-template-columns: 1fr; }
            .race-controls, .telemetry-grid { grid-template-columns: repeat(2, 1fr); }
            .circuit-stage, .circuit-stage svg { min-height: 340px; }
            .offers, .field-grid, .step-grid { grid-template-columns: 1fr; }
            .skill-builder { grid-template-columns: 1fr; }
        }
        @media (max-width: 560px) {
            .brand-sub { display: none; }
            .top-actions .btn-ghost { display: none; }
            .left-column { display: flex; }
            .hero { padding: 17px; min-height: 205px; }
            .hero::after { opacity: .5; }
            .circuit-stage, .circuit-stage svg { min-height: 285px; }
            .track-overlay { inset: 8px 8px auto auto; min-width: 130px; }
            .race-controls { grid-template-columns: 1fr 1fr; }
            .telemetry-grid { grid-template-columns: 1fr 1fr; }
            .wizard-head, .wizard-body { padding: 16px; }
            .wizard-footer { padding: 12px 16px 16px; }
            .result-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="shell">
    <header class="topbar">
        <div class="brand">
            <div class="brand-mark">P</div>
            <div class="brand-copy">
                <div class="brand-title">Open Wheel Career 26</div>
                <div class="brand-sub">PitMetric experimental game · F1 / F2 career simulation</div>
            </div>
        </div>
        <div class="top-actions">
            <span class="status-pill"><span class="status-dot"></span><span id="saveStateLabel">salvataggio locale</span></span>
            <button class="btn btn-ghost btn-small" id="rulesButton" type="button">Regole 2026</button>
            <button class="btn btn-danger btn-small" id="resetButton" type="button">Nuova carriera</button>
        </div>
    </header>

    <main class="layout">
        <aside class="column left-column">
            <section class="card identity">
                <div class="identity-row">
                    <div class="avatar" id="avatarInitials">--</div>
                    <div>
                        <div class="identity-name" id="playerName">Nuovo profilo</div>
                        <div class="identity-role" id="playerRole">Scegli pilota o ingegnere</div>
                    </div>
                </div>
                <div class="overall-block">
                    <div class="overall-ring" id="overallRing"><span id="overallValue">--</span></div>
                    <div>
                        <div class="metric-line"><span>Reputazione</span><strong id="reputationValue">--</strong></div>
                        <div class="metric-line"><span>Stagione</span><strong id="seasonValue">2026</strong></div>
                        <div class="metric-line"><span>Categoria</span><strong id="seriesValue">--</strong></div>
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-head">
                    <div><div class="eyebrow">Progressione</div><div class="title">Abilità</div></div>
                    <span class="chip" id="weekendsValue">0 weekend</span>
                </div>
                <div class="card-pad"><div class="skill-list" id="skillsList"></div></div>
            </section>

            <section class="card">
                <div class="card-head">
                    <div><div class="eyebrow">Human performance</div><div class="title">Rapporti</div></div>
                </div>
                <div class="card-pad relationship" id="relationshipPanel"></div>
            </section>
        </aside>

        <section class="column center-column">
            <section class="card hero">
                <div class="hero-copy">
                    <div class="eyebrow" id="heroEyebrow">Career hub</div>
                    <h1 id="heroTitle">Costruisci la tua carriera.</h1>
                    <p id="heroDescription">Il contratto, la fiducia del team e il tuo rating dipendono dalle decisioni prese in pista, non soltanto dal risultato finale.</p>
                    <div class="hero-meta" id="heroMeta"></div>
                </div>
            </section>

            <section class="card" id="weekendCard">
                <div class="session-strip" id="sessionStrip"></div>
                <div class="track-wrap">
                    <div class="track-toolbar">
                        <div class="track-title"><h2 id="circuitName">Nessun weekend attivo</h2><span id="circuitMeta">2026</span></div>
                        <div class="speed-group" id="speedGroup">
                            <button class="speed-btn active" type="button" data-speed="1">1×</button>
                            <button class="speed-btn" type="button" data-speed="4">4×</button>
                            <button class="speed-btn" type="button" data-speed="12">12×</button>
                        </div>
                    </div>
                    <div class="circuit-stage">
                        <svg viewBox="0 0 1000 600" role="img" aria-label="Simulazione del circuito">
                            <path id="trackShadow" class="track-shadow" d="M180 300 C180 150 300 90 470 105 C650 120 800 170 820 300 C840 430 690 505 500 495 C305 485 170 425 180 300 Z"></path>
                            <path id="trackRoad" class="track-road" d="M180 300 C180 150 300 90 470 105 C650 120 800 170 820 300 C840 430 690 505 500 495 C305 485 170 425 180 300 Z"></path>
                            <path id="trackEdge" class="track-edge" d="M180 300 C180 150 300 90 470 105 C650 120 800 170 820 300 C840 430 690 505 500 495 C305 485 170 425 180 300 Z"></path>
                            <path id="trackLine" class="track-line" d="M180 300 C180 150 300 90 470 105 C650 120 800 170 820 300 C840 430 690 505 500 495 C305 485 170 425 180 300 Z"></path>
                            <g id="carsLayer"></g>
                        </svg>
                        <div class="track-overlay">
                            <div class="eyebrow" id="liveState">Stand-by</div>
                            <div class="big"><span id="lapValue">0</span><span class="muted small"> / </span><span id="totalLapsValue" class="muted small">--</span></div>
                            <div class="metric-line"><span>Posizione</span><strong id="positionValue">--</strong></div>
                            <div class="metric-line"><span>Gap leader</span><strong id="gapValue">--</strong></div>
                        </div>
                    </div>

                    <div class="race-controls" id="raceControls">
                        <button class="control active" type="button" data-control="pace" data-value="balanced"><small>Ritmo</small><strong>Bilanciato</strong></button>
                        <button class="control" type="button" data-control="attack"><small>Duello</small><strong>Attacca</strong></button>
                        <button class="control" type="button" data-control="overtake"><small>ERS</small><strong>Overtake mode</strong></button>
                        <button class="control" type="button" data-control="pit"><small>Strategia</small><strong>Box questo giro</strong></button>
                    </div>
                </div>

                <div class="card-pad" style="border-top:1px solid var(--line)">
                    <div class="telemetry-grid">
                        <div class="telemetry-item"><small>Gomma</small><strong id="tyreTelemetry">--</strong></div>
                        <div class="telemetry-item"><small>Usura</small><strong id="wearTelemetry">--</strong></div>
                        <div class="telemetry-item"><small>Energia</small><strong id="energyTelemetry">--</strong></div>
                        <div class="telemetry-item"><small>Condizione</small><strong id="carTelemetry">--</strong></div>
                    </div>
                </div>

                <div class="weekend-action" id="weekendAction"></div>
            </section>

            <section class="card">
                <div class="card-head">
                    <div><div class="eyebrow">Live timing</div><div class="title">Classifica sessione</div></div>
                    <span class="chip" id="fieldCount">-- auto</span>
                </div>
                <div style="overflow:auto;max-height:360px">
                    <table class="timing">
                        <thead><tr><th>Pos</th><th>Pilota</th><th>Team</th><th>Gomma</th><th>Gap</th></tr></thead>
                        <tbody id="timingBody"></tbody>
                    </table>
                </div>
            </section>

            <section class="card">
                <div class="card-head">
                    <div><div class="eyebrow">Calendario ufficiale</div><div class="title" id="calendarTitle">Stagione 2026</div></div>
                </div>
                <div class="calendar-list" id="calendarList"></div>
            </section>
        </section>

        <aside class="column right-column">
            <section class="card">
                <div class="card-head">
                    <div><div class="eyebrow">Pit wall</div><div class="title">Strategia & meteo</div></div>
                    <span class="chip" id="weatherLabel">☀ 26°C</span>
                </div>
                <div class="card-pad">
                    <div class="strategy-grid">
                        <div class="strategy-row"><div><strong>Prossimo stint</strong><small>Scegli la mescola al prossimo stop</small></div><select class="select" id="nextTyre"><option value="soft">Soft</option><option value="medium" selected>Medium</option><option value="hard">Hard</option><option value="inter">Inter</option><option value="wet">Wet</option></select></div>
                        <div class="strategy-row"><div><strong>Target pit</strong><small>Finestra tattica adattiva</small></div><select class="select" id="pitWindow"><option value="early">Anticipa</option><option value="normal" selected>Finestra</option><option value="late">Estendi</option></select></div>
                        <div class="strategy-row"><div><strong>Rischio</strong><small>Influenza sorpassi, errori e degrado</small></div><select class="select" id="riskMode"><option value="safe">Conservativo</option><option value="balanced" selected>Bilanciato</option><option value="attack">Aggressivo</option></select></div>
                    </div>
                    <div class="forecast" id="forecast"></div>
                </div>
            </section>

            <section class="card">
                <div class="card-head">
                    <div><div class="eyebrow">Radio</div><div class="title" id="radioTitle">Pilota ↔ ingegnere</div></div>
                    <span class="chip" id="trustChip">fiducia --</span>
                </div>
                <div class="radio-log" id="radioLog"></div>
                <div class="radio-choices" id="radioChoices"></div>
            </section>

            <section class="card">
                <div class="card-head">
                    <div><div class="eyebrow">Race control</div><div class="title">Eventi pista</div></div>
                </div>
                <div class="event-feed" id="eventFeed"></div>
            </section>
        </aside>
    </main>
</div>

<div class="overlay" id="careerWizard">
    <div class="wizard">
        <div class="wizard-head">
            <div>
                <div class="eyebrow">Nuova carriera · 2026</div>
                <h1>Crea il tuo personaggio</h1>
                <p>Parti dal paddock reale F1/F2 2026. Il mercato valuterà rating, reputazione e curriculum precedente; ogni weekend farà crescere abilità e rapporti in modo diverso.</p>
            </div>
            <div class="stepper"><span class="step-dot active"></span><span class="step-dot"></span><span class="step-dot"></span></div>
        </div>
        <div class="wizard-body">
            <div id="wizardStep1">
                <div class="step-grid">
                    <button class="choice-card selected" type="button" data-role-choice="driver">
                        <div class="choice-icon">◉</div><h3>Pilota</h3>
                        <p>Gestisci ritmo, duelli, energia, feedback tecnico e il rapporto con il tuo ingegnere. Le tue decisioni in abitacolo hanno conseguenze.</p>
                    </button>
                    <button class="choice-card" type="button" data-role-choice="engineer">
                        <div class="choice-icon">⌁</div><h3>Ingegnere di gara</h3>
                        <p>Leggi telemetria, degrado, gap e meteo. Chiama box, coordina il pilota e costruisci una relazione abbastanza forte da farti ascoltare sotto pressione.</p>
                    </button>
                </div>
                <div class="field-grid">
                    <div class="field"><label for="careerName">Nome</label><input class="input" id="careerName" value="Simone Buttice" maxlength="36"></div>
                    <div class="field"><label for="careerNation">Nazionalità</label><select class="input" id="careerNation"><option>Italia</option><option>Regno Unito</option><option>Francia</option><option>Germania</option><option>Spagna</option><option>Paesi Bassi</option><option>Brasile</option><option>Australia</option><option>Altro</option></select></div>
                    <div class="field"><label for="careerHistory">Stagione precedente</label><select class="input" id="careerHistory"><option value="rookie">Rookie / nessun risultato</option><option value="f3top10">F3 · Top 10</option><option value="f3podiums">F3 · Podium finisher</option><option value="f3champion">F3 · Campione</option><option value="f2top10">F2 · Top 10</option><option value="f2winner">F2 · Vincitore di gare</option><option value="f2champion">F2 · Campione</option></select></div>
                </div>
            </div>
            <div id="wizardStep2" class="hidden">
                <div class="eyebrow">Distribuisci il potenziale</div>
                <div class="title" style="margin-top:5px">Le skill definiscono quali team ti considerano</div>
                <div class="skill-builder" id="skillBuilder"></div>
                <div class="budget"><strong>Punti sviluppo disponibili</strong><span id="skillBudget">24</span></div>
            </div>
            <div id="wizardStep3" class="hidden">
                <div class="eyebrow">Mercato piloti & staff</div>
                <div class="title" style="margin-top:5px">Le offerte generate per il tuo profilo</div>
                <div class="offers" id="offersList"></div>
            </div>
        </div>
        <div class="wizard-footer">
            <button class="btn btn-ghost hidden" id="wizardBack" type="button">Indietro</button>
            <button class="btn btn-primary" id="wizardNext" type="button">Costruisci abilità</button>
        </div>
    </div>
</div>

<div class="modal hidden" id="resultModal">
    <div class="modal-box">
        <div class="modal-head"><div class="eyebrow">Weekend completato</div><h2 id="resultTitle">Debrief</h2></div>
        <div class="modal-body">
            <div class="result-grid">
                <div class="result-box"><small>Risultato</small><strong id="resultPosition">P--</strong></div>
                <div class="result-box"><small>Reputazione</small><strong id="resultReputation">--</strong></div>
                <div class="result-box"><small>Fiducia team</small><strong id="resultTrust">--</strong></div>
            </div>
            <div class="growth-list" id="growthList"></div>
            <div style="display:flex;justify-content:flex-end;margin-top:16px"><button class="btn btn-primary" id="closeResult" type="button">Prossimo round</button></div>
        </div>
    </div>
</div>

<div class="modal hidden" id="rulesModal">
    <div class="modal-box">
        <div class="modal-head"><div class="eyebrow">Regolamento simulato</div><h2>Regole 2026 implementate</h2></div>
        <div class="modal-body small muted" style="line-height:1.65">
            <p><strong style="color:var(--text)">F1.</strong> 22 vetture; qualifica Q1/Q2/Q3 con 6 eliminati in Q1, 6 in Q2 e 10 in Q3. Weekend Sprint a Shanghai, Miami, Montréal, Silverstone, Zandvoort e Singapore. Punti GP 25-18-15-12-10-8-6-4-2-1; Sprint 8-7-6-5-4-3-2-1. In gara il 2026 usa Overtake Mode/energia e active aero al posto del vecchio DRS. In asciutto la strategia deve usare almeno due specifiche slick differenti.</p>
            <p><strong style="color:var(--text)">F2.</strong> Sprint con top 10 invertita dalla qualifica; punti Sprint 10-8-6-5-4-3-2-1 e Feature 25-18-15-12-10-8-6-4-2-1. Baku usa il formato speciale 2026 con due qualifiche e due Feature Race.</p>
            <p>Questa prima versione comprime durata e probabilità per rendere le gare giocabili in pochi minuti, mantenendo però strategia, degrado, incidenti, Safety Car, pit stop, rapporti e progressione collegati tra loro.</p>
            <div style="display:flex;justify-content:flex-end;margin-top:14px"><button class="btn btn-primary" id="closeRules" type="button">Chiudi</button></div>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
(() => {
    'use strict';

    const STORAGE_KEY = 'pitmetric.openwheel26.v1';
    const $ = (selector) => document.querySelector(selector);
    const $$ = (selector) => [...document.querySelectorAll(selector)];
    const clamp = (value, min, max) => Math.max(min, Math.min(max, value));
    const rand = (min, max) => Math.random() * (max - min) + min;
    const pick = (items) => items[Math.floor(Math.random() * items.length)];

    const DRIVER_SKILLS = [
        ['pace', 'Passo', 'Velocità pura sul giro'],
        ['qualifying', 'Qualifica', 'Performance sul giro secco'],
        ['racecraft', 'Racecraft', 'Sorpassi e difesa'],
        ['tyres', 'Gestione gomme', 'Degrado e stint lunghi'],
        ['feedback', 'Feedback', 'Qualità indicazioni tecniche'],
        ['wet', 'Bagnato', 'Grip in condizioni variabili'],
        ['focus', 'Concentrazione', 'Riduce errori e incidenti'],
        ['adaptability', 'Adattabilità', 'Reazione a pista e setup']
    ];
    const ENGINEER_SKILLS = [
        ['strategy', 'Strategia', 'Pit window, undercut e Safety Car'],
        ['tyres', 'Lettura gomme', 'Degrado e previsione stint'],
        ['communication', 'Comunicazione', 'Radio chiara sotto pressione'],
        ['setup', 'Setup', 'Bilanciamento e direzione tecnica'],
        ['data', 'Analisi dati', 'Telemetria e pattern'],
        ['pressure', 'Pressione', 'Decisioni nei momenti critici'],
        ['weather', 'Meteo', 'Lettura transizioni e crossover'],
        ['leadership', 'Leadership', 'Fiducia pilota e squadra']
    ];

    const F1_TEAMS = [
        { name:'Mercedes', short:'MER', color:'#00d2be', perf:96, drivers:['George Russell','Kimi Antonelli'] },
        { name:'Ferrari', short:'FER', color:'#ff2800', perf:94, drivers:['Charles Leclerc','Lewis Hamilton'] },
        { name:'McLaren', short:'MCL', color:'#ff8700', perf:94, drivers:['Lando Norris','Oscar Piastri'] },
        { name:'Red Bull Racing', short:'RBR', color:'#4f65ff', perf:92, drivers:['Max Verstappen','Isack Hadjar'] },
        { name:'Aston Martin', short:'AMR', color:'#229971', perf:87, drivers:['Fernando Alonso','Lance Stroll'] },
        { name:'Williams', short:'WIL', color:'#52a9ff', perf:86, drivers:['Carlos Sainz','Alexander Albon'] },
        { name:'Audi', short:'AUD', color:'#f5f5f5', perf:84, drivers:['Nico Hülkenberg','Gabriel Bortoleto'] },
        { name:'Racing Bulls', short:'VCB', color:'#76c7ff', perf:83, drivers:['Liam Lawson','Arvid Lindblad'] },
        { name:'Haas', short:'HAS', color:'#d6d6d6', perf:81, drivers:['Esteban Ocon','Oliver Bearman'] },
        { name:'Alpine', short:'ALP', color:'#ff73c9', perf:80, drivers:['Pierre Gasly','Franco Colapinto'] },
        { name:'Cadillac', short:'CAD', color:'#d9bf77', perf:79, drivers:['Sergio Perez','Valtteri Bottas'] }
    ];

    const F2_TEAMS = [
        { name:'Invicta Racing', short:'INV', color:'#dcb900', perf:92, drivers:['Rafael Câmara','Joshua Dürksen'] },
        { name:'Hitech', short:'HIT', color:'#b9b9b9', perf:90, drivers:['Ritomo Miyata','Colton Herta'] },
        { name:'Campos Racing', short:'CAM', color:'#ffcf2f', perf:89, drivers:['Noel Leon','Nikola Tsolov'] },
        { name:'DAMS Lucas Oil', short:'DAM', color:'#4b7aff', perf:88, drivers:['Dino Beganovic','Roman Bilinski'] },
        { name:'MP Motorsport', short:'MP', color:'#ff6c3a', perf:87, drivers:['Gabriele Mini','Oliver Goethe'] },
        { name:'PREMA Racing', short:'PRE', color:'#e34b5b', perf:87, drivers:['Sebastian Montoya','Mari Boya'] },
        { name:'Rodin Motorsport', short:'ROD', color:'#c2ff36', perf:85, drivers:['Martinius Stenshorne','Alexander Dunne'] },
        { name:'ART Grand Prix', short:'ART', color:'#ed4545', perf:85, drivers:['Kush Maini','Tasanapol Inthraphuvasak'] },
        { name:'AIX Racing', short:'AIX', color:'#8d61ff', perf:82, drivers:['Emerson Fittipaldi','Cian Shields'] },
        { name:'Van Amersfoort Racing', short:'VAR', color:'#f47932', perf:81, drivers:['Hiyu Yamakoshi','Rafael Villagomez'] },
        { name:'TRIDENT', short:'TRI', color:'#4b93cf', perf:80, drivers:['Laurens van Hoepen','John Bennett'] }
    ];

    const TRACKS = {
        Melbourne:'M206 405 C137 329 151 211 256 175 C338 147 388 111 487 129 C583 146 594 208 680 207 C789 207 851 280 809 351 C777 405 707 395 638 438 C556 489 434 484 350 457 C289 437 249 451 206 405 Z',
        Shanghai:'M193 403 C126 330 153 225 239 207 C299 195 336 215 355 173 C382 112 490 102 526 164 C558 219 529 267 484 288 C443 308 441 347 491 361 C551 378 593 338 625 283 C662 220 764 206 818 267 C869 324 825 421 745 430 C660 440 605 493 492 480 C384 468 270 484 193 403 Z',
        Suzuka:'M175 313 C196 213 303 147 407 163 C484 175 527 228 574 254 C627 284 725 240 803 185 C767 269 674 321 579 326 C495 331 426 300 364 322 C301 344 245 407 201 449 C252 423 331 404 397 415 C482 428 544 486 638 460 C723 437 775 371 805 316 C720 352 641 375 558 363 C474 351 413 289 334 277 C267 267 217 287 175 313 Z',
        Sakhir:'M186 384 L227 205 L388 143 L478 205 L574 151 L741 184 L815 284 L737 340 L792 433 L622 474 L535 408 L420 469 L289 429 Z',
        Jeddah:'M257 491 C206 412 195 313 227 218 C251 147 326 105 395 131 C445 150 481 186 548 163 C631 133 722 142 778 206 C830 267 805 347 753 407 C691 480 615 500 536 462 C459 424 383 450 326 485 C298 502 275 507 257 491 Z',
        Miami:'M183 346 C153 263 209 178 305 172 L455 172 C511 173 533 126 591 139 L784 181 L820 280 L758 332 L797 419 L681 465 L561 427 L472 477 L328 461 L227 416 Z',
        Montreal:'M231 456 C183 393 182 300 213 238 L279 113 L355 169 L462 136 L518 199 L621 176 L743 213 L808 313 L739 390 L654 365 L588 451 L459 475 L354 425 Z',
        Monaco:'M210 405 L176 301 L228 206 L340 191 L399 123 L499 157 L531 226 L641 214 L782 278 L805 366 L731 444 L634 416 L553 480 L418 452 L305 476 Z',
        Barcelona:'M164 337 C153 234 239 152 344 148 L510 146 L575 198 L704 173 L822 231 L803 327 L727 337 L701 422 L575 467 L461 438 L366 480 L247 440 L177 392 Z',
        Spielberg:'M214 415 L174 291 L241 177 L393 132 L540 181 L692 153 L818 236 L773 330 L673 344 L624 439 L478 472 L337 443 Z',
        Silverstone:'M183 361 L215 202 L352 161 L438 224 L524 143 L665 177 L707 258 L817 286 L762 410 L626 428 L548 482 L421 437 L290 469 Z',
        Spa:'M151 341 C169 216 270 172 360 181 L441 128 L536 169 L601 247 L702 206 L837 258 L791 354 L701 364 L637 462 L510 439 L402 482 L305 421 L204 437 Z',
        Budapest:'M181 355 C163 260 224 173 322 155 C405 140 474 191 545 164 C638 128 749 172 803 254 C856 334 796 439 704 458 C608 478 555 430 469 456 C381 483 289 454 225 407 Z',
        Zandvoort:'M238 477 C148 411 148 279 197 190 C241 112 357 99 427 152 C482 194 517 170 568 137 C643 90 748 133 796 218 C847 309 819 424 726 466 C637 506 576 441 493 454 C394 470 319 536 238 477 Z',
        Monza:'M218 470 L172 339 L203 162 L313 125 L369 199 L511 187 L571 129 L753 158 L825 282 L775 405 L642 452 L557 399 L421 481 L313 427 Z',
        Madrid:'M177 353 C142 247 211 147 331 137 L455 133 C533 128 584 175 652 155 C730 132 820 194 828 284 C836 375 758 451 670 451 C586 451 540 412 466 444 C371 485 260 474 198 408 Z',
        Baku:'M171 402 L172 247 L270 172 L417 173 L417 111 L492 111 L493 254 L615 254 L615 191 L744 191 L816 278 L779 401 L672 459 L553 412 L439 482 L306 456 Z',
        Singapore:'M169 383 L193 204 L307 156 L399 202 L476 126 L575 173 L672 139 L821 224 L786 323 L835 394 L715 466 L620 418 L534 474 L432 429 L328 475 L229 442 Z',
        Austin:'M161 337 L198 190 L320 129 L423 197 L520 151 L612 215 L721 158 L825 240 L778 341 L820 413 L688 461 L585 416 L489 481 L387 423 L267 472 L190 413 Z',
        MexicoCity:'M183 414 L160 289 L229 171 L361 151 L444 203 L553 151 L693 178 L812 255 L775 344 L821 420 L690 459 L596 416 L486 476 L368 438 L273 474 Z',
        SaoPaulo:'M239 477 C151 426 144 309 181 218 C220 123 326 99 409 154 C475 198 516 195 581 156 C667 104 780 139 819 229 C853 308 812 399 743 437 C674 474 605 444 536 457 C422 479 334 531 239 477 Z',
        LasVegas:'M186 429 L175 243 L294 196 L331 111 L407 111 L422 272 L657 272 L678 139 L758 139 L816 276 L768 444 L620 463 L545 404 L407 472 L290 446 Z',
        Lusail:'M210 447 C142 375 160 260 215 188 C270 116 387 105 463 149 C528 187 562 153 621 131 C716 96 806 164 832 253 C860 350 796 441 701 462 C612 482 559 436 481 454 C389 475 284 524 210 447 Z',
        YasMarina:'M184 391 L172 247 L264 153 L397 160 L462 112 L560 152 L648 128 L813 222 L785 323 L829 400 L709 473 L594 434 L489 478 L380 432 L270 467 Z'
    };

    const F1_CALENDAR = [
        ['Australian GP','Melbourne','6–8 Mar',58,false], ['Chinese GP','Shanghai','13–15 Mar',56,true], ['Japanese GP','Suzuka','27–29 Mar',53,false], ['Bahrain GP','Sakhir','10–12 Apr',57,false], ['Saudi Arabian GP','Jeddah','17–19 Apr',50,false], ['Miami GP','Miami','1–3 May',57,true], ['Canadian GP','Montreal','22–24 May',70,true], ['Monaco GP','Monaco','5–7 Jun',78,false], ['Spanish GP','Barcelona','12–14 Jun',66,false], ['Austrian GP','Spielberg','26–28 Jun',71,false], ['British GP','Silverstone','3–5 Jul',52,true], ['Belgian GP','Spa','17–19 Jul',44,false], ['Hungarian GP','Budapest','24–26 Jul',70,false], ['Dutch GP','Zandvoort','21–23 Aug',72,true], ['Italian GP','Monza','4–6 Sep',53,false], ['Spanish GP · Madrid','Madrid','11–13 Sep',57,false], ['Azerbaijan GP','Baku','24–26 Sep',51,false], ['Singapore GP','Singapore','9–11 Oct',62,true], ['United States GP','Austin','23–25 Oct',56,false], ['Mexico City GP','MexicoCity','30 Oct–1 Nov',71,false], ['São Paulo GP','SaoPaulo','6–8 Nov',71,false], ['Las Vegas GP','LasVegas','19–21 Nov',50,false], ['Qatar GP','Lusail','27–29 Nov',57,false], ['Abu Dhabi GP','YasMarina','4–6 Dec',58,false]
    ].map((r,i) => ({ round:i+1, name:r[0], venue:r[1], date:r[2], laps:r[3], sprint:r[4], path:TRACKS[r[1]] }));

    const F2_CALENDAR = [
        ['Melbourne','Melbourne','6–8 Mar',33], ['Miami','Miami','1–3 May',35], ['Montréal','Montreal','22–24 May',40], ['Monaco','Monaco','4–7 Jun',42], ['Barcelona','Barcelona','12–14 Jun',37], ['Spielberg','Spielberg','26–28 Jun',40], ['Silverstone','Silverstone','3–5 Jul',29], ['Spa-Francorchamps','Spa','17–19 Jul',25], ['Budapest','Budapest','24–26 Jul',37], ['Monza','Monza','4–6 Sep',30], ['Madrid','Madrid','11–13 Sep',34], ['Baku · Supersized','Baku','24–26 Sep',29], ['Lusail','Lusail','27–29 Nov',32], ['Yas Marina','YasMarina','4–6 Dec',33]
    ].map((r,i) => ({ round:i+1, name:r[0], venue:r[1], date:r[2], laps:r[3], sprint:true, special:r[1]==='Baku', path:TRACKS[r[1]] }));

    const HISTORY = {
        rookie:{ bonus:0, rep:18, label:'Rookie' }, f3top10:{ bonus:2, rep:26, label:'F3 Top 10' }, f3podiums:{ bonus:4, rep:34, label:'F3 podi' }, f3champion:{ bonus:7, rep:45, label:'Campione F3' }, f2top10:{ bonus:5, rep:40, label:'F2 Top 10' }, f2winner:{ bonus:8, rep:53, label:'Vincitore F2' }, f2champion:{ bonus:12, rep:70, label:'Campione F2' }
    };

    const ENGINEERS = ['Maya Keller','Tomás Ricci','Elena Moreau','Jack Mercer','Noah Stein','Aya Nakamura','Sofia Marin','Luca Bernardi','Claire Evans','Jonas Falk','Marta Silva'];
    const DRIVER_RATINGS = {
        'George Russell':94,'Kimi Antonelli':90,'Charles Leclerc':94,'Lewis Hamilton':91,'Lando Norris':95,'Oscar Piastri':94,'Max Verstappen':97,'Isack Hadjar':89,'Liam Lawson':86,'Arvid Lindblad':82,'Pierre Gasly':87,'Franco Colapinto':84,'Esteban Ocon':86,'Oliver Bearman':88,'Nico Hülkenberg':87,'Gabriel Bortoleto':87,'Carlos Sainz':90,'Alexander Albon':89,'Fernando Alonso':92,'Lance Stroll':84,'Sergio Perez':87,'Valtteri Bottas':88,
        'Rafael Câmara':91,'Joshua Dürksen':87,'Ritomo Miyata':86,'Colton Herta':90,'Noel Leon':85,'Nikola Tsolov':90,'Dino Beganovic':89,'Roman Bilinski':85,'Gabriele Mini':89,'Oliver Goethe':88,'Sebastian Montoya':86,'Mari Boya':88,'Martinius Stenshorne':88,'Alexander Dunne':90,'Kush Maini':87,'Tasanapol Inthraphuvasak':84,'Emerson Fittipaldi':84,'Cian Shields':82,'Hiyu Yamakoshi':82,'Rafael Villagomez':83,'Laurens van Hoepen':85,'John Bennett':82
    };

    let state = createEmptyState();
    let simTimer = null;
    let toastTimer = null;
    let currentRadioQuestion = null;

    function createEmptyState() {
        return {
            created:false, role:'driver', name:'', nation:'Italia', history:'rookie', series:null, team:null, partner:null, engineer:null,
            skills:{}, skillDeltas:{}, overall:0, reputation:18, roundIndex:0, sessionIndex:0, weekends:0, seasonPoints:0, wins:0, podiums:0,
            relationships:{ driver:58, engineer:58, team:55, morale:68 },
            weekend:{ active:false, completed:false, result:null, qualifying:null, sessions:[] },
            race:{ active:false, finished:false, speed:1, elapsed:0, lap:0, totalLaps:0, cars:[], playerIndex:-1, pace:'balanced', attack:false, overtake:false, pitRequested:false, safetyCar:false, weather:'dry', temperature:26, feed:[] },
            selectedOffer:null
        };
    }

    function save() {
        if (!state.created) return;
        localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        $('#saveStateLabel').textContent = 'salvato ora';
        setTimeout(() => $('#saveStateLabel').textContent = 'salvataggio locale', 1000);
    }

    function load() {
        try {
            const stored = JSON.parse(localStorage.getItem(STORAGE_KEY));
            if (stored && stored.created) {
                state = Object.assign(createEmptyState(), stored);
                state.relationships = Object.assign(createEmptyState().relationships, stored.relationships || {});
                state.race = Object.assign(createEmptyState().race, stored.race || {}, { active:false, finished:false });
                $('#careerWizard').classList.add('hidden');
                return true;
            }
        } catch (_) {}
        return false;
    }

    function skillDefinitions() { return state.role === 'engineer' ? ENGINEER_SKILLS : DRIVER_SKILLS; }
    function currentCalendar() { return state.series === 'F1' ? F1_CALENDAR : F2_CALENDAR; }
    function currentRound() { return currentCalendar()[clamp(state.roundIndex, 0, currentCalendar().length - 1)]; }
    function currentTeams() { return state.series === 'F1' ? F1_TEAMS : F2_TEAMS; }
    function getTeam() { return currentTeams().find(t => t.name === state.team) || currentTeams()[0]; }
    function initials(name) { return (name || '--').split(/\s+/).slice(0,2).map(x => x[0] || '').join('').toUpperCase(); }
    function overallFromSkills(skills = state.skills) {
        const values = Object.values(skills).map(Number);
        return values.length ? Math.round(values.reduce((a,b) => a+b,0) / values.length) : 0;
    }

    function showToast(message) {
        const toast = $('#toast');
        toast.textContent = message;
        toast.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.remove('show'), 2500);
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>'"]/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[ch]));
    }

    function setupWizard() {
        let step = 1;
        let role = 'driver';
        let draftSkills = {};
        const base = 66;
        let budget = 24;

        const renderBuilder = () => {
            const defs = role === 'engineer' ? ENGINEER_SKILLS : DRIVER_SKILLS;
            if (!Object.keys(draftSkills).length || !defs.every(([key]) => key in draftSkills)) {
                draftSkills = Object.fromEntries(defs.map(([key]) => [key, base]));
                budget = 24;
            }
            $('#skillBuilder').innerHTML = defs.map(([key,label,help]) => `
                <div class="skill-build-row">
                    <div class="skill-build-top">
                        <div class="skill-build-name"><strong>${label}</strong><small>${help}</small></div>
                        <div class="skill-stepper"><button class="step-btn" type="button" data-skill-minus="${key}">−</button><span class="skill-value" data-skill-value="${key}">${draftSkills[key]}</span><button class="step-btn" type="button" data-skill-plus="${key}">+</button></div>
                    </div>
                </div>`).join('');
            $('#skillBudget').textContent = budget;
        };

        const renderStep = () => {
            [1,2,3].forEach(n => $('#wizardStep'+n).classList.toggle('hidden', n !== step));
            $$('.step-dot').forEach((dot,i) => dot.classList.toggle('active', i < step));
            $('#wizardBack').classList.toggle('hidden', step === 1);
            $('#wizardNext').textContent = step === 1 ? 'Costruisci abilità' : step === 2 ? 'Mostra offerte' : 'Scegli una scuderia';
            $('#wizardNext').classList.toggle('hidden', step === 3);
            if (step === 2) renderBuilder();
            if (step === 3) renderOffers(role, draftSkills);
        };

        $$('[data-role-choice]').forEach(button => button.addEventListener('click', () => {
            role = button.dataset.roleChoice;
            $$('[data-role-choice]').forEach(b => b.classList.toggle('selected', b === button));
            draftSkills = {};
        }));

        $('#skillBuilder').addEventListener('click', (event) => {
            const plus = event.target.closest('[data-skill-plus]');
            const minus = event.target.closest('[data-skill-minus]');
            if (plus) {
                const key = plus.dataset.skillPlus;
                if (budget > 0 && draftSkills[key] < 82) { draftSkills[key]++; budget--; renderBuilder(); }
            }
            if (minus) {
                const key = minus.dataset.skillMinus;
                if (draftSkills[key] > base) { draftSkills[key]--; budget++; renderBuilder(); }
            }
        });

        $('#wizardNext').addEventListener('click', () => {
            if (step === 1) {
                const name = $('#careerName').value.trim();
                if (!name) { showToast('Inserisci un nome per la carriera.'); return; }
                step = 2;
            } else if (step === 2) {
                step = 3;
            }
            renderStep();
        });
        $('#wizardBack').addEventListener('click', () => { step = Math.max(1, step - 1); renderStep(); });

        function renderOffers(selectedRole, selectedSkills) {
            const overall = overallFromSkills(selectedSkills);
            const history = HISTORY[$('#careerHistory').value] || HISTORY.rookie;
            const market = overall + history.bonus;
            const offers = generateOffers(market, history.rep);
            $('#offersList').innerHTML = offers.map((offer,index) => `
                <div class="offer">
                    <div class="offer-top"><div><div class="offer-team">${offer.team.name}</div><div class="offer-series">${offer.series} · ${selectedRole === 'driver' ? 'sedile pilota' : 'race engineer'}</div></div><div class="offer-score">${offer.fit}%</div></div>
                    <div class="offer-details">
                        <div class="offer-line"><span>Obiettivo</span><strong>${offer.target}</strong></div>
                        <div class="offer-line"><span>Durata</span><strong>${offer.length}</strong></div>
                        <div class="offer-line"><span>Pressione</span><strong>${offer.pressure}</strong></div>
                    </div>
                    <button class="btn btn-primary" style="width:100%" type="button" data-accept-offer="${index}">Firma contratto</button>
                </div>`).join('');
            $('#offersList').querySelectorAll('[data-accept-offer]').forEach(button => button.addEventListener('click', () => {
                const offer = offers[Number(button.dataset.acceptOffer)];
                startCareer(selectedRole, selectedSkills, offer);
            }));
        }

        function generateOffers(marketRating, initialRep) {
            const candidates = [];
            F2_TEAMS.forEach((team,i) => {
                const threshold = 63 + (team.perf - 80) * .5;
                if (marketRating >= threshold - 5) candidates.push({ series:'F2', team, fit:clamp(Math.round(74 + marketRating - threshold + rand(-5,5)),58,98) });
            });
            if (marketRating >= 76 || initialRep >= 65) {
                F1_TEAMS.filter(team => team.perf <= (marketRating >= 83 ? 96 : 85)).forEach(team => {
                    const threshold = 76 + (team.perf - 79) * .55;
                    if (marketRating >= threshold - 3) candidates.push({ series:'F1', team, fit:clamp(Math.round(66 + marketRating - threshold + rand(-4,4)),55,94) });
                });
            }
            candidates.sort((a,b) => b.fit - a.fit);
            const selected = [];
            const pool = candidates.length ? candidates : F2_TEAMS.slice(-3).map(team => ({series:'F2',team,fit:68}));
            for (const candidate of pool) {
                if (!selected.some(x => x.team.name === candidate.team.name)) selected.push(candidate);
                if (selected.length === 3) break;
            }
            return selected.map((o,i) => ({ ...o, target:o.series === 'F1' ? (o.team.perf > 90 ? 'Podi / vittorie' : 'Punti regolari') : (o.team.perf > 88 ? 'Titolo F2' : 'Top 8'), length:i === 0 ? '1 stagione + opzione' : '1 stagione', pressure:o.team.perf > 90 ? 'Molto alta' : o.team.perf > 85 ? 'Alta' : 'Media' }));
        }

        function startCareer(selectedRole, selectedSkills, offer) {
            const historyKey = $('#careerHistory').value;
            state = createEmptyState();
            state.created = true;
            state.role = selectedRole;
            state.name = $('#careerName').value.trim();
            state.nation = $('#careerNation').value;
            state.history = historyKey;
            state.skills = {...selectedSkills};
            state.skillDeltas = Object.fromEntries(Object.keys(selectedSkills).map(k => [k,0]));
            state.overall = overallFromSkills(selectedSkills);
            state.reputation = HISTORY[historyKey].rep;
            state.series = offer.series;
            state.team = offer.team.name;
            state.partner = offer.team.drivers[0];
            state.engineer = ENGINEERS[(offer.team.short.charCodeAt(0) + offer.team.short.length) % ENGINEERS.length];
            state.relationships = { driver:58, engineer:58, team:clamp(Math.round(52 + offer.fit/8),55,68), morale:70 };
            $('#careerWizard').classList.add('hidden');
            save();
            renderAll();
            addRadio('team', `Benvenuto in ${state.team}. Qui il cronometro conta, ma anche come lavori con le persone.`);
            addFeed('system', `Contratto firmato: ${state.name} → ${state.team} (${state.series}).`);
        }

        renderStep();
    }

    function renderAll() {
        if (!state.created) return;
        state.overall = overallFromSkills();
        renderIdentity();
        renderSkills();
        renderRelationships();
        renderHero();
        renderCalendar();
        renderWeekend();
        renderStrategy();
        renderRadio();
        renderFeed();
    }

    function renderIdentity() {
        $('#avatarInitials').textContent = initials(state.name);
        $('#playerName').textContent = state.name;
        $('#playerRole').textContent = `${state.role === 'driver' ? 'Pilota' : 'Ingegnere di gara'} · ${state.team || 'senza contratto'}`;
        $('#overallValue').textContent = state.overall;
        $('#overallRing').style.setProperty('--score', state.overall);
        $('#reputationValue').textContent = state.reputation;
        $('#seriesValue').textContent = state.series;
        $('#weekendsValue').textContent = `${state.weekends} weekend`;
    }

    function renderSkills() {
        $('#skillsList').innerHTML = skillDefinitions().map(([key,label]) => {
            const value = Math.round(state.skills[key] || 0);
            const delta = state.skillDeltas[key] || 0;
            return `<div class="skill-row"><div><div class="skill-top"><span>${label}</span><span>${value}</span></div><div class="bar"><span style="width:${value}%"></span></div></div><div class="delta">${delta > 0 ? '+'+delta.toFixed(1) : ''}</div></div>`;
        }).join('');
    }

    function renderRelationships() {
        const partnerLabel = state.role === 'driver' ? `Ingegnere · ${state.engineer}` : `Pilota · ${state.partner}`;
        const items = [
            ['◉', partnerLabel, state.role === 'driver' ? state.relationships.engineer : state.relationships.driver],
            ['⌂', 'Fiducia del team', state.relationships.team],
            ['↗', 'Morale personale', state.relationships.morale]
        ];
        $('#relationshipPanel').innerHTML = items.map(([icon,label,value]) => `<div class="relationship-row"><div class="relationship-icon">${icon}</div><div><div class="relationship-label">${label}</div><div class="bar"><span style="width:${value}%"></span></div></div><div class="relationship-score">${Math.round(value)}</div></div>`).join('');
        const trust = state.role === 'driver' ? state.relationships.engineer : state.relationships.driver;
        $('#trustChip').textContent = `fiducia ${Math.round(trust)}`;
        $('#radioTitle').textContent = state.role === 'driver' ? `${state.name.split(' ')[0]} ↔ ${state.engineer}` : `${state.partner} ↔ ${state.name.split(' ')[0]}`;
    }

    function renderHero() {
        const round = currentRound();
        const team = getTeam();
        $('#heroEyebrow').textContent = `${state.series} · Round ${round.round}/${currentCalendar().length}`;
        $('#heroTitle').textContent = `${round.name}.`;
        $('#heroDescription').textContent = state.role === 'driver'
            ? `Hai il sedile ${team.short}. Costruisci il weekend con ${state.engineer}: il team si aspetta feedback utile, gestione gomme e decisioni lucide quando la gara cambia.`
            : `Sei al pit wall ${team.short} con ${state.partner}. La velocità del pilota serve a poco se strategia, radio e fiducia non arrivano al momento giusto.`;
        $('#heroMeta').innerHTML = `<span class="chip">${team.name}</span><span class="chip">${round.date}</span><span class="chip">${round.sprint ? (state.series === 'F1' ? 'Sprint weekend' : 'Sprint + Feature') : 'Weekend standard'}</span><span class="chip">Rep ${state.reputation}</span>`;
    }

    function sessionsForRound(round) {
        if (state.series === 'F1') {
            return round.sprint ? ['FP1','Sprint Qualifying','Sprint','Qualifying','Race'] : ['FP1','FP2','FP3','Qualifying','Race'];
        }
        if (round.special) return ['Practice','Qualifying 1','Qualifying 2','Sprint','Feature Race 1','Feature Race 2'];
        return ['Practice','Qualifying','Sprint','Feature Race'];
    }

    function renderWeekend() {
        const round = currentRound();
        const sessions = state.weekend.active && state.weekend.sessions.length ? state.weekend.sessions : sessionsForRound(round);
        $('#sessionStrip').innerHTML = sessions.map((session,i) => `<div class="session-tab ${state.weekend.active && i < state.sessionIndex ? 'is-done' : ''} ${state.weekend.active && i === state.sessionIndex ? 'is-current' : ''}"><small>${i < state.sessionIndex ? 'completata' : i === state.sessionIndex && state.weekend.active ? 'live' : 'sessione'}</small><strong>${session}</strong></div>`).join('');
        $('#circuitName').textContent = `${round.name} · ${round.venue}`;
        $('#circuitMeta').textContent = `${round.date} · ${round.laps} giri GP`;
        [$('#trackShadow'),$('#trackRoad'),$('#trackEdge'),$('#trackLine')].forEach(path => path.setAttribute('d', round.path));
        $('#totalLapsValue').textContent = state.race.active || state.race.finished ? state.race.totalLaps : round.laps;

        if (!state.weekend.active) {
            $('#weekendAction').innerHTML = `<div class="weekend-action-copy"><strong>Il paddock è pronto</strong><span>Inizia il weekend: prove, qualifica e gara cambieranno rating, reputazione e rapporti.</span></div><button class="btn btn-primary" id="startWeekend" type="button">Inizia weekend</button>`;
            $('#startWeekend').addEventListener('click', startWeekend);
            resetTrackStandby();
        } else if (!state.race.active) {
            const session = state.weekend.sessions[state.sessionIndex];
            const isRace = isRaceSession(session);
            $('#weekendAction').innerHTML = `<div class="weekend-action-copy"><strong>${session}</strong><span>${isRace ? 'Vai in griglia e gestisci la sessione in tempo reale.' : session.includes('Qualifying') ? 'Il risultato dipende da passo, preparazione e pressione.' : 'Usa la sessione per imparare e costruire fiducia.'}</span></div><button class="btn btn-primary" id="runSession" type="button">${isRace ? 'Vai in griglia' : 'Completa sessione'}</button>`;
            $('#runSession').addEventListener('click', runCurrentSession);
            if (!state.race.finished) resetTrackStandby();
        } else {
            $('#weekendAction').innerHTML = `<div class="weekend-action-copy"><strong>Sessione live</strong><span>Segui il timing, parla via radio e reagisci a gomme, traffico, incidenti e Safety Car.</span></div><button class="btn" id="pauseRace" type="button">${simTimer ? 'Pausa' : 'Riprendi'}</button>`;
            $('#pauseRace').addEventListener('click', () => simTimer ? pauseRace() : resumeRace());
        }
        renderTiming();
        updateTelemetry();
    }

    function resetTrackStandby() {
        $('#carsLayer').innerHTML = '';
        $('#liveState').textContent = 'Stand-by';
        $('#lapValue').textContent = '0';
        $('#positionValue').textContent = '--';
        $('#gapValue').textContent = '--';
    }

    function startWeekend() {
        const round = currentRound();
        state.weekend = { active:true, completed:false, result:null, qualifying:null, sessions:sessionsForRound(round) };
        state.sessionIndex = 0;
        state.skillDeltas = Object.fromEntries(Object.keys(state.skills).map(k => [k,0]));
        addRadio('team', `Weekend ${round.name}: briefing completato. Prima priorità: costruire una baseline pulita.`);
        addFeed('system', `Round ${round.round}: ${round.name} iniziato.`);
        save(); renderAll();
    }

    function isRaceSession(session) { return /Sprint|Race/i.test(session); }
    function isQualifying(session) { return /Qualifying/i.test(session); }

    function runCurrentSession() {
        const session = state.weekend.sessions[state.sessionIndex];
        if (isRaceSession(session)) {
            startRace(session);
            return;
        }
        const qualitySkill = state.role === 'driver' ? ((state.skills.pace || 70) + (state.skills.qualifying || state.skills.adaptability || 70)) / 2 : ((state.skills.setup || 70) + (state.skills.data || 70)) / 2;
        const team = getTeam();
        if (isQualifying(session)) {
            const fieldSize = state.series === 'F1' ? 22 : 22;
            const basePos = Math.round(fieldSize - ((qualitySkill * .55 + team.perf * .45 - 75) / 25) * (fieldSize - 3) + rand(-3.3,3.3));
            state.weekend.qualifying = clamp(basePos,1,fieldSize);
            addFeed('qualifying', `${session}: ${state.role === 'driver' ? state.name : state.partner} chiude P${state.weekend.qualifying}.`);
            if (state.role === 'engineer') growSkill('setup', rand(.05,.16)); else growSkill('qualifying', rand(.05,.16));
        } else {
            const gain = rand(.04,.13);
            growSkill(state.role === 'driver' ? 'feedback' : 'data', gain);
            changeRelationship('team', 0.5);
            addRadio(state.role === 'driver' ? 'team' : 'driver', state.role === 'driver' ? 'Buon run. Abbiamo una direzione chiara sul bilanciamento.' : 'La macchina è più prevedibile. Questa modifica la sento davvero.');
            addFeed('practice', `${session}: programma completato, correlazione dati ${Math.round(86 + rand(0,11))}%.`);
        }
        state.sessionIndex++;
        if (state.sessionIndex >= state.weekend.sessions.length) finishWeekend(state.weekend.qualifying || 12);
        save(); renderAll();
    }

    function startRace(sessionName) {
        const round = currentRound();
        const sprint = /Sprint/i.test(sessionName);
        const feature1 = /Feature Race 1/i.test(sessionName);
        const raceLaps = sprint ? Math.max(18, Math.round(round.laps * .55)) : state.series === 'F2' ? round.laps : round.laps;
        const field = buildField();
        const playerIndex = field.findIndex(car => car.player);
        let grid = state.weekend.qualifying || clamp(Math.round(12 + rand(-5,5)),1,field.length);
        if (state.series === 'F2' && sprint && state.weekend.qualifying) grid = state.weekend.qualifying <= 10 ? 11 - state.weekend.qualifying : state.weekend.qualifying;
        field.sort((a,b) => {
            if (a.player) return grid - 1 - b.seed;
            if (b.player) return a.seed - (grid - 1);
            return a.seed - b.seed;
        });
        field.forEach((car,i) => {
            car.progress = -i * .0026;
            car.position = i + 1;
            car.tyre = sprint ? pick(['soft','medium']) : pick(['medium','hard','soft']);
            car.wear = rand(0,4);
            car.energy = rand(84,100);
            car.pitted = false;
            car.pitCount = 0;
            car.retired = false;
            car.damage = 0;
        });
        state.race = {
            active:true, finished:false, speed:1, elapsed:0, lap:0, totalLaps:raceLaps, cars:field, playerIndex:field.findIndex(c => c.player), pace:'balanced', attack:false, overtake:false, pitRequested:false, safetyCar:false,
            weather: Math.random() < .14 ? 'changeable' : 'dry', temperature:Math.round(rand(19,31)), feed:[], sessionName, requiredCompoundChange:state.series === 'F1' && !sprint && Math.random() > .08, feature1
        };
        $('#liveState').textContent = sprint ? 'SPRINT LIVE' : 'RACE LIVE';
        addFeed('start', `${sessionName}: partenza. ${state.race.cars.find(c=>c.player).name} scatta dalla P${grid}.`);
        addRadio('team', `Lights out. ${state.role === 'driver' ? 'Concentrati sulla procedura e proteggi le gomme nel primo settore.' : 'La finestra strategica è aperta: occhio al traffico.'}`);
        buildCarSvg();
        save(); renderAll(); resumeRace();
    }

    function buildField() {
        const teams = currentTeams();
        const result = [];
        teams.forEach((team,ti) => team.drivers.forEach((driver,di) => {
            const replace = state.role === 'driver' && team.name === state.team && di === 1;
            result.push({
                id:replace ? 'player' : `${team.short}-${di}`,
                name:replace ? state.name : driver,
                team:team.name, teamShort:team.short, color:team.color, rating:replace ? state.overall : (DRIVER_RATINGS[driver] || 84),
                perf:team.perf, player:replace, seed:result.length
            });
        }));
        if (state.role === 'engineer') {
            const target = result.find(c => c.name === state.partner) || result.find(c => c.team === state.team);
            if (target) { target.player = true; target.rating = clamp(target.rating + (state.overall-70)*.12,72,99); target.id='player'; }
        }
        return result;
    }

    function buildCarSvg() {
        const layer = $('#carsLayer');
        layer.innerHTML = '';
        state.race.cars.forEach((car,index) => {
            const ns = 'http://www.w3.org/2000/svg';
            const g = document.createElementNS(ns,'g');
            g.setAttribute('class',`car-dot ${car.player ? 'player' : ''}`);
            g.setAttribute('data-car-index',index);
            const body = document.createElementNS(ns,'rect');
            body.setAttribute('x','-7'); body.setAttribute('y','-4'); body.setAttribute('width','14'); body.setAttribute('height','8'); body.setAttribute('rx','3'); body.setAttribute('fill',car.player ? '#c6ff2e' : car.color); body.setAttribute('class','car-body');
            const wing = document.createElementNS(ns,'rect'); wing.setAttribute('x','-9'); wing.setAttribute('y','-6'); wing.setAttribute('width','4'); wing.setAttribute('height','12'); wing.setAttribute('rx','1'); wing.setAttribute('fill',car.player ? '#c6ff2e' : car.color);
            const nose = document.createElementNS(ns,'rect'); nose.setAttribute('x','5'); nose.setAttribute('y','-2'); nose.setAttribute('width','6'); nose.setAttribute('height','4'); nose.setAttribute('rx','1'); nose.setAttribute('fill',car.player ? '#c6ff2e' : car.color);
            const text = document.createElementNS(ns,'text'); text.setAttribute('x','0'); text.setAttribute('y','.5'); text.textContent = car.player ? 'YOU' : String(index+1);
            g.append(body,wing,nose,text); layer.append(g);
        });
        renderCars();
    }

    function resumeRace() {
        if (!state.race.active || state.race.finished || simTimer) return;
        simTimer = setInterval(simTick, 120);
        renderWeekend();
    }
    function pauseRace() { clearInterval(simTimer); simTimer = null; renderWeekend(); }

    function simTick() {
        if (!state.race.active) return;
        const multiplier = state.race.speed;
        for (let step=0; step<multiplier; step++) advanceSimulation();
        renderCars();
        updateRaceHud();
        if (state.race.finished) {
            pauseRace();
            concludeRaceSession();
        }
    }

    function advanceSimulation() {
        const race = state.race;
        race.elapsed++;
        const player = race.cars.find(c => c.player);
        const playerSkill = state.role === 'driver' ? state.overall : clamp((player.rating || 84) + (state.overall - 70) * .08,75,98);
        race.cars.forEach(car => {
            if (car.retired || car.progress >= race.totalLaps) return;
            const paceMode = car.player ? race.pace : 'balanced';
            const tyreFactor = tyrePaceFactor(car);
            const driver = car.player ? playerSkill : car.rating;
            const base = 0.00117 + (driver - 80) * .0000038 + (car.perf - 80) * .0000028;
            const mode = paceMode === 'push' ? 1.045 : paceMode === 'conserve' ? .965 : 1;
            const safety = race.safetyCar ? .64 : 1;
            const variability = 1 + rand(-.018,.018);
            const overtakeBoost = car.player && race.overtake && car.energy > 10 ? 1.025 : 1;
            car.progress += base * mode * tyreFactor * safety * variability * overtakeBoost;
            car.wear = clamp(car.wear + (paceMode === 'push' ? .019 : paceMode === 'conserve' ? .010 : .014) * (car.tyre === 'soft' ? 1.22 : car.tyre === 'hard' ? .78 : 1),0,100);
            car.energy = clamp(car.energy + (car.player && race.overtake ? -.12 : .045),0,100);
            if (car.damage) car.progress -= base * car.damage * .002;
        });

        if (race.pitRequested && player && fractionalLap(player.progress) > .82) performPit(player, true);
        aiPitStops();
        resolveBattles();
        maybeIncident();
        maybeWeather();
        updateOrder();
        race.lap = Math.max(0, Math.floor(player?.progress || 0) + 1);
        if (player && player.progress >= race.totalLaps) race.finished = true;
        if (race.safetyCar && Math.random() < .0035) {
            race.safetyCar = false;
            addFeed('safety','Safety Car in questo giro. Ripartenza imminente.');
            addRadio('team','Safety Car in. Prepara la ripartenza.');
        }
        if (race.elapsed % 52 === 0) maybeRadioPrompt();
        if (race.elapsed % 25 === 0) save();
    }

    function fractionalLap(progress) { return progress - Math.floor(progress); }
    function tyrePaceFactor(car) {
        const base = car.tyre === 'soft' ? 1.012 : car.tyre === 'hard' ? .991 : car.tyre === 'inter' ? (state.race.weather === 'wet' ? 1.015 : .95) : car.tyre === 'wet' ? (state.race.weather === 'wet' ? 1.005 : .92) : 1;
        const cliff = car.wear > 78 ? 1 - (car.wear - 78) * .0026 : 1;
        const weather = state.race.weather === 'wet' && !['inter','wet'].includes(car.tyre) ? .89 : 1;
        return base * cliff * weather;
    }

    function updateOrder() {
        const active = state.race.cars.filter(c => !c.retired).sort((a,b) => b.progress-a.progress);
        const retired = state.race.cars.filter(c => c.retired).sort((a,b)=>b.progress-a.progress);
        [...active,...retired].forEach((car,i) => car.position = i+1);
    }

    function resolveBattles() {
        const race = state.race;
        const ordered = race.cars.filter(c=>!c.retired).sort((a,b)=>b.progress-a.progress);
        for (let i=1;i<ordered.length;i++) {
            const behind = ordered[i], ahead = ordered[i-1];
            const gap = ahead.progress - behind.progress;
            if (gap > .0023 || gap < 0) continue;
            const attackBias = behind.player ? (race.attack ? .09 : .025) + (race.overtake ? .06 : 0) : .025;
            const skill = behind.player ? (state.skills.racecraft || state.skills.strategy || 70) : behind.rating;
            const chance = .014 + attackBias + (skill - 80) * .0012;
            if (Math.random() < chance) {
                behind.progress = ahead.progress + .00045;
                ahead.progress -= .00018;
                addFeed('overtake',`${behind.name} supera ${ahead.name} per la P${i}.`);
                if (behind.player) {
                    growSkill(state.role === 'driver' ? 'racecraft' : 'strategy', .025);
                    if (state.role === 'engineer') changeRelationship('driver', .15);
                }
            }
        }
    }

    function maybeIncident() {
        if (Math.random() > .0017 * Math.max(1,state.race.speed/4)) return;
        const candidates = state.race.cars.filter(c=>!c.retired && c.progress > .08 && c.progress < state.race.totalLaps-.15);
        if (!candidates.length) return;
        let car = pick(candidates);
        const player = car.player;
        const focus = state.role === 'driver' ? (state.skills.focus || 70) : (state.skills.pressure || 70);
        if (player && Math.random() < focus / 120) car = pick(candidates.filter(c=>!c.player) || candidates);
        const severity = Math.random();
        if (severity > .78) {
            car.retired = true;
            addFeed('incident',`${car.name} è fermo: incidente, ritiro.`);
            if (car.player) addRadio('alert','Stop the car. Siamo fuori dalla gara.');
            if (Math.random() < .72) deploySafetyCar();
        } else {
            car.damage = clamp(car.damage + rand(5,18),0,40);
            car.progress -= rand(.003,.009);
            addFeed('incident',`${car.name} ha un contatto e perde tempo${car.damage > 12 ? ' con danno all’ala' : ''}.`);
            if (car.player) addRadio('alert','Contatto rilevato. Controlliamo i dati, resta fuori per ora.');
            if (Math.random() < .25) deploySafetyCar();
        }
    }

    function deploySafetyCar() {
        if (state.race.safetyCar) return;
        state.race.safetyCar = true;
        addFeed('safety','SAFETY CAR: neutralizzazione della gara.');
        addRadio('team','Safety Car, Safety Car. Delta positivo. Valuta la finestra box.');
    }

    function aiPitStops() {
        const race = state.race;
        race.cars.forEach(car => {
            if (car.player || car.retired || car.progress < 1) return;
            const lap = Math.floor(car.progress);
            const threshold = car.tyre === 'soft' ? 55 : car.tyre === 'medium' ? 68 : 78;
            const strategic = race.safetyCar && Math.random() < .018;
            if ((car.wear > threshold || strategic) && fractionalLap(car.progress) > .83) performPit(car,false);
        });
    }

    function performPit(car, isPlayer) {
        car.pitCount++;
        car.pitted = true;
        car.progress -= state.series === 'F1' ? .018 : .014;
        const next = isPlayer ? $('#nextTyre').value : pick(['soft','medium','hard']);
        const old = car.tyre;
        car.tyre = next; car.wear = 0; car.damage = Math.max(0,car.damage-12);
        if (isPlayer) state.race.pitRequested = false;
        addFeed('pit',`${car.name}: pit stop, ${old.toUpperCase()} → ${next.toUpperCase()}.`);
        if (isPlayer) addRadio('team',`Stop completato. ${next.toUpperCase()} montate, traffico in uscita da gestire.`);
    }

    function maybeWeather() {
        const race = state.race;
        if (race.weather !== 'changeable' || race.elapsed < 120 || Math.random() > .0009) return;
        race.weather = 'wet';
        addFeed('weather','Pioggia sul settore 2. Il crossover verso intermedie è vicino.');
        addRadio('team','Rain increasing. Dimmi / dimmiamo se il grip cala: finestra intermedie aperta.');
        setTimeout(() => {},0);
    }

    function renderCars() {
        const path = $('#trackLine');
        let length = 0;
        try { length = path.getTotalLength(); } catch (_) { return; }
        state.race.cars.forEach((car,index) => {
            const g = $(`[data-car-index="${index}"]`);
            if (!g || car.retired) { if (g) g.setAttribute('opacity',car.retired ? '.22':'1'); return; }
            const frac = ((car.progress % 1) + 1) % 1;
            const point = path.getPointAtLength(frac * length);
            const point2 = path.getPointAtLength(((frac + .002) % 1) * length);
            const angle = Math.atan2(point2.y-point.y,point2.x-point.x) * 180/Math.PI;
            const laneOffset = ((index % 3)-1) * 3.3;
            g.setAttribute('transform',`translate(${point.x} ${point.y + laneOffset}) rotate(${angle})`);
        });
    }

    function updateRaceHud() {
        const player = state.race.cars.find(c=>c.player);
        if (!player) return;
        $('#lapValue').textContent = Math.min(state.race.totalLaps,Math.max(1,Math.floor(player.progress)+1));
        $('#totalLapsValue').textContent = state.race.totalLaps;
        $('#positionValue').textContent = `P${player.position}`;
        const leader = state.race.cars.filter(c=>!c.retired).sort((a,b)=>b.progress-a.progress)[0];
        const gap = leader === player ? 'LEADER' : `+${Math.max(0,(leader.progress-player.progress)*82).toFixed(1)}s`;
        $('#gapValue').textContent = gap;
        $('#liveState').textContent = state.race.safetyCar ? 'SAFETY CAR' : state.race.weather === 'wet' ? 'WET RACE' : 'RACE LIVE';
        $('#weatherLabel').textContent = `${state.race.weather === 'wet' ? '☂' : state.race.weather === 'changeable' ? '◑' : '☀'} ${state.race.temperature}°C`;
        renderTiming(); updateTelemetry(); renderFeed();
    }

    function updateTelemetry() {
        const player = state.race.cars?.find?.(c=>c.player);
        if (!player) {
            $('#tyreTelemetry').textContent='--'; $('#wearTelemetry').textContent='--'; $('#energyTelemetry').textContent='--'; $('#carTelemetry').textContent='--'; return;
        }
        $('#tyreTelemetry').textContent = player.tyre?.toUpperCase() || '--';
        $('#wearTelemetry').textContent = `${Math.round(player.wear || 0)}%`;
        $('#energyTelemetry').textContent = `${Math.round(player.energy || 0)}%`;
        $('#carTelemetry').textContent = player.retired ? 'RITIRO' : player.damage > 15 ? 'DANNO' : player.damage > 0 ? 'OK / ala' : 'OK';
    }

    function renderTiming() {
        if (!state.race.cars?.length) { $('#timingBody').innerHTML = `<tr><td colspan="5" class="empty-state">Il live timing apparirà quando una sessione di gara inizierà.</td></tr>`; $('#fieldCount').textContent = '-- auto'; return; }
        const ordered = [...state.race.cars].sort((a,b)=>a.position-b.position);
        const leader = ordered.find(c=>!c.retired);
        $('#fieldCount').textContent = `${ordered.length} auto`;
        $('#timingBody').innerHTML = ordered.slice(0,22).map(car => {
            const gap = car.retired ? 'OUT' : car === leader ? 'LEADER' : `+${Math.max(0,(leader.progress-car.progress)*82).toFixed(1)}`;
            const tyre = car.tyre || 'medium';
            return `<tr class="${car.player?'you':''}"><td class="pos">${car.position}</td><td><strong>${escapeHtml(car.name)}</strong></td><td><span class="team-swatch" style="background:${car.color}"></span>${escapeHtml(car.teamShort)}</td><td><span class="tyre ${tyre}">${tyre[0].toUpperCase()}</span></td><td>${gap}</td></tr>`;
        }).join('');
    }

    function concludeRaceSession() {
        const player = state.race.cars.find(c=>c.player);
        const finalPos = player?.position || 22;
        state.weekend.result = finalPos;
        const session = state.race.sessionName;
        const points = pointsForPosition(finalPos, /Sprint/i.test(session));
        state.seasonPoints += points;
        if (finalPos === 1 && !/Sprint/i.test(session)) state.wins++;
        if (finalPos <= 3 && !/Sprint/i.test(session)) state.podiums++;
        const perfKey = state.role === 'driver' ? (finalPos <= 5 ? 'racecraft' : 'focus') : (finalPos <= 5 ? 'strategy' : 'pressure');
        growSkill(perfKey, finalPos <= 5 ? rand(.08,.23) : rand(.03,.12));
        if (finalPos <= 10) { state.reputation = clamp(state.reputation + (finalPos <= 3 ? 3 : 1),0,100); changeRelationship('team',finalPos<=5?1.4:.6); }
        if (player?.retired) { state.reputation = clamp(state.reputation-1,0,100); changeRelationship('morale',-2); }
        addFeed('finish',`${session}: bandiera a scacchi, P${finalPos}${points ? ` · +${points} punti` : ''}.`);
        state.race.active = false;
        state.race.finished = true;
        state.sessionIndex++;
        if (state.sessionIndex >= state.weekend.sessions.length) finishWeekend(finalPos);
        else { save(); renderAll(); }
    }

    function pointsForPosition(pos,sprint) {
        if (state.series === 'F1') {
            const table = sprint ? [8,7,6,5,4,3,2,1] : [25,18,15,12,10,8,6,4,2,1];
            return table[pos-1] || 0;
        }
        const table = sprint ? [10,8,6,5,4,3,2,1] : [25,18,15,12,10,8,6,4,2,1];
        return table[pos-1] || 0;
    }

    function finishWeekend(finalPos) {
        state.weekend.active = false;
        state.weekend.completed = true;
        state.weekends++;
        const defs = skillDefinitions();
        const learning = state.role === 'driver' ? 'adaptability' : 'leadership';
        growSkill(learning, rand(.06,.17));
        const comm = state.role === 'driver' ? 'feedback' : 'communication';
        growSkill(comm, rand(.04,.14));
        state.relationships.morale = clamp(state.relationships.morale + (finalPos <= 10 ? 1.5 : -.5),0,100);
        state.overall = overallFromSkills();
        $('#resultTitle').textContent = `${currentRound().name} · debrief`;
        $('#resultPosition').textContent = `P${finalPos}`;
        $('#resultReputation').textContent = state.reputation;
        $('#resultTrust').textContent = Math.round(state.relationships.team);
        $('#growthList').innerHTML = defs.map(([key,label]) => state.skillDeltas[key] > 0 ? `<div class="growth-row"><span>${label}</span><span>+${state.skillDeltas[key].toFixed(2)}</span></div>` : '').join('') || `<div class="growth-row"><span>Esperienza weekend</span><span>+0.05</span></div>`;
        $('#resultModal').classList.remove('hidden');
        save(); renderAll();
    }

    function growSkill(key, amount) {
        if (!(key in state.skills)) return;
        state.skills[key] = clamp(Number(state.skills[key]) + amount,1,99);
        state.skillDeltas[key] = (state.skillDeltas[key] || 0) + amount;
    }
    function changeRelationship(key,delta) { state.relationships[key] = clamp((state.relationships[key] || 50) + delta,0,100); }

    function addFeed(type,text) {
        const lap = state.race?.active ? Math.max(1,state.race.lap || 1) : '-';
        state.race.feed = state.race.feed || [];
        state.race.feed.unshift({type,text,lap,ts:Date.now()});
        state.race.feed = state.race.feed.slice(0,60);
        renderFeed();
    }
    function renderFeed() {
        const feed = state.race.feed || [];
        $('#eventFeed').innerHTML = feed.length ? feed.map(item => `<div class="feed-item ${item.type}"><div class="feed-lap">${item.lap === '-' ? 'SYS' : 'L'+item.lap}</div><div>${escapeHtml(item.text)}</div></div>`).join('') : `<div class="empty-state">Eventi, sorpassi, pit stop e incidenti compariranno qui.</div>`;
    }

    function addRadio(who,text) {
        const log = $('#radioLog');
        const label = who === 'you' ? state.name : who === 'driver' ? state.partner : who === 'alert' ? 'Race control' : state.role === 'driver' ? state.engineer : state.name;
        const item = document.createElement('div');
        item.className = `radio-msg ${who}`;
        item.innerHTML = `<strong>${escapeHtml(label || 'Team')}</strong>${escapeHtml(text)}`;
        log.append(item); log.scrollTop = log.scrollHeight;
    }
    function renderRadio() {
        if (!$('#radioLog').children.length) addRadio('team', state.created ? 'Canale radio aperto. Il rapporto cresce con decisioni coerenti e comunicazione utile.' : 'Canale radio non attivo.');
    }

    function maybeRadioPrompt() {
        if (currentRadioQuestion || !state.race.active) return;
        const player = state.race.cars.find(c=>c.player);
        if (!player) return;
        const wear = player.wear;
        const promptsDriver = [
            { q:wear > 62 ? 'Le gomme stanno calando. Box questa tornata?' : 'Come senti il bilanciamento?', choices:[['Box, confermo','pit'],['Posso estendere','extend'],['Più sottosterzo nel lento','feedback']] },
            { q:state.race.safetyCar ? 'Safety Car. Finestra box quasi gratuita: che facciamo?' : 'Auto davanti a meno di un secondo. Vuoi usare energia?', choices:[['Box ora','pit'],['Attacco adesso','attack'],['Conservo energia','save']] }
        ];
        const promptsEngineer = [
            { q:wear > 62 ? 'Le posteriori stanno andando. Non tengo questo passo.' : 'La macchina scivola nel settore centrale. Cosa vuoi che faccia?', choices:[['Box questa tornata','pit'],['Estendi 2 giri','extend'],['Modifica differenziale, resta fuori','setup']] },
            { q:state.race.safetyCar ? 'Safety Car. Dimmi subito: entro?' : 'Sono nel DRS— anzi, Overtake range. Posso spingere?', choices:[['Box, box','pit'],['Push + energia','attack'],['Gestisci e aspetta','save']] }
        ];
        currentRadioQuestion = pick(state.role === 'driver' ? promptsDriver : promptsEngineer);
        addRadio(state.role === 'driver' ? 'team' : 'driver', currentRadioQuestion.q);
        $('#radioChoices').innerHTML = currentRadioQuestion.choices.map(([label,action]) => `<button class="radio-choice" type="button" data-radio-action="${action}">${label}</button>`).join('');
    }

    function handleRadio(action) {
        const player = state.race.cars.find(c=>c.player);
        if (!player) return;
        if (action === 'pit') {
            state.race.pitRequested = true;
            addRadio('you',state.role === 'driver' ? 'Box, box. Ricevuto.' : 'Box, box. Confermo pit questa tornata.');
            changeRelationship(state.role === 'driver' ? 'engineer' : 'driver', player.wear > 55 || state.race.safetyCar ? 1.1 : -.6);
            growSkill(state.role === 'engineer' ? 'strategy' : 'communication', .035);
        } else if (action === 'extend') {
            state.race.pitRequested = false;
            addRadio('you',state.role === 'driver' ? 'Posso tenerle vive ancora un paio di giri.' : 'Resta fuori due giri, proteggi l’asse posteriore.');
            changeRelationship(state.role === 'driver' ? 'engineer' : 'driver', player.wear < 70 ? .6 : -.9);
            growSkill(state.role === 'driver' ? 'tyres' : 'tyres', .03);
        } else if (action === 'attack') {
            state.race.attack = true; state.race.overtake = true;
            addRadio('you',state.role === 'driver' ? 'Copy. Uso energia ora.' : 'Push now. Energia disponibile, vai.');
            changeRelationship(state.role === 'driver' ? 'engineer' : 'driver', .5);
        } else if (action === 'save') {
            state.race.attack = false; state.race.overtake = false;
            addRadio('you',state.role === 'driver' ? 'Ricevuto, ricarico.' : 'Hold position, ricarica e prepara il prossimo rettilineo.');
            growSkill(state.role === 'engineer' ? 'communication' : 'focus', .02);
        } else if (action === 'feedback' || action === 'setup') {
            addRadio('you',state.role === 'driver' ? 'Più sottosterzo nel lento, ingresso buono ma manca rotazione.' : 'Differenziale -1 in ingresso. Dimmi se libera il centro curva.');
            growSkill(state.role === 'driver' ? 'feedback' : 'setup', .045);
            changeRelationship(state.role === 'driver' ? 'engineer' : 'driver', .8);
        }
        currentRadioQuestion = null; $('#radioChoices').innerHTML = ''; save(); renderRelationships();
    }

    function renderCalendar() {
        const calendar = currentCalendar();
        $('#calendarTitle').textContent = `${state.series} · Calendario 2026`;
        $('#calendarList').innerHTML = calendar.map((round,i) => `<div class="round-card ${i===state.roundIndex?'current':''} ${i<state.roundIndex?'done':''}"><div class="round-no">Round ${round.round}</div><div class="round-name">${round.name}</div><div class="round-date">${round.date}</div>${round.sprint ? `<div class="sprint-label">${state.series==='F1'?'SPRINT WEEKEND':round.special?'SPECIAL · 2 FEATURE':'SPRINT + FEATURE'}</div>` : ''}</div>`).join('');
        setTimeout(() => $('#calendarList .current')?.scrollIntoView({inline:'center',block:'nearest',behavior:'smooth'}),40);
    }

    function renderStrategy() {
        const conditions = state.race.weather || 'dry';
        const icons = conditions === 'wet' ? ['☂','☂','◑','◑','☀'] : conditions === 'changeable' ? ['☀','◑','☂','☂','◑'] : ['☀','☀','☀','◑','☀'];
        $('#forecast').innerHTML = icons.map((icon,i) => `<div class="weather-cell"><span>${icon}</span><small>+${i*10}m</small></div>`).join('');
    }

    function bindControls() {
        $('#raceControls').addEventListener('click', event => {
            const button = event.target.closest('[data-control]');
            if (!button || !state.race.active) return;
            const control = button.dataset.control;
            if (control === 'pace') {
                const modes = ['balanced','push','conserve'];
                const next = modes[(modes.indexOf(state.race.pace)+1)%modes.length];
                state.race.pace = next;
                button.querySelector('strong').textContent = next === 'push' ? 'Push' : next === 'conserve' ? 'Conserva' : 'Bilanciato';
            } else if (control === 'attack') {
                state.race.attack = !state.race.attack; button.classList.toggle('active',state.race.attack);
            } else if (control === 'overtake') {
                state.race.overtake = !state.race.overtake; button.classList.toggle('active',state.race.overtake);
            } else if (control === 'pit') {
                state.race.pitRequested = !state.race.pitRequested; button.classList.toggle('active',state.race.pitRequested); addRadio('you',state.race.pitRequested ? 'Box questa tornata.' : 'Cancel pit, resto fuori.');
            }
            save();
        });

        $('#speedGroup').addEventListener('click', event => {
            const button = event.target.closest('[data-speed]'); if (!button) return;
            state.race.speed = Number(button.dataset.speed);
            $$('.speed-btn').forEach(b=>b.classList.toggle('active',b===button));
        });
        $('#radioChoices').addEventListener('click', event => { const button=event.target.closest('[data-radio-action]'); if(button) handleRadio(button.dataset.radioAction); });

        $('#closeResult').addEventListener('click', () => {
            $('#resultModal').classList.add('hidden');
            state.roundIndex++;
            if (state.roundIndex >= currentCalendar().length) endSeason();
            else {
                state.weekend = createEmptyState().weekend; state.sessionIndex=0; state.race=createEmptyState().race;
                save(); renderAll();
            }
        });
        $('#rulesButton').addEventListener('click',()=>$('#rulesModal').classList.remove('hidden'));
        $('#closeRules').addEventListener('click',()=>$('#rulesModal').classList.add('hidden'));
        $('#resetButton').addEventListener('click',()=>{
            if (!confirm('Vuoi davvero cancellare la carriera locale e ricominciare?')) return;
            pauseRace(); localStorage.removeItem(STORAGE_KEY); location.reload();
        });
    }

    function endSeason() {
        state.roundIndex = 0;
        const earnedPromotion = state.series === 'F2' && (state.seasonPoints >= 120 || state.reputation >= 72 || state.overall >= 80);
        if (earnedPromotion) {
            const eligible = F1_TEAMS.filter(t=>t.perf<=88).sort((a,b)=>Math.abs(a.perf-state.overall)-Math.abs(b.perf-state.overall));
            state.series = 'F1'; state.team = (eligible[0] || F1_TEAMS.at(-1)).name; state.partner = getTeam().drivers[0];
            state.reputation = clamp(state.reputation+8,0,100);
            showToast(`Promozione F1: contratto ${state.team}.`);
        } else {
            state.reputation = clamp(state.reputation + (state.seasonPoints > 80 ? 5 : 1),0,100);
            showToast('Stagione conclusa. Contratto rinnovato per il nuovo ciclo carriera.');
        }
        state.seasonPoints=0; state.weekend=createEmptyState().weekend; state.race=createEmptyState().race; save(); renderAll();
    }

    load();
    setupWizard();
    bindControls();
    if (state.created) renderAll();
})();
</script>
</body>
</html>
