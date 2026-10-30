<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class ResendStudioMailbox
{
    /** @return array{data: list<array<string, mixed>>, has_more?: bool} */
    public function received(int $limit = 50, ?string $after = null): array
    {
        return $this->listing('/emails/receiving', $limit, $after);
    }

    /** @return array{data: list<array<string, mixed>>, has_more?: bool} */
    public function sent(int $limit = 100, ?string $after = null): array
    {
        return $this->listing('/emails', $limit, $after);
    }

    /** @return array{data: list<array<string, mixed>>, has_more?: bool} */
    private function listing(string $path, int $limit, ?string $after): array
    {
        $payload = $this->request()
            ->get($path, array_filter(['limit' => max(1, min($limit, 100)), 'after' => $after]))
            ->throw()
            ->json();

        /** @var list<array<string, mixed>> $emails */
        $emails = [];

        if (is_array($payload) && isset($payload['data']) && is_array($payload['data'])) {
            foreach ($payload['data'] as $item) {
                if (is_array($item)) {
                    /** @var array<string, mixed> $item */
                    $emails[] = $item;
                }
            }
        }

        $result = ['data' => $emails];

        if (is_array($payload) && array_key_exists('has_more', $payload)) {
            $result['has_more'] = (bool) $payload['has_more'];
        }

        return $result;
    }

    /** @return array<string, mixed> */
    public function receivedEmail(string $emailId): array
    {
        return $this->email('/emails/receiving/', $emailId);
    }

    /** @return array<string, mixed> */
    public function sentEmail(string $emailId): array
    {
        return $this->email('/emails/', $emailId);
    }

    /** @return array<string, mixed> */
    private function email(string $path, string $emailId): array
    {
        $payload = $this->request()
            ->get($path.rawurlencode($emailId))
            ->throw()
            ->json();

        if (! is_array($payload)) {
            throw new RuntimeException('Resend returned an invalid inbound email payload.');
        }

        /** @var array<string, mixed> $payload */
        return $payload;
    }

    private function request(): PendingRequest
    {
        $key = trim((string) config('services.resend.studio_key', ''));

        if ($key === '') {
            throw new RuntimeException('RESEND_STUDIO_KEY is not configured.');
        }

        return Http::baseUrl('https://api.resend.com')
            ->acceptJson()
            ->withToken($key)
            ->connectTimeout(5)
            ->timeout(15)
            ->retry([500, 1000], throw: false);
    }
}
