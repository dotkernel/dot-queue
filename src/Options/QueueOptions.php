<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue\Options;

use Laminas\Stdlib\AbstractOptions;

/**
 * Class QueueOptions
 * @package Dot\Queue\Options
 */
class QueueOptions extends AbstractOptions
{
    /** @var array  */
    protected $adapters = [];

    /** @var array  */
    protected $queues = [];

    /** @var  string */
    protected $defaultQueue;

    /** @var  array */
    protected $failedJobProvider;

    /** @var  string */
    protected $logger;

    /**
     * QueueOptions constructor.
     * @param null $options
     */
    public function __construct($options = null)
    {
        $this->__strictMode__ = false;
        parent::__construct($options);
    }

    /**
     * @return array
     */
    public function getAdapters(): array
    {
        return $this->adapters;
    }

    /**
     * @param array $adapters
     */
    public function setAdapters(array $adapters)
    {
        $this->adapters = $adapters;
    }

    /**
     * @return array
     */
    public function getQueues(): array
    {
        return $this->queues;
    }

    /**
     * @param array $queues
     */
    public function setQueues(array $queues)
    {
        $this->queues = $queues;
    }

    /**
     * @return string
     */
    public function getDefaultQueue(): string
    {
        return $this->defaultQueue;
    }

    /**
     * @param string $defaultQueue
     * @return QueueOptions
     */
    public function setDefaultQueue(string $defaultQueue): QueueOptions
    {
        $this->defaultQueue = $defaultQueue;
        return $this;
    }

    /**
     * @return array
     */
    public function getFailedJobProvider(): array
    {
        return $this->failedJobProvider;
    }

    /**
     * @param array $failedJobProvider
     * @return QueueOptions
     */
    public function setFailedJobProvider(array $failedJobProvider): QueueOptions
    {
        $this->failedJobProvider = $failedJobProvider;
        return $this;
    }

    /**
     * @return string
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param string $logger
     * @return QueueOptions
     */
    public function setLogger($logger): QueueOptions
    {
        $this->logger = $logger;
        return $this;
    }
}
