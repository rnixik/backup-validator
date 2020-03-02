<?php

namespace BackupValidator\BackupSourceFinder;

class BackupSourceFinder
{
    public function findBackupFile(array $sourceConfig): string
    {
        if ($sourceConfig['type'] !== 'latest') {
            throw new BadConfigException("Unsupported source type: " . $sourceConfig['type']);
        }
        if (empty($sourceConfig['pattern'])) {
            throw new BadConfigException("Empty source pattern");
        }

        if (!is_string($sourceConfig['pattern'])) {
            throw new BadConfigException("Pattern is not a string");
        }

        $files = [];
        foreach (glob($sourceConfig['pattern']) as $file) {
            $files[$file] = filemtime($file);
        }

        arsort($files);

        if (count($files) === 0) {
            throw new FileNotFoundException("File not found by pattern: " . $sourceConfig['pattern']);
        }

        reset($files);
        $latestFile = key($files);

        return $latestFile;
    }
}
