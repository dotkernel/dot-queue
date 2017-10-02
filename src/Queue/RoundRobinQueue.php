<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue\Queue;

use Dot\Queue\Exception\RuntimeException;
use Dot\Queue\Job\JobInterface;

/**
 * Class RoundRobinQueue
 * @package Dot\Queue\Queue
 */
class RoundRobinQueue implements QueueInterface
{
    /** @var  QueueInterface[] */
    protected $queues;

    /**
     * RoundRobinQueue constructor.
     * @param array $queues
     */
    public function __construct(array $queues)
    {
        $this->validateQueues($queues);

        $this->queues = $this->indexQueues($queues);
    }

    /**
     * Validated the queue list given
     * @param array $queues
     */
    protected function validateQueues(array $queues)
    {
        if (empty($queues)) {
            throw new \DomainException('$queues cannot be empty');
        }
        $filtered = array_filter(
            $queues,
            function ($queue) {
                return !$queue instanceof QueueInterface;
            }
        );
        if (!empty($filtered)) {
            throw new RuntimeException('All elements of $queues must implement QueueInterface');
        }
    }

    /**
     * @param QueueInterface[] $queues
     * @return array
     */
    protected function indexQueues($queues)
    {
        return array_combine(
            array_map(
                function (QueueInterface $queue) {
                    return $queue->getName();
                },
                $queues
            ),
            $queues
        );
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'roundRobin';
    }

    /**
     * @param string $name
     * @return QueueInterface
     */
    public function setName(string $name): QueueInterface
    {
        return $this;
    }

    /**
     * @return int
     */
    public function getRetryAfter(): int
    {
        return current($this->queues)->getRetryAfter();
    }

    /**
     * @param int $retryAfter
     * @return QueueInterface
     */
    public function setRetryAfter(int $retryAfter): QueueInterface
    {
        return $this;
    }

    /**
     * @param JobInterface $job
     */
    public function enqueue(JobInterface $job)
    {
        $queue = $job->getQueue();
        if (isset($this->queues[$queue->getName()])) {
            $queue->enqueue($job);
        } else {
            throw new RuntimeException('Job\'s queue could not be found in the queues set');
        }
    }

    /**
     * @return JobInterface|null
     */
    public function dequeue(): ?JobInterface
    {
        $job = null;
        $checked = [];
        while (count($checked) < count($this->queues)) {
            $queue = current($this->queues);
            $job = $queue->dequeue();
            if (false === next($this->queues)) {
                reset($this->queues);
            }
            if ($job) {
                break;
            } else {
                $checked[] = $queue;
            }
        }
        return $job;
    }

    /**
     * @param JobInterface $job
     */
    public function acknowledge(JobInterface $job)
    {
        $job->getQueue()->acknowledge($job);
    }

    /**
     * @param JobInterface $job
     */
    public function remove(JobInterface $job)
    {
        $job->delete();
    }

    /**
     * Purge all queues
     */
    public function purge()
    {
        array_map('purge', $this->queues);
    }

    /**
     * @return QueueManager
     */
    public function getQueueManager(): QueueManager
    {
        return current($this->queues)->getQueueManager();
    }

    /**
     * @param QueueManager $queueManager
     * @return QueueInterface
     */
    public function setQueueManager(QueueManager $queueManager): QueueInterface
    {
        return $this;
    }

    /**
     * @return float|int
     */
    public function count()
    {
        return array_sum(array_map('count', $this->queues));
    }
}
