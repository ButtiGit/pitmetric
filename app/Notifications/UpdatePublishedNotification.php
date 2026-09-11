<?php

namespace App\Notifications;

use App\Models\Update;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use LogicException;

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
        if (! $notifiable instanceof User) {
            throw new LogicException('PitMetric newsletter notifications can only be sent to users.');
        }

        $locale = $notifiable->newsletter_locale === 'it' ? 'it' : 'en';
        $title = $this->update->titleForLocale($locale);
        $excerpt = $this->update->excerptForLocale($locale);
        $updateUrl = route('updates.show', ['update' => $this->update->slug]);
        $unsubscribeUrl = URL::signedRoute('newsletter.unsubscribe', ['user' => $notifiable->id]);

        if ($locale === 'it') {
            return (new MailMessage)
                ->subject('Nuovo aggiornamento PitMetric · '.$title)
                ->greeting('Nuovo dal development log')
                ->line($title)
                ->line($excerpt)
                ->action('Leggi l’aggiornamento', $updateUrl)
                ->line('Ricevi questa email perché hai scelto di iscriverti agli aggiornamenti PitMetric.')
                ->line('Per non ricevere più queste email puoi disiscriverti qui: '.$unsubscribeUrl);
        }

        return (new MailMessage)
            ->subject('New PitMetric update · '.$title)
            ->greeting('New from the development log')
            ->line($title)
            ->line($excerpt)
            ->action('Read the update', $updateUrl)
            ->line('You are receiving this because you opted in to PitMetric development updates.')
            ->line('To stop receiving these emails, unsubscribe here: '.$unsubscribeUrl);
    }
}
