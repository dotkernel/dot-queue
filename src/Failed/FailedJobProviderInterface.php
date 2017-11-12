<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue\Failed;

use Dot\Queue\Job\JobInterface;
use Dot\Queue\Queue\QueueInterface;

/**
 * Interface FailedJobProviderInterface
 * @package Dot\Queue\Failed
 */
interface FailedJobProviderInterface
{
    /**
     * @param QueueInterface $queue
     * @param JobInterface $job
     * @param \Exception|\Throwable $e
     */
    public function log(QueueInterface $queue, JobInterface $job, $e);

    /**
     * @param string|null $queue
     * @return JobInterface[]
     */
    public function findAll(string $queue = null): array;

    /**
     * @param $uuid
     * @return JobInterface|null
     */
    public function find($uuid): ?JobInterface;

    /**
     * Delete a single failed job from storage
     * @param $uuid
     */
    public function forget($uuid);

    /**
     * Deletes all failed jobs from storage
     * @param string|null $queue
     * @return int
     */
    public function flush(string $queue = null): int;
}
