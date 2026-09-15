'use strict';

function game_applyLapConsumption() {
    const game_compound = game_compoundData();
    const game_mode_wear = game_state.game_mode === 'push' || game_state.game_mode === 'deploy' ? 1.32 : (game_state.game_mode === 'manage' ? .72 : (game_state.game_mode === 'lift' ? .80 : 1));
    const game_pressure_wear = 1 + Math.abs(game_state.game_setup.game_tyre_pressure - 22.2) * .06;
    game_state.game_tyre_wear = game_clamp(game_state.game_tyre_wear + game_random(1.8, 2.7) * game_compound.game_wear * game_mode_wear * game_pressure_wear, 0, 100);

    const game_fuel_rate = game_state.game_mode === 'push' || game_state.game_mode === 'deploy' ? 1.67 : (game_state.game_mode === 'lift' ? 1.31 : 1.51);
    game_state.game_fuel = game_clamp(game_state.game_fuel - game_fuel_rate, 0, game_state.game_fuel_capacity);

    if (game_state.game_mode === 'deploy') game_state.game_battery -= game_random(11, 17);
    else if (game_state.game_mode === 'push') game_state.game_battery -= game_random(5, 9);
    else if (game_state.game_mode === 'recharge') game_state.game_battery += game_random(15, 22);
    else if (game_state.game_mode === 'lift') game_state.game_battery += game_random(7, 12);
    else game_state.game_battery += game_random(-2, 4);
    game_state.game_battery = game_clamp(game_state.game_battery, 0, 100);
}

function game_applyRaceDynamics(game_lap_time) {
    const game_reference = game_bootstrap.game_track.game_lap_time_base + game_random(-.4, 1.1);
    const game_delta = game_lap_time - game_reference;
    if (game_delta < -.35 && Math.random() < .42) {
        game_state.game_position = Math.max(1, game_state.game_position - 1);
        game_state.game_gap_ahead = game_random(.4, 1.5);
        game_state.game_gap_back = game_random(.8, 2.6);
        game_addMessage('game_system', `Position gain: P${game_state.game_position}.`);
    } else if (game_delta > .8 && Math.random() < .46) {
        game_state.game_position = Math.min(20, game_state.game_position + 1);
        game_state.game_gap_ahead = game_random(.9, 2.8);
        game_state.game_gap_back = game_random(.3, 1.4);
        game_addMessage('game_system', `Position lost: P${game_state.game_position}.`);
    } else {
        game_state.game_gap_ahead = game_clamp(game_state.game_gap_ahead + game_delta * .28 + game_random(-.25, .25), .1, 8);
        game_state.game_gap_back = game_clamp(game_state.game_gap_back - game_delta * .22 + game_random(-.22, .22), .1, 8);
    }
}

function game_maybeIncident() {
    const game_risk = (game_state.game_mode === 'push' || game_state.game_mode === 'deploy' ? .024 : .009)
        + Math.max(0, game_state.game_tyre_wear - 62) * .0011
        + game_state.game_track_wetness * .00055;
    if (Math.random() > game_risk) return;
    const game_roll = Math.random();
    if (game_roll < .30) {
        const game_damage = game_random(7, 22);
        game_state.game_damage.game_front_wing = game_clamp(game_state.game_damage.game_front_wing + game_damage, 0, 100);
        game_addAlert('game_warn', 'Front wing damage', `Load loss estimated ${Math.round(game_state.game_damage.game_front_wing)}%. Check driver feedback and decide whether to box.`);
        game_addMessage('game_driver', 'I touched the car ahead. Front wing feels different, more understeer now. Check the data.');
    } else if (game_roll < .52) {
        game_state.game_damage.game_floor = game_clamp(game_state.game_damage.game_floor + game_random(5, 17), 0, 100);
        game_addAlert('game_warn', 'Floor strike', 'Downforce loss detected after kerb impact.');
        game_addMessage('game_driver', 'Big hit on the kerb. Rear feels less planted in high speed.');
    } else if (game_roll < .68) {
        game_state.game_damage.game_suspension = game_clamp(game_state.game_damage.game_suspension + game_random(4, 13), 0, 100);
        game_addAlert('game_warn', 'Suspension load anomaly', 'One corner shows abnormal load transfer.');
        game_addMessage('game_driver', 'Something feels odd on turn-in, maybe steering or suspension.');
    } else if (game_roll < .84) {
        game_state.game_damage.game_engine = game_clamp(game_state.game_damage.game_engine + game_random(3, 10), 0, 100);
        game_addAlert('game_warn', 'Power unit degradation', 'Small power loss and temperature rise detected.');
        game_addMessage('game_driver', 'I am down on power on the straight. Is everything okay?');
    } else {
        game_state.game_position = Math.min(20, game_state.game_position + Math.ceil(game_random(1, 3)));
        game_state.game_driver_confidence = game_clamp(game_state.game_driver_confidence - 6, 0, 100);
        game_addAlert('game_warn', 'Off-track', 'Time lost. No major damage detected, but tyres may be dirty.');
        game_addMessage('game_driver', 'Sorry, went wide. Tyres are dirty. Give me a lap to clean them.');
    }
}

function game_maybeDriverIssue() {
    if (game_state.game_issue_cooldown > 0 || game_state.game_pending_issue) return;
    let game_issue = null;
    if (game_state.game_tyre_wear > 62 && Math.random() < .38) game_issue = 'game_tyres_gone';
    else if (Math.max(...game_state.game_tyre_temp) > 108 && Math.random() < .42) game_issue = 'game_tyres_hot';
    else if (game_state.game_fuel < Math.max(2.2, (game_session().game_laps - game_state.game_lap) * 1.42) && Math.random() < .5) game_issue = 'game_fuel_low';
    else if (game_state.game_battery < 24 && Math.random() < .5) game_issue = 'game_battery_low';
    else if (game_state.game_track_wetness > 14 && !['INTER', 'WET'].includes(game_state.game_tyre_compound) && Math.random() < .65) game_issue = 'game_rain_tyres';
    else if (game_state.game_damage.game_front_wing > 26 && Math.random() < .55) game_issue = 'game_wing_damage';
    else if (game_state.game_gap_ahead < 1.0 && game_state.game_battery > 38 && Math.random() < .22) game_issue = 'game_attack_window';
    if (game_issue) game_raiseIssue(game_issue);
}

function game_raiseIssue(game_issue) {
    const game_issue_data = {
        game_tyres_gone: { game_text: 'Rear tyres are dropping fast. Do you want me to manage or are we boxing?', game_ideal: ['manage', 'box'], game_timeout: 36 },
        game_tyres_hot: { game_text: 'Tyre temps are getting high. Should I back off?', game_ideal: ['manage', 'lift'], game_timeout: 30 },
        game_fuel_low: { game_text: 'Fuel number looks tight from my side. What do you want?', game_ideal: ['lift'], game_timeout: 28 },
        game_battery_low: { game_text: 'Battery is low. Recharge or keep attacking?', game_ideal: ['recharge'], game_timeout: 26 },
        game_rain_tyres: { game_text: 'Grip is going away, especially sector two. Is it inters time?', game_ideal: ['box'], game_timeout: 26 },
        game_wing_damage: { game_text: 'Front is really struggling. Are we staying out with this damage?', game_ideal: ['box'], game_timeout: 28 },
        game_attack_window: { game_text: 'I am inside one second. Can I use battery to attack?', game_ideal: ['deploy', 'push'], game_timeout: 20 },
    }[game_issue];
    if (!game_issue_data) return;
    game_state.game_pending_issue = {
        game_key: game_issue,
        game_ideal: game_issue_data.game_ideal,
        game_time_left: game_issue_data.game_timeout,
    };
    game_addMessage('game_driver', game_issue_data.game_text);
    game_addAlert('game_warn', 'Driver question', 'Il pilota aspetta una risposta. Usa radio o comandi rapidi.');
}

function game_updateRadio(game_sim_seconds) {
    if (game_state.game_pending_issue) {
        game_state.game_pending_issue.game_time_left -= game_sim_seconds;
        if (game_state.game_pending_issue.game_time_left <= 0) {
            game_adjustScore(-7, 'No answer to driver');
            game_addMessage('game_driver', 'No answer... I will make the call myself.');
            game_state.game_pending_issue = null;
            game_state.game_issue_cooldown = 28;
        }
    }
    if (game_state.game_radio_cooldown <= 0) {
        game_state.game_radio_cooldown = game_random(22, 42);
        const game_lines = game_unsolicitedDriverLines();
        game_addMessage('game_driver', game_lines[Math.floor(Math.random() * game_lines.length)]);
    }
}

function game_unsolicitedDriverLines() {
    const game_balance = game_balanceState();
    const game_lines = [
        `Balance update: ${game_balance.game_text}.`,
        `Tyres feel ${game_state.game_tyre_wear > 48 ? 'quite used now' : 'still okay'}.`,
        `How is the gap ahead? I can ${game_state.game_battery > 55 ? 'attack if you want' : 'save battery for later'}.`,
        `Track grip is improving. I can feel more front end now.`,
        `Brakes are ${Math.max(...game_state.game_brake_temp) > 920 ? 'very hot' : 'under control'}.`,
    ];
    if (game_state.game_track_wetness > 8) game_lines.push('There are wet patches off-line. Tell me if rain gets worse.');
    if (game_state.game_damage.game_floor > 12) game_lines.push('High-speed balance still feels inconsistent after that floor hit.');
    return game_lines;
}

function game_balanceState() {
    const game_setup = game_state.game_setup;
    let game_front = (game_setup.game_front_wing - 7.6) * .9 - (game_setup.game_brake_bias - 53.7) * .3;
    let game_rear = (57 - game_setup.game_diff_entry) * .08 + (22.2 - game_setup.game_tyre_pressure) * .3;
    game_front -= game_state.game_damage.game_front_wing * .045;
    game_rear -= game_state.game_damage.game_floor * .028;
    const game_net = game_front - game_rear;
    if (game_net < -1.2) return { game_key: 'understeer', game_text: 'understeer mid-corner, especially long turns' };
    if (game_net > 1.4) return { game_key: 'oversteer', game_text: 'rear is nervous on entry and traction' };
    return { game_key: 'balanced', game_text: 'pretty neutral; only small movement on entry' };
}

function game_executePitStop() {
    const game_old = game_state.game_tyre_compound;
    game_state.game_tyre_compound = game_state.game_pit_tyre;
    game_state.game_tyre_wear = 0;
    game_state.game_tyre_age = 0;
    game_state.game_tyre_temp = [78, 78, 76, 76];
    game_state.game_pit_loss_pending = game_random(20.8, 24.5);
    game_state.game_position = Math.min(20, game_state.game_position + Math.round(game_random(1, 4)));
    game_state.game_setup.game_front_wing = game_clamp(game_state.game_setup.game_front_wing + Number(game_state.game_pit_wing || 0), 1, 12);
    if (game_state.game_damage.game_front_wing > 15) {
        game_state.game_damage.game_front_wing = Math.max(0, game_state.game_damage.game_front_wing - game_random(38, 70));
        game_state.game_pit_loss_pending += 4.2;
    }
    game_state.game_pending_pit = false;
    game_state.game_pit_wing = 0;
    game_addMessage('game_system', `Pit stop: ${game_old} → ${game_state.game_tyre_compound}. Estimated pit loss added.`);
    game_addMessage('game_driver', 'Pit exit. Tyres are cold, give me the target.');
    game_adjustScore(2, 'Executed pit stop');
}
