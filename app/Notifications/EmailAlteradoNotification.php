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
        private readonly string $novoEmail
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Alteração de e-mail da conta')
            ->greeting('Olá, '.$notifiable->name.'!')
            ->line('O e-mail associado à sua conta no Sistema Escolar foi alterado.')
            ->line('E-mail anterior: '.($this->emailAnterior ?: 'não definido'))
            ->line('Novo e-mail: '.$this->novoEmail)
            ->line('Se não reconhece esta alteração, contacte imediatamente a administração.');
    }
}
