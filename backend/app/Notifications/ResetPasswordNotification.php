<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Password reset mail pointing at the SPA.
 *
 * Laravel's default builds a link to a Blade route this API does not serve, so
 * the token is handed to the Vue app instead. The email is carried in the URL
 * because Laravel's broker verifies the token against it on submission.
 */
class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $token,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $minutes = config('auth.passwords.users.expire', 60);

        return (new MailMessage())
            ->subject('Réinitialisez votre mot de passe Nuvo')
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Vous avez demandé à réinitialiser votre mot de passe Nuvo.')
            ->action('Choisir un nouveau mot de passe', $this->resetUrl($notifiable))
            ->line("Ce lien expire dans {$minutes} minutes.")
            ->line('Si vous n\'êtes pas à l\'origine de cette demande, ignorez cet email : votre mot de passe reste inchangé.')
            ->salutation('L\'équipe Nuvo');
    }

    private function resetUrl(object $notifiable): string
    {
        return config('app.frontend_url') . '/reset-password?' . http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }
}
