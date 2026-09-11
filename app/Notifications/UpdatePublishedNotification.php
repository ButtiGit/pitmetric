<?php

namespace App\Notifications;

use App\Models\Update;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class UpdatePublishedNotification extends Notification
{
    use Queueable;

    public function __construct(public Update $update) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locale = ($notifiable->newsletter_locale ?? 'en') === 'it' ? 'it' : 'en';
        $title = $this->update->titleForLocale($locale);
        $excerpt = $this->update->excerptForLocale($locale);
        $unsubscribe = URL::signedRoute('newsletter.unsubscribe', ['user' => $notifiable->getKey()]);

        if ($locale === 'it') {
            return (new MailMessage)
                ->subject('Nuovo aggiornamento PitMetric · '.$title)
                ->greeting('Nuovo dal development log')
                ->line($title)
                ->line($excerpt)
                ->action('Leggi l’aggiornamento', route('updates.show', $this->update))
                ->line('Ricevi questa email perché hai scelto di iscriverti agli aggiornamenti PitMetric.')
                ->action('Disiscriviti dalla newsletter', $unsubscribe);
        }

        return (new MailMessage)
            ->subject('New PitMetric update · '.$title)
            ->greeting('New from the development log')
            ->line($title)
            ->line($excerpt)
            ->action('Read the update', route('updates.show', $this->update))
            ->line('You are receiving this because you opted in to PitMetric development updates.')
            ->action('Unsubscribe from the newsletter', $unsubscribe);
    }
}
