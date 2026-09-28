<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DemoSessionController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'demo@pitmetric.app'],
            [
                'name' => 'PitMetric Demo',
                'password' => Str::random(64),
            ],
        );

        $user->forceFill([
            'name' => 'PitMetric Demo',
            'email_verified_at' => $user->email_verified_at ?? now(),
            'database_access_enabled' => false,
            'newsletter_subscribed_at' => null,
        ])->save();

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('pitmetric.demo_read_only', true);

        return to_route('dashboard');
    }
}
