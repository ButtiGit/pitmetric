@if ($canWrite)
    @php
        $canRecordNext = $nextScheduleItem
            && $nextScheduleItem->eventEntry
            && ! in_array($nextScheduleItem->status, ['completed', 'cancelled'], true);
        $mobileTracksideTitle = $nextScheduleItem
            ? $nextScheduleItem->starts_at->format('H:i').' · '.($nextScheduleItem->label ?: ucfirst($nextScheduleItem->session_type))
            : ($it ? 'Nessuna attività pianificata' : 'No activity scheduled');
        $mobileTracksideMeta = $nextScheduleItem?->eventEntry?->driver?->display_name
            ?? ($nextScheduleItem ? 'Team' : ($it ? 'Pianifica' : 'Plan'));
    @endphp

    <x-pitmetric.mobile-action-dock
        data-pm-trackside-dock
        :eyebrow="$nextScheduleItem?->status === 'live' ? ($it ? 'In pista' : 'On track') : ($it ? 'Prossima attività' : 'Next activity')"
        :title="$mobileTracksideTitle"
        :meta="$mobileTracksideMeta"
    >
        @if ($canRecordNext)
            <button
                type="button"
                class="pm-mobile-action-item pm-mobile-action-item--primary pm-mobile-action-span-2"
                aria-haspopup="dialog"
                aria-controls="record-next-schedule"
                data-pm-mobile-primary-action
                onclick="document.getElementById('record-next-schedule').showModal()"
            >
                {{ $it ? 'Registra risultato' : 'Record result' }}
            </button>
        @else
            <button
                type="button"
                class="pm-mobile-action-item pm-mobile-action-item--primary pm-mobile-action-span-2"
                aria-haspopup="dialog"
                aria-controls="add-schedule-item"
                data-pm-mobile-primary-action
                onclick="document.getElementById('add-schedule-item').showModal()"
            >
                {{ $it ? 'Pianifica' : 'Schedule' }}
            </button>
        @endif

        <button type="button" class="pm-mobile-action-item" aria-haspopup="dialog" aria-controls="add-event-note" onclick="document.getElementById('add-event-note').showModal()">
            {{ $it ? 'Nota' : 'Note' }}
        </button>
        <button type="button" class="pm-mobile-action-item" aria-haspopup="dialog" aria-controls="add-event-task" onclick="document.getElementById('add-event-task').showModal()">
            {{ $it ? 'Lavoro' : 'Task' }}
        </button>
    </x-pitmetric.mobile-action-dock>
@endif
