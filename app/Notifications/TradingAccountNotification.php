<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * A simple in-app (database) notification about a trader's account —
 * credentials issued, phase passed, funded, or breached. Email templates
 * are added once SMTP is configured; database delivery always works.
 */
class TradingAccountNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public int $accountId,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'account_id' => $this->accountId,
        ];
    }
}
