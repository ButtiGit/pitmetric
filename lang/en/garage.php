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
        'description' => 'Choose the vehicle type to give it a recognizable silhouette in the Garage.',
        'submit' => 'Add vehicle',
    ],
    'list' => [
        'title' => 'Garage',
        'description' => 'Edit or archive vehicles associated with your workspace.',
    ],
    'empty' => [
        'title' => 'Your garage is empty',
        'description' => 'Add your first vehicle to start organizing components, sessions and maintenance.',
    ],
    'fields' => [
        'name' => 'Vehicle name',
        'category' => 'Vehicle type',
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
        'formula' => 'Formula / single-seater',
        'gt' => 'GT',
        'touring' => 'Touring car',
        'rally' => 'Rally car',
        'prototype' => 'Prototype / LMP',
        'hypercar' => 'Hypercar',
        'drift' => 'Drift car',
        'road_car' => 'Road car',
        'car' => 'Generic car',
        'motorcycle' => 'Motorcycle',
        'quad' => 'Quad / ATV',
        'buggy' => 'Buggy',
        'offroad' => 'Off-road vehicle',
        'truck' => 'Racing truck / truck',
        'boat' => 'Boat',
        'other' => 'Other',
    ],
    'statuses' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],
    'components' => [
        'title' => 'Installed components',
        'hover_hint' => 'Hover the vehicle for shortcuts',
        'empty' => 'No components installed',
        'empty_action' => 'Open Components',
        'open' => 'Open component',
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
