<?php

namespace App\Http\Controllers;

use App\Models\Update;
use Illuminate\Contracts\View\View;

class PublicUpdateController extends Controller
{
    public function index(): View
    {
        $updates = Update::query()
            ->published()
            ->latest('published_at')
            ->paginate(9);

        return view('public.updates.index', compact('updates'));
    }

    public function show(Update $update): View
    {
        abort_unless(
            $update->status === 'published'
            && $update->published_at !== null
            && $update->published_at->isPast(),
            404,
        );

        return view('public.updates.show', compact('update'));
    }
}
