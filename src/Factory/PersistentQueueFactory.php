<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue\Factory;

use Dot\Queue\Adapter\AdapterManager;
use Dot\Queue\Queue\PersistentQueue;
use Psr\Container\ContainerInterface;

/**
 * Class PersistentQueueFactory
 * @package Dot\Queue\Factory
 */
class PersistentQueueFactory
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     * @return PersistentQueue
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $adapterManager = $container->get(AdapterManager::class);
        $queue = new PersistentQueue($options);
        $queue->setAdapterManager($adapterManager);

        return $queue;
    }
}
