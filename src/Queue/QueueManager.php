<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue\Queue;

use Dot\Queue\Exception\RuntimeException;
use Dot\Queue\Factory\PersistentQueueFactory;
use Dot\Queue\Job\JobInterface;
use Dot\Queue\Options\QueueOptions;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\ServiceManager;

/**
 * Class QueueManager
 * @package Dot\Queue
 */
class QueueManager extends AbstractPluginManager
{
    /** @var  ServiceManager */
    protected $container;

    /** @var  QueueOptions */
    protected $options;

    /** @var QueueInterface[] */
    protected $queues = [];

    /** @var string  */
    protected $instanceOf = QueueInterface::class;

    /** @var array  */
    protected $factories = [
        PersistentQueue::class => PersistentQueueFactory::class,
        InMemoryQueue::class => InvokableFactory::class,
    ];

    /**
     * QueueManager constructor.
     * @param QueueOptions $options
     * @param null $configInstanceOrParentLocator
     * @param array $config
     */
    public function __construct(QueueOptions $options, $configInstanceOrParentLocator = null, array $config = [])
    {
        parent::__construct($configInstanceOrParentLocator, $config);
        $this->container = $configInstanceOrParentLocator;
        $this->options = $options;
    }

    /**
     * @param string $name
     * @param array|null $options
     * @return mixed
     */
    public function get($name, array $options = null)
    {
        if (isset($this->queues[$name]) && empty($options)) {
            return $this->queues[$name];
        }

        $queuesConfig = $this->options->getQueues();
        if (!isset($queuesConfig[$name]) || !is_array($queuesConfig[$name])) {
            throw new RuntimeException(sprintf('Queue with name `%s` is not configured', $name));
        }

        $type = $queuesConfig[$name]['type'] ?? PersistentQueue::class;

        $queueOptions = $options ?? [];
        $queueOptions['name'] = $name;
        $queueOptions += $queuesConfig[$name]['options'];

        /** @var QueueInterface $queue */
        $queue = parent::get($type, $queueOptions);
        $queue->setQueueManager($this);

        if (empty($options)) {
            $this->queues[$name] = $queue;
        }

        return $queue;
    }

    /**
     * @return array
     */
    public function queueList(): array
    {
        return array_keys($this->options->getQueues());
    }

    /**
     * @param string $className
     * @param array $options
     * @return JobInterface
     */
    public function createJob(string $className, array $options = []): JobInterface
    {
        $job = $className;
        if ($this->container->has($className)) {
            $job = $this->container->build($className);
        }

        if (is_string($job) && class_exists($job)) {
            $job = new $job();
        }

        if (!$job instanceof JobInterface) {
            throw new RuntimeException(sprintf('Could not create job `%s`', $className));
        }

        $job->setQueueManager($this)
            ->withData($options);

        return $job;
    }

    /**
     * @param string $payload
     * @return JobInterface
     */
    public function createJobFromPayload(string $payload): JobInterface
    {
        $data = $this->unserialize($payload);
        return $this->createJob($data['class'], $data);
    }

    /**
     * @param JobInterface $job
     * @return string
     */
    public function serialize(JobInterface $job): string
    {
        $payload = \json_encode($job);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new RuntimeException(
                'Unable to JSON encode payload. Error code: '.json_last_error()
            );
        }

        return $payload;
    }

    /**
     * @param string $payload
     * @return array
     */
    public function unserialize(string $payload): array
    {
        $data = \json_decode($payload, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new RuntimeException(
                'Unable to JSON decode payload. Error code: ' . json_last_error()
            );
        }

        return $data;
    }

    /**
     * @param JobInterface $job
     * @return string
     */
    public function createPayload(JobInterface $job): string
    {
        return $this->serialize($job);
    }

    /**
     * @return QueueInterface
     */
    public function getDefaultQueue(): QueueInterface
    {
        return $this->get($this->options->getDefaultQueue());
    }

    /**
     * @return QueueOptions
     */
    public function getOptions(): QueueOptions
    {
        return $this->options;
    }

    /**
     * @param QueueOptions $options
     * @return QueueManager
     */
    public function setOptions(QueueOptions $options): QueueManager
    {
        $this->options = $options;
        return $this;
    }
}
