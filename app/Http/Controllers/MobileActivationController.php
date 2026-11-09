<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMobileActivationRequest;
use App\Services\StudioAppActivations;
use Illuminate\Http\JsonResponse;

final class MobileActivationController extends Controller
{
    public function store(StoreMobileActivationRequest $request, StudioAppActivations $activations): JsonResponse
    {
        $validated = $request->validated();
        $activations->record($validated['installation_id'], $validated['platform']);

        return response()->json(['registered' => true, 'release_key' => StudioAppActivations::RELEASE_KEY]);
    }
}
