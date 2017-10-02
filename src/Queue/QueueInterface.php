<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue\Queue;

use Dot\Queue\Job\JobInterface;

/**
 * Interface QueueInterface
 * @package Dot\Queue
 */
interface QueueInterface extends \Countable
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @param string $name
     * @return QueueInterface
     */
    public function setName(string $name): QueueInterface;

    /**
     * @return int
     */
    public function getRetryAfter(): int;

    /**
     * @param int $retryAfter
     * @return QueueInterface
     */
    public function setRetryAfter(int $retryAfter): QueueInterface;

    /**
     * @param JobInterface $job
     */
    public function enqueue(JobInterface $job);

    /**
     * @return JobInterface
     */
    public function dequeue(): ?JobInterface;

    /**
     * @param JobInterface $job
     */
    public function acknowledge(JobInterface $job);

    /**
     * @param JobInterface $job
     */
    public function remove(JobInterface $job);

    /**
     * Purges the queue
     */
    public function purge();

    /**
     * @return QueueManager
     */
    public function getQueueManager(): QueueManager;

    /**
     * @param QueueManager $queueManager
     * @return QueueInterface
     */
    public function setQueueManager(QueueManager $queueManager): QueueInterface;
}
