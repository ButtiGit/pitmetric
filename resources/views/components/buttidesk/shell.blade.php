@props(['title' => 'ButtiDesk-Pro'])
<x-layouts::app :title="$title">
    <div class="bd">
        <div class="bd-wrap">
            <header class="bd-header">
                <a class="bd-brand" href="{{ route('buttidesk.index') }}" aria-label="ButtiDesk-Pro — dashboard">
                    <span class="bd-mark">B<span>·</span></span>
                    <span class="bd-brand-copy"><strong>ButtiDesk<span>-Pro</span></strong><small>WORKSPACE / SERVICE OPERATIONS</small></span>
                </a>
                <div class="bd-header-right">
                    <span class="bd-live"><i></i> Operativo</span>
                    <a class="bd-back" href="{{ route('dashboard') }}">« Torna a PitMetric</a>
                </div>
            </header>

            <nav class="bd-nav" aria-label="Navigazione ButtiDesk-Pro">
                <a href="{{ route('buttidesk.index') }}" @class(['active' => request()->routeIs('buttidesk.index')])>Panoramica</a>
                <a href="{{ route('buttidesk.tickets.index') }}" @class(['active' => request()->routeIs('buttidesk.tickets.index', 'buttidesk.tickets.show')])>Ticket</a>
                <a href="{{ route('buttidesk.tickets.reports') }}" @class(['active' => request()->routeIs('buttidesk.tickets.reports', 'buttidesk.tickets.report')])>Report</a>
                <a href="{{ route('buttidesk.contacts.index') }}" @class(['active' => request()->routeIs('buttidesk.contacts.*')])>Rubrica</a>
                <a href="{{ route('buttidesk.calendar.index') }}" @class(['active' => request()->routeIs('buttidesk.calendar.*')])>Calendario</a>
                @can('manage-updates')
                    <a href="{{ route('buttidesk.mail.index') }}" @class(['active' => request()->routeIs('buttidesk.mail.*')])>Mail</a>
                @endcan
            </nav>

            @if (session('status'))
                <div class="bd-flash" role="status">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="bd-error" role="alert"><strong>Controlla i dati inseriti</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            {{ $slot }}
            <footer class="bd-footer">
                <span>ButtiDesk-Pro · Un modulo indipendente, connesso a PitMetric</span>
                <span>Accesso e dati protetti dal workspace attivo</span>
            </footer>
        </div>
    </div>
</x-layouts::app>
