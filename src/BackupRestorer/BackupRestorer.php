<?php

namespace BackupValidator\BackupRestorer;

class BackupRestorer
{
    public function restore(string $backupFilename, array $restoreConfig, callable $outputFunction)
    {
        $stopContainerCommand = "docker stop " . escapeshellarg($restoreConfig['container_name']) . " || true";
        $this->execute($stopContainerCommand, $outputFunction, $restoreConfig['verbose'] ?? false);

        // docker run --rm -v /var/backups/db/some.dump:/var/backups/db/backup.dump \
        //   -e POSTGRES_USER=user -e POSTGRES_DB=dbname --name some-validator -d postgres:latest
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
            '-e',
            escapeshellarg("POSTGRES_PASSWORD={$restoreConfig['password']}"),
            '--name',
            escapeshellarg($restoreConfig['container_name']),
            '-d',
            escapeshellarg($restoreConfig['image']),
        ];

        $this->execute(implode(' ', $runContainerCommand), $outputFunction, $restoreConfig['verbose'] ?? false);

        $waitCommand = "docker exec " . escapeshellarg($restoreConfig['container_name'])
            . " bash -c 'while ! pg_isready; do sleep 1; done;'";
        $this->execute($waitCommand, $outputFunction, $restoreConfig['verbose'] ?? false);

        sleep(1);

        $restoreCommand = "docker exec " . escapeshellarg($restoreConfig['container_name'])
            . " pg_restore -e -U " . escapeshellarg($restoreConfig['user'])
            . " -d " . escapeshellarg($restoreConfig['database'])
            . " /var/backups/db/backup.dump";
        $this->execute($restoreCommand, $outputFunction, $restoreConfig['verbose'] ?? false);
    }

    public function cleanup(array $restoreConfig, callable $outputFunction)
    {
        $stopContainerCommand = "docker stop " . escapeshellarg($restoreConfig['container_name']) . " || true";
        $this->execute($stopContainerCommand, $outputFunction, $restoreConfig['verbose'] ?? false);
    }

    private function execute(string $command, callable $outputFunction, bool $verbose = false)
    {
        if ($verbose) {
            $outputFunction($command);
        }

        $output = [];
        exec($command, $output, $execCode);

        if ($verbose) {
            $outputFunction(implode(' ', $output));
        }

        if ($execCode != 0) {
            throw new RestoreException(implode(' ', $output), $execCode);
        }
    }
}
