<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class CustomResetPasswordNotification extends ResetPassword
{
    /**
     * Get the reset password notification mail message.
     */
    public function toMail($notifiable)
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('Redefinição de Senha — ' . config('app.name'))
            ->greeting('Olá!')
            ->line('Recebemos uma solicitação para redefinir a senha da sua conta no **' . config('app.name') . '**. Clique no botão abaixo para criar uma nova senha segura.')
            ->action('Redefinir Senha', $url)
            ->line('⏱ Este link expira em **60 minutos**. Após esse prazo, solicite uma nova redefinição.')
            ->line('Se você não solicitou esta redefinição, ignore este e-mail — a sua conta permanece segura.')
            ->salutation('Cumprimentos, ' . config('app.name'));
    }
}