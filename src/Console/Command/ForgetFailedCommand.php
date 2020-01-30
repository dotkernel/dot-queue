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
 * Class ForgetFailedCommand
 * @package Dot\Queue\Console\Command
 */
class ForgetFailedCommand extends AbstractFailedCommand
{
    /**
     * @param Route $route
     * @param AdapterInterface $console
     */
    public function __invoke(Route $route, AdapterInterface $console)
    {
        $uuid = $route->getMatchedParam('uuid', null);
        if (!$uuid) {
            $console->writeLine('Specify job\'s UUID to forget');
        } else {
            $job = $this->failedJobProvider->find($uuid);
            if (!$job) {
                $console->writeLine('Cannot find job with UUID: ' . $uuid);
            } else {
                $this->failedJobProvider->forget($uuid);
                $console->writeLine("Job $uuid was removed from the failed list");
            }
        }
    }
}
