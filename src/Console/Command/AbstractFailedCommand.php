<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue\Console\Command;

use Dot\Console\Command\AbstractCommand;
use Dot\Queue\Failed\FailedJobProviderInterface;
use Dot\Queue\Queue\QueueManager;

/**
 * Class AbstractFailedCommand
 * @package Dot\Queue\Console\Command
 */
abstract class AbstractFailedCommand extends AbstractCommand
{
    /** @var  FailedJobProviderInterface */
    protected $failedJobProvider;

    /** @var  QueueManager */
    protected $queueManager;

    /**
     * AbstractFailedCommand constructor.
     * @param FailedJobProviderInterface $failedJobProvider
     * @param QueueManager $queueManager
     */
    public function __construct(FailedJobProviderInterface $failedJobProvider, QueueManager $queueManager)
    {
        $this->failedJobProvider = $failedJobProvider;
        $this->queueManager = $queueManager;
    }
}
