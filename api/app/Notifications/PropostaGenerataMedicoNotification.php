<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\ContropropostaMedico;

class PropostaGenerataMedicoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $proposta;

    public function __construct(ContropropostaMedico $proposta)
    {
        $this->proposta = $proposta;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $paziente = $this->proposta->preventivoPaziente->anagraficaPaziente->user;
        $url = config('app.frontend_url') . '/dashboard/preventivi-accettati';
        return (new MailMessage)
            ->subject('Nuova Proposta Automatica Generata')
            ->greeting('Buongiorno Dott. ' . $notifiable->name . ',')
            ->line('Il nostro sistema ha generato automaticamente una nuova proposta per il preventivo di ' . $paziente->name . '.')
            ->line('Non è richiesta alcuna azione da parte sua. Verrà notificato se il paziente accetterà la proposta.')
            ->action('Vedi i Tuoi Preventivi', $url)
            ->salutation('Cordiali saluti,');
    }
}
