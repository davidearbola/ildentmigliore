<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;

class CustomVerifyEmail extends Notification
{
    use Queueable;

    public function __construct()
    {
        //
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $backendVerificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173') . 
                       '/verify-email?verify_url=' . urlencode($backendVerificationUrl);

        return (new MailMessage)
            ->greeting('Ciao!')
                    ->subject('Verifica il tuo Indirizzo Email')
                    ->line('Per favore, clicca il pulsante qui sotto per verificare il tuo indirizzo email.')
                    ->action('Verifica Indirizzo Email', $frontendUrl)
            ->line('Se non hai creato tu questo account, non è richiesta alcuna ulteriore azione.')
            ->salutation('A presto,');
    }
}