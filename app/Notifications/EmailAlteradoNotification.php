<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailAlteradoNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ?string $emailAnterior,
        private readonly string  $novoEmail
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $firstName = explode(' ', trim($notifiable->name))[0];

        return (new MailMessage)
            ->subject('O seu endereço de e-mail foi alterado — ' . config('app.name'))
            ->greeting('Olá, ' . $firstName . '!')
            ->line('Informamos que o endereço de e-mail associado à sua conta no **' . config('app.name') . '** foi alterado.')
            ->line('**E-mail anterior:** ' . ($this->emailAnterior ?: 'não definido'))
            ->line('**Novo e-mail:** ' . $this->novoEmail)
            ->line('⚠️ Se não reconhece esta alteração, contacte **imediatamente** a administração da escola, pois pode indicar acesso não autorizado à sua conta.')
            ->action('Contactar a Administração', url('/'))
            ->salutation('Com os melhores cumprimentos, ' . config('app.name'));
    }
}