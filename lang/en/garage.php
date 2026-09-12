<?php

return [
    'title' => 'Your vehicles',
    'description' => 'This is the first real PitMetric module. Vehicles are stored on the server and belong only to your workspace.',
    'validation_title' => 'Check the highlighted vehicle data.',
    'workspace' => [
        'badge' => 'SERVER WORKSPACE',
        'copy' => 'Garage data is now saved in the PitMetric database. Other accounts cannot read or modify vehicles from this workspace.',
        'vehicle_count' => 'Vehicles',
    ],
    'create' => [
        'title' => 'Add a vehicle',
        'description' => 'Create the vehicle that will later be linked to configurations, sessions, maintenance and expenses.',
        'submit' => 'Add vehicle',
    ],
    'list' => [
        'title' => 'Workspace garage',
        'description' => 'Edit or archive the vehicles stored in your personal workspace.',
    ],
    'empty' => [
        'title' => 'Your garage is empty',
        'description' => 'Add your first kart, car, motorcycle or prototype. From this point on it is real server data, not a browser demo.',
    ],
    'fields' => [
        'name' => 'Vehicle name',
        'category' => 'Category',
        'status' => 'Status',
        'manufacturer' => 'Manufacturer',
        'model' => 'Model',
        'year' => 'Year',
        'identifier' => 'Identifier / chassis',
        'notes' => 'Notes',
        'notes_placeholder' => 'Optional setup, chassis or identification notes.',
    ],
    'categories' => [
        'kart' => 'Kart',
        'car' => 'Car',
        'motorcycle' => 'Motorcycle',
        'prototype' => 'Prototype',
        'other' => 'Other',
    ],
    'statuses' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],
    'edit' => [
        'toggle' => 'Edit vehicle',
        'submit' => 'Save changes',
    ],
    'delete' => [
        'submit' => 'Archive vehicle',
        'confirm' => 'Archive this vehicle? It will disappear from the active garage but remains recoverable in the database.',
    ],
    'messages' => [
        'created' => 'Vehicle added to your workspace.',
        'updated' => 'Vehicle updated.',
        'deleted' => 'Vehicle archived.',
    ],
    'next' => [
        'title' => 'Next: real components',
        'description' => 'The remaining manager sections are still local demos. The next vertical slice will connect components to this workspace and these real vehicles.',
    ],
];
