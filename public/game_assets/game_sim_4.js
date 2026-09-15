'use strict';

function game_finishSession() {
    game_state.game_phase = 'finished';
    game_state.game_speed = 0;
    game_state.game_throttle = 0;
    game_state.game_brake = 0;
    game_state.game_gear = 0;
    game_state.game_rpm = 0;
    const game_current = game_session();
    let game_result_position = game_state.game_position;
    if (game_current.game_type === 'qualifying') {
        const game_setup_quality = game_clamp(1 - game_setupPenalty() / 3, 0, 1);
        const game_pace_quality = game_clamp((91.0 - (game_state.game_best_lap || 92)) / 3, 0, 1);
        game_result_position = game_clamp(Math.round(18 - game_setup_quality * 7 - game_pace_quality * 7 + game_random(-2, 2)), 1, 20);
        game_state.game_position = game_result_position;
        if (game_current.game_key === 'sprint_qualifying') game_state.game_sprint_grid = game_result_position;
        if (game_current.game_key === 'qualifying') game_state.game_race_grid = game_result_position;
    }
    const game_result = {
        game_key: game_current.game_key,
        game_name: game_current.game_name,
        game_position: game_result_position,
        game_best_lap: game_state.game_best_lap,
        game_score: game_state.game_score,
        game_tyre_wear: game_state.game_tyre_wear,
        game_damage_total: Object.values(game_state.game_damage).reduce((game_sum, game_value) => game_sum + game_value, 0),
    };
    const game_existing_index = game_state.game_results.findIndex(game_item => game_item.game_key === game_result.game_key);
    if (game_existing_index >= 0) game_state.game_results[game_existing_index] = game_result;
    else game_state.game_results.push(game_result);
    game_addMessage('game_system', `${game_current.game_name} complete · P${game_result_position}.`);
    game_save(false);
    game_render();
    setTimeout(game_showSessionResult, 250);
}

function game_showSessionResult() {
    const game_current = game_session();
    const game_result = game_state.game_results.find(game_item => game_item.game_key === game_current.game_key) || {
        game_position: game_state.game_position,
        game_best_lap: game_state.game_best_lap,
        game_score: game_state.game_score,
    };
    const game_overlay = document.getElementById('game_overlay');
    document.getElementById('game_modal_title').textContent = `${game_current.game_name} complete`;
    document.getElementById('game_modal_text').textContent = game_state.game_session_index === game_bootstrap.game_sessions.length - 1
        ? `Weekend terminato. Il risultato finale dipende da quanto bene hai gestito strategia, affidabilità, comunicazione e dati durante tutto il weekend.`
        : `Debrief pronto. Prima della prossima sessione puoi modificare il setup in base a ciò che hai visto nei dati e sentito dal pilota.`;
    document.getElementById('game_modal_results').innerHTML = `
        <div class="game_result_card"><div class="game_result_label">Result</div><div class="game_result_value">P${game_result.game_position}</div></div>
        <div class="game_result_card"><div class="game_result_label">Best lap</div><div class="game_result_value">${game_formatLap(game_result.game_best_lap)}</div></div>
        <div class="game_result_card"><div class="game_result_label">Engineer score</div><div class="game_result_value">${Math.round(game_result.game_score)}/100</div></div>`;
    const game_action = document.getElementById('game_modal_action');
    game_action.textContent = game_state.game_session_index === game_bootstrap.game_sessions.length - 1 ? 'Weekend summary' : `Go to ${game_bootstrap.game_sessions[game_state.game_session_index + 1].game_name}`;
    game_overlay.hidden = false;
}

function game_continueWeekend() {
    const game_overlay = document.getElementById('game_overlay');
    game_overlay.hidden = true;
    if (game_state.game_session_index >= game_bootstrap.game_sessions.length - 1) {
        game_showWeekendSummary();
        return;
    }
    game_state.game_session_index += 1;
    game_prepareSession();
}

function game_showWeekendSummary() {
    const game_overlay = document.getElementById('game_overlay');
    const game_race_result = game_state.game_results.find(game_item => game_item.game_key === 'race');
    const game_average_score = game_state.game_results.length
        ? game_state.game_results.reduce((game_sum, game_item) => game_sum + game_item.game_score, 0) / game_state.game_results.length
        : game_state.game_score;
    document.getElementById('game_modal_title').textContent = 'Weekend complete';
    document.getElementById('game_modal_text').textContent = game_average_score >= 80
        ? 'Ottimo lavoro da ingegnere: decisioni coerenti, buona lettura dei dati e comunicazione solida con il pilota.'
        : (game_average_score >= 60 ? 'Weekend solido, ma ci sono state decisioni migliorabili. Riprova cercando di anticipare i problemi invece di reagire tardi.' : 'Weekend difficile. La simulazione ti ha punito per decisioni lente o incoerenti: usa telemetria, feedback e contesto prima di dare ordini.');
    document.getElementById('game_modal_results').innerHTML = `
        <div class="game_result_card"><div class="game_result_label">Race result</div><div class="game_result_value">P${game_race_result ? game_race_result.game_position : '--'}</div></div>
        <div class="game_result_card"><div class="game_result_label">Weekend score</div><div class="game_result_value">${Math.round(game_average_score)}/100</div></div>
        <div class="game_result_card"><div class="game_result_label">Sessions</div><div class="game_result_value">${game_state.game_results.length}/5</div></div>`;
    const game_action = document.getElementById('game_modal_action');
    game_action.textContent = 'Close summary';
    game_action.dataset.gameSummary = '1';
    game_overlay.hidden = false;
}

function game_sampleHistory() {
    if (Math.random() > .35) return;
    game_state.game_history.push({
        game_tyre: game_state.game_tyre_wear,
        game_battery: game_state.game_battery,
        game_fuel: game_state.game_fuel_capacity > 0 ? game_state.game_fuel / game_state.game_fuel_capacity * 100 : 0,
    });
    if (game_state.game_history.length > 120) game_state.game_history.shift();
}

function game_drawChart() {
    const game_canvas = document.getElementById('game_chart');
    if (!game_canvas) return;
    const game_ctx = game_canvas.getContext('2d');
    const game_width = game_canvas.width;
    const game_height = game_canvas.height;
    game_ctx.clearRect(0, 0, game_width, game_height);
    game_ctx.fillStyle = '#090d13';
    game_ctx.fillRect(0, 0, game_width, game_height);
    game_ctx.strokeStyle = '#1c2430';
    game_ctx.lineWidth = 1;
    for (let game_i = 1; game_i < 4; game_i++) {
        const game_y = game_height * game_i / 4;
        game_ctx.beginPath();
        game_ctx.moveTo(0, game_y);
        game_ctx.lineTo(game_width, game_y);
        game_ctx.stroke();
    }
    const game_series = [
        { game_key: 'game_tyre', game_color: '#f7c948' },
        { game_key: 'game_battery', game_color: '#4ea1ff' },
        { game_key: 'game_fuel', game_color: '#37d67a' },
    ];
    game_series.forEach(game_line => {
        if (game_state.game_history.length < 2) return;
        game_ctx.strokeStyle = game_line.game_color;
        game_ctx.lineWidth = 2;
        game_ctx.beginPath();
        game_state.game_history.forEach((game_point, game_index) => {
            const game_x = game_index / Math.max(1, game_state.game_history.length - 1) * game_width;
            const game_y = game_height - game_clamp(game_point[game_line.game_key], 0, 100) / 100 * game_height;
            if (game_index === 0) game_ctx.moveTo(game_x, game_y); else game_ctx.lineTo(game_x, game_y);
        });
        game_ctx.stroke();
    });
    game_ctx.font = '10px system-ui';
    game_ctx.fillStyle = '#8d98a8';
    game_ctx.fillText('TYRE', 10, 15);
    game_ctx.fillStyle = '#f7c948'; game_ctx.fillRect(44, 8, 18, 2);
    game_ctx.fillStyle = '#8d98a8'; game_ctx.fillText('BATTERY', 72, 15);
    game_ctx.fillStyle = '#4ea1ff'; game_ctx.fillRect(120, 8, 18, 2);
    game_ctx.fillStyle = '#8d98a8'; game_ctx.fillText('FUEL', 148, 15);
    game_ctx.fillStyle = '#37d67a'; game_ctx.fillRect(180, 8, 18, 2);
}

function game_addMessage(game_type, game_text) {
    game_state.game_chat.push({ game_type, game_text, game_time: Date.now() });
    if (game_state.game_chat.length > 90) game_state.game_chat.shift();
    game_renderChat();
}

function game_save(game_notify = true) {
    try {
        localStorage.setItem(game_storage_key, JSON.stringify(game_state));
        if (game_notify) game_addMessage('game_system', 'Weekend saved locally in this browser.');
    } catch (game_error) {
        if (game_notify) game_addMessage('game_system', 'Could not save this browser session.');
    }
}

function game_load() {
    try {
        const game_raw = localStorage.getItem(game_storage_key);
        if (!game_raw) return false;
        const game_saved = JSON.parse(game_raw);
        game_state = { ...game_createInitialState(), ...game_saved };
        game_state.game_damage = { ...game_createInitialState().game_damage, ...(game_saved.game_damage || {}) };
        game_state.game_setup = { ...game_createInitialState().game_setup, ...(game_saved.game_setup || {}) };
        return true;
    } catch (game_error) {
        return false;
    }
}

function game_reset() {
    if (!window.confirm('Azzerare completamente il weekend e ricominciare da FP1?')) return;
    localStorage.removeItem(game_storage_key);
    game_state = game_createInitialState();
    game_prepareSession();
}
