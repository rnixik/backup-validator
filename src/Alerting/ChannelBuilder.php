<?php

namespace BackupValidator\Alerting;

class ChannelBuilder
{
    /**
     * @param array $channelConfig
     * @return ChannelInterface
     * @throws BadConfigException
     */
    public function buildChannel(array $channelConfig): ChannelInterface
    {
        $type = $channelConfig['type'];
        $className = "\\BackupValidator\\Alerting\\Channels\\{$type}";
        if (!class_exists($className)) {
            throw new BadConfigException("Channel type '$type' is not supported");
        }

        /** @var ChannelInterface $channel */
        $channel = new $className();
        $channel->initialize($channelConfig);

        return $channel;
    }
}
