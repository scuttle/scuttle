<?php

namespace Tests\Regressions\SCUTTLE9;

use Laravel\Passport\Passport;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\User;

class RegressionSlugsInColonsTest extends TestCase {

    use DatabaseTransactions;

    /** @test */
    public function allow_colons_in_slugs_in_v1_API()
    {
        Passport::actingAs(factory(User::class)->create(),['read-article']);
        $response = $this->get('/api/v1/page/slug/component:theme');
        $response->assertStatus(200);

        $response->assertJson(['slug' => 'component:theme']);
    }
}
