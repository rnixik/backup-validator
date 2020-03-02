<?php

namespace BackupValidator\Alerting;

interface ChannelInterface
{
    public function initialize(array $channelConfig);

    public function send(bool $isAlertTesting, string $backupName, string $output);
}
