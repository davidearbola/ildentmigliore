<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\ContropropostaMedico;

class NuovaPropostaNotification extends Notification implements ShouldQueue
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
        $medico = $this->proposta->medico;
        // L'URL punterà al frontend
        $url = env('FRONTEND_URL') . '/dashboard/proposte';

        return (new MailMessage)
                    ->subject('Hai ricevuto una nuova proposta!')
                    ->greeting('Ciao ' . $notifiable->name . ',')
                    ->line('Una buona notizia! Lo studio medico "' . $medico->anagraficaMedico->ragione_sociale . '" ti ha inviato una nuova proposta.')
                    ->line('Accedi alla piattaforma per visualizzare i dettagli e confrontarla con le altre.')
                    ->action('Vedi le tue proposte', $url)
            ->line('Grazie per usare la nostra piattaforma!')
            ->salutation('A presto,');
    }
}