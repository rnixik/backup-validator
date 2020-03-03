<?php

namespace BackupValidator\Alerting\Traits;

trait ChannelWithSubjectAndBodyTrait
{
    private $subject = 'Backup \'%backup_name%\' is invalid!';
    private $subjectTest = 'Backup \'%backup_name%\' is valid and alerting is working';

    private $body = '%output%';
    private $bodyTest = '%output%';

    protected function getSubject(bool $isAlertTesting, string $backupName, string $output): string
    {
        return $this->replaceVariables($isAlertTesting ? $this->subjectTest : $this->subject, [
            'backup_name' => $backupName,
            'output' => $output,
        ]);
    }

    protected function getBody(bool $isAlertTesting, string $backupName, string $output): string
    {
        return $this->replaceVariables($isAlertTesting ? $this->bodyTest : $this->body, [
            'backup_name' => $backupName,
            'output' => $output,
        ]);
    }

    private function replaceVariables(string $input, array $variables): string
    {
        foreach ($variables as $name => $value) {
            $input = str_replace("%$name%", $value, $input);
        }

        return $input;
    }
}
