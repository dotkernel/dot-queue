<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue\Exception;

use Throwable;

/**
 * Class MaxAttemptsExceededException
 * @package Dot\Queue\Exception
 */
class MaxAttemptsExceededException extends \RuntimeException implements ExceptionInterface
{
    public function __construct($message = "", Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
