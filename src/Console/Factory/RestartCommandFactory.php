<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue\Console\Factory;

use Dot\Queue\Console\Command\RestartCommand;
use Dot\Queue\Queue\QueueManager;
use Psr\Container\ContainerInterface;

/**
 * Class RestartCommandFactory
 * @package Dot\Queue\Console\Factory
 */
class RestartCommandFactory
{
    /**
     * @param ContainerInterface $container
     * @return RestartCommand
     */
    public function __invoke(ContainerInterface $container)
    {
        return new RestartCommand($container->get(QueueManager::class));
    }
}
