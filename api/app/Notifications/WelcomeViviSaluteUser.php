<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\User;

class WelcomeViviSaluteUser extends Notification
{
    use Queueable;

    protected string $password;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $password)
    {
        $this->password = $password;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $loginUrl = env('FRONTEND_URL', 'http://localhost:5173') . '/login';
        
        return (new MailMessage)
            ->subject('Benvenuto su Il Dentista Migliore!')
            ->greeting("Buongiorno Dott. {$notifiable->name},")
            ->line('Siamo felici di darti il benvenuto nel nostro network! Un nostro consulente ti ha appena iscritto alla nostra piattaforma.')
            ->line('Puoi accedere usando le seguenti credenziali:')
            ->line("**Email:** {$notifiable->email}")
            ->line("**Password Temporanea:** {$this->password}")
            ->line('Ti verrà richiesto di cambiare la password al tuo primo accesso per motivi di sicurezza.')
            ->action('Accedi Ora', $loginUrl)
            ->line('Grazie per esserti unito a noi!')
            ->salutation(' ');
    }
}
