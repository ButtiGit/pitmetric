<?php

namespace App\Http\Controllers;

use App\Models\Update;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $updates = Schema::hasTable('updates')
            ? Update::query()->published()->latest('updated_at')->get()
            : collect();

        return response()
            ->view('sitemap', compact('updates'))
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
