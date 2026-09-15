'use strict';

const game_bootstrap = JSON.parse(document.getElementById('game_bootstrap_data').textContent);
const game_storage_key = 'game_pitmetric_engineer_sim_v1';
const game_tick_ms = 250;
const game_compounds = {
    game_SOFT: { game_code: 'S', game_grip: 1.05, game_wear: 1.38, game_temp: 1.16 },
    game_MEDIUM: { game_code: 'M', game_grip: 1.00, game_wear: 1.00, game_temp: 1.00 },
    game_HARD: { game_code: 'H', game_grip: .965, game_wear: .73, game_temp: .86 },
    game_INTER: { game_code: 'I', game_grip: .93, game_wear: 1.08, game_temp: .94 },
    game_WET: { game_code: 'W', game_grip: .88, game_wear: .82, game_temp: .84 },
};

let game_interval = null;
let game_state = game_createInitialState();

function game_createInitialState() {
    return {
        game_version: 1,
        game_session_index: 0,
        game_phase: 'briefing',
        game_lap: 0,
        game_lap_progress: 0,
        game_elapsed: 0,
        game_position: 12,
        game_gap_ahead: 1.2,
        game_gap_back: 1.4,
        game_last_lap: null,
        game_best_lap: null,
        game_lap_times: [],
        game_sector: 1,
        game_speed: 0,
        game_throttle: 0,
        game_brake: 0,
        game_gear: 0,
        game_rpm: 0,
        game_fuel: 36,
        game_fuel_capacity: 36,
        game_battery: 82,
        game_tyre_compound: 'MEDIUM',
        game_tyre_wear: 0,
        game_tyre_age: 0,
        game_tyre_temp: [88, 88, 86, 86],
        game_brake_temp: [520, 515, 500, 500],
        game_engine_temp: 98,
        game_weather: 'DRY',
        game_rain_probability: 12,
        game_track_wetness: 0,
        game_track_grip: 72,
        game_mode: 'balanced',
        game_pending_pit: false,
        game_pit_tyre: 'MEDIUM',
        game_pit_wing: 0,
        game_driver_confidence: 78,
        game_score: 50,
        game_score_delta: 0,
        game_damage: {
            game_front_wing: 0,
            game_floor: 0,
            game_rear_wing: 0,
            game_suspension: 0,
            game_engine: 0,
        },
        game_setup: {
            game_front_wing: 7,
            game_brake_bias: 54.0,
            game_diff_entry: 58,
            game_tyre_pressure: 22.5,
            game_ride_height: 29,
        },
        game_results: [],
        game_sprint_grid: 12,
        game_race_grid: 12,
        game_speed_multiplier: 4,
        game_chat: [],
        game_alerts: [],
        game_history: [],
        game_pending_issue: null,
        game_issue_cooldown: 0,
        game_radio_cooldown: 18,
        game_event_cooldown: 12,
        game_pit_loss_pending: 0,
        game_last_command: null,
    };
}

function game_session() {
    return game_bootstrap.game_sessions[game_state.game_session_index];
}

function game_clamp(game_value, game_min, game_max) {
    return Math.min(game_max, Math.max(game_min, game_value));
}

function game_random(game_min, game_max) {
    return game_min + Math.random() * (game_max - game_min);
}

function game_round(game_value, game_digits = 0) {
    const game_factor = 10 ** game_digits;
    return Math.round(game_value * game_factor) / game_factor;
}

function game_formatLap(game_seconds) {
    if (!Number.isFinite(game_seconds)) return '--:--.---';
    const game_minutes = Math.floor(game_seconds / 60);
    const game_rest = game_seconds - game_minutes * 60;
    return `${game_minutes}:${game_rest.toFixed(3).padStart(6, '0')}`;
}

function game_compoundData() {
    return game_compounds[`game_${game_state.game_tyre_compound}`] || game_compounds.game_MEDIUM;
}

function game_sessionFuel(game_session_data) {
    const game_per_lap = 1.52;
    const game_margin = game_session_data.game_type === 'race' ? 3.0 : 5.0;
    return game_round(game_session_data.game_laps * game_per_lap + game_margin, 1);
}

function game_defaultTyre(game_session_data) {
    if (game_session_data.game_type === 'qualifying') return 'SOFT';
    if (game_session_data.game_key === 'sprint') return 'MEDIUM';
    if (game_session_data.game_key === 'race') return 'MEDIUM';
    return 'MEDIUM';
}

function game_prepareSession() {
    const game_current = game_session();
    game_state.game_phase = 'briefing';
    game_state.game_lap = 0;
    game_state.game_lap_progress = 0;
    game_state.game_elapsed = 0;
    game_state.game_last_lap = null;
    game_state.game_best_lap = null;
    game_state.game_lap_times = [];
    game_state.game_sector = 1;
    game_state.game_speed = 0;
    game_state.game_throttle = 0;
    game_state.game_brake = 0;
    game_state.game_gear = 0;
    game_state.game_rpm = 0;
    game_state.game_fuel_capacity = game_sessionFuel(game_current);
    game_state.game_fuel = game_state.game_fuel_capacity;
    game_state.game_battery = game_current.game_type === 'qualifying' ? 100 : 82;
    game_state.game_tyre_compound = game_defaultTyre(game_current);
    game_state.game_pit_tyre = game_current.game_type === 'qualifying' ? 'SOFT' : 'MEDIUM';
    game_state.game_tyre_wear = 0;
    game_state.game_tyre_age = 0;
    game_state.game_tyre_temp = [84, 84, 82, 82];
    game_state.game_brake_temp = [430, 425, 410, 410];
    game_state.game_engine_temp = 94;
    game_state.game_mode = game_current.game_type === 'qualifying' ? 'push' : 'balanced';
    game_state.game_pending_pit = false;
    game_state.game_pending_issue = null;
    game_state.game_radio_cooldown = 15;
    game_state.game_event_cooldown = 10;
    game_state.game_issue_cooldown = 0;
    game_state.game_pit_loss_pending = 0;
    game_state.game_weather = Math.random() < .13 ? 'CLOUDY' : 'DRY';
    game_state.game_rain_probability = Math.round(game_random(8, game_state.game_weather === 'CLOUDY' ? 46 : 24));
    game_state.game_track_wetness = 0;
    game_state.game_track_grip = game_current.game_key === 'fp1' ? 72 : game_clamp(76 + game_state.game_session_index * 4, 0, 96);
    if (game_current.game_key === 'sprint') game_state.game_position = game_state.game_sprint_grid;
    else if (game_current.game_key === 'race') game_state.game_position = game_state.game_race_grid;
    else game_state.game_position = Math.max(1, Math.min(20, game_state.game_position || 12));
    game_state.game_gap_ahead = game_random(.6, 2.2);
    game_state.game_gap_back = game_random(.6, 2.4);
    game_clearAlerts();
    game_addAlert('game_info', 'Session briefing', game_current.game_objective);
    game_addMessage('game_system', `Briefing ${game_current.game_name}: ${game_current.game_objective}`);
    game_addMessage('game_driver', game_driverOpeningLine());
    game_render();
    game_save(false);
}

function game_driverOpeningLine() {
    const game_current = game_session();
    if (game_current.game_key === 'fp1') return 'Radio check. Car feels okay from the installation lap. Dimmi cosa vuoi che provi e ti do il feedback.';
    if (game_current.game_key === 'sprint_qualifying') return 'Okay, focus. Ho bisogno di sapere quando spingere e se dobbiamo preparare la batteria prima del giro.';
    if (game_current.game_key === 'sprint') return `Starting P${game_state.game_position}. Dammi gap e istruzioni chiare, qui sarà tutto molto veloce.`;
    if (game_current.game_key === 'qualifying') return 'Tyres are cold now. Call the push lap when temperatures and traffic are good.';
    return `Starting P${game_state.game_position}. Long race. Keep me updated on tyres, gaps, weather and strategy.`;
}

function game_startSession() {
    if (game_state.game_phase === 'running') return;
    if (game_state.game_phase === 'finished') {
        game_showSessionResult();
        return;
    }
    game_state.game_phase = 'running';
    game_state.game_lap = 1;
    game_state.game_driver_confidence = game_clamp(game_state.game_driver_confidence + 2, 0, 100);
    game_addMessage('game_system', `${game_session().game_name} started. Telemetry live.`);
    game_addMessage('game_driver', 'We are live. Radio is clear.');
    game_addAlert('game_info', 'Telemetry live', 'Non riceverai una strategia automatica: interpreta dati e feedback e dai tu gli ordini.');
    game_render();
}

function game_tick() {
    if (game_state.game_phase !== 'running') return;
    const game_current = game_session();
    const game_sim_seconds = (game_tick_ms / 1000) * game_state.game_speed_multiplier;
    game_state.game_elapsed += game_sim_seconds;
    game_state.game_radio_cooldown -= game_sim_seconds;
    game_state.game_event_cooldown -= game_sim_seconds;
    game_state.game_issue_cooldown = Math.max(0, game_state.game_issue_cooldown - game_sim_seconds);

    const game_lap_base = game_bootstrap.game_track.game_lap_time_base;
    const game_pace_modifier = game_paceModifier();
    const game_target_lap = game_lap_base + game_pace_modifier;
    game_state.game_lap_progress += game_sim_seconds / Math.max(70, game_target_lap);
    game_state.game_sector = game_state.game_lap_progress < .34 ? 1 : (game_state.game_lap_progress < .68 ? 2 : 3);

    game_updateTelemetry();
    game_updateTemperatures(game_sim_seconds);
    game_updateWeather(game_sim_seconds);
    game_updateWarnings();
    game_updateRadio(game_sim_seconds);
    game_sampleHistory();

    if (game_state.game_lap_progress >= 1) {
        game_completeLap(game_target_lap);
    }
    game_renderLive();
}

function game_paceModifier() {
    const game_mode_map = {
        game_push: -1.15,
        game_balanced: 0,
        game_manage: .72,
        game_lift: 1.25,
        game_recharge: 1.55,
        game_deploy: -.58,
    };
    let game_delta = game_mode_map[`game_${game_state.game_mode}`] ?? 0;
    const game_compound = game_compoundData();
    game_delta += (1 - game_compound.game_grip) * 7.5;
    game_delta += game_state.game_tyre_wear * .035;
    game_delta += game_state.game_damage.game_front_wing * .022;
    game_delta += game_state.game_damage.game_floor * .026;
    game_delta += game_state.game_damage.game_rear_wing * .016;
    game_delta += game_state.game_damage.game_suspension * .04;
    game_delta += game_state.game_damage.game_engine * .032;
    game_delta += Math.max(0, 45 - game_state.game_battery) * .008;
    game_delta += game_setupPenalty();
    if (game_state.game_track_wetness > 8 && !['INTER', 'WET'].includes(game_state.game_tyre_compound)) {
        game_delta += game_state.game_track_wetness * .075;
    }
    if (game_state.game_track_wetness < 12 && ['INTER', 'WET'].includes(game_state.game_tyre_compound)) {
        game_delta += game_state.game_tyre_compound === 'WET' ? 4.6 : 2.3;
    }
    game_delta += game_random(-.18, .18);
    return game_delta;
}

function game_setupPenalty() {
    const game_setup = game_state.game_setup;
    let game_penalty = 0;
    game_penalty += Math.abs(game_setup.game_front_wing - 7.6) * .08;
    game_penalty += Math.abs(game_setup.game_brake_bias - 53.7) * .05;
    game_penalty += Math.abs(game_setup.game_diff_entry - 57) * .012;
    game_penalty += Math.abs(game_setup.game_tyre_pressure - 22.2) * .16;
    game_penalty += Math.abs(game_setup.game_ride_height - 28) * .025;
    if (game_state.game_track_wetness > 15) game_penalty += Math.max(0, 29 - game_setup.game_ride_height) * .05;
    return game_penalty;
}

function game_updateTelemetry() {
    const game_progress = game_state.game_lap_progress;
    const game_curve = Math.abs(Math.sin(game_progress * Math.PI * 8.2));
    const game_braking_zone = Math.pow(game_curve, 7);
    const game_attack = ['push', 'deploy'].includes(game_state.game_mode) ? 1.04 : (game_state.game_mode === 'manage' ? .96 : 1);
    let game_speed = 315 * game_attack - game_braking_zone * 205 + Math.sin(game_progress * 27) * 18;
    game_speed -= game_state.game_damage.game_front_wing * .18 + game_state.game_damage.game_floor * .13 + game_state.game_damage.game_engine * .24;
    game_speed *= 1 - game_state.game_track_wetness * .0025;
    game_state.game_speed = game_clamp(game_speed, 58, 335);
    game_state.game_brake = game_clamp(game_braking_zone * 96, 0, 100);
    game_state.game_throttle = game_clamp(100 - game_state.game_brake * 1.02 - Math.max(0, Math.sin(game_progress * 19)) * 10, 0, 100);
    game_state.game_gear = game_clamp(Math.round((game_state.game_speed - 40) / 42), 1, 8);
    game_state.game_rpm = game_clamp(7600 + game_state.game_speed * 18 + Math.sin(game_progress * 36) * 500, 7500, 12000);
}

function game_updateTemperatures(game_sim_seconds) {
    const game_compound = game_compoundData();
    const game_mode_heat = game_state.game_mode === 'push' || game_state.game_mode === 'deploy' ? 1.25 : (game_state.game_mode === 'manage' ? .55 : .85);
    const game_pressure_heat = 1 + (game_state.game_setup.game_tyre_pressure - 22.2) * .10;
    const game_target_front = 90 + game_mode_heat * 7 * game_compound.game_temp * game_pressure_heat + game_state.game_track_wetness * -.16;
    const game_target_rear = 88 + game_mode_heat * 8 * game_compound.game_temp * game_pressure_heat + game_state.game_track_wetness * -.14;
    game_state.game_tyre_temp = game_state.game_tyre_temp.map((game_temp, game_index) => {
        const game_target = game_index < 2 ? game_target_front : game_target_rear;
        return game_temp + (game_target - game_temp) * Math.min(.16, game_sim_seconds * .02) + game_random(-.12, .12);
    });
    const game_brake_target = 460 + game_state.game_brake * 5.6 + (game_state.game_setup.game_brake_bias - 54) * 18;
    game_state.game_brake_temp = game_state.game_brake_temp.map((game_temp, game_index) => game_temp + (game_brake_target - game_temp - (game_index > 1 ? 35 : 0)) * Math.min(.2, game_sim_seconds * .027));
    const game_engine_target = 99 + (game_state.game_mode === 'push' || game_state.game_mode === 'deploy' ? 11 : 5) + Math.max(0, 50 - game_state.game_speed) * .03;
    game_state.game_engine_temp += (game_engine_target - game_state.game_engine_temp) * Math.min(.12, game_sim_seconds * .015);
}

function game_updateWeather(game_sim_seconds) {
    if (Math.random() < .00075 * game_sim_seconds) {
        game_state.game_rain_probability = game_clamp(game_state.game_rain_probability + game_random(6, 18), 0, 100);
    }
    if (game_state.game_rain_probability > 58 && Math.random() < .0012 * game_sim_seconds) {
        game_state.game_weather = 'RAIN';
    }
    if (game_state.game_weather === 'RAIN') {
        game_state.game_track_wetness = game_clamp(game_state.game_track_wetness + game_sim_seconds * .055, 0, 100);
        game_state.game_rain_probability = game_clamp(game_state.game_rain_probability + game_sim_seconds * .025, 0, 100);
    } else {
        game_state.game_track_wetness = game_clamp(game_state.game_track_wetness - game_sim_seconds * .02, 0, 100);
    }
    if (game_state.game_track_wetness > 35) game_state.game_weather = 'WET';
    if (game_state.game_weather === 'WET' && game_state.game_rain_probability < 36) game_state.game_weather = 'DRYING';
    if (game_state.game_weather === 'DRYING') game_state.game_track_wetness = game_clamp(game_state.game_track_wetness - game_sim_seconds * .06, 0, 100);
    if (game_state.game_track_wetness < 4 && game_state.game_weather === 'DRYING') game_state.game_weather = 'DRY';
}

function game_completeLap(game_target_lap) {
    const game_current = game_session();
    const game_lap_time = game_target_lap + game_random(-.32, .32) + game_state.game_pit_loss_pending;
    game_state.game_pit_loss_pending = 0;
    game_state.game_last_lap = game_lap_time;
    game_state.game_best_lap = game_state.game_best_lap === null ? game_lap_time : Math.min(game_state.game_best_lap, game_lap_time);
    game_state.game_lap_times.push(game_lap_time);
    if (game_state.game_lap_times.length > 30) game_state.game_lap_times.shift();
    game_state.game_lap_progress -= 1;
    game_state.game_tyre_age += 1;
    game_applyLapConsumption();
    game_applyRaceDynamics(game_lap_time);
    game_maybeIncident();
    game_maybeDriverIssue();

    if (game_state.game_pending_pit && game_current.game_type !== 'qualifying') {
        game_executePitStop();
    }

    if (game_state.game_lap >= game_current.game_laps) {
        game_finishSession();
        return;
    }
    game_state.game_lap += 1;
    game_state.game_track_grip = game_clamp(game_state.game_track_grip + .35, 0, 100);
}
