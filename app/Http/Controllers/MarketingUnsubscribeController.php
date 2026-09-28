<?php

namespace App\Http\Controllers;

use App\Models\MarketingSuppression;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MarketingUnsubscribeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $email = strtolower(trim((string) $request->query('email')));

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new NotFoundHttpException;
        }

        MarketingSuppression::query()->firstOrCreate([
            'email_hash' => hash('sha256', $email),
        ]);

        return view('public.marketing-unsubscribed');
    }
}
