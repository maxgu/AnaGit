<?php

namespace Anagit\Command;

use ZF\Console\Route;
use Zend\Console\ColorInterface;
use Zend\Console\Adapter\AdapterInterface;
use Symfony\Component\Process\Process;
use Anagit\Domain\Model\Commit;

class RunAnalyze
{
    const COMMIT_PART_SIZE = 100;
    const RESULT_COUNT = 50;

    public function __invoke(Route $route, AdapterInterface $console)
    {
        $gitFolder = realpath(getcwd()) . '/.git';

        if (!file_exists($gitFolder)) {
            $console->writeLine(
                "Git folder (.git) does not exists in current folder",
                ColorInterface::RED
            );
            return;
        }

        if (!is_dir('/tmp/anagit')) {
            mkdir('/tmp/anagit');
            mkdir('/tmp/anagit/data');
            mkdir('/tmp/anagit/data/cache');
            mkdir('/tmp/anagit/data/cache/top10-changed-files');
        }

        $this->viewCommitsCount($console);

        $console->writeLine("");

        $commits = [];

        if (file_exists('/tmp/anagit/data/cache/commits.php')) {
            $commits = unserialize(file_get_contents('/tmp/anagit/data/cache/commits.php'));
        }

        $console->write("Getting commit info");

        if (empty($commits)) {
            for ($i = 0; $i < 1000; $i++) {
                $commitsPart = $this->getCommits($i);

                $commits = array_merge($commits, $commitsPart);
                $console->write(".");

                if (count($commitsPart) < self::COMMIT_PART_SIZE) {
                    break;
                }
            }

            file_put_contents('/tmp/anagit/data/cache/commits.php', serialize($commits));
        }

        $console->writeLine("");

        $console->write("Calculate top ".self::RESULT_COUNT." changed files");
        $this->viewTop10ChangedFiles($console, $commits);

        $console->writeLine("");

        $console->write("Calculate top ".self::RESULT_COUNT." commiters");
        $this->viewTop10Commiters($console, $commits);

    }

    private function getGitData($command)
    {
        $process = new Process($command);
        $process->run();

        $output = $process->getOutput();
        $result = explode(PHP_EOL, $output);

        return array_filter($result);
    }

    /**
     * @param $iteration
     * @return Commit[]
     */
    private function getCommits($iteration)
    {
        $offset = '';
        if ($iteration > 0) {
            $offset = '--skip=' . self::COMMIT_PART_SIZE * $iteration;
        }

        $lastCommitCommand = "git log -%d %s --all --date-order --pretty=format:'%%h|%%an|%%ai'";

        $rawData = $this->getGitData(
            sprintf($lastCommitCommand, self::COMMIT_PART_SIZE , $offset)
        );

        $commits = [];
        foreach ($rawData as $rawCommit) {
            $commits[] = Commit::createFromString($rawCommit);
        }

        return $commits;
    }

    private function viewCommitsCount($console)
    {
        $allCommitCount = $this->getGitData("git rev-list --all --count");
        $console->writeLine("Find {$allCommitCount[0]} commits");
    }

    private function viewTop10ChangedFiles($console, $commits)
    {
        $statistic = [];
        /** @var Commit $commit */
        foreach ($commits as $i => $commit) {
            $isCached = false;
            if (file_exists('/tmp/anagit/data/cache/top10-changed-files/' . $this->generatePath($commit->getHash()) . $commit->getHash() . '.php')) {
                $files = unserialize(file_get_contents('/tmp/anagit/data/cache/top10-changed-files/' . $this->generatePath($commit->getHash()) . $commit->getHash() . '.php'));
                $isCached = true;
            } else {
                $changedFilesCommand = new Process(
                    "git diff-tree --no-commit-id --name-only -r " . $commit->getHash()
                );
                $changedFilesCommand->run();

                $filesOutput = $changedFilesCommand->getOutput();
                $files = explode(PHP_EOL, $filesOutput);
            }

            foreach ($files as $filePath) {
                if (empty($filePath)) {
                    continue;
                }

                $pathParts = pathinfo($filePath);
                if (!isset($pathParts['extension'])) {
                    continue;
                }

                if (in_array($pathParts['extension'], ['css', 'js'])) {
                    continue;
                }

                if (!isset($statistic[$filePath])) {
                    $statistic[$filePath] = 0;
                }

                $statistic[$filePath]++;
            }

            if ($i%100 == 0) {
                $console->write(".");
            }

            if (!$isCached) {
                if (!is_dir('/tmp/anagit/data/cache/top10-changed-files/' . $this->generatePath($commit->getHash()))) {
                    mkdir('/tmp/anagit/data/cache/top10-changed-files/' . $this->generatePath($commit->getHash()), 0755, true);
                }

                file_put_contents(
                    '/tmp/anagit/data/cache/top10-changed-files/' . $this->generatePath($commit->getHash()) . $commit->getHash() . '.php',
                    serialize($files)
                );
            }
        }

        $console->writeLine("");
        $console->writeLine("Top ".self::RESULT_COUNT." changed files:");

        $this->showStatistic($console, $statistic);
    }

    private function generatePath($objectId, $step = 2)
    {
        if (empty($objectId)) {
            throw new \InvalidArgumentException('$objectID can not be empty');
        }
        // prepare path "/2f/c2/9a"
        $path = '';
        for ($i = 0; $i < (strlen($objectId) - $step); $i += $step) {
            $path .= substr($objectId, $i, $step) . DIRECTORY_SEPARATOR;
        }
        return $path;
    }

    private function viewTop10Commiters($console, $commits)
    {
        $statistic = [];
        /** @var Commit $commit */
        foreach ($commits as $i => $commit) {
            if (!isset($statistic[$commit->getAuthor()])) {
                $statistic[$commit->getAuthor()] = 0;
            }

            $statistic[$commit->getAuthor()]++;

            if ($i%100 == 0) {
                $console->write(".");
            }
        }

        $console->writeLine("");
        $console->writeLine("Top ".self::RESULT_COUNT." commiters:");

        $this->showStatistic($console, $statistic);
    }

    private function showStatistic($console, $statistic)
    {
        arsort($statistic);

        foreach(array_slice($statistic, 0, self::RESULT_COUNT) as $filePath => $changedTimes) {
            $console->writeLine(sprintf("  [%4s] %s", $changedTimes, $filePath), ColorInterface::GREEN);
        }
    }
}