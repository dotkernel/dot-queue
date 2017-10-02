<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue\Factory;

use Psr\Container\ContainerInterface;

/**
 * Class DatabaseAdapterFactory
 * @package Dot\Queue\Exception
 */
class DatabaseAdapterFactory
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     * @return mixed
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if (isset($options['db_adapter']) && is_string($options['db_adapter'])) {
            $options['db_adapter'] = $container->get($options['db_adapter']);
        }

        return new $requestedName($options);
    }
}
