<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue;

use Dot\Queue\Adapter\AdapterManager;
use Dot\Queue\Console\Command\ConsumeCommand;
use Dot\Queue\Console\Command\FailedTableCommand;
use Dot\Queue\Console\Command\FlushFailedCommand;
use Dot\Queue\Console\Command\ForgetFailedCommand;
use Dot\Queue\Console\Command\ListFailedCommand;
use Dot\Queue\Console\Command\JobsTableCommand;
use Dot\Queue\Console\Command\RetryCommand;
use Dot\Queue\Console\Factory\ConsumeCommandFactory;
use Dot\Queue\Console\Factory\FailedCommandFactory;
use Dot\Queue\Factory\AdapterManagerFactory;
use Dot\Queue\Factory\ConsumerFactory;
use Dot\Queue\Factory\DatabaseFailedJobProviderFactory;
use Dot\Queue\Factory\QueueManagerFactory;
use Dot\Queue\Factory\QueueOptionsFactory;
use Dot\Queue\Failed\FailedJobProviderInterface;
use Dot\Queue\Options\QueueOptions;
use Dot\Queue\Queue\QueueManager;
use Zend\Filter\ToInt;
use Zend\ServiceManager\Factory\InvokableFactory;
use ZF\Console\Filter\Explode;

/**
 * Class ConfigProvider
 * @package Dot\Queue
 */
class ConfigProvider
{
    public function __invoke()
    {
        return [
            'dependencies' => $this->getDependencies(),
            'dot_console' => $this->getCommands(),
        ];
    }

    public function getDependencies()
    {
        return [
            'factories' => [
                QueueOptions::class => QueueOptionsFactory::class,
                AdapterManager::class => AdapterManagerFactory::class,
                QueueManager::class => QueueManagerFactory::class,
                Consumer::class => ConsumerFactory::class,

                FailedJobProviderInterface::class => DatabaseFailedJobProviderFactory::class,

                // commands factories
                ConsumeCommand::class => ConsumeCommandFactory::class,
                ListFailedCommand::class => FailedCommandFactory::class,
                FlushFailedCommand::class => FailedCommandFactory::class,
                ForgetFailedCommand::class => FailedCommandFactory::class,
                RetryCommand::class => FailedCommandFactory::class,
                JobsTableCommand::class => InvokableFactory::class,
                FailedTableCommand::class => InvokableFactory::class,
            ]
        ];
    }

    public function getCommands()
    {
        return [
            'commands' => [
                [
                    'name' => 'queue:consume',
                    'route' => '[--queues=] [--all] [--sleep=] [--max-runtime=]' .
                        ' [--max-jobs=] [--memory-limit=] [--stop-on-error] [--stop-on-empty]',
                    'short_description' => 'Run the queue consumer loop',
                    'description' => 'Start the consumer worker loop, to consume jobs from specified queues',
                    'options_descriptions' => [
                        '--queues' => 'Comma separated list of queue names to run, defaults to the default queue',
                        '--all' => 'Run the consumer on all defined queues, round robin',
                        '--max-runtime' => 'Keep the consumer running a specified amount of time only',
                        '--max-jobs' => 'Specify how many jobs to run before closing the consumer',
                        '--memory-limit' => 'Set the memory limit allowed to be used by the consumer',
                        '--sleep' => 'Seconds to sleep the consumer worker if queue is empty',
                        '--stop-on-error' => 'Flag indicating the consumer to stop if an error has occurred',
                        '--stop-on-empty' => 'Flag indicating the consumer to stop if queues are empty',
                    ],
                    'filters' => [
                        'queues' => new Explode(','),
                        'max-runtime' => new ToInt(),
                        'max-jobs' => new ToInt(),
                        'memory-limit' => new ToInt(),
                        'sleep' => new ToInt(),
                    ],
                    'defaults' => [
                    ],
                    'handler' => ConsumeCommand::class,
                ],
                [
                    'name' => 'queue:failed',
                    'route' => '[--queue=]',
                    'short_description' => 'List all failed jobs or filtered by queue',
                    'options_descriptions' => [
                        '--queue' => 'Specify the queue name to filter failed jobs'
                    ],
                    'handler' => ListFailedCommand::class,
                ],
                [
                    'name' => 'queue:flush',
                    'route' => '[--queue]',
                    'short_description' => 'Flush all failed jobs from storage',
                    'options_descriptions' => [
                        '--queue' => 'Flush failed jobs only from the specified queue name'
                    ],
                    'handler' => FlushFailedCommand::class,
                ],
                [
                    'name' => 'queue:forget',
                    'route' => '<uuid>',
                    'short_description' => 'Remove a failed job from the storage',
                    'options_descriptions' => [
                        '<uuid>' => 'UUID of the job to be removed'
                    ],
                    'handler' => ForgetFailedCommand::class,
                ],
                [
                    'name' => 'queue:retry',
                    'route' => '[<uuid>] [--queue=]',
                    'short_description' => 'Re-dispatch failed jobs back into the queue for retrying',
                    'options_descriptions' => [
                        '<uuid>' => 'Optionally, specify the job\'s UUID to retry',
                        '--queue' => 'Optionally, specify the queue name for which to retry jobs'
                    ],
                    'handler' => RetryCommand::class,
                ],
                [
                    'name' => 'queue:jobs-table',
                    'route' => '[--table-name=] [--namespace=] [--path=]',
                    'short_description' => 'Create phinx migration file for jobs table',
                    'options_descriptions' => [
                        '--namespace' => 'Migration class namespace to use, defaults to Data\\Database\\Migrations',
                        '--table-name' => 'Name of the jobs table, defaults to `jobs`',
                        '--path' => 'Path where to put the migration file,'.
                            ' relative to the current working directory, defaults to `data/database/migrations`'
                    ],
                    'defaults' => [
                        'namespace' => 'Data\\Database\\Migrations',
                        'table-name' => 'jobs',
                        'path' => 'data/database/migrations',
                    ],
                    'handler' => JobsTableCommand::class,
                ],
                [
                    'name' => 'queue:failed-table',
                    'route' => '[--table-name=] [--namespace=] [--path=]',
                    'short_description' => 'Create phinx migration file for jobs table',
                    'options_descriptions' => [
                        '--namespace' => 'Migration class namespace to use, defaults to Data\\Database\\Migrations',
                        '--table-name' => 'Name of the jobs table, defaults to `failed_jobs`',
                        '--path' => 'Path where to put the migration file,'.
                            ' relative to the current working directory, defaults to `data/database/migrations`'
                    ],
                    'defaults' => [
                        'namespace' => 'Data\\Database\\Migrations',
                        'table-name' => 'failed_jobs',
                        'path' => 'data/database/migrations',
                    ],
                    'handler' => FailedTableCommand::class,
                ]
            ]
        ];
    }
}
