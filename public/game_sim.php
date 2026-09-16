<?php

declare(strict_types=1);

header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$game_title = 'PitMetric · Race Engineer';
?>
<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive,nosnippet">
    <meta name="referrer" content="same-origin">
    <title><?= htmlspecialchars($game_title, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/game_assets/game_sim.css?v=2">
</head>
<body>
<div class="game_app" id="game_app">
    <header class="game_topbar">
        <div class="game_brand">
            <span class="game_brand_mark">PM</span>
            <div>
                <strong>RACE ENGINEER</strong>
                <small>PitMetric experimental game</small>
            </div>
        </div>
        <div class="game_weekend_identity">
            <span class="game_round" id="game_round_label">ROUND --</span>
            <strong id="game_gp_label">Seleziona un weekend</strong>
            <span class="game_sprint_badge" id="game_sprint_badge" hidden>SPRINT</span>
        </div>
        <div class="game_top_actions">
            <button class="game_icon_button" id="game_calendar_button" type="button">CALENDARIO</button>
            <button class="game_icon_button" id="game_new_weekend_button" type="button">NUOVO WEEKEND</button>
        </div>
    </header>

    <section class="game_session_strip" id="game_session_strip"></section>

    <main class="game_cockpit">
        <section class="game_track_column">
            <div class="game_track_scene">
                <div class="game_scene_header">
                    <div>
                        <span class="game_live_pill" id="game_live_pill">BRIEFING</span>
                        <h1 id="game_session_name">Weekend briefing</h1>
                        <p id="game_session_subtitle">Scegli team, pilota e gara per iniziare.</p>
                    </div>
                    <div class="game_position_box">
                        <span id="game_position">P--</span>
                        <small>POS</small>
                    </div>
                </div>

                <div class="game_track_visual">
                    <svg class="game_track_svg" viewBox="0 0 520 330" aria-hidden="true">
                        <path class="game_track_shadow" d="M78 182 C55 94 126 43 218 68 C300 16 436 67 431 157 C482 214 423 297 333 265 C270 317 139 280 154 222 C111 231 88 213 78 182Z"/>
                        <path class="game_track_line" id="game_track_line" pathLength="1000" d="M78 182 C55 94 126 43 218 68 C300 16 436 67 431 157 C482 214 423 297 333 265 C270 317 139 280 154 222 C111 231 88 213 78 182Z"/>
                        <circle class="game_track_car" id="game_track_car" cx="78" cy="182" r="8"/>
                    </svg>
                    <div class="game_track_hud">
                        <div><small>LAP</small><strong id="game_lap">0 / --</strong></div>
                        <div><small>SECTOR</small><strong id="game_sector">S1</strong></div>
                        <div><small>GAP AHEAD</small><strong id="game_gap_ahead">--</strong></div>
                        <div><small>GAP BEHIND</small><strong id="game_gap_back">--</strong></div>
                    </div>
                </div>

                <div class="game_car_and_data">
                    <div class="game_car_card">
                        <div class="game_car_title"><span>CAR STATUS</span><b id="game_car_status">OK</b></div>
                        <svg class="game_car_svg" viewBox="0 0 220 330" role="img" aria-label="F1 vista dall'alto">
                            <rect class="game_car_tyre" x="35" y="76" width="27" height="62" rx="7"/>
                            <rect class="game_car_tyre" x="158" y="76" width="27" height="62" rx="7"/>
                            <rect class="game_car_tyre" x="29" y="222" width="31" height="68" rx="7"/>
                            <rect class="game_car_tyre" x="160" y="222" width="31" height="68" rx="7"/>
                            <path class="game_car_body" d="M96 40 L124 40 L137 87 L147 119 L142 217 L134 284 L86 284 L78 217 L73 119 L83 87 Z"/>
                            <path class="game_car_accent" d="M101 55 L119 55 L128 101 L124 224 L96 224 L92 101 Z"/>
                            <rect id="game_damage_front" class="game_damage_zone" x="48" y="20" width="124" height="24" rx="5"/>
                            <rect id="game_damage_rear" class="game_damage_zone" x="53" y="286" width="114" height="24" rx="5"/>
                            <path id="game_damage_floor" class="game_damage_zone" d="M76 120 L144 120 L151 224 L69 224 Z"/>
                            <circle id="game_damage_suspension" class="game_damage_zone" cx="153" cy="108" r="12"/>
                            <circle id="game_damage_engine" class="game_damage_zone" cx="110" cy="245" r="22"/>
                            <text id="game_car_number" x="110" y="178" text-anchor="middle">--</text>
                        </svg>
                    </div>

                    <div class="game_live_data">
                        <div class="game_data_tile"><small>SPEED</small><strong id="game_speed">0</strong><span>km/h</span></div>
                        <div class="game_data_tile"><small>TYRE</small><strong id="game_tyre">M</strong><span id="game_tyre_wear">0% wear</span></div>
                        <div class="game_data_tile"><small>FUEL</small><strong id="game_fuel">--</strong><span id="game_fuel_delta">target --</span></div>
                        <div class="game_data_tile"><small>BATTERY</small><strong id="game_battery">--</strong><span id="game_mode">BALANCED</span></div>
                        <div class="game_data_tile"><small>WEATHER</small><strong id="game_weather">DRY</strong><span id="game_rain">rain --</span></div>
                        <div class="game_data_tile"><small>LAST LAP</small><strong id="game_last_lap">--:--.---</strong><span id="game_best_lap">best --</span></div>
                    </div>
                </div>
            </div>

            <div class="game_pitcrew_panel">
                <div class="game_panel_heading">
                    <div><span class="game_kicker">PIT WALL → GARAGE</span><h2>Pit crew orders</h2></div>
                    <span class="game_crew_state" id="game_crew_state">STANDBY</span>
                </div>
                <div class="game_pitcrew_orders">
                    <button data-game-crew="prepare soft" type="button">PREP SOFT</button>
                    <button data-game-crew="prepare medium" type="button">PREP MEDIUM</button>
                    <button data-game-crew="prepare hard" type="button">PREP HARD</button>
                    <button data-game-crew="prepare inter" type="button">PREP INTER</button>
                    <button data-game-crew="prepare wet" type="button">PREP WET</button>
                    <button data-game-crew="wing +1" type="button">WING +1</button>
                    <button data-game-crew="wing -1" type="button">WING -1</button>
                    <button data-game-crew="repair wing" type="button">REPAIR WING</button>
                    <button data-game-crew="fast stop" type="button">FAST STOP</button>
                    <button data-game-crew="safe stop" type="button">SAFE STOP</button>
                    <button data-game-crew="check damage" type="button">CHECK DAMAGE</button>
                    <button data-game-crew="pit crew ready" type="button">CREW READY</button>
                </div>
            </div>
        </section>

        <section class="game_radio_column">
            <div class="game_radio_header">
                <div class="game_driver_identity">
                    <span class="game_driver_number" id="game_driver_number">--</span>
                    <div><strong id="game_driver_name">Driver</strong><small id="game_team_name">Team</small></div>
                </div>
                <div class="game_radio_status"><span class="game_radio_dot"></span> RADIO LIVE</div>
            </div>

            <div class="game_radio_log" id="game_radio_log"></div>

            <div class="game_radio_quick">
                <button data-game-radio="push now" type="button">PUSH</button>
                <button data-game-radio="manage tyres" type="button">MANAGE</button>
                <button data-game-radio="lift and coast" type="button">LIFT & COAST</button>
                <button data-game-radio="recharge battery" type="button">RECHARGE</button>
                <button data-game-radio="deploy battery" type="button">DEPLOY</button>
                <button data-game-radio="box this lap" type="button">BOX</button>
                <button data-game-radio="stay out" type="button">STAY OUT</button>
                <button data-game-radio="car feedback" type="button">FEEDBACK?</button>
            </div>

            <div class="game_channel_tabs">
                <button class="game_channel_active" data-game-channel="driver" type="button">RADIO PILOTA</button>
                <button data-game-channel="crew" type="button">PIT CREW</button>
            </div>
            <form class="game_radio_form" id="game_radio_form">
                <span class="game_channel_prefix" id="game_channel_prefix">DRIVER</span>
                <input id="game_radio_input" maxlength="180" autocomplete="off" placeholder="Es. 'push now', 'come sono le gomme?', 'box this lap'...">
                <button type="submit">SEND</button>
            </form>
        </section>

        <aside class="game_commands_column">
            <div class="game_command_panel">
                <div class="game_panel_heading"><div><span class="game_kicker">RADIO LANGUAGE</span><h2>Comandi capiti</h2></div></div>
                <p class="game_command_hint">Clicca un comando per copiarlo nella radio. Puoi anche scriverlo in modo naturale.</p>
                <div class="game_command_group">
                    <h3>Pilota</h3>
                    <button data-game-command-example="push now">push now</button>
                    <button data-game-command-example="manage tyres">manage tyres</button>
                    <button data-game-command-example="lift and coast">lift and coast</button>
                    <button data-game-command-example="recharge battery">recharge battery</button>
                    <button data-game-command-example="deploy battery">deploy battery</button>
                    <button data-game-command-example="box this lap">box this lap</button>
                    <button data-game-command-example="stay out">stay out</button>
                    <button data-game-command-example="attack car ahead">attack car ahead</button>
                    <button data-game-command-example="defend position">defend position</button>
                    <button data-game-command-example="car feedback">car feedback</button>
                    <button data-game-command-example="how are the tyres?">how are the tyres?</button>
                    <button data-game-command-example="gap ahead?">gap ahead?</button>
                    <button data-game-command-example="fuel status?">fuel status?</button>
                    <button data-game-command-example="weather update?">weather update?</button>
                    <button data-game-command-example="damage report?">damage report?</button>
                </div>
                <div class="game_command_group">
                    <h3>Pit crew</h3>
                    <button data-game-crew-example="prepare soft">prepare soft</button>
                    <button data-game-crew-example="prepare medium">prepare medium</button>
                    <button data-game-crew-example="prepare hard">prepare hard</button>
                    <button data-game-crew-example="prepare inter">prepare inter</button>
                    <button data-game-crew-example="prepare wet">prepare wet</button>
                    <button data-game-crew-example="wing +1">wing +1</button>
                    <button data-game-crew-example="wing -1">wing -1</button>
                    <button data-game-crew-example="repair wing">repair wing</button>
                    <button data-game-crew-example="no repair">no repair</button>
                    <button data-game-crew-example="fast stop">fast stop</button>
                    <button data-game-crew-example="safe stop">safe stop</button>
                    <button data-game-crew-example="check damage">check damage</button>
                    <button data-game-crew-example="pit crew ready">pit crew ready</button>
                </div>
            </div>

            <div class="game_skill_panel">
                <span class="game_kicker">YOUR PACKAGE</span>
                <h2 id="game_package_title">--</h2>
                <div class="game_skill_rows" id="game_skill_rows"></div>
                <p class="game_rating_note">I rating sono bilanciamento di gioco basato sulla stagione 2026, non valutazioni ufficiali F1.</p>
            </div>
        </aside>
    </main>

    <footer class="game_bottom_bar">
        <div class="game_engineer_score">ENGINEER SCORE <strong id="game_score">50</strong><span id="game_confidence">Driver confidence 75%</span></div>
        <div class="game_speed_buttons">
            <button data-game-speed="1" type="button">1×</button>
            <button data-game-speed="8" type="button">8×</button>
            <button class="game_speed_active" data-game-speed="24" type="button">24×</button>
            <button data-game-speed="48" type="button">48×</button>
        </div>
        <button class="game_session_button" id="game_session_button" type="button">START SESSION</button>
    </footer>
</div>

<div class="game_setup_overlay" id="game_setup_overlay">
    <div class="game_setup_screen">
        <div class="game_setup_intro">
            <span class="game_kicker">PITMETRIC GAME MODE</span>
            <h1>Siediti al muretto.</h1>
            <p>Scegli la tua scuderia, il pilota che seguirai e il weekend. Da quel momento sarai tu il suo race engineer.</p>
        </div>
        <div class="game_setup_section">
            <div class="game_setup_title"><span>01</span><h2>Scuderia</h2></div>
            <div class="game_team_grid" id="game_team_grid"></div>
        </div>
        <div class="game_setup_section">
            <div class="game_setup_title"><span>02</span><h2>Pilota</h2></div>
            <div class="game_driver_grid" id="game_driver_grid"></div>
        </div>
        <div class="game_setup_section">
            <div class="game_setup_title"><span>03</span><h2>Grand Prix 2026</h2></div>
            <div class="game_calendar_grid" id="game_calendar_grid"></div>
        </div>
        <div class="game_setup_footer">
            <div id="game_setup_summary">Seleziona scuderia, pilota e gara.</div>
            <button id="game_start_weekend" type="button" disabled>ENTER PIT WALL</button>
        </div>
    </div>
</div>

<div class="game_calendar_overlay" id="game_calendar_overlay" hidden>
    <div class="game_calendar_modal">
        <div class="game_modal_head"><div><span class="game_kicker">OFFICIAL 2026 SEASON</span><h2>Calendario</h2></div><button id="game_calendar_close" type="button">×</button></div>
        <div class="game_calendar_full" id="game_calendar_full"></div>
    </div>
</div>

<div class="game_result_overlay" id="game_result_overlay" hidden>
    <div class="game_result_modal">
        <span class="game_kicker">SESSION COMPLETE</span>
        <h2 id="game_result_title">Session complete</h2>
        <p id="game_result_text"></p>
        <div class="game_result_stats" id="game_result_stats"></div>
        <button id="game_result_continue" type="button">CONTINUE WEEKEND</button>
    </div>
</div>

<script src="/game_assets/game_data_2026.js?v=2"></script>
<script src="/game_assets/game_sim.js?v=2" defer></script>
</body>
</html>
