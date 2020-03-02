<?php

namespace BackupValidator\TestsRunner;

class TestsRunner
{
    /**
     * @param array $restoreConfig
     * @param array $testsConfig
     * @param callable $outputFunction
     * @return TestsRunResult
     * @throws TestErrorException
     */
    public function run(array $restoreConfig, array $testsConfig, callable $outputFunction): TestsRunResult
    {
        // docker exec some-validator psql -d dbname -U user -c "SELECT * FROM users;"

        $runQueryCommandArguments = [
            'docker',
            'exec',
            escapeshellarg($restoreConfig['container_name']),
            'psql',
            '-d',
            escapeshellarg($restoreConfig['database']),
            '-U',
            escapeshellarg($restoreConfig['user']),
            '--tuples-only',
            '-c',
        ];

        $success = 0;
        $fail = 0;

        foreach ($testsConfig as $testConfig) {
            try {
                $outputFunction('"' . $testConfig['name'] . '"...', false);
                $this->runTest($runQueryCommandArguments, $testConfig);
                $outputFunction('OK');
                $success++;
            } catch (TestFailedException $e) {
                $outputFunction('FAILED');
                $outputFunction($e->getMessage());
                $fail++;
            }
        }

        $result = new TestsRunResult();
        $result->successfulNum = $success;
        $result->failedNum = $fail;

        return $result;
    }

    /**
     * @param array $runQueryCommandArguments
     * @param array $testConfig
     * @throws TestErrorException
     * @throws TestFailedException
     */
    private function runTest(array $runQueryCommandArguments, array $testConfig)
    {
        $runQueryCommandArguments[] = escapeshellarg($testConfig['sql']);

        $command = implode(' ', $runQueryCommandArguments);
        $output = $this->execute($command, $testConfig['name']);
        $result = reset($output);
        if (!$result) {
            throw new TestErrorException($testConfig['name'], $command, 'Empty result: ' . implode(' ', $output));
        }
        $actual = trim($result);
        $this->assert($actual, $testConfig['expected_operator'], $testConfig['expected_value']);
    }

    /**
     * @param $actual
     * @param $operator
     * @param $expected
     * @throws TestFailedException
     */
    private function assert($actual, $operator, $expected)
    {
        if ($operator === '>=' && $actual < $expected) {
            throw new TestFailedException("Actual $actual < $expected Expected");
        }
        if ($operator === '<=' && $actual > $expected) {
            throw new TestFailedException("Actual $actual > $expected Expected");
        }
        if ($operator === '==' && $actual != $expected) {
            throw new TestFailedException("Actual $actual != $expected Expected");
        }
    }

    private function execute(string $command, string $testName): array
    {
        $output = [];
        exec($command, $output, $execCode);
        $outputStr = implode(' ', $output);
        if ($execCode != 0) {
            throw new TestErrorException($testName, $command, $outputStr, $execCode);
        }

        return $output;
    }
}
