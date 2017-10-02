<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue\Console\Command;

use Dot\Queue\Job\JobInterface;
use LucidFrame\Console\ConsoleTable;
use Zend\Console\Adapter\AdapterInterface;
use ZF\Console\Route;

/**
 * Class ListFailedCommand
 * @package Dot\Queue\Console\Command
 */
class ListFailedCommand extends AbstractFailedCommand
{
    /**
     * @param Route $route
     * @param AdapterInterface $console
     * @return int
     */
    public function __invoke(Route $route, AdapterInterface $console)
    {
        $queue = $route->getMatchedParam('queue', null);
        $failedJobs = $this->failedJobProvider->findAll($queue);

        if (empty($failedJobs)) {
            $console->writeLine('No failed jobs!');
            return 0;
        }

        $this->displayFailedJobs($failedJobs, $console);
        return 0;
    }

    /**
     * @param array $jobs
     * @param AdapterInterface $console
     */
    protected function displayFailedJobs(array $jobs, AdapterInterface $console)
    {
        $table = new ConsoleTable();
        $table->addHeader('UUID')
            ->addHeader('Queue')
            ->addHeader('Class');
        /** @var JobInterface $job */
        foreach ($jobs as $job) {
            $table->addRow()
                ->addColumn($job->getUUID())
                ->addColumn($job->getQueue()->getName())
                ->addColumn(get_class($job));
        }

        $console->writeLine($table->getTable());
    }
}
