<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue\Db;

use Zend\Db\Adapter\Driver\DriverInterface;
use Zend\Db\Adapter\ParameterContainer;
use Zend\Db\Adapter\Platform\PlatformInterface;

/**
 * Class SelectDecorator
 * @package Dot\Queue\Db
 */
class SelectDecorator extends \Zend\Db\Sql\Platform\Mysql\SelectDecorator
{
    /** @var null|bool  */
    protected $lockForUpdate = null;

    /**
     * @param PlatformInterface $platform
     * @param DriverInterface|null $driver
     * @param ParameterContainer|null $parameterContainer
     * @return array|string
     */
    protected function processStatementEnd(
        PlatformInterface $platform,
        DriverInterface $driver = null,
        ParameterContainer $parameterContainer = null
    ) {
        $sqlString = parent::processStatementEnd($platform, $driver, $parameterContainer);
        if ($this->lockForUpdate) {
            $sqlString .= " FOR UPDATE";
        }

        return $sqlString;
    }
}
