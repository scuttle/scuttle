<?php

use App\Notifications\PostJobStatusToDiscord;
use Illuminate\Support\Facades\Notification;

function discord($message): void {
    Notification::route('discord', env('DISCORD_BOT_CHANNEL'))->notify(new PostJobStatusToDiscord(
        $message
    ));
    return;
}
