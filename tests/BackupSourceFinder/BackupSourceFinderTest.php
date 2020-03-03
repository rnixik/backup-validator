<?php

namespace BackupValidator\Tests\BackupSourceFinder;

use BackupValidator\BackupSourceFinder\BackupSourceFinder;
use PHPUnit\Framework\TestCase;

class BackupSourceFinderTest extends TestCase
{
    public function testFindBackupFile(): void
    {
        $dir = sys_get_temp_dir();
        $files = [
            'awesome_1.dump' => strtotime('-2 day'),
            'awesome_2.dump' => strtotime('now'),
            'awesome_3.dump' => strtotime('-1 day'),
        ];
        foreach ($files as $file => $time) {
            touch("$dir/$file", $time);
        }

        $backupSourceFinder = new BackupSourceFinder();
        $actualFile = $backupSourceFinder->findBackupFile([
            'type' => 'latest',
            'pattern' => "$dir/awesome_*.dump",
        ]);

        $this->assertEquals("$dir/awesome_2.dump", $actualFile);

        foreach ($files as $file => $time) {
            @unlink("$dir/$file");
        }
    }
}
