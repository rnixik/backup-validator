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

            try {
                $wasAlerted = $this->alerting->alertIfNecessary($name, $config['alert_channels'], $backupConfig['alerting'], $successful, $this->outputBuffer);
                if ($wasAlerted) {
                    $this->output("Notifications have been sent");
                }
            } catch (\Exception $e) {
                $this->output($e->getMessage());
            }
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

        $outputCallable = function (string $message, bool $terminateLine = true) {
            $this->output($message, $terminateLine);
        };

        $this->output("Validating backup for {$name}");
        $this->output("Finding backup file...", false);
        $backupFilename = $this->backupSourceFinder->findBackupFile($backupConfig['source']);
        $this->output("OK");
        $this->output("Restoring $backupFilename...", false);
        $this->backupRestorer->restore($backupFilename, $backupConfig['restore'], $outputCallable);
        $this->output('OK');

        try {
            $this->output("Running tests:");
            $runResult = $this->testsRunner->run($backupConfig['restore'], $backupConfig['tests'], $outputCallable);
            $this->output("Successful: {$runResult->successfulNum}, Failed: {$runResult->failedNum}");
            if ($runResult->failedNum) {
                $successful = false;
            }
        } catch (TestErrorException $e) {
            $successful = false;
            $this->output('"' . $e->testName . '" - ERROR: ' . $e->output);
        }

        $this->backupRestorer->cleanup($backupConfig['restore'], $outputCallable);

        return $successful;
    }

    private function output(string $message, bool $terminateLine = true)
    {
        $line = $message . ($terminateLine ? PHP_EOL : '');
        echo $line;
        $this->outputBuffer .= $line;
    }
}
