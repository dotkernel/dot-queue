<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue\Console\Factory;

use Dot\Queue\Console\Command\ConsumeCommand;
use Dot\Queue\Consumer;
use Psr\Container\ContainerInterface;

/**
 * Class ConsumeCommandFactory
 * @package Dot\Queue\Console\Factory
 */
class ConsumeCommandFactory
{
    /**
     * @param ContainerInterface $container
     * @return ConsumeCommand
     */
    public function __invoke(ContainerInterface $container)
    {
        return new ConsumeCommand($container->get(Consumer::class));
    }
}
