<?php

namespace App\Modules\ButtiDeskPro\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ButtiDeskPro\Models\Contact;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class ContactController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $type = (string) $request->query('type', '');

        $contacts = Contact::query()
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('company', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            }))
            ->when(in_array($type, ['client', 'supplier', 'both'], true), fn ($query) => $query->where('type', $type))
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        return view('buttidesk.contacts', compact('contacts', 'search', 'type'));
    }

    public function store(Request $request): RedirectResponse
    {
        $contact = Contact::query()->create($this->validated($request));

        return to_route('buttidesk.contacts.index')->with('status', 'Contatto '.$contact->name.' creato.');
    }

    public function update(Request $request, Contact $contact): RedirectResponse
    {
        $contact->update($this->validated($request));

        return to_route('buttidesk.contacts.index')->with('status', 'Contatto aggiornato.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(['client', 'supplier', 'both'])],
            'name' => ['required', 'string', 'max:180'],
            'company' => ['nullable', 'string', 'max:180'],
            'email' => ['nullable', 'email:rfc', 'max:254'],
            'phone' => ['nullable', 'string', 'max:60'],
            'vat_number' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ]);
    }
}
