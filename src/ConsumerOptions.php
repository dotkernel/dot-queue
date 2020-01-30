<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue;

use Laminas\Stdlib\AbstractOptions;

/**
 * Class WorkerOptions
 * @package Dot\Queue
 */
class ConsumerOptions extends AbstractOptions
{
    const QUEUES_ALL = 'QUEUES_ALL';

    /** @var int  */
    protected $sleep = 1;

    /** @var int  */
    protected $maxRuntime = 0;

    /** @var int  */
    protected $maxJobs = 0;

    /** @var int  */
    protected $memoryLimit = 128;

    /** @var  array */
    protected $queues;

    /** @var bool  */
    protected $stopOnError = false;

    /** @var bool  */
    protected $stopOnEmpty = false;

    /**
     * WorkerOptions constructor.
     * @param null $options
     */
    public function __construct($options = null)
    {
        $this->__strictMode__ = false;
        parent::__construct($options);
    }

    /**
     * @return array|string
     */
    public function getQueues()
    {
        return $this->queues ?? [];
    }

    /**
     * @param array|string $queues
     * @return ConsumerOptions
     */
    public function setQueues($queues): ConsumerOptions
    {
        $this->queues = $queues;
        return $this;
    }

    /**
     * @return int
     */
    public function getSleep(): int
    {
        return $this->sleep;
    }

    /**
     * @param int $sleep
     * @return ConsumerOptions
     */
    public function setSleep(int $sleep): ConsumerOptions
    {
        $this->sleep = $sleep;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxRuntime(): int
    {
        return $this->maxRuntime;
    }

    /**
     * @param int $maxRuntime
     * @return ConsumerOptions
     */
    public function setMaxRuntime(int $maxRuntime): ConsumerOptions
    {
        $this->maxRuntime = $maxRuntime;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxJobs(): int
    {
        return $this->maxJobs;
    }

    /**
     * @param int $maxJobs
     * @return ConsumerOptions
     */
    public function setMaxJobs(int $maxJobs): ConsumerOptions
    {
        $this->maxJobs = $maxJobs;
        return $this;
    }

    /**
     * @return int
     */
    public function getMemoryLimit(): int
    {
        return $this->memoryLimit;
    }

    /**
     * @param int $memoryLimit
     * @return ConsumerOptions
     */
    public function setMemoryLimit(int $memoryLimit): ConsumerOptions
    {
        $this->memoryLimit = $memoryLimit;
        return $this;
    }

    /**
     * @return bool
     */
    public function isStopOnError(): bool
    {
        return $this->stopOnError;
    }

    /**
     * @param bool $stopOnError
     * @return ConsumerOptions
     */
    public function setStopOnError(bool $stopOnError): ConsumerOptions
    {
        $this->stopOnError = $stopOnError;
        return $this;
    }

    /**
     * @return bool
     */
    public function isStopOnEmpty(): bool
    {
        return $this->stopOnEmpty;
    }

    /**
     * @param bool $stopOnEmpty
     * @return ConsumerOptions
     */
    public function setStopOnEmpty(bool $stopOnEmpty): ConsumerOptions
    {
        $this->stopOnEmpty = $stopOnEmpty;
        return $this;
    }
}
