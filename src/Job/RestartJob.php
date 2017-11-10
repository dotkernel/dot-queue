<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue\Job;

use Dot\Queue\Exception\ShouldStopException;

/**
 * Class RestartJob
 * @package Dot\Queue\Job
 */
class RestartJob extends AbstractJob
{
    /** @var int  */
    protected $maxAttempts = 1;

    /** @var int  */
    protected $priority = 999999;

    /**
     * Terminate the queue consumer this job is running onto
     * Because you'll run this with supervisor or alike, it will automatically be restarted
     */
    public function process()
    {
        throw new ShouldStopException('Force restart queues');
    }

    /**
     * @param \Throwable|\Exception $e
     */
    public function error($e)
    {
        \error_log('Could not restart queue: ' . $this->getQueue()->getName() . ', error: ' . $e->getMessage());
    }

    public function failed($e)
    {
        \error_log('Queue: ' . $this->getQueue()->getName() . ', failed to restart with error: ' . $e->getMessage());
    }
}
