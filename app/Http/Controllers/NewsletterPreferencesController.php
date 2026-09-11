<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsletterPreferencesController extends Controller
{
    public function edit(Request $request): View
    {
        return view('newsletter.preferences', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subscribed' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $user->forceFill([
            'newsletter_subscribed_at' => ($data['subscribed'] ?? false) ? ($user->newsletter_subscribed_at ?? now()) : null,
            'newsletter_locale' => app()->getLocale() === 'it' ? 'it' : 'en',
        ])->save();

        return back()->with('status', __('newsletter.saved'));
    }

    public function unsubscribe(Request $request, User $user): View
    {
        $user->forceFill(['newsletter_subscribed_at' => null])->save();

        return view('newsletter.unsubscribed');
    }
}
