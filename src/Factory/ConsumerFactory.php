<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue\Factory;

use Dot\Queue\Consumer;
use Dot\Queue\Failed\FailedJobProviderInterface;
use Dot\Queue\Options\QueueOptions;
use Dot\Queue\Queue\QueueManager;
use Psr\Container\ContainerInterface;

/**
 * Class ConsumerFactory
 * @package Dot\Queue\Factory
 */
class ConsumerFactory
{
    /**
     * @param ContainerInterface $container
     * @return Consumer
     */
    public function __invoke(ContainerInterface $container)
    {
        return new Consumer(
            $container->get(QueueManager::class),
            $container->get(QueueOptions::class),
            $container->get(FailedJobProviderInterface::class)
        );
    }
}
