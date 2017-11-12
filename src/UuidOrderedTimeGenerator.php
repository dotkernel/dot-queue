<?php
/**
 * @see https://github.com/dotkernel/frontend/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/frontend/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue;

use Ramsey\Uuid\Codec\OrderedTimeCodec;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory;
use Ramsey\Uuid\UuidInterface;

/**
 * Class UuidOrderedTimeGenerator
 * @package uDare\Core\Common
 */
final class UuidOrderedTimeGenerator
{
    /** @var  UuidFactory */
    private static $factory;

    /**
     * @return UuidInterface
     */
    public static function generateUuid(): UuidInterface
    {
        return self::getFactory()->uuid1();
    }

    /**
     * @return UuidFactory
     */
    private static function getFactory(): UuidFactory
    {
        if (!self::$factory) {
            self::$factory = clone Uuid::getFactory();

            $codec = new OrderedTimeCodec(
                self::$factory->getUuidBuilder()
            );

            self::$factory->setCodec($codec);
        }

        return self::$factory;
    }
}
