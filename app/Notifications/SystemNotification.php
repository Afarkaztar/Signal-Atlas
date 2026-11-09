<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi in-app generik (disimpan di tabel notifications).
 */
class SystemNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public ?string $url = null,
        public string $icon = 'bell',
        public string $tone = 'info',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $path = $this->url ? (parse_url($this->url, PHP_URL_PATH) ?: $this->url) : null;

        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $path,
            'icon' => $this->icon,
            'tone' => $this->tone,
        ];
    }
}
