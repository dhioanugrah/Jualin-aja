<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Notifikasi umum untuk penawaran, booking, dan pelanggaran (F12).
 * Formatnya sama dengan ProductModerated: judul, isi, tautan, dan nada warna.
 */
class TransactionUpdate extends Notification
{
    /**
     * @param  'info'|'success'|'warning'|'danger'  $tone
     */
    public function __construct(
        public string $title,
        public string $body,
        public string $url,
        public string $tone = 'info',
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{title: string, body: string, url: string, tone: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
            'tone' => $this->tone,
        ];
    }
}
