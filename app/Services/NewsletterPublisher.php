<?php

namespace App\Services;

use App\Models\Update;
use App\Models\User;
use App\Notifications\UpdatePublishedNotification;
use Throwable;

class NewsletterPublisher
{
    public function send(Update $update): void
    {
        User::query()
            ->whereNotNull('email_verified_at')
            ->whereNotNull('newsletter_subscribed_at')
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($update): void {
                foreach ($users as $user) {
                    try {
                        $user->notify(new UpdatePublishedNotification($update));
                    } catch (Throwable $exception) {
                        report($exception);
                    }
                }
            });
    }
}
