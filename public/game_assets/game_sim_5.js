'use strict';

function game_render() {
    const game_current = game_session();
    document.getElementById('game_session_name').textContent = game_current.game_name;
    document.getElementById('game_session_objective').textContent = game_current.game_objective;
    document.getElementById('game_position').textContent = game_current.game_type === 'practice' && game_state.game_phase === 'briefing' ? 'P--' : `P${game_state.game_position}`;
    document.getElementById('game_session_status').textContent = game_state.game_phase === 'running' ? 'LIVE' : (game_state.game_phase === 'finished' ? 'Complete' : 'Briefing');
    const game_badge = document.getElementById('game_session_badge');
    game_badge.classList.toggle('game_live', game_state.game_phase === 'running');
    document.getElementById('game_session_action').textContent = game_state.game_phase === 'briefing' ? `Start ${game_current.game_name}` : (game_state.game_phase === 'running' ? 'Session running' : 'Session result');
    document.getElementById('game_session_action').disabled = game_state.game_phase === 'running';
    document.getElementById('game_setup_lock').textContent = game_state.game_phase === 'running' ? 'LOCKED' : 'UNLOCKED';
    document.getElementById('game_apply_setup').disabled = game_state.game_phase === 'running';
    ['game_setup_wing', 'game_setup_brake', 'game_setup_diff', 'game_setup_pressure', 'game_setup_height'].forEach(game_id => document.getElementById(game_id).disabled = game_state.game_phase === 'running');
    document.getElementById('game_pit_status').textContent = game_state.game_pending_pit ? 'BOX CALLED' : 'PIT READY';
    document.getElementById('game_pit_status').classList.toggle('game_alert', game_state.game_pending_pit);
    document.getElementById('game_pit_tyre').value = game_state.game_pit_tyre;
    document.getElementById('game_pit_wing').value = String(game_state.game_pit_wing || 0);
    game_renderSchedule();
    game_renderSetup();
    game_renderAlerts();
    game_renderChat();
    game_renderLive();
}

function game_renderSchedule() {
    const game_root = document.getElementById('game_schedule');
    game_root.innerHTML = game_bootstrap.game_sessions.map((game_item, game_index) => {
        const game_done = game_state.game_results.some(game_result => game_result.game_key === game_item.game_key);
        const game_class = game_done ? 'game_done' : (game_index === game_state.game_session_index ? 'game_current' : '');
        const game_result = game_state.game_results.find(game_result_item => game_result_item.game_key === game_item.game_key);
        return `<div class="game_schedule_item ${game_class}">
            <div class="game_schedule_day">${game_item.game_day} · ${game_item.game_time}</div>
            <div class="game_schedule_name">${game_item.game_name}</div>
            <div class="game_schedule_meta">${game_done ? `P${game_result.game_position} · score ${Math.round(game_result.game_score)}` : `${game_item.game_laps} laps`}</div>
        </div>`;
    }).join('');
}

function game_renderLive() {
    const game_current = game_session();
    document.getElementById('game_lap').textContent = `${game_state.game_lap || 0} / ${game_current.game_laps}`;
    document.getElementById('game_sector').textContent = game_state.game_phase === 'running' ? `Sector ${game_state.game_sector}` : `${game_current.game_day} ${game_current.game_time}`;
    document.getElementById('game_last_lap').textContent = game_formatLap(game_state.game_last_lap);
    document.getElementById('game_best_lap').textContent = `Best ${game_formatLap(game_state.game_best_lap)}`;
    document.getElementById('game_gap_ahead').textContent = game_state.game_phase === 'running' ? `${game_state.game_gap_ahead.toFixed(1)}s` : '--';
    document.getElementById('game_gap_back').textContent = game_state.game_phase === 'running' ? `Behind ${game_state.game_gap_back.toFixed(1)}s` : 'Behind --';
    const game_compound = game_compoundData();
    document.getElementById('game_tyre_compound').textContent = game_compound.game_code;
    document.getElementById('game_tyre_age').textContent = `${game_state.game_tyre_age} laps · ${Math.round(game_state.game_tyre_wear)}% wear`;
    document.getElementById('game_weather').textContent = game_state.game_weather;
    document.getElementById('game_rain').textContent = `Rain ${Math.round(game_state.game_rain_probability)}% · wet ${Math.round(game_state.game_track_wetness)}%`;
    document.getElementById('game_mode').textContent = game_state.game_mode.toUpperCase();
    document.getElementById('game_driver_confidence').textContent = `Confidence ${Math.round(game_state.game_driver_confidence)}%`;
    document.getElementById('game_score').textContent = Math.round(game_state.game_score);
    document.getElementById('game_score_trend').textContent = game_state.game_score_delta ? `${game_state.game_score_delta > 0 ? '+' : ''}${game_state.game_score_delta} last decision` : 'Decision quality';

    game_setTelemetry('game_speed', `${Math.round(game_state.game_speed)} km/h`, game_state.game_speed / 335 * 100, 'game_speed_bar');
    game_setTelemetry('game_throttle', `${Math.round(game_state.game_throttle)}%`, game_state.game_throttle, 'game_throttle_bar');
    game_setTelemetry('game_brake', `${Math.round(game_state.game_brake)}%`, game_state.game_brake, 'game_brake_bar');
    document.getElementById('game_gear').textContent = `${game_state.game_gear || 'N'} · ${Math.round(game_state.game_rpm)}`;
    game_setBar('game_rpm_bar', (game_state.game_rpm - 7000) / 5000 * 100, game_state.game_rpm > 11500 ? 'game_warn' : 'game_ok');
    game_setTelemetry('game_fuel', `${game_state.game_fuel.toFixed(1)} kg`, game_state.game_fuel_capacity ? game_state.game_fuel / game_state.game_fuel_capacity * 100 : 0, 'game_fuel_bar', game_state.game_fuel < 3 ? 'game_bad' : 'game_ok');
    game_setTelemetry('game_battery', `${Math.round(game_state.game_battery)}%`, game_state.game_battery, 'game_battery_bar', game_state.game_battery < 20 ? 'game_bad' : 'game_ok');
    game_setTelemetry('game_tyre_wear', `${Math.round(game_state.game_tyre_wear)}%`, game_state.game_tyre_wear, 'game_tyre_wear_bar', game_state.game_tyre_wear > 70 ? 'game_bad' : (game_state.game_tyre_wear > 50 ? 'game_warn' : 'game_ok'));
    game_setTelemetry('game_engine_temp', `${Math.round(game_state.game_engine_temp)}°C`, game_clamp((game_state.game_engine_temp - 80) / 40 * 100, 0, 100), 'game_engine_temp_bar', game_state.game_engine_temp > 114 ? 'game_bad' : (game_state.game_engine_temp > 109 ? 'game_warn' : 'game_ok'));

    document.getElementById('game_tyre_front').textContent = `${Math.round(game_state.game_tyre_temp[0])}° / ${Math.round(game_state.game_tyre_temp[1])}°`;
    document.getElementById('game_tyre_rear').textContent = `${Math.round(game_state.game_tyre_temp[2])}° / ${Math.round(game_state.game_tyre_temp[3])}°`;
    document.getElementById('game_brake_front').textContent = `Brake ${Math.round(game_state.game_brake_temp[0])}° / ${Math.round(game_state.game_brake_temp[1])}°`;
    document.getElementById('game_brake_rear').textContent = `Brake ${Math.round(game_state.game_brake_temp[2])}° / ${Math.round(game_state.game_brake_temp[3])}°`;
    const game_balance = game_balanceState();
    document.getElementById('game_balance_badge').textContent = game_balance.game_key.toUpperCase();

    game_renderDamage();
    game_renderAlerts();
    game_drawChart();
    document.getElementById('game_footer_status').textContent = game_footerText();
}

function game_setTelemetry(game_value_id, game_text, game_percent, game_bar_id, game_state_key = 'game_ok') {
    document.getElementById(game_value_id).textContent = game_text;
    game_setBar(game_bar_id, game_percent, game_state_key);
}

function game_setBar(game_id, game_percent, game_state_key = 'game_ok') {
    const game_bar = document.getElementById(game_id);
    game_bar.style.width = `${game_clamp(game_percent, 0, 100)}%`;
    if (game_state_key === 'game_bad') game_bar.style.background = 'var(--game_danger)';
    else if (game_state_key === 'game_warn') game_bar.style.background = 'var(--game_yellow)';
    else game_bar.style.background = 'var(--game_green)';
}

function game_renderDamage() {
    const game_rows = [
        ['Front wing', game_state.game_damage.game_front_wing, 'game_car_front_wing'],
        ['Floor', game_state.game_damage.game_floor, 'game_car_floor'],
        ['Rear wing', game_state.game_damage.game_rear_wing, 'game_car_rear_wing'],
        ['Suspension', game_state.game_damage.game_suspension, null],
        ['Power unit', game_state.game_damage.game_engine, 'game_car_engine'],
    ];
    document.getElementById('game_damage_list').innerHTML = game_rows.map(game_row => `
        <div class="game_damage_row">
            <span class="game_damage_name">${game_row[0]}</span>
            <div class="game_bar"><div class="game_bar_fill" style="width:${game_clamp(game_row[1], 0, 100)}%; background:${game_damageColor(game_row[1])}"></div></div>
            <span class="game_damage_value">${Math.round(game_row[1])}%</span>
        </div>`).join('');
    game_rows.forEach(game_row => {
        if (!game_row[2]) return;
        const game_element = document.getElementById(game_row[2]);
        const game_color = game_damageColor(game_row[1]);
        game_element.style.stroke = game_color;
        game_element.style.fill = game_row[1] > 1 ? `${game_color}33` : 'rgba(55,214,122,.14)';
    });
    const game_suspension_color = game_damageColor(game_state.game_damage.game_suspension);
    ['game_car_suspension_fl', 'game_car_suspension_fr', 'game_car_suspension_rl', 'game_car_suspension_rr'].forEach(game_id => {
        const game_element = document.getElementById(game_id);
        game_element.style.stroke = game_suspension_color;
        game_element.style.fill = game_state.game_damage.game_suspension > 1 ? `${game_suspension_color}33` : 'rgba(55,214,122,.14)';
    });
    const game_damage_max = Math.max(...Object.values(game_state.game_damage));
    const game_status = document.getElementById('game_car_status');
    game_status.textContent = game_damage_max < 10 ? 'CAR OK' : (game_damage_max < 35 ? 'MINOR DAMAGE' : 'DAMAGE');
    game_status.classList.toggle('game_alert', game_damage_max >= 10);
}

function game_damageColor(game_value) {
    if (game_value >= 55) return '#ff5160';
    if (game_value >= 25) return '#ff8f3d';
    if (game_value >= 10) return '#f7c948';
    return '#37d67a';
}
