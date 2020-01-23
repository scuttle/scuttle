<?php

namespace App\Console;

use App\Forum;
use App\Jobs\SQS\PushForumId;
use App\Jobs\SQS\PushPageId;
use App\Jobs\SQS\PushPageSlug;
use App\Jobs\SQS\PushRevisionId;
use App\Jobs\SQS\PushWikidotSite;
use App\Page;
use App\Revision;
use App\Wiki;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Get new pages every minute. Send the most recent slug we have.
        $schedule->call(function() {
            $wikis = Wiki::whereNotNull('metadata->wd_site')->get();
            foreach($wikis as $wiki) {
                $job = new PushWikidotSite($wiki->id);
                $job->send('scuttle-wikis-scheduled-refresh');
            }
        })->everyMinute();

        // Once a day, get all active pages on a wiki, chunk their slugs into groups of 10, and send them as SQS messages.
        // 2stacks will shoot back metadata for those pages.
        $schedule->call(function() {
            $wikis = Wiki::whereNotNull('metadata->wd_site')->get();
            foreach ($wikis as $wiki) {
                $slugs = DB::table('pages')->where('wiki_id', $wiki->id)->where('deleted_at', null)->pluck('slug')->chunk(1000);
                $fifostring = bin2hex(random_bytes(64));
                    foreach ($slugs as $slug) {
                            $job = new App\Jobs\SQS\PushPageSlug($slug->implode(','), $wiki->id);
                            $job->send('scuttle-sched-page-updates.fifo', $fifostring);
                    }
                };
        })->dailyAt('3:00');

        // Once a day, queue requests for fresh vote info for each active page.
        $schedule->call(function() {
            Log::info("Starting new vote refresh job.");
            $wikis = Wiki::whereNotNull('metadata->wd_site')->get();
            foreach ($wikis as $wiki) {
                $activepages = Page::where('wiki_id',$wiki->id)->where('wd_page_id','!=',null)->pluck('wd_page_id');
                Log::info("Queueing " . count($activepages) . " jobs for " . $wiki->subdomain);
                set_time_limit(2700);
                foreach($activepages as $activepage) {
                    $job = new \App\Jobs\SQS\PushPageId($activepage, $wiki->id);
                    $job->send('scuttle-pages-missing-votes');
                }
            }
        })->cron('5 */8 * * *');

        // Once a day, get fresh forum posts. This needs to start from the beginning, i.e., checking for the existence of new forums and everything.
        $schedule->call(function() {
            $fifostring = bin2hex(random_bytes(64));
            $forums = Forum::all();
            foreach ($forums as $forum) {
                $job = new PushForumId($forum->wd_forum_id, $forum->wiki_id);
                $job->send('scuttle-forums-needing-update.fifo', $fifostring);
            }
        })->dailyAt('22:00');

        // Go get all the forums for a particular wikidot site.
        $schedule->call(function() {
            $wikis = Wiki::whereNotNull('metadata->wd_site')->get();
            foreach ($wikis as $wiki) {
                $job = new PushWikidotSite($wiki->id);
                $job->send('scuttle-forums-missing-metadata');
            }
        })->dailyAt('6:00');

        // Daily Maintenance:
        // Go find missing revisions daily.
        $schedule->call(function() {
            $revs = Revision::where('needs_content', 1)->get();
            foreach($revs as $rev) {
                $job = new \App\Jobs\SQS\PushRevisionId($rev->wd_revision_id, $rev->page->wiki->id);
                $job->send('scuttle-revisions-missing-content');
            }
        })->dailyAt('4:30');


        // Run maintenance tasks daily.
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
