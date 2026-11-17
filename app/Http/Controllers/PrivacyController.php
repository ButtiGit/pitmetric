<?php

namespace App\Http\Controllers;

use App\Http\Requests\RespondToPrivacyRequest;
use App\Http\Requests\StorePrivacyRequest;
use App\Models\PrivacyRequest;
use App\Services\PrivacyRequests;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

final class PrivacyController extends Controller
{
    public function index(Request $request): View
    {
        $ready = Schema::hasTable('privacy_requests');

        return view('settings.privacy', [
            'ready' => $ready,
            'requests' => $ready ? PrivacyRequest::query()->where('user_id', $request->user()?->id)->latest()->paginate(10) : null,
        ]);
    }

    public function store(StorePrivacyRequest $request, PrivacyRequests $requests): RedirectResponse
    {
        abort_unless(Schema::hasTable('privacy_requests'), 503, 'Gestione privacy in attivazione.');
        $requests->submit($request->user(), $request->string('type')->toString(), $request->string('details')->toString());

        return to_route('privacy.index')->with('status', 'Richiesta ricevuta. Segui il riscontro in questa pagina.');
    }

    public function respond(RespondToPrivacyRequest $request, PrivacyRequest $privacyRequest, PrivacyRequests $requests): RedirectResponse
    {
        Gate::authorize('manage-updates');
        $requests->respond($privacyRequest, $request->user(), $request->string('status')->toString(), $request->string('message')->toString());

        return redirect()->route('studio.site.index', ['privacy_page' => $request->integer('privacy_page', 1)])->withFragment('privacy')->with('status', 'Riscontro registrato e visibile al richiedente.');
    }
}
