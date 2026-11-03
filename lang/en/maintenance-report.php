<?php

return [
    'open' => 'Maintenance report / PDF', 'from' => 'From', 'to' => 'To', 'period' => 'Period (Europe/Rome)',
    'all' => 'All', 'filter' => 'Apply filters', 'component' => 'Component', 'unknown' => 'Not recorded / cannot be reconstructed',
    'scope' => 'Recorded completed interventions only, each shown once. Open, blocked and cancelled work orders are excluded.',
    'history' => 'The kart is derived from installations at the time of the work. Missing or overlapping installations are not attributed. Names and serial numbers come from current master records.',
    'usage_help' => 'Usage before work is reconstructed from the available ledger up to the work date and time. This is not an immutable snapshot: later entries or corrections may change it. No recorded usage means unavailable, not zero.',
    'cost_help' => 'EUR costs recorded on maintenance interventions. Automatically generated expenses are not added again. Missing amounts are excluded from the total.',
    'total' => 'Interventions', 'cost_total' => 'Total recorded costs', 'cost_coverage' => 'Interventions with a recorded cost',
    'empty' => 'No completed interventions match the selected filters.', 'performed' => 'Performed at', 'author' => 'Recorded by',
    'operator_help' => 'The record author does not necessarily identify who performed the work. A work order assignee does not certify the operator.',
    'vehicle_at_time' => 'Kart at the time of work', 'work' => 'Work performed', 'orders' => 'Linked completed work orders',
    'lifetime' => 'Total recorded usage up to the work', 'since_service' => 'Usage since the previous reset',
    'resets' => 'Resets recorded with this intervention', 'no_reset' => 'No linked reset recorded', 'cost' => 'Recorded cost (EUR)',
];
