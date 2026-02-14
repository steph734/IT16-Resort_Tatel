<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class AdminResetPasswordNotification extends ResetPassword
{
    /**
     * Get the reset password notification mail message for the admin.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Admin Password Reset Notification')
            ->line('You are receiving this email because we received a password reset request for your admin account.')
            ->action('Reset Password', url(route('admin.password.reset', [
                'token' => $this->token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false)))
            ->line('If you did not request a password reset, no further action is required.');
    }
}
