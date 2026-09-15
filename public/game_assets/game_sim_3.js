'use strict';

function game_updateWarnings() {
    const game_tyre_max = Math.max(...game_state.game_tyre_temp);
    const game_brake_max = Math.max(...game_state.game_brake_temp);
    if (game_state.game_tyre_wear > 75) game_addAlertUnique('game_critical', 'Tyres critical', `Wear ${Math.round(game_state.game_tyre_wear)}%. Pace and puncture risk are increasing.`, 'game_tyres_critical');
    else if (game_state.game_tyre_wear > 55) game_addAlertUnique('game_warn', 'Tyre degradation', `Wear ${Math.round(game_state.game_tyre_wear)}%.`, 'game_tyres_warn');
    if (game_tyre_max > 108) game_addAlertUnique('game_warn', 'Tyre overheating', `Peak ${Math.round(game_tyre_max)}°C.`, 'game_tyre_hot');
    if (game_brake_max > 930) game_addAlertUnique('game_critical', 'Brake temperature', `Peak ${Math.round(game_brake_max)}°C. Cooling required.`, 'game_brake_hot');
    if (game_state.game_engine_temp > 114) game_addAlertUnique('game_warn', 'Power unit temperature', `${Math.round(game_state.game_engine_temp)}°C. Consider lift and coast.`, 'game_engine_hot');
    if (game_state.game_fuel < 2.5 && game_state.game_lap < game_session().game_laps) game_addAlertUnique('game_critical', 'Fuel critical', `${game_state.game_fuel.toFixed(1)} kg remaining.`, 'game_fuel_critical');
    if (game_state.game_battery < 20) game_addAlertUnique('game_warn', 'Low battery', `${Math.round(game_state.game_battery)}% remaining.`, 'game_battery_low');
    if (game_state.game_track_wetness > 12 && !['INTER', 'WET'].includes(game_state.game_tyre_compound)) game_addAlertUnique('game_critical', 'Slicks on wet track', `Wetness ${Math.round(game_state.game_track_wetness)}%.`, 'game_wrong_tyre_wet');
    if (game_state.game_track_wetness < 8 && ['INTER', 'WET'].includes(game_state.game_tyre_compound)) game_addAlertUnique('game_warn', 'Wet tyre overheating risk', 'Track is mostly dry.', 'game_wrong_tyre_dry');
}

function game_addAlert(game_level, game_title, game_text, game_key = null) {
    game_state.game_alerts.unshift({
        game_level,
        game_title,
        game_text,
        game_key: game_key || `game_${Date.now()}_${Math.random()}`,
        game_time: Date.now(),
    });
    game_state.game_alerts = game_state.game_alerts.slice(0, 7);
}

function game_addAlertUnique(game_level, game_title, game_text, game_key) {
    const game_existing = game_state.game_alerts.find(game_alert => game_alert.game_key === game_key);
    if (game_existing) {
        game_existing.game_text = game_text;
        game_existing.game_time = Date.now();
        return;
    }
    game_addAlert(game_level, game_title, game_text, game_key);
}

function game_clearAlerts() {
    game_state.game_alerts = [];
}

function game_adjustScore(game_delta, game_reason) {
    game_state.game_score = game_clamp(game_state.game_score + game_delta, 0, 100);
    game_state.game_score_delta = game_delta;
    if (Math.abs(game_delta) >= 4) game_addMessage('game_system', `${game_delta > 0 ? '+' : ''}${game_delta} engineer score · ${game_reason}`);
}

function game_resolvePendingIssue(game_command) {
    if (!game_state.game_pending_issue) return;
    const game_ideal = game_state.game_pending_issue.game_ideal;
    if (game_ideal.includes(game_command)) {
        game_adjustScore(7, 'Correct response to driver');
        game_state.game_driver_confidence = game_clamp(game_state.game_driver_confidence + 4, 0, 100);
        game_addMessage('game_driver', 'Copy. That makes sense.');
    } else {
        game_adjustScore(-4, 'Questionable call');
        game_state.game_driver_confidence = game_clamp(game_state.game_driver_confidence - 3, 0, 100);
        game_addMessage('game_driver', 'Copy... I am not fully convinced, but I will do it.');
    }
    game_state.game_pending_issue = null;
    game_state.game_issue_cooldown = 26;
}

function game_applyCommand(game_command, game_source = 'button') {
    const game_running = game_state.game_phase === 'running';
    if (!game_running && !['feedback'].includes(game_command)) {
        game_addMessage('game_driver', 'We are not on track yet. Save the instruction for the session.');
        return;
    }
    game_state.game_last_command = game_command;
    if (game_command === 'push') {
        game_state.game_mode = 'push';
        game_addMessage('game_engineer', 'Push now. Use everything you need.');
        game_addMessage('game_driver', 'Copy, pushing.');
    } else if (game_command === 'manage') {
        game_state.game_mode = 'manage';
        game_addMessage('game_engineer', 'Manage tyres. Bring the temperatures down.');
        game_addMessage('game_driver', 'Copy, managing tyres.');
    } else if (game_command === 'lift') {
        game_state.game_mode = 'lift';
        game_addMessage('game_engineer', 'Lift and coast. Save fuel and cool the car.');
        game_addMessage('game_driver', 'Understood, lift and coast.');
    } else if (game_command === 'recharge') {
        game_state.game_mode = 'recharge';
        game_addMessage('game_engineer', 'Recharge battery this lap.');
        game_addMessage('game_driver', 'Recharge on.');
    } else if (game_command === 'deploy') {
        game_state.game_mode = 'deploy';
        game_addMessage('game_engineer', 'Deploy. Overtake mode available.');
        game_addMessage('game_driver', 'Copy, using battery.');
    } else if (game_command === 'box') {
        if (game_session().game_type === 'qualifying') {
            game_addMessage('game_driver', 'Copy box, but remember this qualifying run will be compromised.');
        }
        game_state.game_pending_pit = true;
        game_addMessage('game_engineer', `Box this lap. ${game_state.game_pit_tyre} tyres.`);
        game_addMessage('game_driver', 'Box this lap, copy.');
    } else if (game_command === 'stay') {
        game_state.game_pending_pit = false;
        game_addMessage('game_engineer', 'Stay out. Cancel box.');
        game_addMessage('game_driver', 'Staying out.');
    } else if (game_command === 'feedback') {
        const game_balance = game_balanceState();
        game_addMessage('game_engineer', 'Give me car balance and tyre feedback.');
        game_addMessage('game_driver', `Balance is ${game_balance.game_text}. Tyre wear feels around ${Math.round(game_state.game_tyre_wear)}%, fronts ${Math.round(game_state.game_tyre_temp[0])}/${Math.round(game_state.game_tyre_temp[1])}, rears ${Math.round(game_state.game_tyre_temp[2])}/${Math.round(game_state.game_tyre_temp[3])}.`);
    }
    if (game_source !== 'parser') game_resolvePendingIssue(game_command);
    game_render();
}

function game_parseRadio(game_text) {
    const game_lower = game_text.toLowerCase();
    game_addMessage('game_engineer', game_text);
    if (/\b(box|pit|rientra|fermati)\b/.test(game_lower)) return game_applyCommand('box', 'parser'), game_resolvePendingIssue('box');
    if (/\b(stay out|resta fuori|non box|cancel pit)\b/.test(game_lower)) return game_applyCommand('stay', 'parser'), game_resolvePendingIssue('stay');
    if (/\b(push|spingi|attack|attacca)\b/.test(game_lower)) return game_applyCommand('push', 'parser'), game_resolvePendingIssue('push');
    if (/\b(manage|gestisci|gomme|tyres)\b/.test(game_lower) && !/come|feedback|senti/.test(game_lower)) return game_applyCommand('manage', 'parser'), game_resolvePendingIssue('manage');
    if (/\b(lift|coast|risparmia carburante|save fuel)\b/.test(game_lower)) return game_applyCommand('lift', 'parser'), game_resolvePendingIssue('lift');
    if (/\b(recharge|ricarica|charge)\b/.test(game_lower)) return game_applyCommand('recharge', 'parser'), game_resolvePendingIssue('recharge');
    if (/\b(deploy|overtake|usa batteria|use battery)\b/.test(game_lower)) return game_applyCommand('deploy', 'parser'), game_resolvePendingIssue('deploy');
    if (/\b(feedback|come la senti|come senti|bilanciamento|balance|front|rear)\b/.test(game_lower)) return game_applyCommand('feedback', 'parser');
    if (/\b(gap|distacco)\b/.test(game_lower)) {
        game_addMessage('game_driver', `Copy. I have P${game_state.game_position}; gap ahead ${game_state.game_gap_ahead.toFixed(1)}s, behind ${game_state.game_gap_back.toFixed(1)}s.`);
        return;
    }
    if (/\b(meteo|weather|rain|pioggia)\b/.test(game_lower)) {
        game_addMessage('game_driver', `Track feels ${game_state.game_weather.toLowerCase()}. Wetness around ${Math.round(game_state.game_track_wetness)}%. I can feel ${game_state.game_track_wetness > 10 ? 'low grip off-line' : 'normal grip'}.`);
        return;
    }
    if (/\b(danno|damage|wing|ala|floor|fondo)\b/.test(game_lower)) {
        game_addMessage('game_driver', `From the cockpit: front is ${game_state.game_damage.game_front_wing > 20 ? 'definitely damaged' : 'mostly okay'}, rear ${game_state.game_damage.game_floor > 18 ? 'unstable in high speed' : 'feels normal'}.`);
        return;
    }
    game_addMessage('game_driver', game_genericRadioReply(game_text));
}

function game_genericRadioReply(game_text) {
    const game_replies = [
        'Copy. Understood.',
        'Okay, I will keep you updated.',
        'Copy that. Let me know if the target changes.',
        'Understood. I will report if the balance changes.',
    ];
    if (game_text.includes('?')) return 'I hear you. From the car it feels stable enough, but check the telemetry because I may be missing something.';
    return game_replies[Math.floor(Math.random() * game_replies.length)];
}
