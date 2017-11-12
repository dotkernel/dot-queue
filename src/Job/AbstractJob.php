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
use Dot\Queue\UuidOrderedTimeBinaryCodec;
use Dot\Queue\UuidOrderedTimeGenerator;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Class Job
 * @package Dot\Queue\Job
 */
abstract class AbstractJob implements JobInterface, \JsonSerializable
{
    /** @var  UuidInterface */
    protected $uuid;

    /** @var  array */
    protected $data;

    /** @var int  */
    protected $attempts = 0;

    /** @var int  */
    protected $maxAttempts = 1;

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

    /** @var bool  */
    protected $released = false;

    /** @var bool  */
    protected $deleted = false;

    /**
     * AbstractJob constructor.
     */
    public function __construct()
    {
        $this->uuid = UuidOrderedTimeGenerator::generateUuid();
    }

    /**
     * @param array $data
     * @return JobInterface
     */
    public function withData(array $data = []): JobInterface
    {
        if (isset($data['uuid'])) {
            $this->setUUID($data['uuid']);
        }

        if (isset($data['attempts'])) {
            $this->setAttempts((int)$data['attempts']);
        }

        if (isset($data['maxAttempts'])) {
            $this->setMaxAttempts((int)$data['maxAttempts']);
        }

        if (isset($data['priority'])) {
            $this->setPriority((int)$data['priority']);
        }

        if (isset($data['timeout'])) {
            $this->setTimeout((int)$data['timeout']);
        }

        if (isset($data['queue'])) {
            $this->setQueue($data['queue']);
        }

        if (isset($data['data']) && is_array($data['data'])) {
            $this->data = $data['data'];
        }

        return $this;
    }

    /**
     * @return UuidInterface
     */
    public function getUUID(): UuidInterface
    {
        if (!$this->uuid) {
            $this->uuid = UuidOrderedTimeGenerator::generateUuid();
        }

        return $this->uuid;
    }

    /**
     * @param mixed $uuid
     * @return JobInterface
     */
    public function setUUID($uuid): JobInterface
    {
        if (is_string($uuid)) {
            $uuid = Uuid::fromString($uuid);
        }

        $uuid = UuidOrderedTimeBinaryCodec::decode($uuid);

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
        $this->released = true;
        $this->delete();

        $this->setDelay($delay + $this->getQueue()->getRetryAfter());
        return $this->dispatch($this->getQueue());
    }

    /**
     * Deletes the job from the queue
     */
    public function delete()
    {
        $this->deleted = true;
        $this->getQueue()->remove($this);
    }

    /**
     * Process the job
     */
    abstract public function process();

    /**
     * @param \Exception|\Throwable $e
     */
    public function error($e)
    {
        // TODO: Implement error() method.
    }

    /**
     * @param \Exception|\Throwable $e
     */
    public function failed($e)
    {
        // TODO: Implement failed() method.
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'class' => get_class($this),
            'queue' => $this->getQueue()->getName(),
            'uuid' => $this->getUUID()->toString(),
            'attempts' => $this->getAttempts(),
            'maxAttempts' => $this->getMaxAttempts(),
            'priority' => $this->getPriority(),
            'timeout' => $this->getTimeout(),
            'data' => $this->data,
        ];
    }

    /**
     * @return bool
     */
    public function isReleased(): bool
    {
        return $this->released;
    }

    /**
     * @param bool $released
     * @return AbstractJob
     */
    public function setReleased(bool $released): AbstractJob
    {
        $this->released = $released;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * @param bool $deleted
     * @return AbstractJob
     */
    public function setDeleted(bool $deleted): AbstractJob
    {
        $this->deleted = $deleted;
        return $this;
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
