<?php

namespace BackupValidator\Tests\Alerting;

use BackupValidator\Alerting\Alerting;
use BackupValidator\Alerting\ChannelBuilder;
use BackupValidator\Alerting\Channels\Slack;
use PHPUnit\Framework\TestCase;

class AlertingTest extends TestCase
{
    public function testAlertIfNecessaryAlert(): void
    {
        $alerting = $this->getAlerting(true, false, 'output_demo');
        /** @noinspection PhpUnhandledExceptionInspection */
        $wasAlert = $alerting->alertIfNecessary(
            'awesome',
            $this->getChannelsConfig(),
            $this->getBackupConfig(),
            false,
            'output_demo'
        );
        $this->assertTrue($wasAlert);
    }

    public function testAlertIfNecessaryDoNotAlert(): void
    {
        $alerting = $this->getAlerting(false, true, 'demo');
        /** @noinspection PhpUnhandledExceptionInspection */
        $wasAlert = $alerting->alertIfNecessary(
            'awesome',
            $this->getChannelsConfig(),
            $this->getBackupConfig(),
            true,
            'demo'
        );
        $this->assertFalse($wasAlert);
    }

    public function testAlertIfNecessaryAlertByDayOfWeek(): void
    {
        $alerting = $this->getAlerting(true, true, 'demo');
        /** @noinspection PhpUnhandledExceptionInspection */
        $wasAlert = $alerting->alertIfNecessary(
            'awesome',
            $this->getChannelsConfig(),
            array_merge($this->getBackupConfig(), [
                'always_alert' => [
                    'days_of_week' => [date('w')],
                ],
            ]),
            true,
            'demo'
        );
        $this->assertTrue($wasAlert);
    }

    private function getChannelsConfig(): array
    {
        return [
            'alert_to_mock' => [
                'type' => 'Slack',
                'webhook' => '',
            ],
        ];
    }

    private function getBackupConfig(): array
    {
        return [
            'channels' => [
                'alert_to_mock',
            ],
        ];
    }

    private function getAlerting(bool $shouldAlert, bool $backupIsValid, string $output): Alerting
    {
        $slackMock = $this->createMock(Slack::class);
        if ($shouldAlert) {
            $slackMock->expects($this->once())->method('send')
                ->with(
                    $backupIsValid,
                    'awesome',
                    $output
                );
        } else {
            $slackMock->expects($this->never())->method('send');
        }

        $channelBuilderMock = $this->createMock(ChannelBuilder::class);
        $channelBuilderMock->method('buildChannel')
            ->willReturn($slackMock);

        return new Alerting($channelBuilderMock);
    }
}
