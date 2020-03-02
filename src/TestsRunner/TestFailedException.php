<?php

namespace BackupValidator\TestsRunner;

use Throwable;

class TestFailedException extends \Exception
{
    public function __construct($message, $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
