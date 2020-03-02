<?php

namespace BackupValidator;

class ValidationResult
{
    /** @var bool */
    public $successful;

    public function __construct(bool $successful)
    {
        $this->successful = $successful;
    }
}
