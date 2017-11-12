<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue;

use Dot\Queue\Exception\MaxAttemptsExceededException;
use Dot\Queue\Exception\ShouldStopException;
use Dot\Queue\Failed\FailedJobProviderInterface;
use Dot\Queue\Job\JobInterface;
use Dot\Queue\Job\RestartJob;
use Dot\Queue\Options\QueueOptions;
use Dot\Queue\Queue\QueueInterface;
use Dot\Queue\Queue\QueueManager;
use Psr\Log\LogLevel;

/**
 * Class Worker
 * @package Dot\Queue
 */
class Consumer
{
    /** @var  QueueManager */
    protected $queueManager;

    /** @var  QueueOptions */
    protected $queueOptions;

    /** @var bool  */
    protected $shutdown = false;

    /** @var bool  */
    protected $pause = false;

    /** @var  int */
    protected $startTime;

    /** @var int  */
    protected $processedJobs = 0;

    /** @var  ConsumerOptions */
    protected $options;

    /** @var  FailedJobProviderInterface */
    protected $failedJobProvider;

    /**
     * Consumer constructor.
     * @param QueueManager $queueManager
     * @param QueueOptions $queueOptions
     * @param FailedJobProviderInterface $failedJobProvider
     */
    public function __construct(
        QueueManager $queueManager,
        QueueOptions $queueOptions,
        FailedJobProviderInterface $failedJobProvider
    ) {
        $this->queueManager = $queueManager;
        $this->queueOptions = $queueOptions;
        $this->failedJobProvider = $failedJobProvider;
    }

    /**
     * @param QueueInterface $queue
     * @param ConsumerOptions $options
     */
    public function run(QueueInterface $queue, ConsumerOptions $options)
    {
        $this->options = $options;
        $this->listenForSignals();

        $this->queueManager->log(LogLevel::INFO, sprintf('starting queue `%s`', $queue->getName()));

        $this->startTime = microtime(true);
        $this->processedJobs = 0;
        while ($this->tick($queue)) {
            // NO-OP
        }

        $this->queueManager->log(LogLevel::INFO, sprintf('stopping queue `%s`', $queue->getName()));
    }

    /**
     * Return false to stop the loop
     *
     * @param QueueInterface $queue
     * @return bool
     */
    protected function tick(QueueInterface $queue): bool
    {
        if ($this->shutdown) {
            return false;
        }

        if ($this->shouldStop()) {
            return false;
        }

        if ($this->pause) {
            $this->sleep($this->options->getSleep());
            return true;
        }

        if (!$job = $this->getNextJob($queue)) {
            $this->queueManager->log(LogLevel::INFO, sprintf('queue `%s` is empty', $queue->getName()));

            if ($this->options->isStopOnEmpty()) {
                return false;
            }

            $this->sleep($this->options->getSleep());
            return true;
        }

        $this->registerTimeoutHandler($job);

        $this->process($job, $queue);
        $this->processedJobs++;

        if (0 === $this->options->getMaxJobs()) {
            return true;
        }

        return $this->processedJobs !== $this->options->getMaxJobs();
    }

    /**
     * @param QueueInterface $queue
     * @return JobInterface|null
     */
    protected function getNextJob(QueueInterface $queue): ?JobInterface
    {
        try {
            return $queue->dequeue();
        } catch (\Exception $e) {
            $this->queueManager->log(
                LogLevel::ERROR,
                "error fetching next job from queue `{$queue->getName()}`"
            );
            return null;
        } catch (\Throwable $e) {
            $this->queueManager->log(
                LogLevel::ERROR,
                "error fetching next job from queue `{$queue->getName()}`"
            );

            return null;
        }
    }

    /**
     * @return bool
     */
    protected function shouldStop(): bool
    {
        if ($this->options->getMaxRuntime() > 0
            && microtime(true) > ($this->startTime + $this->options->getMaxRuntime())) {
            $this->queueManager->log(LogLevel::WARNING, 'maximum runtime exceeded, stopping queue');
            return true;
        }

        if ($this->memoryExceeded($this->options->getMemoryLimit())) {
            $this->queueManager->log(LogLevel::WARNING, 'memory limit exceeded, stopping queue');
            return true;
        }

        return false;
    }

    /**
     * @param JobInterface $job
     * @param QueueInterface $queue
     */
    public function process(JobInterface $job, QueueInterface $queue)
    {
        $this->queueManager->log(
            LogLevel::INFO,
            "starting job {$job->getUUID()->toString()} in queue {$queue->getName()}"
        );

        try {
            $this->runJob($job);
            // acknowledge that the job successfully ran
            $queue->acknowledge($job);
        } catch (ShouldStopException $e) {
            $this->shutdown = true;
            if ($job instanceof RestartJob) {
                $this->queueManager->log(
                    LogLevel::INFO,
                    "restart command processed for queue `{$queue->getName()}`"
                );
                $queue->acknowledge($job);
            } else {
                $this->handleJobException($e->getPrevious() ? $e->getPrevious() : $e, $job);
            }
        } catch (MaxAttemptsExceededException $e) {
            $this->handleJobFailed($e->getPrevious() ? $e->getPrevious() : $e, $job);
        } catch (\Exception $e) {
            $this->handleJobException($e, $job);
        } catch (\Throwable $e) {
            $this->handleJobException($e, $job);
        }
    }

    /**
     * @param JobInterface $job
     */
    protected function runJob(JobInterface $job)
    {
        // TODO: trigger before job event

        // mark job as failed if already exceeds max attempts
        // this could happen if the job constantly timeouts, without the job raising exceptions from inside
        if ($job->getMaxAttempts() > 0 && $job->getAttempts() > $job->getMaxAttempts()) {
            throw new MaxAttemptsExceededException('Job exceeded maximum attempts due to timeouts');
        }

        $job->process();

        // TODO: trigger after job event
    }

    /**
     * @param \Exception|\Throwable $e
     * @param JobInterface $job
     * @throws \Exception|\Throwable
     */
    protected function handleJobException($e, JobInterface $job)
    {
        if ($job->getMaxAttempts() > 0 && $job->getAttempts() >= $job->getMaxAttempts()) {
            $this->handleJobFailed(
                new MaxAttemptsExceededException('Job exceeded its maximum attempts', $e),
                $job
            );

            return;
        }

        $this->queueManager->log(LogLevel::ERROR, "error in job {$job->getUUID()->toString()}");
        $this->queueManager->log(LogLevel::ERROR, $e->getTraceAsString());

        // TODO: trigger job exception event

        // call the error method on the job, for possible cleanup
        $job->error($e);

        if (!$job->isReleased() && !$job->isDeleted()) {
            // release the job back into the queue, if there's attempts left
            $job->release();
        }

        if ($this->options->isStopOnError()) {
            throw $e;
        }
    }

    /**
     * @param JobInterface $job
     * @param \Exception|\Throwable $e
     */
    protected function handleJobFailed($e, JobInterface $job)
    {
        try {
            if (!$job->isReleased() && !$job->isDeleted()) {
                $job->delete();
                // call the failed method of the job for cleaning up
            }

            $this->queueManager->log(LogLevel::ERROR, "job {$job->getUUID()->toString()} has failed");
            $this->queueManager->log(LogLevel::ERROR, $e->getTraceAsString());

            // call the error method, then the failed method, for cleanup
            $job->error($e);
            $job->failed($e);
        } finally {
            try {
                $this->queueManager->log(
                    LogLevel::INFO,
                    "moving job {$job->getUUID()->toString()} to failed jobs list"
                );
                $this->failedJobProvider->log($job->getQueue(), $job, $e);
            } catch (\Exception $e) {
                $this->queueManager->log(
                    LogLevel::ERROR,
                    "error moving job {$job->getUUID()->toString()} to failed jobs list"
                );
            }

            // TODO: trigger job failed event
        }
    }

    /**
     * Listen for UNIX signals, if available
     */
    protected function listenForSignals()
    {
        if ($this->supportsAsyncSignals()) {
            pcntl_async_signals(true);

            pcntl_signal(SIGTERM, function () {
                $this->shutdown = true;
            });

            pcntl_signal(SIGQUIT, function () {
                $this->shutdown = true;
            });

            pcntl_signal(SIGINT, function () {
                $this->shutdown = true;
            });

            pcntl_signal(SIGUSR2, function () {
                $this->pause = true;
            });

            pcntl_signal(SIGCONT, function () {
                $this->pause = false;
            });
        }
    }

    /**
     * @param JobInterface $job
     */
    protected function registerTimeoutHandler(JobInterface $job)
    {
        if ($this->supportsAsyncSignals()) {
            // We will register a signal handler for the alarm signal so that we can kill this
            // process if it is running too long because it has frozen. This uses the async
            // signals supported in recent versions of PHP to accomplish it conveniently.
            pcntl_signal(SIGALRM, function () {
                $this->kill(1);
            });
            pcntl_alarm(
                max($job->getTimeout(), 0)
            );
        }
    }

    /**
     * @return bool
     */
    protected function supportsAsyncSignals(): bool
    {
        return version_compare(PHP_VERSION, '7.1.0') >= 0 &&
            extension_loaded('pcntl');
    }

    /**
     * Determine if the memory limit has been exceeded.
     *
     * @param  int   $memoryLimit
     * @return bool
     */
    public function memoryExceeded($memoryLimit)
    {
        return (memory_get_usage() / 1024 / 1024) >= $memoryLimit;
    }


    /**
     * Stop listening and bail out of the script.
     *
     * @param  int  $status
     * @return void
     */
    public function stop($status = 0)
    {
        exit($status);
    }
    /**
     * Kill the process.
     *
     * @param  int  $status
     * @return void
     */
    public function kill($status = 0)
    {
        if (extension_loaded('posix')) {
            posix_kill(getmypid(), SIGKILL);
        }
        exit($status);
    }

    /**
     * Sleep the script for a given number of seconds.
     *
     * @param  int   $seconds
     * @return void
     */
    public function sleep($seconds)
    {
        sleep($seconds);
    }

    /**
     * @return QueueManager
     */
    public function getQueueManager(): QueueManager
    {
        return $this->queueManager;
    }

    /**
     * @param QueueManager $queueManager
     * @return Consumer
     */
    public function setQueueManager(QueueManager $queueManager): Consumer
    {
        $this->queueManager = $queueManager;
        return $this;
    }

    /**
     * @return QueueOptions
     */
    public function getQueueOptions(): QueueOptions
    {
        return $this->queueOptions;
    }

    /**
     * @param QueueOptions $queueOptions
     * @return Consumer
     */
    public function setQueueOptions(QueueOptions $queueOptions): Consumer
    {
        $this->queueOptions = $queueOptions;
        return $this;
    }
}
