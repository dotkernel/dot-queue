<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue\Adapter;

use Dot\Queue\Exception\RuntimeException;
use Dot\Queue\Factory\DatabaseAdapterFactory;
use Dot\Queue\Options\QueueOptions;
use Zend\ServiceManager\AbstractPluginManager;

/**
 * Class AdapterManager
 * @package Dot\Queue\Adapter
 */
class AdapterManager extends AbstractPluginManager
{
    /** @var  QueueOptions */
    protected $options;

    /** @var AdapterInterface[] */
    protected $adapters = [];

    /** @var string  */
    protected $instanceOf = AdapterInterface::class;

    /** @var array  */
    protected $factories = [
        DatabaseAdapter::class => DatabaseAdapterFactory::class,
    ];

    /**
     * AdapterManager constructor.
     * @param QueueOptions $options
     * @param null $configInstanceOrParentLocator
     * @param array $config
     */
    public function __construct(QueueOptions $options, $configInstanceOrParentLocator = null, array $config = [])
    {
        parent::__construct($configInstanceOrParentLocator, $config);
        $this->options = $options;
    }

    /**
     * @param string $name
     * @param array|null $options
     * @return mixed
     */
    public function get($name, array $options = null)
    {
        if (isset($this->adapters[$name])) {
            return $this->adapters[$name];
        }

        $adaptersConfig = $this->options->getAdapters();
        if (!isset($adaptersConfig[$name]) || !is_array($adaptersConfig[$name])) {
            throw new RuntimeException(sprintf('Adapter with name `%s` is not configured', $name));
        }

        if (!isset($adaptersConfig[$name]['type']) || !is_string($adaptersConfig[$name]['type'])) {
            throw new RuntimeException(sprintf('Queue adapter type not specified for adapter `%s`', $name));
        }

        $type = $adaptersConfig[$name]['type'];

        $options = $options ?? [];
        $options += $adaptersConfig[$name]['options'];

        $adapter = parent::get($type, $options);

        $this->adapters[$name] = $adapter;
        return $adapter;
    }
}
