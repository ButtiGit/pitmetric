@can('team-write')
    @php($mobileMaintenanceAttention = (int) $maintenanceSummary['overdue'] + (int) $maintenanceSummary['due_soon'])
    <x-pitmetric.mobile-action-dock
        :eyebrow="$it ? 'Trackside' : 'Trackside'"
        :title="$mobileMaintenanceAttention > 0 ? ($it ? 'Controlli manutenzione prima di girare' : 'Maintenance checks before running') : ($it ? 'Pronto per la prossima uscita' : 'Ready for the next outing')"
        :meta="$mobileMaintenanceAttention > 0 ? $mobileMaintenanceAttention.' alert' : ($it ? 'Pronto' : 'Ready')"
    >
        <button
            type="button"
            class="pm-mobile-action-item pm-mobile-action-item--primary pm-mobile-action-span-3"
            aria-haspopup="dialog"
            aria-controls="record-session"
            data-pm-mobile-primary-action
            onclick="document.getElementById('record-session').showModal()"
        >
            {{ $it ? 'Registra sessione' : 'Record session' }}
        </button>
        <a class="pm-mobile-action-item" href="{{ route('maintenance.index') }}">
            {{ $it ? 'Manut.' : 'Maint.' }}
        </a>
    </x-pitmetric.mobile-action-dock>
@endcan
