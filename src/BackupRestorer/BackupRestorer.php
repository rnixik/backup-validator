<?php

namespace BackupValidator\BackupRestorer;

class BackupRestorer
{
    public function restore(string $backupFilename, array $restoreConfig)
    {
        $stopContainerCommand = "docker stop " . escapeshellarg($restoreConfig['container_name']) . " || true";
        $this->execute($stopContainerCommand);

        // docker run --rm -v /var/backups/db/some.dump:/var/backups/db/backup.dump -e POSTGRES_USER=user -e POSTGRES_DB=dbname --name some-validator -d postgres:latest
        $runContainerCommand = [
            'docker',
            'run',
            '--rm',
            '-v',
            escapeshellarg("$backupFilename:/var/backups/db/backup.dump"),
            '-e',
            escapeshellarg("POSTGRES_USER={$restoreConfig['user']}"),
            '-e',
            escapeshellarg("POSTGRES_DB={$restoreConfig['database']}"),
            '--name',
            escapeshellarg($restoreConfig['container_name']),
            '-d',
            escapeshellarg($restoreConfig['image']),
        ];

        $this->execute(implode(' ', $runContainerCommand));

        $waitCommand = "docker exec " . escapeshellarg($restoreConfig['container_name'])
            . " bash -c 'while ! pg_isready; do sleep 1; done;'";
        $this->execute($waitCommand);

        sleep(1);

        $restoreCommand = "docker exec " . escapeshellarg($restoreConfig['container_name'])
            . " pg_restore -e -U " . escapeshellarg($restoreConfig['user'])
            . " -d " . escapeshellarg($restoreConfig['database'])
            . " /var/backups/db/backup.dump";
        $this->execute($restoreCommand);
    }

    public function cleanup(array $restoreConfig)
    {
        $stopContainerCommand = "docker stop " . escapeshellarg($restoreConfig['container_name']) . " || true";
        $this->execute($stopContainerCommand);
    }

    private function execute(string $command)
    {
        $output = [];
        exec($command, $output, $execCode);
        if ($execCode != 0) {
            throw new RestoreException(implode(' ', $output), $execCode);
        }
    }
}
