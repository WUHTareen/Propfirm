<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A contact-form submission from the public site, emailed to the support
 * inbox. Sent on-demand via Notification::route('mail', ...).
 */
class ContactMessage extends Notification
{
    use Queueable;

    /**
     * @param  array{name: string, email: string, subject?: ?string, message: string}  $data
     */
    public function __construct(public array $data)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->data['subject'] ?? null;

        return (new MailMessage)
            ->subject('Contact form: '.($subject ?: 'New message'))
            ->replyTo($this->data['email'], $this->data['name'])
            ->greeting('New contact message')
            ->line('From: '.$this->data['name'].' <'.$this->data['email'].'>')
            ->when($subject, fn (MailMessage $m) => $m->line('Subject: '.$subject))
            ->line('Message:')
            ->line($this->data['message']);
    }
}
