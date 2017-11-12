<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue\Factory;

use Dot\Queue\Exception\RuntimeException;
use Dot\Queue\Options\QueueOptions;
use Dot\Queue\Queue\QueueManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class QueueManagerFactory
 * @package Dot\Queue\Factory
 */
class QueueManagerFactory
{
    /**
     * @param ContainerInterface $container
     * @return QueueManager
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config')['dot_queue'];
        /** @var QueueOptions $options */
        $options = $container->get(QueueOptions::class);

        $logger = null;
        if ($options->getLogger()) {
            $logger = $this->getLoggerInstance($container, $options->getLogger());
        }

        return new QueueManager($options, $container, $config['queue_manager'], $logger);
    }

    /**
     * @param ContainerInterface $container
     * @param $logger
     * @return LoggerInterface
     */
    protected function getLoggerInstance(ContainerInterface $container, $logger): LoggerInterface
    {
        if ($logger instanceof LoggerInterface) {
            return $logger;
        }

        if ($container->has($logger)) {
            $logger = $container->get($logger);
        }

        if (is_string($logger) && class_exists($logger)) {
            $logger = new $logger;
        }

        if (!$logger instanceof LoggerInterface) {
            throw new RuntimeException(sprintf(
                'Queue logger should implement PSR3 `%s`, but `%s` was given',
                LoggerInterface::class,
                is_object($logger) ? get_class($logger) : gettype($logger)
            ));
        }

        return $logger;
    }
}
