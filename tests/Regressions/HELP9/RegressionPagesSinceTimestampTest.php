<?php

namespace Tests\Regressions\HELP9;

use Laravel\Passport\Passport;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\User;

class RegressionPagesSinceTimestampTest extends TestCase {

    use DatabaseTransactions;

    /** @test */
    public function get_pages_since_timestamp_in_v1_API()
    {
        Passport::actingAs(factory(User::class)->create(),['read-metadata']);
        $timestamp = 1500000000;
        $response = $this->get("/api/v1/page/since/$timestamp"); # 2017-07-14
        $response->assertStatus(200);

        # Get a new response using one of the pages to assert its timestamp
        $pageId = $response->getContent()[0]['id'];
        $response = $this->get("/api/v1/page/$pageId");
        $this->assertGreaterThanOrEqual($timestamp, $response->getContent()['metadata']['wd_page_created_at']);
        # This will only test a single page - should eventually test all pages returned in the response
    }
}
