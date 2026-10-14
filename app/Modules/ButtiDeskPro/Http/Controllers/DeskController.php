<?php

namespace App\Modules\ButtiDeskPro\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ButtiDeskPro\Models\Appointment;
use App\Modules\ButtiDeskPro\Models\Contact;
use App\Modules\ButtiDeskPro\Models\Ticket;
use App\Modules\ButtiDeskPro\Models\Worklog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

final class DeskController extends Controller
{
    public function index(): View
    {
        return view('buttidesk.home', [
            'openTickets' => Ticket::query()->whereNotIn('status', ['done', 'cancelled'])->count(),
            'urgentTickets' => Ticket::query()->where('priority', 'urgent')->whereNotIn('status', ['done', 'cancelled'])->count(),
            'contactsCount' => Contact::query()->count(),
            'minutesThisMonth' => Worklog::query()->where('performed_at', '>=', Carbon::now((string) config('buttidesk.timezone'))->startOfMonth()->utc())->sum('duration_minutes'),
            'latestTickets' => Ticket::query()->with('client')->latest()->limit(7)->get(),
            'upcoming' => Appointment::query()->with('ticket')->where('ends_at', '>=', now())->orderBy('starts_at')->limit(6)->get(),
        ]);
    }
}
