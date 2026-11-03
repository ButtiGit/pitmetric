<?php

return [
    'title' => 'Open work list',
    'open' => 'Print open work',
    'all' => 'All',
    'all_open' => 'All open tasks and maintenance jobs matching the filters, across every page. The task board status tab is not applied.',
    'maintenance_scope' => 'For maintenance, the event filter selects jobs on components currently installed on vehicles entered in that event. This does not assign those jobs to the event. Maintenance jobs without a kart are included only without event/kart filters.',
    'total' => 'Open work items',
    'work' => 'Work / priority / state',
    'context' => 'Context / responsibility / deadline',
    'no_vehicle' => 'General team work / no current vehicle',
    'no_event' => 'No direct event link',
    'operator' => 'Operator / completion time',
    'empty' => 'No open work matches these filters.',
    'manual' => 'Paper checkboxes are for handwritten notes only. Printing does not change task or maintenance states.',
    'groups' => [
        0 => 'Overdue, urgent or blocked',
        1 => 'In progress',
        2 => 'To do',
    ],
    'sources' => ['task' => 'Team task', 'maintenance' => 'Maintenance'],
];
