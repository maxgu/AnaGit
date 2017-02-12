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

        $this->viewCommitsCount($console);

        $console->writeLine("");

        $commits = [];

        $console->write("Getting commit info");
        for ($i = 0; $i < 100; $i++) {
            $commitsPart = $this->getCommits($i);

            $commits = array_merge($commits, $commitsPart);
            $console->write(".");

            if (count($commitsPart) < self::COMMIT_PART_SIZE) {
                break;
            }
        }
        $console->writeLine("");

        $console->write("Calculate top 10 changed files");
        $this->viewTop10ChangedFiles($console, $commits);

        $console->writeLine("");

        $console->write("Calculate top 10 commiters");
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
            $changedFilesCommand = new Process(
                "git diff-tree --no-commit-id --name-only -r " . $commit->getHash()
            );
            $changedFilesCommand->run();

            $filesOutput = $changedFilesCommand->getOutput();
            $files = explode(PHP_EOL, $filesOutput);

            foreach ($files as $filePath) {
                if (empty($filePath)) {
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
        }

        $console->writeLine("");
        $console->writeLine("Top 10 changed files:");

        $this->showStatistic($console, $statistic);
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

            if ($i == 500) break;
        }

        $console->writeLine("");
        $console->writeLine("Top 10 commiters:");

        $this->showStatistic($console, $statistic);
    }

    private function showStatistic($console, $statistic)
    {
        arsort($statistic);

        foreach(array_slice($statistic, 0, 10) as $filePath => $changedTimes) {
            $console->writeLine(sprintf("  [%4s] %s", $changedTimes, $filePath), ColorInterface::GREEN);
        }
    }
}