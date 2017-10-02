<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue\Adapter;

use Dot\Queue\Job\JobInterface;
use Dot\Queue\Queue\QueueInterface;

/**
 * Interface AdapterInterface
 * @package Dot\Queue\Adapter
 */
interface AdapterInterface
{
    /**
     * @param QueueInterface $queue
     * @return int
     */
    public function count(QueueInterface $queue): int;

    /**
     * @param QueueInterface $queue
     * @param JobInterface $job
     */
    public function enqueue(QueueInterface $queue, JobInterface $job);

    /**
     * @param QueueInterface $queue
     * @return JobInterface|null
     */
    public function dequeue(QueueInterface $queue): ?JobInterface;

    /**
     * @param QueueInterface $queue
     * @param JobInterface $job
     */
    public function acknowledge(QueueInterface $queue, JobInterface $job);

    /**
     * @param QueueInterface $queue
     * @param JobInterface $job
     */
    public function delete(QueueInterface $queue, JobInterface $job);

    /**
     * @param QueueInterface $queue
     */
    public function purge(QueueInterface $queue);
}
