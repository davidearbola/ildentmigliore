<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\ContropropostaMedico;

class PropostaAccettataMedicoNotification extends Notification implements ShouldQueue
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
            ->subject('Congratulazioni! Una sua proposta è stata accettata')
            ->greeting('Buongiorno Dott. ' . $notifiable->name . ',')
            ->line('Il paziente ' . $paziente->name . ' ha accettato la sua proposta.')
            ->line('Potrà ora visualizzare i suoi dettagli di contatto nella sua dashboard per organizzare una visita.')
            ->action('Vedi Proposte Accettate', $url)
            ->salutation('Cordiali saluti,');
    }
}
