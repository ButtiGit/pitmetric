<?php

return [
    'title' => 'Your vehicles',
    'description' => 'Organize the vehicles you use on track and progressively connect them to configurations, sessions, maintenance and expenses.',
    'validation_title' => 'Check the highlighted vehicle data.',
    'workspace' => [
        'badge' => 'WORKSPACE',
        'copy' => 'Here you can manage the vehicles associated with your PitMetric account and keep their main details up to date.',
        'vehicle_count' => 'Vehicles',
    ],
    'create' => [
        'title' => 'Add a vehicle',
        'description' => 'Add the kart, car, motorcycle or prototype you want to manage with PitMetric.',
        'submit' => 'Add vehicle',
    ],
    'list' => [
        'title' => 'Garage',
        'description' => 'Edit or archive vehicles associated with your workspace.',
    ],
    'empty' => [
        'title' => 'Your garage is empty',
        'description' => 'Add your first kart, car, motorcycle or prototype to start organizing your work.',
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
        'confirm' => 'Archive this vehicle?',
    ],
    'messages' => [
        'created' => 'Vehicle added to your workspace.',
        'updated' => 'Vehicle updated.',
        'deleted' => 'Vehicle archived.',
        'unavailable' => 'Garage is temporarily unavailable. Try again after the application update.',
    ],
    'next' => [
        'title' => 'Continue with components',
        'description' => 'Add and organize vehicle components to build configurations and keep usage and maintenance under control.',
    ],
];
