'use strict';

function game_renderAlerts() {
    const game_root = document.getElementById('game_alerts');
    document.getElementById('game_alert_count').textContent = String(game_state.game_alerts.length);
    game_root.innerHTML = game_state.game_alerts.length ? game_state.game_alerts.map(game_alert => `
        <div class="game_alert ${game_alert.game_level}">
            <div class="game_alert_title">${game_escape(game_alert.game_title)}</div>
            <div class="game_alert_text">${game_escape(game_alert.game_text)}</div>
        </div>`).join('') : '<div class="game_alert game_info"><div class="game_alert_title">No active alerts</div><div class="game_alert_text">Car and strategy currently inside expected operating window.</div></div>';
}

function game_renderChat() {
    const game_root = document.getElementById('game_chat_log');
    if (!game_root) return;
    game_root.innerHTML = game_state.game_chat.map(game_message => `
        <div class="game_message ${game_message.game_type}">
            <div class="game_message_sender">${game_message.game_type === 'game_driver' ? game_bootstrap.game_driver.game_name : (game_message.game_type === 'game_engineer' ? 'You · Race Engineer' : 'PitMetric')}</div>
            <div>${game_escape(game_message.game_text)}</div>
        </div>`).join('');
    game_root.scrollTop = game_root.scrollHeight;
}

function game_renderSetup() {
    const game_setup = game_state.game_setup;
    const game_map = [
        ['game_setup_wing', 'game_setup_wing_value', game_setup.game_front_wing, 0],
        ['game_setup_brake', 'game_setup_brake_value', game_setup.game_brake_bias, 1],
        ['game_setup_diff', 'game_setup_diff_value', game_setup.game_diff_entry, 0],
        ['game_setup_pressure', 'game_setup_pressure_value', game_setup.game_tyre_pressure, 1],
        ['game_setup_height', 'game_setup_height_value', game_setup.game_ride_height, 0],
    ];
    game_map.forEach(game_item => {
        document.getElementById(game_item[0]).value = game_item[2];
        document.getElementById(game_item[1]).textContent = Number(game_item[2]).toFixed(game_item[3]);
    });
}

function game_applySetup() {
    if (game_state.game_phase === 'running') return;
    game_state.game_setup = {
        game_front_wing: Number(document.getElementById('game_setup_wing').value),
        game_brake_bias: Number(document.getElementById('game_setup_brake').value),
        game_diff_entry: Number(document.getElementById('game_setup_diff').value),
        game_tyre_pressure: Number(document.getElementById('game_setup_pressure').value),
        game_ride_height: Number(document.getElementById('game_setup_height').value),
    };
    const game_balance = game_balanceState();
    game_addMessage('game_system', `Setup applied. Predicted balance: ${game_balance.game_key}.`);
    game_addAlert('game_info', 'Setup updated', `Front wing ${game_state.game_setup.game_front_wing}, brake bias ${game_state.game_setup.game_brake_bias.toFixed(1)}%, diff ${game_state.game_setup.game_diff_entry}%.`);
    game_save(false);
    game_render();
}

function game_footerText() {
    if (game_state.game_phase === 'briefing') return `${game_session().game_name} briefing · setup editable · press Start when ready.`;
    if (game_state.game_phase === 'finished') return `${game_session().game_name} complete · open result and continue the weekend.`;
    const game_issue = game_state.game_pending_issue ? ` · DRIVER WAITING ${Math.ceil(game_state.game_pending_issue.game_time_left)}s` : '';
    return `LIVE · P${game_state.game_position} · lap ${game_state.game_lap}/${game_session().game_laps} · ${game_state.game_mode.toUpperCase()} · ${game_state.game_tyre_compound} ${Math.round(game_state.game_tyre_wear)}% wear${game_issue}`;
}

function game_escape(game_text) {
    return String(game_text).replace(/[&<>'"]/g, game_char => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', "'":'&#39;', '"':'&quot;' }[game_char]));
}

function game_bind() {
    document.getElementById('game_session_action').addEventListener('click', game_startSession);
    document.getElementById('game_save_button').addEventListener('click', () => game_save(true));
    document.getElementById('game_reset_button').addEventListener('click', game_reset);
    document.getElementById('game_apply_setup').addEventListener('click', game_applySetup);
    document.getElementById('game_pit_tyre').addEventListener('change', game_event => game_state.game_pit_tyre = game_event.target.value);
    document.getElementById('game_pit_wing').addEventListener('change', game_event => game_state.game_pit_wing = Number(game_event.target.value));
    document.querySelectorAll('[data-game-command]').forEach(game_button => game_button.addEventListener('click', () => game_applyCommand(game_button.dataset.gameCommand)));
    document.querySelectorAll('[data-game-chat]').forEach(game_button => game_button.addEventListener('click', () => game_applyCommand(game_button.dataset.gameChat)));
    document.querySelectorAll('[data-game-speed]').forEach(game_button => game_button.addEventListener('click', () => {
        game_state.game_speed_multiplier = Number(game_button.dataset.gameSpeed);
        document.querySelectorAll('[data-game-speed]').forEach(game_speed_button => game_speed_button.classList.toggle('game_active', game_speed_button === game_button));
    }));
    document.getElementById('game_chat_form').addEventListener('submit', game_event => {
        game_event.preventDefault();
        const game_input = document.getElementById('game_chat_input');
        const game_text = game_input.value.trim();
        if (!game_text) return;
        game_input.value = '';
        game_parseRadio(game_text);
    });
    ['game_setup_wing', 'game_setup_brake', 'game_setup_diff', 'game_setup_pressure', 'game_setup_height'].forEach(game_id => {
        document.getElementById(game_id).addEventListener('input', () => {
            document.getElementById('game_setup_wing_value').textContent = Number(document.getElementById('game_setup_wing').value).toFixed(0);
            document.getElementById('game_setup_brake_value').textContent = Number(document.getElementById('game_setup_brake').value).toFixed(1);
            document.getElementById('game_setup_diff_value').textContent = Number(document.getElementById('game_setup_diff').value).toFixed(0);
            document.getElementById('game_setup_pressure_value').textContent = Number(document.getElementById('game_setup_pressure').value).toFixed(1);
            document.getElementById('game_setup_height_value').textContent = Number(document.getElementById('game_setup_height').value).toFixed(0);
        });
    });
    document.getElementById('game_modal_action').addEventListener('click', game_event => {
        if (game_event.currentTarget.dataset.gameSummary === '1') {
            document.getElementById('game_overlay').hidden = true;
            delete game_event.currentTarget.dataset.gameSummary;
            return;
        }
        game_continueWeekend();
    });
    window.addEventListener('beforeunload', () => game_save(false));
}

function game_boot() {
    game_bind();
    const game_loaded = game_load();
    if (!game_loaded) game_prepareSession();
    else {
        if (!Array.isArray(game_state.game_chat) || game_state.game_chat.length === 0) {
            game_addMessage('game_system', 'Saved weekend restored.');
        }
        game_render();
    }
    document.querySelectorAll('[data-game-speed]').forEach(game_button => game_button.classList.toggle('game_active', Number(game_button.dataset.gameSpeed) === game_state.game_speed_multiplier));
    game_interval = window.setInterval(game_tick, game_tick_ms);
}

game_boot();
