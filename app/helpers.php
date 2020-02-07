<?php

use App\Notifications\PostJobStatusToDiscord;
use Illuminate\Support\Facades\Notification;

function discord($message): void {
    $job = new PostJobStatusToDiscord($message);
    Notification::route('discord', env('DISCORD_BOT_CHANNEL'))->notify($job);
    return;
}
