<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class PushPageSlug implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $slug;

    /**
     * Create a new job instance.
     *
     * @param string $slug
     */
    public function __construct(string $slug)
    {
        $this->slug = $slug;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::error('Job PushPageSlug was handled by SCUTTLE instead of 2stacks.');
        // Empty set. 2stacks handles the other side of this job.
    }
}
