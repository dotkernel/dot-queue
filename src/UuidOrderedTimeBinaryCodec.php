<?php
/**
 * @see https://github.com/dotkernel/frontend/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/frontend/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue;

use Ramsey\Uuid\Codec\CodecInterface;
use Ramsey\Uuid\Codec\OrderedTimeCodec;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory;
use Ramsey\Uuid\UuidInterface;

/**
 * Class UuidOrderedTimeCodec
 * @package uDare\Core\Common
 */
class UuidOrderedTimeBinaryCodec
{
    /** @var  UuidFactory */
    private static $factory;

    /** @var  CodecInterface */
    private static $codec;

    /**
     * Converts a Uuid to a ordered binary representation, as stored in the backend
     * @param $uuid
     * @return string
     */
    public static function encode($uuid)
    {
        if (is_string($uuid)) {
            $uuid = self::getFactory()->fromString($uuid);
        }

        return self::getCodec()->encodeBinary($uuid);
    }

    /**
     * Converts the binary value coming from backend, to a UuidInterface
     * @param $bytes
     * @return UuidInterface
     */
    public static function decode($bytes): UuidInterface
    {
        if ($bytes instanceof UuidInterface) {
            return $bytes;
        }

        return self::getCodec()->decodeBytes($bytes);
    }

    /**
     * @return CodecInterface
     */
    private static function getCodec(): CodecInterface
    {
        if (!self::$codec) {
            self::$codec = new OrderedTimeCodec(self::getFactory()->getUuidBuilder());
        }
        return self::$codec;
    }

    /**
     * @return UuidFactory
     */
    private static function getFactory(): UuidFactory
    {
        if (!self::$factory) {
            self::$factory = clone Uuid::getFactory();
        }
        return self::$factory;
    }
}
