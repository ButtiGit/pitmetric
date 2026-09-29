<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use JsonException;

final class ResendWebhookController extends Controller
{
    private const SIGNATURE_TOLERANCE_SECONDS = 300;

    public function __invoke(Request $request): JsonResponse
    {
        $secret = (string) config('services.resend.webhook_secret', '');

        if ($secret === '') {
            Log::error('Resend webhook rejected because RESEND_WEBHOOK_SECRET is not configured.');

            return response()->json(['message' => 'Webhook secret not configured.'], 503);
        }

        $messageId = $request->header('svix-id');
        $timestamp = $request->header('svix-timestamp');
        $signature = $request->header('svix-signature');

        if (! is_string($messageId) || ! is_string($timestamp) || ! is_string($signature)) {
            return response()->json(['message' => 'Missing webhook signature headers.'], 400);
        }

        if (! ctype_digit($timestamp) || abs(time() - (int) $timestamp) > self::SIGNATURE_TOLERANCE_SECONDS) {
            return response()->json(['message' => 'Invalid webhook timestamp.'], 401);
        }

        $payload = $request->getContent();

        if (! $this->hasValidSignature($payload, $messageId, $timestamp, $signature, $secret)) {
            return response()->json(['message' => 'Invalid webhook signature.'], 401);
        }

        try {
            $event = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return response()->json(['message' => 'Invalid JSON payload.'], 400);
        }

        if (! is_array($event) || ! isset($event['type']) || ! is_string($event['type'])) {
            return response()->json(['message' => 'Invalid webhook payload.'], 400);
        }

        if ($event['type'] === 'email.received') {
            $data = isset($event['data']) && is_array($event['data']) ? $event['data'] : [];

            Log::info('Resend inbound email received.', [
                'webhook_id' => $messageId,
                'email_id' => $data['email_id'] ?? null,
                'message_id' => $data['message_id'] ?? null,
                'from' => $data['from'] ?? null,
                'to' => $data['to'] ?? [],
                'cc' => $data['cc'] ?? [],
                'subject' => $data['subject'] ?? null,
                'attachments' => $data['attachments'] ?? [],
            ]);
        }

        return response()->json(['received' => true]);
    }

    private function hasValidSignature(
        string $payload,
        string $messageId,
        string $timestamp,
        string $signatureHeader,
        string $secret,
    ): bool {
        $key = $this->decodeSigningSecret($secret);

        if ($key === null) {
            return false;
        }

        $signedContent = $messageId.'.'.$timestamp.'.'.$payload;
        $expectedSignature = base64_encode(hash_hmac('sha256', $signedContent, $key, true));
        $candidates = preg_split('/\s+/', trim($signatureHeader)) ?: [];

        foreach ($candidates as $candidate) {
            [$version, $signature] = array_pad(explode(',', $candidate, 2), 2, null);

            if ($version === 'v1' && is_string($signature) && hash_equals($expectedSignature, $signature)) {
                return true;
            }
        }

        return false;
    }

    private function decodeSigningSecret(string $secret): ?string
    {
        $encoded = str_starts_with($secret, 'whsec_') ? substr($secret, 6) : $secret;
        $decoded = base64_decode($encoded, true);

        return $decoded === false ? null : $decoded;
    }
}
