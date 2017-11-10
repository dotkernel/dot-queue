<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue\Failed;

use Dot\Queue\Exception\RuntimeException;
use Dot\Queue\Job\JobInterface;
use Dot\Queue\Queue\QueueInterface;
use Dot\Queue\Queue\QueueManager;
use Dot\Queue\UuidOrderedTimeBinaryCodec;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Sql;

/**
 * Class DatabaseFailedJobProvider
 * @package Dot\Queue\Failed
 */
class DatabaseFailedJobProvider implements FailedJobProviderInterface
{
    /** @var  QueueManager */
    protected $queueManager;

    /** @var  Adapter */
    protected $dbAdapter;

    /** @var  Sql */
    protected $sql;

    /** @var string  */
    protected $table = 'failed_jobs';

    /**
     * DatabaseFailedJobProvider constructor.
     * @param array|null $options
     */
    public function __construct(array $options = null)
    {
        if (isset($options['queue_manager'])) {
            $this->setQueueManager($options['queue_manager']);
        }

        if (isset($options['db_adapter'])) {
            $this->setDbAdapter($options['db_adapter']);
        }

        if (isset($options['table'])) {
            $this->setTable($options['table']);
        }
    }

    protected function validate()
    {
        if (!$this->dbAdapter instanceof Adapter) {
            throw new RuntimeException('Database adapter was not set');
        }
    }

    /**
     * @param QueueInterface $queue
     * @param JobInterface $job
     * @param \Exception|\Throwable $e
     */
    public function log(QueueInterface $queue, JobInterface $job, $e)
    {
        $data = [
            'uuid' => UuidOrderedTimeBinaryCodec::encode($job->getUUID()),
            'queue' => $job->getQueue()->getName(),
            'payload' => $queue->getQueueManager()->createPayload($job),
            'exception' => $e->getTraceAsString(),
            'failedAt' => time()
        ];

        $insert = $this->getSql()->insert($this->getTable())
            ->columns(array_keys($data))
            ->values($data);

        $this->getSql()->prepareStatementForSqlObject($insert)->execute();
    }

    /**
     * @param string|null $queue
     * @return array
     */
    public function findAll(string $queue = null): array
    {
        $select = $this->getSql()->select($this->getTable())->order(['failedAt DESC']);
        if ($queue) {
            $select->where(['queue' => $queue]);
        }
        $r = $this->getSql()->prepareStatementForSqlObject($select)->execute();
        $result = new ResultSet();
        $result->initialize($r);

        $jobs = [];
        $result->next();
        while ($result->valid()) {
            $data = $result->current();
            $jobs[] = $this->queueManager->createJobFromPayload($data['payload']);

            $result->next();
        }

        return $jobs;
    }

    /**
     * @param $uuid
     * @return JobInterface|null
     */
    public function find($uuid): ?JobInterface
    {
        $select = $this->getSql()->select($this->getTable())
            ->where(['uuid' => UuidOrderedTimeBinaryCodec::encode($uuid)]);

        $r = $this->getSql()->prepareStatementForSqlObject($select)->execute();
        $result = new ResultSet();
        $result->initialize($r);

        $result->next();
        if ($result->valid()) {
            $data = $result->current();
            return $this->queueManager->createJobFromPayload($data['payload']);
        }

        return null;
    }

    /**
     * @param $uuid
     */
    public function forget($uuid)
    {
        $delete = $this->getSql()->delete($this->getTable())
            ->where(['uuid' => UuidOrderedTimeBinaryCodec::encode($uuid)]);
        $this->getSql()->prepareStatementForSqlObject($delete)->execute();
    }

    /**
     * @param string|null $queue
     * @return int
     */
    public function flush(string $queue = null): int
    {
        $delete = $this->getSql()->delete($this->getTable());
        if ($queue) {
            $delete->where(['queue' => $queue]);
        }

        $r = $this->getSql()->prepareStatementForSqlObject($delete)->execute();
        return $r->getAffectedRows();
    }

    /**
     * @return Adapter
     */
    public function getDbAdapter(): Adapter
    {
        return $this->dbAdapter;
    }

    /**
     * @param Adapter $dbAdapter
     * @return DatabaseFailedJobProvider
     */
    public function setDbAdapter(Adapter $dbAdapter): DatabaseFailedJobProvider
    {
        $this->dbAdapter = $dbAdapter;
        return $this;
    }

    /**
     * @return Sql
     */
    public function getSql(): Sql
    {
        if (!$this->sql) {
            $this->sql = new Sql($this->getDbAdapter());
        }
        return $this->sql;
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * @param string $table
     * @return DatabaseFailedJobProvider
     */
    public function setTable(string $table): DatabaseFailedJobProvider
    {
        $this->table = $table;
        return $this;
    }

    /**
     * @return QueueManager
     */
    public function getQueueManager(): QueueManager
    {
        return $this->queueManager;
    }

    /**
     * @param QueueManager $queueManager
     * @return DatabaseFailedJobProvider
     */
    public function setQueueManager(QueueManager $queueManager): DatabaseFailedJobProvider
    {
        $this->queueManager = $queueManager;
        return $this;
    }
}
