<?php

namespace App\Modules\ButtiDeskPro\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

final class EnsureDeskReady
{
    public function handle(Request $request, Closure $next): Response
    {
        // Never touch Desk database tables when the feature is disabled.
        abort_unless(config('buttidesk.enabled', false), 404);

        // A partially applied migration must affect only Desk, never PitMetric.
        foreach ([
            'desk_contacts',
            'desk_tickets',
            'desk_worklogs',
            'desk_appointments',
            'desk_calendar_feeds',
            'desk_mail_logs',
        ] as $table) {
            abort_unless(Schema::hasTable($table), 503, 'ButtiDesk-Pro non è pronto: esegui le migrazioni.');
        }

        return $next($request);
    }
}
