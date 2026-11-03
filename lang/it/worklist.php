<?php

return [
    'title' => 'Elenco lavori da completare',
    'open' => 'Stampa lavori aperti',
    'all' => 'Tutti',
    'all_open' => 'Tutte le attività e manutenzioni aperte corrispondenti ai filtri, su tutte le pagine. La scheda di stato selezionata nella bacheca non viene applicata.',
    'maintenance_scope' => 'Per le manutenzioni, il filtro evento seleziona i lavori sui componenti attualmente montati sui mezzi iscritti. Non attribuisce quei lavori all’evento. Le manutenzioni senza kart compaiono solo senza filtri evento/kart.',
    'total' => 'Lavori aperti',
    'work' => 'Lavoro / priorità / stato',
    'context' => 'Contesto / responsabile / scadenza',
    'no_vehicle' => 'Lavoro generale / nessun mezzo attuale',
    'no_event' => 'Nessun collegamento diretto',
    'operator' => 'Operatore / ora esecuzione',
    'empty' => 'Nessun lavoro aperto corrisponde ai filtri.',
    'manual' => 'Le caselle sono per annotazioni manuali. La stampa non modifica lo stato delle attività o delle manutenzioni.',
    'groups' => [
        0 => 'Scaduti, urgenti o bloccati',
        1 => 'In corso',
        2 => 'Da fare',
    ],
    'sources' => ['task' => 'Attività team', 'maintenance' => 'Manutenzione'],
];
