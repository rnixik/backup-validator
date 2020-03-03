<?php

namespace BackupValidator\Alerting\Channels;

use BackupValidator\Alerting\ChannelInterface;
use BackupValidator\Alerting\Traits\ChannelWithSubjectAndBodyTrait;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class Slack implements ChannelInterface
{
    use ChannelWithSubjectAndBodyTrait;

    private $webhookUrl;

    public function initialize(array $channelConfig)
    {
        $this->webhookUrl = $channelConfig['webhook'];
        if (isset($channelConfig['subject'])) {
            $this->subject = $channelConfig['subject'];
        }
        if (isset($channelConfig['subject_test'])) {
            $this->subjectTest = $channelConfig['subject_test'];
        }
        if (isset($channelConfig['body'])) {
            $this->body = $channelConfig['body'];
        }
        if (isset($channelConfig['body_test'])) {
            $this->bodyTest = $channelConfig['body_test'];
        }
    }

    public function send(bool $isAlertTesting, string $backupName, string $output)
    {
        $subject = $this->getSubject($isAlertTesting, $backupName, $output);
        $body = $this->getBody($isAlertTesting, $backupName, $output);

        $httpClient = new Client();
        $httpClient->post($this->webhookUrl, [
            RequestOptions::JSON => [
                'attachments' => [
                    [
                        'fallback' => $subject,
                        "color" => $isAlertTesting ? "#0000D0" : "#D00000",
                        "fields" => [
                            [
                                "title" => $subject,
                                "value" => $body,
                                "short" => false,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
