<?php

namespace App\Console;

use App\Jobs\SQS\PushPageSlug;
use App\Jobs\SQS\PushRevisionId;
use App\Jobs\SQS\PushWikidotSite;
use App\Page;
use App\Revision;
use App\Wiki;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

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
                $slug = Page::where('wiki_id',$wiki->id)->orderBy('wd_page_id','desc')->pluck('slug')->first();

                $job = new PushPageSlug($slug, $wiki->id);
                $job->send('scuttle-wikis-scheduled-refresh');
            }
        })->everyMinute();

        // Tell 2stacks to send us a page manifest every minute so we can note deleted pages.

        // Also note, every

        // Go find missing revisions hourly.
        $schedule->call(function() {
            $revs = Revision::where('needs_content', 1)->get();
            foreach($revs as $rev) {
                $job = new \App\Jobs\SQS\PushRevisionId($rev->wd_revision_id, $rev->page->wiki->id);
                $job->send('scuttle-revisions-missing-content');
            }
        })->hourly();


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
