<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue\Job;

use Dot\Queue\Exception\ShouldStopException;
use Psr\Log\LogLevel;

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
        $this->getQueueManager()->log(LogLevel::ERROR, sprintf(
            'Could not restart queue `%s` due to error',
            $this->getQueue()->getName()
        ));
        $this->getQueueManager()->log(LogLevel::ERROR, $e->getTraceAsString());
    }

    /**
     * @param \Exception|\Throwable $e
     */
    public function failed($e)
    {
        $this->getQueueManager()->log(LogLevel::ERROR, sprintf(
            'Restart job failed due to error',
            $this->getQueue()->getName()
        ));
    }
}
