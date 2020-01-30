<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue\Console\Command;

use Dot\Queue\Job\JobInterface;
use Laminas\Console\Adapter\AdapterInterface;
use ZF\Console\Route;

/**
 * Class RetryCommand
 * @package Dot\Queue\Console\Command
 */
class RetryCommand extends AbstractFailedCommand
{
    /**
     * @param Route $route
     * @param AdapterInterface $console
     * @return int
     */
    public function __invoke(Route $route, AdapterInterface $console)
    {
        $uuid = $route->getMatchedParam('uuid', null);
        $queue = $route->getMatchedParam('queue', null);

        $job = null;
        if ($uuid) {
            $job = $this->failedJobProvider->find($uuid);
            if (!$job) {
                $console->writeLine("Cannot find job with UUID: $uuid");
                return 0;
            }

            //remove it from the failed jobs and dispatch it to the right queue
            $this->failedJobProvider->forget($uuid);
            $this->retryJob($job);
            $console->writeLine("Job $uuid dispatched to queue {$job->getQueue()->getName()}");
            return 0;
        } else {
            $jobs = $this->failedJobProvider->findAll($queue);
            $this->failedJobProvider->flush($queue);
            foreach ($jobs as $job) {
                $this->retryJob($job);
            }

            $n = count($jobs);
            $console->writeLine("$n job(s) were scheduled for retrying");
            return 0;
        }
    }

    /**
     * @param JobInterface $job
     */
    protected function retryJob(JobInterface $job)
    {
        $job->setAttempts(0);
        $job->dispatch($job->getQueue());
    }
}
