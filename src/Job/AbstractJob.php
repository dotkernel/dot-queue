<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue\Job;

use Dot\Queue\Exception\RuntimeException;
use Dot\Queue\Queue\QueueInterface;
use Dot\Queue\Queue\QueueManager;
use Ramsey\Uuid\Uuid;

/**
 * Class Job
 * @package Dot\Queue\Job
 */
abstract class AbstractJob implements JobInterface, \JsonSerializable
{
    /** @var  string */
    protected $uuid;

    /** @var  array */
    protected $data;

    /** @var int  */
    protected $attempts = 0;

    /** @var int  */
    protected $maxAttempts = 3;

    /** @var int  */
    protected $priority = 1;

    /** @var int  */
    protected $delay = 0;

    /** @var int  */
    protected $timeout = 30;

    /** @var  QueueInterface */
    protected $queue;

    /** @var  QueueManager|string */
    protected $queueManager;

    /**
     * @param array $options
     * @return JobInterface
     */
    public function setOptions(array $options = []): JobInterface
    {
        if (isset($options['uuid'])) {
            $this->setUUID((string)$options['uuid']);
        }

        if (isset($options['attempts'])) {
            $this->setAttempts((int)$options['attempts']);
        }

        if (isset($options['maxAttempts'])) {
            $this->setMaxAttempts((int)$options['maxAttempts']);
        }

        if (isset($options['priority'])) {
            $this->setPriority((int)$options['priority']);
        }

        if (isset($options['timeout'])) {
            $this->setTimeout((int)$options['timeout']);
        }

        if (isset($options['queue'])) {
            $this->setQueue($options['queue']);
        }

        if (isset($options['data']) && is_array($options['data'])) {
            $this->data = $options['data'];
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getUUID(): string
    {
        if (!$this->uuid) {
            $this->uuid = Uuid::uuid4()->toString();
        }
        return $this->uuid;
    }

    /**
     * @param string $uuid
     * @return JobInterface
     */
    public function setUUID(string $uuid): JobInterface
    {
        $this->uuid = $uuid;
        return $this;
    }

    /**
     * @param string $key
     * @param $value
     * @return JobInterface
     */
    public function set(string $key, $value): JobInterface
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * @param string $key
     * @param null $default
     * @return mixed|null
     */
    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * @return int
     */
    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    /**
     * @param int $maxAttempts
     * @return JobInterface
     */
    public function setMaxAttempts(int $maxAttempts): JobInterface
    {
        $this->maxAttempts = $maxAttempts;
        return $this;
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     * @return JobInterface
     */
    public function setPriority(int $priority): JobInterface
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * @return int
     */
    public function getDelay(): int
    {
        return $this->delay;
    }

    /**
     * @param int $delay
     * @return JobInterface
     */
    public function setDelay(int $delay): JobInterface
    {
        $this->delay = $delay;
        return $this;
    }

    /**
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     * @return JobInterface
     */
    public function setTimeout(int $timeout): JobInterface
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * @return int
     */
    public function getAttempts(): int
    {
        return $this->attempts;
    }

    /**
     * @param int $attempts
     * @return JobInterface
     */
    public function setAttempts(int $attempts): JobInterface
    {
        $this->attempts = $attempts;
        return $this;
    }

    /**
     * @return int
     */
    public function increment(): int
    {
        $this->attempts++;
        return $this->attempts;
    }

    /**
     * @param null|string|QueueInterface $queue
     * @return JobInterface
     */
    public function dispatch($queue = null): JobInterface
    {
        $queue = $queue ?? $this->queueManager->getDefaultQueue();
        if (is_string($queue)) {
            $queue = $this->queueManager->get($queue);
        }

        $this->setQueue($queue);
        $queue->enqueue($this);
        return $this;
    }

    /**
     * @param int $delay
     * @return JobInterface
     */
    public function release(int $delay = 0): JobInterface
    {
        $this->delete();

        $this->setDelay($delay + $this->getQueue()->getRetryAfter());
        return $this->dispatch($this->getQueue());
    }

    /**
     * Deletes the job from the queue
     */
    public function delete()
    {
        $this->getQueue()->remove($this);
    }

    /**
     * Process the job
     */
    abstract public function process();

    /**
     * @param \Exception|\Throwable $e
     */
    abstract public function failed($e);

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'class' => get_class($this),
            'queue' => $this->getQueue()->getName(),
            'uuid' => $this->getUUID(),
            'attempts' => $this->getAttempts(),
            'maxAttempts' => $this->getMaxAttempts(),
            'priority' => $this->getPriority(),
            'timeout' => $this->getTimeout(),
            'data' => $this->data,
        ];
    }

    /**
     * @return QueueInterface
     */
    public function getQueue(): QueueInterface
    {
        if (!$this->queue) {
            $this->queue = $this->queueManager->getDefaultQueue();
        }

        if (is_string($this->queue)) {
            $this->queue = $this->getQueueManager()->get($this->queue);
        }

        return $this->queue;
    }

    /**
     * @param string|QueueInterface $queue
     * @return JobInterface
     */
    public function setQueue($queue): JobInterface
    {
        $this->queue = $queue;
        return $this;
    }

    /**
     * @return QueueManager
     */
    public function getQueueManager(): QueueManager
    {
        if (!$this->queueManager instanceof QueueManager) {
            throw new RuntimeException(sprintf('Queue Manager not set for job `%s`', get_class($this)));
        }
        return $this->queueManager;
    }

    /**
     * @param QueueManager $queueManager
     * @return JobInterface
     */
    public function setQueueManager(QueueManager $queueManager): JobInterface
    {
        $this->queueManager = $queueManager;
        return $this;
    }
}
