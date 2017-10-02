<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue\Factory;

use Dot\Queue\Adapter\AdapterManager;
use Dot\Queue\Options\QueueOptions;
use Psr\Container\ContainerInterface;

/**
 * Class AdapterManagerFactory
 * @package Dot\Queue\Factory
 */
class AdapterManagerFactory
{
    /**
     * @param ContainerInterface $container
     * @return AdapterManager
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config')['dot_queue'];
        $options = $container->get(QueueOptions::class);
        return new AdapterManager($options, $container, $config['adapter_manager']);
    }
}
