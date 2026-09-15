'use strict';

game_completeLap = function (game_target_lap) {
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

    if (game_state.game_pending_pit) {
        game_executePitStop();
    }

    if (game_state.game_lap >= game_current.game_laps) {
        game_finishSession();
        return;
    }

    game_state.game_lap += 1;
    game_state.game_track_grip = game_clamp(game_state.game_track_grip + .35, 0, 100);
};
