<?php

namespace BackupValidator\ConfigLoader;

class ConfigLoader
{
    private $yamlParser;

    public function __construct(\Spyc $yamlParser)
    {
        $this->yamlParser = $yamlParser;
    }

    public function load(string $filename): array
    {
        return $this->yamlParser->loadFile($filename);
    }
}
