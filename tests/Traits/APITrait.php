<?php

namespace Tests\Traits;

trait APITrait {

    protected $response;

    public function test_invalid_posts($uri)
    {
        # When we use illegal arguments in the post...
        $response = $this->postJson($uri, [
            'limit' => 9999,
            'offset' => -36,
            'direction' => 'nope',
        ]);

        # Then we will get a 422 instead, and explanations on what the problem is..
        $response->assertStatus(422);
        $errorResponse = json_decode($response->getContent(), true);
        $this->assertEquals("The given data was invalid.", $errorResponse["message"]);
        $this->assertEquals("The limit may not be greater than 100.", $errorResponse["errors"]["limit"][0]);
        $this->assertEquals("The offset must be at least 0.", $errorResponse["errors"]["offset"][0]);
        $this->assertEquals("The selected direction is invalid.", $errorResponse["errors"]["direction"][0]);
    }

    public function test_specified_limit($uri)
    {
        # When we use an explicit limit...
        $response = $this->postJson($uri, [
            'limit' => 3
        ]);

        # Then we will get that many items back.
        $this->assertCount(3, json_decode($response->getContent(), true));
    }
}
