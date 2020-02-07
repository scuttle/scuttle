<?php

use App\Notifications\PostJobStatusToDiscord;
use Illuminate\Support\Facades\Notification;

$embedColor = 6513507; // hex #999

$messageTypes = [
    '2stacks-sched-get-page-metas' => [
        'title' => 'Page Metadata Job',
        'emoji' => '<:scp:619361872449372200>',
    ],
    '2stacks-queue-vote-job' => [
        'title' => 'Page Vote Job',
        'emoji' => '<:scp:619361872449372200>',
    ],
    '2stacks-get-forum-threads' => [
        'title' => 'Forum Thread Job',
        'emoji' => '<:scp:619361872449372200>',
    ],
    '2stacks-get-forum-categories' => [
        'title' => 'Forum Category Job',
        'emoji' => '<:scp:619361872449372200>',
    ],
    '2stacks-get-revision-content' => [
        'title' => 'Revision Content Job',
        'emoji' => '<:scp:619361872449372200>',
    ],
    'new-page' => [
        'title' => 'New Page',
        'emoji' => '<:eyesss:619357671799259147>',
    ],
];

function discord($type, $message): void {
    $template = $messageTypes[$type];

    $embed = (object) [
        'title' => "**$template->title**",
        'description' => "$template->emoji $message",
        'color' => $embedColor,
    ];

    $job = new PostJobStatusToDiscord($embed);
    Notification::route('discord', env('DISCORD_BOT_CHANNEL'))->notify($job);
    return;
}
