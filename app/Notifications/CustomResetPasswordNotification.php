<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class CustomResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $expireMinutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return (new MailMessage)
            ->subject('Redefinição de palavra-passe — ' . config('app.name'))
            ->greeting('Olá!')
            ->line('Recebemos um pedido para redefinir a palavra-passe da sua conta no **' . config('app.name') . '**.')
            ->line('Clique no botão abaixo para criar uma nova palavra-passe. O link é válido por **' . $expireMinutes . ' minutos**.')
            ->action('Redefinir Palavra-passe', $url)
            ->line('Se não solicitou esta redefinição, ignore este e-mail. A sua conta permanece segura e nenhuma alteração foi efectuada.')
            ->salutation('Com os melhores cumprimentos, ' . config('app.name'));
    }
}