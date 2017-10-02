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
 * Class InMemoryQueue
 * @package Dot\Queue\Queue
 */
class InMemoryQueue extends AbstractQueue
{
    /** @var  \SplQueue */
    protected $queue;

    /**
     * InMemoryQueue constructor.
     * @param null $options
     */
    public function __construct($options = null)
    {
        parent::__construct($options);
        $this->queue = new \SplQueue();
        $this->queue->setIteratorMode(\SplQueue::IT_MODE_DELETE);
    }

    /**
     * @param JobInterface $job
     */
    public function enqueue(JobInterface $job)
    {
        $job->setQueue($this);
        $this->queue->enqueue($job);
    }

    /**
     * @return JobInterface|null
     */
    public function dequeue(): ?JobInterface
    {
        $job = null;
        if ($this->count()) {
            /** @var JobInterface $job */
            $job = $this->queue->dequeue();
            $job->setQueue($this);
        }

        return $job;
    }

    /**
     * @param JobInterface $job
     */
    public function acknowledge(JobInterface $job)
    {
        // NO-OP
    }

    public function remove(JobInterface $job)
    {
        // TODO: Implement remove() method.
    }

    /**
     * Clears the queue
     */
    public function purge()
    {
        $this->queue = new \SplQueue();
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->queue->count();
    }
}
