<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue\Factory;

use Dot\Queue\Options\QueueOptions;
use Dot\Queue\Queue\QueueManager;
use Psr\Container\ContainerInterface;

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
        $options = $container->get(QueueOptions::class);
        return new QueueManager($options, $container, $config['queue_manager']);
    }
}
