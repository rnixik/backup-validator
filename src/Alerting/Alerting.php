<?php

namespace BackupValidator\Alerting;

class Alerting
{
    private $channelBuilder;

    public function __construct(ChannelBuilder $channelBuilder)
    {
        $this->channelBuilder = $channelBuilder;
    }

    /**
     * @param string $backupName
     * @param array $channelsConfig
     * @param array $backupAlertingConfig
     * @param bool $backupIsValid
     * @param string $outputBuffer
     * @return bool
     * @throws BadConfigException
     */
    public function alertIfNecessary(
        string $backupName,
        array $channelsConfig,
        array $backupAlertingConfig,
        bool $backupIsValid,
        string $outputBuffer
    ): bool {
        $shouldAlert = $this->shouldAlert($backupIsValid, $backupAlertingConfig['always_alert'] ?? []);

        foreach ($backupAlertingConfig['channels'] as $channelName) {
            if (!array_key_exists($channelName, $channelsConfig)) {
                throw new BadConfigException("Channel '$channelName' not found in alert_channels");
            }
            $channel = $this->channelBuilder->buildChannel($channelsConfig[$channelName]);

            if ($shouldAlert) {
                $channel->send($backupIsValid, $backupName, $outputBuffer);
            }
        }

        return $shouldAlert;
    }

    private function shouldAlert(bool $backupIsValid, array $alwaysAlertConfig): bool
    {
        if (!$backupIsValid) {
            return true;
        }

        if (isset($alwaysAlertConfig['days_of_week']) && in_array(date('w'), $alwaysAlertConfig['days_of_week'])) {
            return true;
        }

        return false;
    }
}
