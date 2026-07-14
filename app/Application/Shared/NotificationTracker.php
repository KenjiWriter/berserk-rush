<?php

namespace App\Application\Shared;

class NotificationTracker
{
    private static array $notifications = [];

    public function addSuccess(string $message): void
    {
        self::$notifications[] = [
            'type' => 'success',
            'message' => $message
        ];
    }

    public function addInfo(string $message): void
    {
        self::$notifications[] = [
            'type' => 'info',
            'message' => $message
        ];
    }

    public function flush(): array
    {
        $flushed = self::$notifications;
        self::$notifications = [];
        return $flushed;
    }
}
