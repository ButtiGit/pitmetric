<?php

function resendWebhookSignature(string $payload, string $messageId, string $timestamp, string $secret): string
{
    return 'v1,'.base64_encode(hash_hmac('sha256', $messageId.'.'.$timestamp.'.'.$payload, $secret, true));
}

it('accepts a valid signed inbound email event', function () {
    $rawSecret = 'pitmetric-test-webhook-secret';
    config()->set('services.resend.webhook_secret', 'whsec_'.base64_encode($rawSecret));

    $messageId = 'msg_test_received';
    $timestamp = (string) time();
    $payload = json_encode([
        'type' => 'email.received',
        'created_at' => now()->toIso8601String(),
        'data' => [
            'email_id' => 'email_test_123',
            'message_id' => '<test@example.com>',
            'from' => 'Sender <sender@example.com>',
            'to' => ['test@reply.pitmetric.it'],
            'cc' => [],
            'subject' => 'Test ricezione PitMetric',
            'attachments' => [],
        ],
    ], JSON_THROW_ON_ERROR);

    $response = $this->call('POST', '/webhooks/resend', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_SVIX_ID' => $messageId,
        'HTTP_SVIX_TIMESTAMP' => $timestamp,
        'HTTP_SVIX_SIGNATURE' => resendWebhookSignature($payload, $messageId, $timestamp, $rawSecret),
    ], $payload);

    $response->assertOk()->assertJson(['received' => true]);
});

it('rejects a webhook with an invalid signature', function () {
    config()->set('services.resend.webhook_secret', 'whsec_'.base64_encode('pitmetric-test-webhook-secret'));

    $timestamp = (string) time();
    $payload = json_encode(['type' => 'email.received', 'data' => []], JSON_THROW_ON_ERROR);

    $response = $this->call('POST', '/webhooks/resend', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_SVIX_ID' => 'msg_invalid',
        'HTTP_SVIX_TIMESTAMP' => $timestamp,
        'HTTP_SVIX_SIGNATURE' => 'v1,invalid-signature',
    ], $payload);

    $response->assertUnauthorized()->assertJson(['message' => 'Invalid webhook signature.']);
});

it('fails closed when the webhook secret is not configured', function () {
    config()->set('services.resend.webhook_secret', null);

    $response = $this->postJson('/webhooks/resend', ['type' => 'email.received']);

    $response->assertStatus(503)->assertJson(['message' => 'Webhook secret not configured.']);
});
