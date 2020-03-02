<?php

namespace BackupValidator;

use BackupValidator\Alerting\Alerting;
use BackupValidator\BackupRestorer\BackupRestorer;
use BackupValidator\BackupSourceFinder\BackupSourceFinder;
use BackupValidator\BackupSourceFinder\BadConfigException;
use BackupValidator\BackupSourceFinder\FileNotFoundException;
use BackupValidator\ConfigLoader\ConfigLoader;
use BackupValidator\TestsRunner\TestErrorException;
use BackupValidator\TestsRunner\TestsRunner;

class Validator
{
    /** @var ConfigLoader */
    private $configLoader;

    /** @var BackupSourceFinder */
    private $backupSourceFinder;

    /** @var BackupRestorer */
    private $backupRestorer;

    /** @var TestsRunner */
    private $testsRunner;

    /** @var Alerting */
    private $alerting;

    /** @var string */
    private $outputBuffer = '';

    public function __construct(
        ConfigLoader $configLoader,
        BackupSourceFinder $backupSourceFinder,
        BackupRestorer $backupRestorer,
        TestsRunner $testsRunner,
        Alerting $alerting
    ) {
        $this->configLoader = $configLoader;
        $this->backupSourceFinder = $backupSourceFinder;
        $this->backupRestorer = $backupRestorer;
        $this->testsRunner = $testsRunner;
        $this->alerting = $alerting;
    }

    public function validate(string $configFilename)
    {
        $config = $this->configLoader->load($configFilename);
        foreach ($config['backups'] as $name => $backupConfig) {
            $this->outputBuffer = '';
            try {
                $successful = $this->validateBackup($name, $backupConfig);
            } catch (\Exception $e) {
                $successful = false;
                $this->output($e->getMessage());
            }

            $this->alerting->alertIfNecessary($name, $config['alert_channels'], $backupConfig['alerting'], $successful, $this->outputBuffer);
        }
    }

    /**
     * @param string $name
     * @param array $backupConfig
     * @return bool
     * @throws BadConfigException
     * @throws FileNotFoundException
     */
    private function validateBackup(string $name, array $backupConfig): bool
    {
        $successful = true;

        $this->output("Validating backup for {$name}");
        $this->output("Finding backup file...", false);
        $backupFilename = $this->backupSourceFinder->findBackupFile($backupConfig['source']);
        $this->output("OK");
        $this->output("Restoring $backupFilename...", false);
        $this->backupRestorer->restore($backupFilename, $backupConfig['restore']);
        $this->output('OK');

        try {
            $this->output("Running tests:");
            $runResult = $this->testsRunner->run($backupConfig['restore'], $backupConfig['tests'], function (string $message, bool $terminateLine = true) {
                $this->output($message, $terminateLine);
            });
            $this->output("Successful: {$runResult->successfulNum}, Failed: {$runResult->failedNum}");
            if ($runResult->failedNum) {
                $successful = false;
            }
        } catch (TestErrorException $e) {
            $successful = false;
            $this->output('"' . $e->testName . '" - ERROR: ' . $e->output);
        }

        return $successful;
    }

    private function output(string $message, bool $terminateLine = true)
    {
        $line = $message . ($terminateLine ? PHP_EOL : '');
        echo $line;
        $this->outputBuffer .= $line;
    }
}
