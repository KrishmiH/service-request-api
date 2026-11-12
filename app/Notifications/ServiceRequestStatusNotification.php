<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ServiceRequestStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $requestTitle,
        public string $status,
        public string $message
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'request_title' => $this->requestTitle,
            'status' => $this->status,
            'message' => $this->message,
        ];
    }
}
