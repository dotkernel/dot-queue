<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue\Queue;

use Dot\Queue\Exception\RuntimeException;

/**
 * Class Queue
 * @package Dot\Queue
 */
abstract class AbstractQueue implements QueueInterface
{
    /** @var  QueueManager */
    protected $queueManager;

    /** @var  string */
    protected $name;

    /** @var int  */
    protected $retryAfter = 60;

    /**
     * AbstractQueue constructor.
     * @param array|null $options
     */
    public function __construct(array $options = null)
    {
        if (isset($options['name'])) {
            $this->setName($options['name']);
        }

        if (isset($options['retry_after'])) {
            $this->setRetryAfter($options['retry_after']);
        }

        if (isset($options['queue_manager'])) {
            $this->setQueueManager($options['queue_manager']);
        }
    }

    /**
     * Make sure required dependencies were injected
     */
    public function validate()
    {
        if (!$this->queueManager instanceof QueueManager) {
            throw new RuntimeException(sprintf('Queue manager is not set for queue `%s`', $this->getName()));
        }
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
     * @return QueueInterface
     */
    public function setQueueManager(QueueManager $queueManager): QueueInterface
    {
        $this->queueManager = $queueManager;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return QueueInterface
     */
    public function setName(string $name): QueueInterface
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return int
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }

    /**
     * @param int $retryAfter
     * @return QueueInterface
     */
    public function setRetryAfter(int $retryAfter): QueueInterface
    {
        $this->retryAfter = $retryAfter;
        return $this;
    }
}
