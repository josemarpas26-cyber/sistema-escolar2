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
        $firstName = explode(' ', trim($notifiable->name))[0];

        return (new MailMessage)
            ->subject('As suas credenciais de acesso — ' . config('app.name'))
            ->greeting('Olá, ' . $firstName . '!')
            ->line('A sua conta no **' . config('app.name') . '** está pronta. Abaixo encontra as credenciais para o primeiro acesso:')
            ->line('**Utilizador (e-mail):** ' . $notifiable->email)
            ->line('**Palavra-passe provisória:** `' . $this->senhaProvisoria . '`')
            ->action('Entrar na Plataforma', url('/'))
            ->line('⚠️ Por segurança, altere a palavra-passe imediatamente após o primeiro início de sessão. Nunca partilhe as suas credenciais com terceiros.')
            ->salutation('Com os melhores cumprimentos, ' . config('app.name'));
    }
}