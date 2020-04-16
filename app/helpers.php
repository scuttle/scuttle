<?php

use App\Notifications\PostJobStatusToDiscord;
use Illuminate\Support\Facades\Notification;

// This is an alternative to array_diff that works about 1400x faster.
// It will find a single mismatch in a pair of 11,000+ item lists in under 2 seconds on a single core.
// https://stackoverflow.com/a/6700430/3946227
function leo_array_diff($a, $b) {
    $map = array();
    foreach($a as $val) $map[$val] = 1;
    foreach($b as $val) unset($map[$val]);
    return array_keys($map);
}

function discord($type, $message): void {

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
        'page-new' => [
            'title' => 'New Page',
            'emoji' => '<:eyesss:619357671799259147>',
        ],
        'page-missing' => [
            'title' => 'Missing Page',
            'emoji' => '🧐',
        ],
        'page-deleted' => [
            'title' => 'Page Deleted',
            'emoji' => '<:rip:619357639880605726>',
        ],
        'page-moved' => [
            'title' => 'Page Moved',
            'emoji' => '➡️',
        ],
        'page-updated' => [
            'title' => 'Page Updated',
            'emoji' => '🔄️',
        ],
        'security' => [
            'title' => 'Security Advisory',
            'emoji' => '<:ping:619357511081787393>',
        ],
    ];

    $embedColor = 6513507; // hex #999

    $template = $messageTypes[$type];

    $embed = (object) [
        'title' => "**".$template["title"]."**",
        'description' => $template["emoji"]." ".$message,
        'color' => $embedColor,
    ];
    try {
        $job = new PostJobStatusToDiscord($embed);
        Notification::route('discord', env('DISCORD_BOT_CHANNEL'))->notify($job);
    } catch (\NotificationChannels\Discord\Exceptions\CouldNotSendNotification $e) {
        \Illuminate\Support\Facades\Log::info('Discord message didn\'t send: '.$e->getMessage());
    }
    return;
}
