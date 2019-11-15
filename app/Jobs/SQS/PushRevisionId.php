<?php


namespace App\Jobs\SQS;

use Aws\Sqs\SqsClient;
use Aws\Exception\AwsException;
use App\Wiki;

class PushRevisionId {

    public $callback_url;
    public $wd_site;
    public $revision_id;
    public $wd_url;

    public function __construct(int $revision_id, int $wiki_id)
    {
        $wiki = Wiki::find($wiki_id);
        $domain = $wiki->domains->pluck('domain')->first();
        $this->callback_url = 'https://' . $domain . '/api';
        $metadata = json_decode($wiki->metadata, true);
        $this->wd_site = $metadata["wd_site"];
        $this->wd_url = $metadata["wd_url"];
        $this->revision_id = $revision_id;
    }

    public function send(string $queue)
    {
        $client = new SqsClient([
            'region' => env('SQS_REGION'),
            'version' => '2012-11-05',
            'credentials' => [
                'key' => env('SQS_KEY'),
                'secret' => env('SQS_SECRET')
            ]
        ]);

        $params = [
            'DelaySeconds' => 0,
            'MessageAttributes' =>  [
                'wikidot_site' => [
                    'DataType' => 'String',
                    'StringValue' => $this->wd_site
                ],
                'wikidot_url' => [
                    'DataType' => 'String',
                    'StringValue' => $this->wd_url
                ],
                'callback_url' => [
                    'DataType' => 'String',
                    'StringValue' => $this->callback_url
                ],
                'revision_id' => [
                    'DataType' => 'Number',
                    'StringValue' => $this->revision_id
                ]
            ],
            'MessageBody' => bin2hex(random_bytes(8)),
            'QueueUrl' => env('SQS_PREFIX') . '/' . $queue
        ];

        try {
            $result = $client->sendMessage($params);
            var_dump($result);
        } catch (AwsException $e) {
            // output error message if fails
            error_log($e->getMessage());
        }

    }
}
