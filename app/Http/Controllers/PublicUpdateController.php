<?php

namespace App\Http\Controllers;

use App\Models\Update;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $this->abortUnlessPublished($update);

        return view('public.updates.show', compact('update'));
    }

    public function media(Update $update): StreamedResponse
    {
        $this->abortUnlessPublished($update);

        $mediaPath = $update->getAttribute('media_path');

        abort_unless(
            is_string($mediaPath)
            && $mediaPath !== ''
            && Storage::disk('public')->exists($mediaPath),
            404,
        );

        return Storage::disk('public')->response(
            $mediaPath,
            null,
            ['Cache-Control' => 'public, max-age=31536000, immutable'],
        );
    }

    private function abortUnlessPublished(Update $update): void
    {
        abort_unless(
            $update->status === 'published'
            && $update->published_at !== null
            && $update->published_at->isPast(),
            404,
        );
    }
}
