<?php

namespace Tests\Feature;

use Laravel\Passport\Passport;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\User;

class PagesSinceTimestampTest extends TestCase {

    use DatabaseTransactions;

    /** @test */
    public function get_pages_since_timestamp_in_v1_API()
    {
        # Given an authenticated user with access to the read-metadata and read-article scopes...
        Passport::actingAs(factory(User::class)->create(),['read-metadata', 'read-article']);

        # When an illegal timestamp is sent...
        $timestamp = "cats";
        $response = $this->getJson("/api/v1/page/since/$timestamp");

        # Then an HTTP 422 Unprocessable Entity response is returned, with an explanation.
        $response->assertStatus(422);
        $errorResponse = json_decode($response->getContent(), true);
        $this->assertEquals("The given data was invalid.", $errorResponse["message"]);
        $this->assertEquals("The timestamp must be an integer.", $errorResponse["errors"]["timestamp"][0]);

        ###

        # When a valid timestamp (2020-06-12) is sent...
        $timestamp = 1591994817;
        $response = $this->getJson("/api/v1/page/since/$timestamp");

        # Then we get a valid payload.
        $response->assertStatus(200);
        $this->assertJson($response->content());

        ###

        # When we take the value of an item returned and look it up via a different API route...
        $payload = json_decode($response->getContent(), true);
        $pageId = $payload[0]['id'];
        $response = $this->get("/api/v1/page/$pageId");
        $retrievedPage = json_decode($response->getContent(), true);

        # Then we will receive more information about the same page as what was in our digest.
        $this->assertEquals($retrievedPage['id'], $pageId);
        $this->assertEquals($retrievedPage['wd_page_id'], $payload[0]['wd_page_id']);
        $this->assertGreaterThanOrEqual($timestamp, $retrievedPage['metadata']['wd_page_created_at']);
    }

    /** @test */
    public function post_pages_since_timestamp_in_v1_API()
    {
        # Given an authenticated user with access to the read-metadata and read-article scopes...
        Passport::actingAs(factory(User::class)->create(),['read-metadata', 'read-article']);

        # When an illegal timestamp is sent in the URI...
        $timestamp = "cats";
        $response = $this->postJson("/api/v1/page/since/$timestamp");

        # Then an HTTP 422 Unprocessable Entity response is returned, with an explanation.
        $response->assertStatus(422);
        $errorResponse = json_decode($response->getContent(), true);
        $this->assertEquals("The given data was invalid.", $errorResponse["message"]);
        $this->assertEquals("The timestamp must be an integer.", $errorResponse["errors"]["timestamp"][0]);

        ###

        # When a valid timestamp (2020-06-12) is sent...
        $timestamp = 1591994817;
        $response = $this->postJson("/api/v1/page/since/$timestamp");

        # Then we get a valid payload.
        $response->assertStatus(200);
        $this->assertJson($response->content());

        ###

        # When we take the value of an item returned and look it up via a different API route...
        $payload = json_decode($response->getContent(), true);
        $pageId = $payload[0]['id'];
        $response = $this->get("/api/v1/page/$pageId");
        $retrievedPage = json_decode($response->getContent(), true);

        # Then we will receive more information about the same page as what was in our digest.
        $this->assertEquals($retrievedPage['id'], $pageId);
        $this->assertEquals($retrievedPage['wd_page_id'], $payload[0]['wd_page_id']);
        $this->assertGreaterThanOrEqual($timestamp, $retrievedPage['metadata']['wd_page_created_at']);

        ###

        # When we use an explicit limit...
        $response = $this->postJson("/api/v1/page/since/$timestamp", [
            'limit' => 3
        ]);

        # Then we will get that many items back.
        $this->assertCount(3, json_decode($response->getContent(), true));

        ###

        # When we use illegal arguments in the post...
        $response = $this->postJson("/api/v1/page/since/$timestamp", [
            'limit' => 9999,
            'offset' => -36,
            'direction' => 'nope',
        ]);

        # Then we will get a 422 instead.
        $response->assertStatus(422);
        $errorResponse = json_decode($response->getContent(), true);
        $this->assertEquals("The given data was invalid.", $errorResponse["message"]);
        $this->assertEquals("The limit may not be greater than 100.", $errorResponse["errors"]["limit"][0]);
        $this->assertEquals("The offset must be at least 0.", $errorResponse["errors"]["offset"][0]);
        $this->assertEquals("The selected direction is invalid.", $errorResponse["errors"]["direction"][0]);


    }
}
