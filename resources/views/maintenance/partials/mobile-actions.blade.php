@can('team-write')
    <x-pitmetric.mobile-action-dock
        :eyebrow="$it ? 'Manutenzione' : 'Maintenance'"
        :title="$workSummary['blocked'] > 0 ? ($it ? 'Ci sono lavori bloccati da risolvere' : 'Blocked work needs attention') : ($it ? 'Aggiorna la coda operativa' : 'Keep the operations queue moving')"
        :meta="$workSummary['open'].' '.($it ? 'aperti' : 'open')"
    >
        <button
            type="button"
            class="pm-mobile-action-item pm-mobile-action-item--primary pm-mobile-action-span-2"
            aria-haspopup="dialog"
            aria-controls="create-maintenance-work-order"
            data-pm-mobile-primary-action
            onclick="document.getElementById('create-maintenance-work-order').showModal()"
        >
            {{ $it ? '+ Lavoro' : '+ Work order' }}
        </button>
        <button
            type="button"
            class="pm-mobile-action-item pm-mobile-action-span-2"
            aria-haspopup="dialog"
            aria-controls="create-maintenance-schedule"
            onclick="document.getElementById('create-maintenance-schedule').showModal()"
        >
            {{ $it ? '+ Piano' : '+ Schedule' }}
        </button>
    </x-pitmetric.mobile-action-dock>
@endcan
