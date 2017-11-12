<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue\Console\Command;

use Dot\Console\Command\AbstractCommand;
use Dot\Queue\Job\RestartJob;
use Dot\Queue\Queue\QueueManager;
use Zend\Console\Adapter\AdapterInterface;
use ZF\Console\Route;

/**
 * Class RestartCommand
 * @package Dot\Queue\Console\Command
 */
class RestartCommand extends AbstractCommand
{
    /** @var  QueueManager */
    protected $qm;

    /**
     * RestartCommand constructor.
     * @param QueueManager $qm
     */
    public function __construct(QueueManager $qm)
    {
        $this->qm = $qm;
    }

    /**
     * @param Route $route
     * @param AdapterInterface $console
     */
    public function __invoke(Route $route, AdapterInterface $console)
    {
        $queues = $route->getMatchedParam('queues');
        if (!$queues) {
            $queues = $this->qm->queueList();
        } else {
            $queues = \explode(',', $queues);
        }

        foreach ($queues as $queue) {
            $this->qm->createJob(RestartJob::class)
                ->dispatch($queue);
        }
    }
}
