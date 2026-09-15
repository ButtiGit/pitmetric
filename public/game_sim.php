<?php

declare(strict_types=1);

header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$game_title = 'PitMetric Race Engineer Simulator';
$game_driver = [
    'game_name' => 'Luca Moretti',
    'game_number' => 27,
    'game_team' => 'PitMetric Racing',
    'game_country' => 'ITA',
];
$game_track = [
    'game_name' => 'Autodromo Internazionale',
    'game_length_km' => 5.41,
    'game_lap_time_base' => 89.4,
    'game_overtake_difficulty' => 0.62,
];
$game_sessions = [
    [
        'game_key' => 'fp1',
        'game_name' => 'FP1',
        'game_day' => 'Venerdì',
        'game_time' => '13:30',
        'game_laps' => 18,
        'game_type' => 'practice',
        'game_objective' => 'Capire bilanciamento, degrado gomme e consumo. Usa i feedback del pilota per preparare il setup.',
    ],
    [
        'game_key' => 'sprint_qualifying',
        'game_name' => 'Sprint Qualifying',
        'game_day' => 'Venerdì',
        'game_time' => '17:30',
        'game_laps' => 10,
        'game_type' => 'qualifying',
        'game_objective' => 'Massimizza il giro senza surriscaldare le gomme. Il risultato determina la griglia Sprint.',
    ],
    [
        'game_key' => 'sprint',
        'game_name' => 'Sprint',
        'game_day' => 'Sabato',
        'game_time' => '12:00',
        'game_laps' => 24,
        'game_type' => 'race',
        'game_objective' => 'Gestisci gomma, batteria e posizione in una gara corta dove ogni errore costa subito.',
    ],
    [
        'game_key' => 'qualifying',
        'game_name' => 'Qualifying',
        'game_day' => 'Sabato',
        'game_time' => '16:00',
        'game_laps' => 12,
        'game_type' => 'qualifying',
        'game_objective' => 'Prepara il giro decisivo e trova il compromesso tra temperatura gomme, traffico e deploy.',
    ],
    [
        'game_key' => 'race',
        'game_name' => 'Race',
        'game_day' => 'Domenica',
        'game_time' => '15:00',
        'game_laps' => 58,
        'game_type' => 'race',
        'game_objective' => 'Porta a casa il risultato finale: strategia, affidabilità, meteo, pit stop e radio sono tutti nelle tue mani.',
    ],
];

$game_bootstrap = [
    'game_title' => $game_title,
    'game_driver' => $game_driver,
    'game_track' => $game_track,
    'game_sessions' => $game_sessions,
];
?>
<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive,nosnippet">
    <meta name="referrer" content="same-origin">
    <title><?= htmlspecialchars($game_title, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/game_assets/game_sim.css?v=1">
</head>
<body>
<div class="game_shell" id="game_app">
    <header class="game_header">
        <div class="game_brand">
            <div class="game_logo">PM</div>
            <div class="game_brand_text">
                <h1 class="game_brand_title">Race Engineer Simulator</h1>
                <p class="game_brand_subtitle">Pagina sperimentale PitMetric · accesso diretto · nessun link pubblico</p>
            </div>
        </div>
        <div class="game_header_actions">
            <button class="game_button" id="game_save_button" type="button">Salva</button>
            <button class="game_button game_danger" id="game_reset_button" type="button">Nuovo weekend</button>
        </div>
    </header>

    <section class="game_schedule" id="game_schedule"></section>

    <main class="game_grid">
        <div class="game_stack">
            <section class="game_panel">
                <div class="game_panel_body">
                    <div class="game_session_hero">
                        <div>
                            <div class="game_badge" id="game_session_badge"><span class="game_live_dot"></span><span id="game_session_status">Briefing</span></div>
                            <h2 class="game_session_name" id="game_session_name">FP1</h2>
                            <p class="game_session_objective" id="game_session_objective"></p>
                        </div>
                        <div class="game_position">
                            <div class="game_position_value" id="game_position">P--</div>
                            <div class="game_position_label">Posizione</div>
                        </div>
                    </div>
                    <div class="game_race_strip">
                        <div class="game_stat"><div class="game_stat_label">Lap</div><div class="game_stat_value" id="game_lap">0 / 18</div><div class="game_stat_sub" id="game_sector">S1</div></div>
                        <div class="game_stat"><div class="game_stat_label">Last lap</div><div class="game_stat_value" id="game_last_lap">--:--.---</div><div class="game_stat_sub" id="game_best_lap">Best --</div></div>
                        <div class="game_stat"><div class="game_stat_label">Gap ahead</div><div class="game_stat_value" id="game_gap_ahead">--</div><div class="game_stat_sub" id="game_gap_back">Behind --</div></div>
                        <div class="game_stat"><div class="game_stat_label">Tyre</div><div class="game_stat_value" id="game_tyre_compound">M</div><div class="game_stat_sub" id="game_tyre_age">0 laps</div></div>
                        <div class="game_stat"><div class="game_stat_label">Weather</div><div class="game_stat_value" id="game_weather">DRY</div><div class="game_stat_sub" id="game_rain">Rain 0%</div></div>
                        <div class="game_stat"><div class="game_stat_label">Mode</div><div class="game_stat_value" id="game_mode">BALANCED</div><div class="game_stat_sub" id="game_driver_confidence">Confidence 78%</div></div>
                        <div class="game_stat"><div class="game_stat_label">Engineer score</div><div class="game_stat_value" id="game_score">50</div><div class="game_stat_sub" id="game_score_trend">Decision quality</div></div>
                    </div>
                </div>
            </section>

            <section class="game_panel">
                <div class="game_panel_header">
                    <h3 class="game_panel_title">Live telemetry</h3>
                    <span class="game_badge game_live" id="game_telemetry_badge">LIVE DATA</span>
                </div>
                <div class="game_panel_body">
                    <div class="game_telemetry_grid">
                        <div class="game_telemetry_card"><div class="game_telemetry_label">Speed</div><div class="game_telemetry_value" id="game_speed">0 km/h</div><div class="game_bar"><div class="game_bar_fill" id="game_speed_bar"></div></div></div>
                        <div class="game_telemetry_card"><div class="game_telemetry_label">Throttle</div><div class="game_telemetry_value" id="game_throttle">0%</div><div class="game_bar"><div class="game_bar_fill" id="game_throttle_bar"></div></div></div>
                        <div class="game_telemetry_card"><div class="game_telemetry_label">Brake</div><div class="game_telemetry_value" id="game_brake">0%</div><div class="game_bar"><div class="game_bar_fill" id="game_brake_bar"></div></div></div>
                        <div class="game_telemetry_card"><div class="game_telemetry_label">Gear / RPM</div><div class="game_telemetry_value" id="game_gear">N · 0</div><div class="game_bar"><div class="game_bar_fill" id="game_rpm_bar"></div></div></div>
                        <div class="game_telemetry_card"><div class="game_telemetry_label">Fuel</div><div class="game_telemetry_value" id="game_fuel">0.0 kg</div><div class="game_bar"><div class="game_bar_fill" id="game_fuel_bar"></div></div></div>
                        <div class="game_telemetry_card"><div class="game_telemetry_label">Battery</div><div class="game_telemetry_value" id="game_battery">100%</div><div class="game_bar"><div class="game_bar_fill" id="game_battery_bar"></div></div></div>
                        <div class="game_telemetry_card"><div class="game_telemetry_label">Tyre wear</div><div class="game_telemetry_value" id="game_tyre_wear">0%</div><div class="game_bar"><div class="game_bar_fill" id="game_tyre_wear_bar"></div></div></div>
                        <div class="game_telemetry_card"><div class="game_telemetry_label">Engine temp</div><div class="game_telemetry_value" id="game_engine_temp">96°C</div><div class="game_bar"><div class="game_bar_fill" id="game_engine_temp_bar"></div></div></div>
                    </div>
                </div>
            </section>

            <section class="game_panel">
                <div class="game_panel_header"><h3 class="game_panel_title">Car condition & damage</h3><span class="game_badge" id="game_car_status">CAR OK</span></div>
                <div class="game_panel_body game_car_area">
                    <div class="game_car_wrap">
                        <svg id="game_car_svg" viewBox="0 0 220 330" role="img" aria-label="Macchina vista dall'alto">
                            <rect class="game_car_tyre" x="35" y="76" width="27" height="62" rx="7" />
                            <rect class="game_car_tyre" x="158" y="76" width="27" height="62" rx="7" />
                            <rect class="game_car_tyre" x="29" y="222" width="31" height="68" rx="7" />
                            <rect class="game_car_tyre" x="160" y="222" width="31" height="68" rx="7" />
                            <path class="game_car_body" d="M96 40 L124 40 L137 87 L147 119 L142 217 L134 284 L86 284 L78 217 L73 119 L83 87 Z" />
                            <path class="game_car_accent" d="M101 55 L119 55 L128 101 L124 224 L96 224 L92 101 Z" />
                            <rect id="game_car_front_wing" class="game_car_damage_zone" x="48" y="20" width="124" height="24" rx="5" />
                            <rect id="game_car_rear_wing" class="game_car_damage_zone" x="53" y="286" width="114" height="24" rx="5" />
                            <path id="game_car_floor" class="game_car_damage_zone" d="M76 120 L144 120 L151 224 L69 224 Z" />
                            <circle id="game_car_suspension_fl" class="game_car_damage_zone" cx="67" cy="108" r="9" />
                            <circle id="game_car_suspension_fr" class="game_car_damage_zone" cx="153" cy="108" r="9" />
                            <circle id="game_car_suspension_rl" class="game_car_damage_zone" cx="67" cy="253" r="9" />
                            <circle id="game_car_suspension_rr" class="game_car_damage_zone" cx="153" cy="253" r="9" />
                            <circle id="game_car_engine" class="game_car_damage_zone" cx="110" cy="245" r="22" />
                            <text x="110" y="174" text-anchor="middle" fill="#fff" font-size="30" font-weight="900">27</text>
                        </svg>
                    </div>
                    <div>
                        <div class="game_damage_list" id="game_damage_list"></div>
                        <canvas class="game_chart" id="game_chart" width="680" height="155"></canvas>
                    </div>
                </div>
            </section>
        </div>

        <div class="game_stack">
            <section class="game_panel">
                <div class="game_panel_header"><h3 class="game_panel_title">Engineer controls</h3><span class="game_badge" id="game_pit_status">PIT CLOSED</span></div>
                <div class="game_panel_body">
                    <div class="game_engineer_controls">
                        <button class="game_button" data-game-command="push" type="button">PUSH</button>
                        <button class="game_button" data-game-command="manage" type="button">MANAGE TYRES</button>
                        <button class="game_button" data-game-command="lift" type="button">LIFT & COAST</button>
                        <button class="game_button" data-game-command="recharge" type="button">RECHARGE</button>
                        <button class="game_button" data-game-command="deploy" type="button">DEPLOY</button>
                        <button class="game_button game_warning" data-game-command="box" type="button">BOX THIS LAP</button>
                    </div>
                    <div style="height:10px"></div>
                    <div class="game_control_card">
                        <div class="game_control_title">Next pit tyre</div>
                        <select class="game_select" id="game_pit_tyre">
                            <option value="SOFT">Soft</option>
                            <option value="MEDIUM" selected>Medium</option>
                            <option value="HARD">Hard</option>
                            <option value="INTER">Intermediate</option>
                            <option value="WET">Wet</option>
                        </select>
                    </div>
                    <div style="height:8px"></div>
                    <div class="game_control_card">
                        <div class="game_control_title">Front wing at pit stop</div>
                        <select class="game_select" id="game_pit_wing">
                            <option value="0">No change</option>
                            <option value="1">+1 click</option>
                            <option value="-1">-1 click</option>
                            <option value="2">+2 clicks</option>
                            <option value="-2">-2 clicks</option>
                        </select>
                    </div>
                </div>
            </section>

            <section class="game_panel">
                <div class="game_panel_header"><h3 class="game_panel_title">Garage setup</h3><span class="game_badge" id="game_setup_lock">UNLOCKED</span></div>
                <div class="game_panel_body">
                    <div class="game_setup_grid">
                        <label class="game_setup_row"><span>Front wing</span><input id="game_setup_wing" type="range" min="1" max="12" step="1"><span class="game_setup_value" id="game_setup_wing_value">7</span></label>
                        <label class="game_setup_row"><span>Brake bias</span><input id="game_setup_brake" type="range" min="50" max="58" step="0.1"><span class="game_setup_value" id="game_setup_brake_value">54.0</span></label>
                        <label class="game_setup_row"><span>Diff entry</span><input id="game_setup_diff" type="range" min="45" max="75" step="1"><span class="game_setup_value" id="game_setup_diff_value">58</span></label>
                        <label class="game_setup_row"><span>Tyre pressure</span><input id="game_setup_pressure" type="range" min="20" max="25" step="0.1"><span class="game_setup_value" id="game_setup_pressure_value">22.5</span></label>
                        <label class="game_setup_row"><span>Ride height</span><input id="game_setup_height" type="range" min="20" max="40" step="1"><span class="game_setup_value" id="game_setup_height_value">29</span></label>
                    </div>
                    <button class="game_button game_green" id="game_apply_setup" type="button" style="width:100%; margin-top:12px;">Apply setup</button>
                </div>
            </section>

            <section class="game_panel">
                <div class="game_panel_header"><h3 class="game_panel_title">Engineer alerts</h3><span class="game_badge" id="game_alert_count">0</span></div>
                <div class="game_panel_body"><div class="game_alerts" id="game_alerts"></div></div>
            </section>
        </div>

        <div class="game_stack">
            <section class="game_panel game_chat">
                <div class="game_panel_header"><h3 class="game_panel_title">Driver radio</h3><span class="game_badge game_live">#27 <?= htmlspecialchars($game_driver['game_name'], ENT_QUOTES, 'UTF-8') ?></span></div>
                <div class="game_chat_log" id="game_chat_log"></div>
                <div class="game_chat_controls">
                    <div class="game_quick_commands">
                        <button class="game_quick_button" data-game-chat="push" type="button">Push now</button>
                        <button class="game_quick_button" data-game-chat="manage" type="button">Manage tyres</button>
                        <button class="game_quick_button" data-game-chat="lift" type="button">Lift & coast</button>
                        <button class="game_quick_button" data-game-chat="box" type="button">Box this lap</button>
                        <button class="game_quick_button" data-game-chat="stay" type="button">Stay out</button>
                        <button class="game_quick_button" data-game-chat="deploy" type="button">Use overtake</button>
                        <button class="game_quick_button" data-game-chat="recharge" type="button">Recharge</button>
                        <button class="game_quick_button" data-game-chat="feedback" type="button">Car feedback?</button>
                    </div>
                    <form class="game_chat_form" id="game_chat_form">
                        <input class="game_input" id="game_chat_input" name="game_message" maxlength="180" autocomplete="off" placeholder="Scrivi al pilota: 'box', 'push', 'come senti il front?'...">
                        <button class="game_button game_primary" type="submit">Radio</button>
                    </form>
                </div>
            </section>

            <section class="game_panel">
                <div class="game_panel_header"><h3 class="game_panel_title">Tyres & temperatures</h3><span class="game_badge" id="game_balance_badge">BALANCED</span></div>
                <div class="game_panel_body">
                    <div class="game_telemetry_grid" style="grid-template-columns:repeat(2,minmax(0,1fr));">
                        <div class="game_telemetry_card"><div class="game_telemetry_label">FL / FR</div><div class="game_telemetry_value" id="game_tyre_front">-- / --</div><div class="game_stat_sub" id="game_brake_front">Brake -- / --</div></div>
                        <div class="game_telemetry_card"><div class="game_telemetry_label">RL / RR</div><div class="game_telemetry_value" id="game_tyre_rear">-- / --</div><div class="game_stat_sub" id="game_brake_rear">Brake -- / --</div></div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <div class="game_footer_bar">
        <div class="game_footer_status" id="game_footer_status">Weekend pronto. Entra nel briefing FP1 e avvia la sessione quando vuoi.</div>
        <div class="game_speed_controls" id="game_speed_controls">
            <button class="game_speed_button" data-game-speed="1" type="button">1×</button>
            <button class="game_speed_button game_active" data-game-speed="4" type="button">4×</button>
            <button class="game_speed_button" data-game-speed="8" type="button">8×</button>
            <button class="game_speed_button" data-game-speed="16" type="button">16×</button>
        </div>
        <button class="game_button game_primary" id="game_session_action" type="button">Start FP1</button>
    </div>
</div>

<div class="game_overlay" id="game_overlay" hidden>
    <div class="game_modal">
        <h2 class="game_modal_title" id="game_modal_title">Session complete</h2>
        <p class="game_modal_text" id="game_modal_text"></p>
        <div class="game_result_grid" id="game_modal_results"></div>
        <button class="game_button game_primary" id="game_modal_action" type="button">Continue</button>
    </div>
</div>

<script id="game_bootstrap_data" type="application/json"><?= json_encode($game_bootstrap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script src="/game_assets/game_sim.js?v=1" defer></script>
</body>
</html>
