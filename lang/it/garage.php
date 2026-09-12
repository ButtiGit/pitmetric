<?php

return [
    'title' => 'I tuoi mezzi',
    'description' => 'Questo è il primo modulo reale di PitMetric. I mezzi vengono salvati sul server e appartengono esclusivamente al tuo workspace.',
    'validation_title' => 'Controlla i dati del mezzo evidenziati.',
    'workspace' => [
        'badge' => 'WORKSPACE SERVER',
        'copy' => 'I dati del Garage ora vengono salvati nel database PitMetric. Gli altri account non possono leggere o modificare i mezzi di questo workspace.',
        'vehicle_count' => 'Mezzi',
    ],
    'create' => [
        'title' => 'Aggiungi un mezzo',
        'description' => 'Crea il mezzo che in seguito collegheremo a configurazioni, sessioni, manutenzione e spese.',
        'submit' => 'Aggiungi mezzo',
    ],
    'list' => [
        'title' => 'Garage del workspace',
        'description' => 'Modifica o archivia i mezzi salvati nel tuo workspace personale.',
    ],
    'empty' => [
        'title' => 'Il tuo garage è vuoto',
        'description' => 'Aggiungi il tuo primo kart, auto, moto o prototipo. Da questo momento sono dati reali sul server, non più una demo nel browser.',
    ],
    'fields' => [
        'name' => 'Nome mezzo',
        'category' => 'Categoria',
        'status' => 'Stato',
        'manufacturer' => 'Marca',
        'model' => 'Modello',
        'year' => 'Anno',
        'identifier' => 'Identificativo / telaio',
        'notes' => 'Note',
        'notes_placeholder' => 'Note facoltative su telaio, allestimento o identificazione.',
    ],
    'categories' => [
        'kart' => 'Kart',
        'car' => 'Auto',
        'motorcycle' => 'Moto',
        'prototype' => 'Prototipo',
        'other' => 'Altro',
    ],
    'statuses' => [
        'active' => 'Attivo',
        'inactive' => 'Inattivo',
    ],
    'edit' => [
        'toggle' => 'Modifica mezzo',
        'submit' => 'Salva modifiche',
    ],
    'delete' => [
        'submit' => 'Archivia mezzo',
        'confirm' => 'Archiviare questo mezzo? Sparirà dal garage attivo ma resterà recuperabile nel database.',
    ],
    'messages' => [
        'created' => 'Mezzo aggiunto al tuo workspace.',
        'updated' => 'Mezzo aggiornato.',
        'deleted' => 'Mezzo archiviato.',
    ],
    'next' => [
        'title' => 'Prossimo passo: componenti reali',
        'description' => 'Le altre sezioni del gestionale sono ancora demo locali. La prossima vertical slice collegherà i componenti a questo workspace e a questi mezzi reali.',
    ],
];
