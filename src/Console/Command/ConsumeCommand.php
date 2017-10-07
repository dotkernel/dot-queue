<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue\Console\Command;

use Dot\Console\Command\AbstractCommand;
use Dot\Queue\Consumer;
use Dot\Queue\ConsumerOptions;
use Dot\Queue\Queue\QueueInterface;
use Dot\Queue\Queue\RoundRobinQueue;
use Zend\Console\Adapter\AdapterInterface;
use ZF\Console\Route;

/**
 * Class RunCommand
 * @package Dot\Queue\Console\Command
 *
 */
class ConsumeCommand extends AbstractCommand
{
    /** @var  Consumer */
    protected $consumer;

    /** @var  AdapterInterface */
    protected $console;

    /**
     * ConsumeCommand constructor.
     * @param Consumer $consumer
     */
    public function __construct(Consumer $consumer)
    {
        $this->consumer = $consumer;
    }

    /**
     * @param Route $route
     * @param AdapterInterface $console
     */
    public function __invoke(Route $route, AdapterInterface $console)
    {
        $this->console = $console;

        $options = $this->getConsumerOptions($route);
        $queue = $this->getQueue($options);
        $this->consumer->run($queue, $options);
    }

    /**
     * @param Route $route
     * @return ConsumerOptions
     */
    protected function getConsumerOptions(Route $route): ConsumerOptions
    {
        $opts = $route->getMatches();
        $options = [];
        if (isset($opts['queues'])) {
            $options['queues'] = \explode(',', $opts['queues']);
        }
        if (isset($opts['max-runtime'])) {
            $options['maxRuntime'] = (int)$opts['max-runtime'];
        }
        if (isset($opts['max-jobs'])) {
            $options['maxJobs'] = (int)$opts['max-jobs'];
        }
        if (isset($opts['memory-limit'])) {
            $options['memoryLimit'] = (int)$opts['memory-limit'];
        }
        if (isset($opts['sleep'])) {
            $options['sleep'] = (int)$opts['sleep'];
        }
        if (isset($opts['stop-on-error'])) {
            $options['stopOnError'] = (bool)$opts['stop-on-error'];
        }
        if (isset($opts['stop-on-empty'])) {
            $options['stopOnEmpty'] = (bool)$opts['stop-on-empty'];
        }
        if (isset($opts['all']) && $opts['all']) {
            $options['queues'] = ConsumerOptions::QUEUES_ALL;
        }

        return new ConsumerOptions($options);
    }

    /**
     * @param ConsumerOptions $options
     * @return QueueInterface
     */
    protected function getQueue(ConsumerOptions $options): QueueInterface
    {
        $queueList = $this->consumer->getQueueManager()->queueList();

        $queues = $options->getQueues();
        if ($queues === ConsumerOptions::QUEUES_ALL) {
            $queues = $queueList;
        }

        $this->validateQueueNames($queues, $queueList);
        if (empty($queues)) {
            $queues[] = $this->consumer->getQueueManager()->getDefaultQueue();
        }

        $q = [];
        foreach ($queues as $queue) {
            if (!$queue instanceof QueueInterface) {
                /** @var QueueInterface $queue */
                $queue = $this->consumer->getQueueManager()->get($queue);
            }
            $q[] = $queue;
        }

        if (count($q) > 1) {
            return new RoundRobinQueue($q);
        } else {
            return $q[0];
        }
    }

    /**
     * @param array $queues
     * @param array $list
     */
    protected function validateQueueNames(array $queues, array $list)
    {
        foreach ($queues as $queue) {
            if (!in_array($queue, $list)) {
                $this->console->writeLine(sprintf('Invalid queue name given `%s`', $queue));
                exit(0);
            }
        }
    }
}
