<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue\Factory;

use Dot\Queue\Options\QueueOptions;
use Psr\Container\ContainerInterface;

/**
 * Class QueueOptionsFactory
 * @package Dot\Queue\Factory
 */
class QueueOptionsFactory
{
    /**
     * @param ContainerInterface $container
     * @return QueueOptions
     */
    public function __invoke(ContainerInterface $container)
    {
        return new QueueOptions($container->get('config')['dot_queue']);
    }
}
