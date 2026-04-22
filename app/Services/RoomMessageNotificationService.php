<?php

namespace App\Services;

use App\Models\Message;
use App\Notifications\RoomMessageNotification;
use Illuminate\Support\Str;

class RoomMessageNotificationService
{
    public function notifyRoomMembers(Message $message): void
    {
        $message->loadMissing(['room.users:id,name,chat_room_notifications_mode', 'user:id,name']);

        $room = $message->room;
        if (! $room) {
            return;
        }

        $senderId = (int) $message->user_id;
        $content = (string) ($message->content ?? '');

        foreach ($room->users as $member) {
            if ((int) $member->id === $senderId) {
                continue;
            }

            $roomMode = (string) ($member->pivot?->notification_mode ?? '');
            $mode = $roomMode !== ''
                ? $roomMode
                : (string) ($member->chat_room_notifications_mode ?? 'all');

            if ($mode === 'none') {
                continue;
            }

            if ($mode === 'mentions' && ! $this->containsMentionForUser($content, (string) $member->name)) {
                continue;
            }

            $member->notify(new RoomMessageNotification($message));
        }
    }

    private function containsMentionForUser(string $content, string $fullName): bool
    {
        $text = mb_strtolower(Str::ascii(trim($content)));
        $name = mb_strtolower(Str::ascii(trim($fullName)));

        if ($text === '' || $name === '') {
            return false;
        }

        if (str_contains($text, $name)) {
            return true;
        }

        $nameParts = preg_split('/\s+/', $name) ?: [];
        foreach ($nameParts as $part) {
            // Ignora partículas muito curtas para reduzir falsos positivos.
            if (mb_strlen($part) < 3) {
                continue;
            }

            if (str_contains($text, $part)) {
                return true;
            }
        }

        return false;
    }
}
