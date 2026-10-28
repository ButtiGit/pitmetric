<?php

namespace App\Http\Controllers;

use App\Services\SessionWeatherService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class MobileWeatherController extends Controller
{
    public function show(Request $request, SessionWeatherService $weatherService): JsonResponse
    {
        $coordinates = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        try {
            $forecast = $weatherService->capture((float) $coordinates['latitude'], (float) $coordinates['longitude']);
        } catch (ConnectionException|RuntimeException $exception) {
            report($exception);

            return response()->json(['message' => __('The weather service is temporarily unavailable. Try again in a moment.')], 503);
        }

        return response()->json(['data' => $forecast]);
    }
}
