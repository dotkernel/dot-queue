<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue\Console\Factory;

use Dot\Queue\Failed\FailedJobProviderInterface;
use Dot\Queue\Queue\QueueManager;
use Psr\Container\ContainerInterface;

/**
 * Class FailedCommandFactory
 * @package Dot\Queue\Console\Factory
 */
class FailedCommandFactory
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @return mixed
     */
    public function __invoke(ContainerInterface $container, $requestedName)
    {
        return new $requestedName(
            $container->get(FailedJobProviderInterface::class),
            $container->get(QueueManager::class)
        );
    }
}
