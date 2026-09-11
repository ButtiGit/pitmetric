<?php

namespace App\Http\Controllers;

use App\Models\Update;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

class PublicUpdateController extends Controller
{
    public function index(): View
    {
        if (! Schema::hasTable('updates')) {
            $updates = new LengthAwarePaginator(
                items: [],
                total: 0,
                perPage: 9,
                currentPage: LengthAwarePaginator::resolveCurrentPage(),
                options: [
                    'path' => request()->url(),
                    'query' => request()->query(),
                ],
            );

            return view('public.updates.index', compact('updates'));
        }

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
