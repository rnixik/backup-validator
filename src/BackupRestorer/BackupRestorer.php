<?php

namespace BackupValidator\BackupRestorer;

class BackupRestorer
{
    /**
     * @param string $backupFilename
     * @param array $restoreConfig
     * @param callable $outputFunction
     * @throws RestoreException
     */
    public function restore(string $backupFilename, array $restoreConfig, callable $outputFunction)
    {
        $stopContainerCommand = "docker stop " . escapeshellarg($restoreConfig['container_name']) . " 2>&1 || true";
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
            '2>&1',
        ];

        $this->execute(implode(' ', $runContainerCommand), $outputFunction, $restoreConfig['verbose'] ?? false);

        $waitCommand = "docker exec " . escapeshellarg($restoreConfig['container_name'])
            . " bash -c 'while ! pg_isready; do sleep 1; done;' 2>&1";
        $this->execute($waitCommand, $outputFunction, $restoreConfig['verbose'] ?? false);

        sleep(1);

        $restoreCommand = "docker exec " . escapeshellarg($restoreConfig['container_name'])
            . " pg_restore -e -U " . escapeshellarg($restoreConfig['user'])
            . " -d " . escapeshellarg($restoreConfig['database'])
            . " /var/backups/db/backup.dump 2>&1";
        $this->execute($restoreCommand, $outputFunction, $restoreConfig['verbose'] ?? false);
    }

    /**
     * @param array $restoreConfig
     * @param callable $outputFunction
     * @throws RestoreException
     */
    public function cleanup(array $restoreConfig, callable $outputFunction)
    {
        $stopContainerCommand = "docker stop " . escapeshellarg($restoreConfig['container_name']) . " 2>&1 || true";
        $this->execute($stopContainerCommand, $outputFunction, $restoreConfig['verbose'] ?? false);
    }

    /**
     * @param string $command
     * @param callable $outputFunction
     * @param bool $verbose
     * @throws RestoreException
     */
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
