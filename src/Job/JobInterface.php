<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue\Job;

use Dot\Queue\Queue\QueueInterface;
use Dot\Queue\Queue\QueueManager;

/**
 * Interface JobInterface
 * @package Dot\Queue\Job
 */
interface JobInterface
{
    /**
     * @return string
     */
    public function getUUID(): string;

    /**
     * @param string $uuid
     * @return JobInterface
     */
    public function setUUID(string $uuid): JobInterface;

    /**
     * @param string $key
     * @param $value
     * @return JobInterface
     */
    public function set(string $key, $value): JobInterface;

    /**
     * @param string $key
     * @param null $default
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * @return int
     */
    public function getPriority(): int;

    /**
     * @param int $priority
     * @return JobInterface
     */
    public function setPriority(int $priority): JobInterface;

    /**
     * @return int
     */
    public function getAttempts(): int;

    /**
     * @param int $attempts
     * @return JobInterface
     */
    public function setAttempts(int $attempts): JobInterface;

    /**
     * @return int
     */
    public function increment(): int;

    /**
     * @return int
     */
    public function getMaxAttempts(): int;

    /**
     * @param int $maxAttempts
     * @return JobInterface
     */
    public function setMaxAttempts(int $maxAttempts): JobInterface;

    /**
     * @return int
     */
    public function getDelay(): int;

    /**
     * @param int $delay
     * @return JobInterface
     */
    public function setDelay(int $delay): JobInterface;

    /**
     * @return int
     */
    public function getTimeout(): int;

    /**
     * @param int $timeout
     * @return JobInterface
     */
    public function setTimeout(int $timeout): JobInterface;

    /**
     * @param array $options
     * @return JobInterface
     */
    public function setOptions(array $options = []): JobInterface;

    /**
     * @param null $queue
     * @return JobInterface
     */
    public function dispatch($queue = null): JobInterface;

    /**
     * Release job back into the queue
     * @param int $delay
     * @return mixed
     */
    public function release(int $delay = 0): JobInterface;

    /**
     * Deletes reserved job from the queue
     */
    public function delete();

    /**
     * Process the job
     */
    public function process();

    /**
     * @param \Exception|\Throwable $e
     */
    public function failed($e);

    /**
     * @return QueueInterface
     */
    public function getQueue(): QueueInterface;

    /**
     * @param string|QueueInterface $queue
     * @return JobInterface
     */
    public function setQueue($queue): JobInterface;

    /**
     * @return QueueManager
     */
    public function getQueueManager(): QueueManager;

    /**
     * @param QueueManager $queueManager
     * @return JobInterface
     */
    public function setQueueManager(QueueManager $queueManager): JobInterface;
}
