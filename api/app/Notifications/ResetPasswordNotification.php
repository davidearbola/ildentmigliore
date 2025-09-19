<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $url = env('FRONTEND_URL', 'http://localhost:5173') . 
               '/reset-password?token=' . $this->token . 
               '&email=' . urlencode($notifiable->getEmailForPasswordReset());
               
               
               return (new MailMessage)
            ->greeting('Ciao!')
                    ->subject('Notifica di Reset Password')
                    ->line('Stai ricevendo questa email perché abbiamo ricevuto una richiesta di reset password per il tuo account.')
                    ->action('Reset Password', $url)
                    ->line('Questo link di reset scadrà tra 60 minuti.')
            ->line('Se non hai richiesto tu il reset della password, non è richiesta alcuna ulteriore azione.')
            ->salutation('Grazie');
    }

    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}