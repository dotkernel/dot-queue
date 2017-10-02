<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue\Queue;

use Dot\Queue\Adapter\AdapterInterface;
use Dot\Queue\Adapter\AdapterManager;
use Dot\Queue\Exception\RuntimeException;
use Dot\Queue\Job\JobInterface;

/**
 * Class Queue
 * @package Dot\Queue\Queue
 */
class PersistentQueue extends AbstractQueue
{
    /** @var  AdapterInterface|string */
    protected $adapter;

    /** @var  AdapterManager */
    protected $adapterManager;

    /**
     * AbstractQueue constructor.
     * @param array|null $options
     */
    public function __construct(array $options = null)
    {
        parent::__construct($options);

        if (isset($options['adapter_manager']) && $options['adapter_manager'] instanceof AdapterManager) {
            $this->adapterManager = $options['adapter_manager'];
        }

        if (isset($options['adapter'])) {
            $this->setAdapter($options['adapter']);
        }
    }

    public function validate()
    {
        parent::validate();

        if (!$this->adapter instanceof AdapterInterface && !is_string($this->adapter)) {
            throw new RuntimeException(sprintf('Queue adapter is not set for queue `%s`', $this->getName()));
        }
    }

    /**
     * @return int
     */
    public function count(): int
    {
        $this->validate();
        return $this->getAdapter()->count($this);
    }

    /**
     * @param JobInterface $job
     */
    public function enqueue(JobInterface $job)
    {
        $this->validate();
        $job->setQueue($this);
        $this->getAdapter()->enqueue($this, $job);
    }

    /**
     * @return JobInterface
     */
    public function dequeue(): ?JobInterface
    {
        $this->validate();
        $job = $this->getAdapter()->dequeue($this);
        if ($job) {
            $job->setQueue($this);
        }

        return $job;
    }

    /**
     * @param JobInterface $job
     */
    public function acknowledge(JobInterface $job)
    {
        $this->validate();
        $this->getAdapter()->acknowledge($this, $job);
    }

    /**
     * @param JobInterface $job
     */
    public function remove(JobInterface $job)
    {
        $this->validate();
        $this->getAdapter()->delete($this, $job);
    }

    /**
     * Purge this queue
     */
    public function purge()
    {
        $this->validate();
        $this->getAdapter()->purge($this);
    }

    /**
     * @return AdapterInterface
     */
    public function getAdapter(): AdapterInterface
    {
        if (is_string($this->adapter)) {
            $this->adapter = $this->adapterManager->get($this->adapter);
        }

        return $this->adapter;
    }

    /**
     * @param AdapterInterface|string $adapter
     */
    public function setAdapter($adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @return AdapterManager
     */
    public function getAdapterManager(): AdapterManager
    {
        if (!$this->adapterManager) {
            throw new RuntimeException(sprintf('Adapter manager was not set for queue `%s`', $this->getName()));
        }
        return $this->adapterManager;
    }

    /**
     * @param AdapterManager $adapterManager
     * @return PersistentQueue
     */
    public function setAdapterManager(AdapterManager $adapterManager): PersistentQueue
    {
        $this->adapterManager = $adapterManager;
        return $this;
    }
}
