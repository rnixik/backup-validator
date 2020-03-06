<?php

namespace BackupValidator\Tests\BackupSourceFinder;

use BackupValidator\BackupSourceFinder\BackupSourceFinder;
use BackupValidator\BackupSourceFinder\FileNotFoundException;
use PHPUnit\Framework\TestCase;

class BackupSourceFinderTest extends TestCase
{
    public function testFindBackupFileOk(): void
    {
        $dir = sys_get_temp_dir();
        $files = [
            'awesome_1.dump' => strtotime('-5 day'),
            'awesome_2.dump' => strtotime('-2 day'),
            'awesome_3.dump' => strtotime('-3 day'),
        ];
        foreach ($files as $file => $time) {
            touch("$dir/$file", $time);
        }

        $backupSourceFinder = new BackupSourceFinder();
        /** @noinspection PhpUnhandledExceptionInspection */
        $actualFile = $backupSourceFinder->findBackupFile([
            'type' => 'latest',
            'pattern' => "$dir/awesome_*.dump",
        ]);

        $this->assertEquals("$dir/awesome_2.dump", $actualFile);

        foreach ($files as $file => $time) {
            @unlink("$dir/$file");
        }
    }

    public function testFindBackupFileNotFound(): void
    {
        $dir = sys_get_temp_dir();

        $backupSourceFinder = new BackupSourceFinder();
        $this->expectException(FileNotFoundException::class);
        /** @noinspection PhpUnhandledExceptionInspection */
        $backupSourceFinder->findBackupFile([
            'type' => 'latest',
            'pattern' => "$dir/fake_*.dump",
        ]);
    }

    public function testFindBackupFileTooOld(): void
    {
        $dir = sys_get_temp_dir();
        $files = [
            'awesome_1.dump' => strtotime('-5 day'),
            'awesome_2.dump' => strtotime('-2 day'),
            'awesome_3.dump' => strtotime('-3 day'),
        ];
        foreach ($files as $file => $time) {
            touch("$dir/$file", $time);
        }

        $backupSourceFinder = new BackupSourceFinder();
        $this->expectException(FileNotFoundException::class);
        /** @noinspection PhpUnhandledExceptionInspection */
        $backupSourceFinder->findBackupFile([
            'type' => 'latest',
            'pattern' => "$dir/awesome_*.dump",
            'not_older_than_hours' => 24,
        ]);

        foreach ($files as $file => $time) {
            @unlink("$dir/$file");
        }
    }
}
