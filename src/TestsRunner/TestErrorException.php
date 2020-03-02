<?php

namespace BackupValidator\TestsRunner;

use Throwable;

class TestErrorException extends \Exception
{
    public $testName;

    public $output;

    public $command;

    public function __construct($testName, $command, $output, $code = 0, Throwable $previous = null)
    {
        $this->testName = $testName;
        $this->command = $command;
        $this->output = $output;

        parent::__construct('Failed test', $code, $previous);
    }
}
