<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue\Console\Command;

use Laminas\Console\Adapter\AdapterInterface;
use ZF\Console\Route;

/**
 * Class FlushFailedCommand
 * @package Dot\Queue\Console\Command
 */
class FlushFailedCommand extends AbstractFailedCommand
{
    /**
     * @param Route $route
     * @param AdapterInterface $console
     */
    public function __invoke(Route $route, AdapterInterface $console)
    {
        $queue = $route->getMatchedParam('queue', null);
        $n = $this->failedJobProvider->flush($queue);

        $console->writeLine("$n job(s) were flushed!");
    }
}
