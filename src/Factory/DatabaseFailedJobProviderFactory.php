<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue\Factory;

use Dot\Queue\Failed\DatabaseFailedJobProvider;
use Dot\Queue\Options\QueueOptions;
use Dot\Queue\Queue\QueueManager;
use Psr\Container\ContainerInterface;

/**
 * Class DatabaseFailedJobProviderFactory
 * @package Dot\Queue\Factory
 */
class DatabaseFailedJobProviderFactory
{
    /**
     * @param ContainerInterface $container
     * @return DatabaseFailedJobProvider
     */
    public function __invoke(ContainerInterface $container)
    {
        /** @var QueueOptions $options */
        $options = $container->get(QueueOptions::class);

        $config = $options->getFailedJobProvider();
        $config['queue_manager'] = $container->get(QueueManager::class);

        if (isset($config['db_adapter']) && is_string($config['db_adapter'])) {
            $config['db_adapter'] = $container->get($config['db_adapter']);
        }

        return new DatabaseFailedJobProvider($config);
    }
}
