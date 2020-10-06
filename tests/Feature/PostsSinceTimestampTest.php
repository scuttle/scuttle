<?php

namespace Tests\Feature;

use Laravel\Passport\Passport;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\User;

class PostsSinceTimestampTest extends TestCase {

    use DatabaseTransactions;

    /** @test */
    public function get_posts_since_timestamp_in_v1_API()
    {
        # Given an authenticated user with access to the read-metadata and read-article scopes...
        Passport::actingAs(factory(User::class)->create(),['read-post']);

        # When an illegal timestamp is sent...
        $timestamp = "cats";
        $response = $this->getJson("/api/v1/post/since/$timestamp");

        # Then an HTTP 422 Unprocessable Entity response is returned, with an explanation.
        $response->assertStatus(422);

        ###

        # When a valid timestamp (2020-06-12) is sent...
        $timestamp = 1591994817;
        $response = $this->getJson("/api/v1/post/since/$timestamp");

        # Then we get a valid payload.
        $response->assertStatus(200);
        $this->assertJson($response->content());

        ###

        # When we take the value of an item returned and look it up via a different API route...
        $payload = json_decode($response->getContent(), true);
        $postId = $payload[0]['id'];
        $response = $this->get("/api/v1/post/$postId");
        $retrievedPost = json_decode($response->getContent(), true);

        # Then we will receive more information about the same post as what was in our digest.
        $this->assertEquals($retrievedPost['id'], $postId);
        $this->assertEquals($retrievedPost['wd_post_id'], $payload[0]['wd_post_id']);
    }

    /** @test */
    public function post_posts_since_timestamp_in_v1_API()
    {
        # Given an authenticated user with access to the read-metadata and read-article scopes...
        Passport::actingAs(factory(User::class)->create(),['read-post']);

        # When an illegal timestamp is sent in the URI...
        $timestamp = "cats";
        $response = $this->postJson("/api/v1/post/since/$timestamp");

        # Then an HTTP 422 Unprocessable Entity response is returned, with an explanation.
        $response->assertStatus(422);

        ###

        # When a valid timestamp (2020-06-12) is sent...
        $timestamp = 1591994817;
        $response = $this->postJson("/api/v1/post/since/$timestamp");

        # Then we get a valid payload.
        $response->assertStatus(200);
        $this->assertJson($response->content());

        ###

        # When we take the value of an item returned and look it up via a different API route...
        $payload = json_decode($response->getContent(), true);
        $postId = $payload[0]['id'];
        $response = $this->get("/api/v1/post/$postId");
        $retrievedPost = json_decode($response->getContent(), true);

        # Then we will receive more information about the same post as what was in our digest.
        $this->assertEquals($retrievedPost['id'], $postId);
        $this->assertEquals($retrievedPost['wd_post_id'], $payload[0]['wd_post_id']);

        ###

        # When we use an explicit limit...
        $response = $this->postJson("/api/v1/post/since/$timestamp", [
            'limit' => 3
        ]);

        # Then we will get that many items back.
        $this->assertCount(3, json_decode($response->getContent(), true));

        ###

        # When we use illegal arguments in the post...
        $response = $this->postJson("/api/v1/post/since/$timestamp", [
            'limit' => 9999,
            'offset' => -36,
            'direction' => 'nope',
        ]);

        # Then we will get a 422 instead.
        $response->assertStatus(422);
    }
}
