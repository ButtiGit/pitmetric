<?php

return [
    'title' => 'I tuoi mezzi',
    'description' => 'Organizza i mezzi che usi in pista e collegali progressivamente a configurazioni, sessioni, manutenzione e spese.',
    'validation_title' => 'Controlla i dati del mezzo evidenziati.',
    'workspace' => [
        'badge' => 'WORKSPACE',
        'copy' => 'Qui trovi i mezzi associati al tuo account PitMetric e puoi mantenerne aggiornati i dati principali.',
        'vehicle_count' => 'Mezzi',
    ],
    'create' => [
        'title' => 'Aggiungi un mezzo',
        'description' => 'Scegli il tipo di mezzo per assegnargli anche una silhouette riconoscibile nel Garage.',
        'submit' => 'Aggiungi mezzo',
    ],
    'list' => [
        'title' => 'Garage',
        'description' => 'Modifica o archivia i mezzi associati al tuo workspace.',
    ],
    'empty' => [
        'title' => 'Il tuo garage è vuoto',
        'description' => 'Aggiungi il tuo primo mezzo per iniziare a organizzare componenti, sessioni e manutenzione.',
    ],
    'fields' => [
        'name' => 'Nome mezzo',
        'category' => 'Tipo veicolo',
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
        'formula' => 'Formula / monoposto',
        'gt' => 'GT',
        'touring' => 'Turismo',
        'rally' => 'Rally',
        'prototype' => 'Prototipo / LMP',
        'hypercar' => 'Hypercar',
        'drift' => 'Drift',
        'road_car' => 'Auto stradale',
        'car' => 'Auto generica',
        'motorcycle' => 'Moto',
        'quad' => 'Quad / ATV',
        'buggy' => 'Buggy',
        'offroad' => 'Fuoristrada',
        'truck' => 'Racing truck / camion',
        'boat' => 'Imbarcazione',
        'other' => 'Altro',
    ],
    'statuses' => [
        'active' => 'Attivo',
        'inactive' => 'Inattivo',
    ],
    'components' => [
        'title' => 'Componenti montati',
        'hover_hint' => 'Passa sul mezzo per le scorciatoie',
        'empty' => 'Nessun componente montato',
        'empty_action' => 'Apri Componenti',
        'open' => 'Apri componente',
    ],
    'edit' => [
        'toggle' => 'Modifica mezzo',
        'submit' => 'Salva modifiche',
    ],
    'delete' => [
        'submit' => 'Archivia mezzo',
        'confirm' => 'Archiviare questo mezzo?',
    ],
    'messages' => [
        'created' => 'Mezzo aggiunto al tuo workspace.',
        'updated' => 'Mezzo aggiornato.',
        'deleted' => 'Mezzo archiviato.',
        'unavailable' => 'Il Garage è temporaneamente non disponibile. Riprova dopo l’aggiornamento dell’applicazione.',
    ],
    'next' => [
        'title' => 'Continua con i componenti',
        'description' => 'Aggiungi e organizza i componenti del mezzo per costruire configurazioni e tenere sotto controllo utilizzo e manutenzione.',
    ],
];
