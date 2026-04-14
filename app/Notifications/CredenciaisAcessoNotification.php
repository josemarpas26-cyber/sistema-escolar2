<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CredenciaisAcessoNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $senhaProvisoria
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Credenciais de acesso ao Sistema Escolar')
            ->greeting('Olá, '.$notifiable->name.'!')
            ->line('A sua conta foi criada com uma palavra-passe provisória.')
            ->line('Palavra-passe provisória: '.$this->senhaProvisoria)
            ->line('Por segurança, altere a palavra-passe no primeiro acesso.');
    }
}
