<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class CustomResetPasswordNotification extends ResetPassword
{
    /**
     * Get the reset password notification mail message.
     */
    public function toMail($notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('Redefinição de Senha - IPIKK')
            ->greeting('Olá!')
            ->line('Recebemos uma solicitação para redefinir a senha da sua conta no Sistema IPIKK.')
            ->action('Redefinir Senha', $url)
            ->line('Este link de redefinição de senha expira em 60 minutos.')
            ->line('Se você não solicitou a redefinição de senha, nenhuma ação adicional é necessária.')
            ->salutation('Cumprimentos, Sistema IPIKK');
    }
}