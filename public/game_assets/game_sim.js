'use strict';

(() => {
    const game_files = [
        '/game_assets/game_sim_1.js?v=1',
        '/game_assets/game_sim_2.js?v=1',
        '/game_assets/game_sim_3.js?v=1',
        '/game_assets/game_sim_4.js?v=1',
        '/game_assets/game_sim_5.js?v=1',
        '/game_assets/game_sim_7.js?v=1',
        '/game_assets/game_sim_6.js?v=1',
    ];

    let game_chain = Promise.resolve();

    game_files.forEach((game_file) => {
        game_chain = game_chain.then(() => new Promise((game_resolve, game_reject) => {
            const game_script = document.createElement('script');
            game_script.src = game_file;
            game_script.async = false;
            game_script.onload = game_resolve;
            game_script.onerror = game_reject;
            document.head.appendChild(game_script);
        }));
    });

    game_chain.catch((game_error) => {
        console.error('game_sim loader failed', game_error);
        const game_app = document.getElementById('game_app');
        if (game_app) {
            game_app.innerHTML = '<div style="padding:24px;font-family:system-ui;color:#fff;background:#111;border-radius:12px">game_sim failed to load. Refresh the page after deployment.</div>';
        }
    });
})();
