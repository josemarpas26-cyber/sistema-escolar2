<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BoasVindasNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $firstName = explode(' ', trim($notifiable->name))[0];

        return (new MailMessage)
            ->subject('Bem-vindo(a) ao ' . config('app.name') . ' 🎓')
            ->greeting('Olá, ' . $firstName . '!')
            ->line('A sua conta foi criada com sucesso. Estamos felizes em tê-lo(a) na nossa plataforma.')
            ->line('Através do **' . config('app.name') . '** pode acompanhar notas, pautas, boletins e muito mais — tudo num só lugar.')
            ->action('Aceder à Plataforma', url('/'))
            ->line('Se tiver dúvidas ou precisar de apoio no primeiro acesso, a secretaria ou a administração da escola estão disponíveis para ajudar.')
            ->salutation('Com os melhores cumprimentos, ' . config('app.name'));
    }
}