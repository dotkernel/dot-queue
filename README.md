# dot-queue

![OSS Lifecycle](https://img.shields.io/osslifecycle/dotkernel/dot-queue)
[![GitHub license](https://img.shields.io/github/license/dotkernel/dot-queue)](https://github.com/dotkernel/dot-queue/LICENSE)

DotKernel queue component

## Requirements
* PHP >= 7.1
* zendframework/zend-servicemanager
* zendframework/zend-db (optional - install if using the database adapter)

## Installation
Run the following command
```bash
$ composer require dotkernel/dot-queue
```

After installing all dependencies, add the `\Dot\Queue\ConfigProvider::class` to your configuration aggregate, in order to register all dependencies and console commands.

## Queues
The following queue implementations are provided by this package
* `Dot\Queue\Queue\InMemoryQueue` - non-persisten queue, based on \SplQueue, mainly for testing purposes
* `Dot\Queue\Queue\PersistentQueue` - uses an adapter to persist/fetch jobs to/from a storage

Queues must implement `Dot\Queue\Queue\QueueInterface` or extend `Dot\Queue\Queue\AbstractQueue`.

## Queue adapters
Queue adapters are used in collaboration with the `PersistentQueue`. Queue adapters are specific to the storage used
Provided queue adapters:
* `Dot\Queue\Adapter\DatabaseAdapter` - based on `zend-db`, enqueues/dequeues jobs using a MySql storage

Queue adapters must implement `Dot\Queue\Adapter\AdapterInterface`

## Configuring queues
At this moment, the package offers only a database adapter to be used with the persistent queue.
Therefore, the config template below shows how to configure a MySQL queue.

Create a config file in your `config/autoload` folder, and replace the `{{QUEUE_NAME}}` with something appropriate
##### queue.global.php
```php
<?php

return [
    'dot_queue' => [
        'default_queue' => '{{QUEUE_NAME}}',
    
        'failed_job_provider' => [
            // these options are specific to the provider used
            // we give here the database failed job provider options
            'db_adapter' => 'database',
            'table' => 'failed_jobs',
        ],

        'adapter_manager' => [],
        'adapters' => [
            'database' => [
                'type' => \Dot\Queue\Adapter\DatabaseAdapter::class,
                'options' => [
                    // configured zend db service name adapter
                    'db_adapter' => 'database',
                    'table' => 'jobs',
                    'failed_table' => 'failed_jobs'
                ],
                // other adapters...
            ]
        ],

        'queue_manager' => [],
        'queues' => [
            '{{QUEUE_NAME}}' => [
                // this is the default queue type, if not specified
                // 'type' => \Dot\Queue\Queue\PersistentQueue::class,
                'options' => [
                    'adapter' => 'database',
                    // after how many seconds, failed job will be attempted again
                    'retry_after' => 60,
                    // maybe other queue options later
                ]
            ],
            // other queues...
        ]
    ]
];
```

You can configure multiple adapters and multiple queues. Multiple queues can also use the same queue adapter.

## Creating job classes

A job represent the unit of work that will be processed by the queue as the queue is consumed.
Create job classes by extending `Dot\Queue\Job\AbstractJob`.

A job must declare 2 methods
* `process()` - will be called when the job is processed by the queue. Do your work in here.
* `failed($e)` - called when the job fails(the max attempts are exceeded). It will receive the exception that caused the failure

```php
//...
class MyJob extends AbstractJob
{
    public function process()
    {
        //...
    }
    
    public function failed($e)
    {
        //...
    }
}
```

You can also inject the job class with the needed dependencies. Use a factory class and register the job in the service container for that.


## The QueueManager

The `Dot\Queue\Queue\QueueManager` is the main class to be injected wherever you dispatch job to a queue.

In order to create and dispatch a job
```php
$job = $queueManager->createJob(MyJob::class)
    ->setMaxAttempts(3)
    ->setTimeout(30)
    ->setDelay(0)
    ->setPriority(1);
    
// set custom data into the job, that you can access when the job will be processed
$job->set('key1', 'some data')
    ->set('key2', 'some other data');
    
// dispatch the job
$job->dispatch(); //to the default queue OR
$job->dispatch('queue_name');
```

### Important
* When creating jobs, always use the queue managers `->createJob(className)` method.
This will make sure the job is fetched from the container and will be properly initialized.

* In order to avoid serialization complication, we advice you to set only scalar or array data into the jobs' payload.
This should not represent a limitation, because you can inject services into the job, that can later fetch objects from database and so on...

* Jobs already define some sane defaults for the max attempts, timeout and other job option. Override only what you need.


## Consuming jobs

Run the following dotkernel command in order to start the worker loop to consume the default queue
```bash
$ php dot queue:consume
```

For details on the supported command options run
```bash
$ php dot help queue:consume
```

Useful consumer options
* `--all` - consume all defined queues, in a round robin fashion
* `--queues=` - comma separated list of queues to consume
* `--max-runtime=` - run the consumer only the specified number of seconds
* `--max-jobs=` - run the consumer until the specified number of jobs have been processed(this includes also the failed jobs)
* `--sleep=` - pause the queue for the specified amount of seconds(in case the queue is empty)
Check the command's help for a full list of options

In production, we advise you to use a monitoring software, such as `supervisord` in order to make sure that the consumer is kept alive.
During development you can emulate supervisord with the npm-package called `forever`

## Database migrations
In order to generate migrations files (to be used by Phinx library) for the jobs table and failed jobs table, two commands are provided
* `$ php dot queue:jobs-table`
* `$ php dot queue:failed-table`

Running these commands, will generate migration files with the following default options
* the namespace will be set to `Data\Database\Migrations`
* the table names will be `jobs` and `failed_jobs` respectively
* the path where the files will be generated `data/database/migrations`

You can override these options using the `--namespace=`, `--table-name=` and `--path` options respectively

After you have generated the files you can run
```bash
$ vendor/bin/phinx --configuration=your/config/file migrate
```

in order to create the tables

## Handling failed jobs
We provide several commands to help you manage the failed jobs
* `php dot queue:failed [--queue=]` lists all failed jobs, or filtered by queue name
* `php dot queue:flush [--queue=]` remove all failed jobs, optionally filtered by queue name
* `php dot queue:forget <uuid>` remove the job with specified ID from the failed job list
* `php dot queue:retry [<uuid>] [--queue]` re-dispatch a job back into its queue, for retrying. If no ID given, retry all failed jobs or filtered by queue


## @TODO - QUEUE EVENTS
