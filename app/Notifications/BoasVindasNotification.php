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
        return (new MailMessage)
            ->subject('Bem-vindo ao Sistema Escolar')
            ->greeting('Olá, '.$notifiable->name.'!')
            ->line('Seja bem-vindo ao Sistema Escolar.')
            ->line('A sua conta foi criada com sucesso e já pode aceder à plataforma.')
            ->line('Se precisar de apoio, contacte a secretaria ou a administração da escola.');
    }
}
