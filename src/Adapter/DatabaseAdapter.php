<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue\Adapter;

use Dot\Queue\Db\SelectDecorator;
use Dot\Queue\Job\JobInterface;
use Dot\Queue\Queue\QueueInterface;
use Dot\Queue\UuidOrderedTimeBinaryCodec;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Where;

/**
 * Class DatabaseAdapter
 * @package Dot\Queue\Adapter
 */
class DatabaseAdapter extends AbstractAdapter
{
    /** @var  Adapter */
    protected $dbAdapter;

    /** @var Sql */
    protected $sql;

    /** @var string  */
    protected $table = 'jobs';

    /**
     * DatabaseAdapter constructor.
     * @param array|null $options
     */
    public function __construct(array $options = null)
    {
        if (isset($options['db_adapter'])) {
            $this->setDbAdapter($options['db_adapter']);
        }

        if (isset($options['table'])) {
            $this->setTable($options['table']);
        }
    }

    /**
     * Validates is all dependencies were injected
     */
    protected function validate()
    {
        if (!$this->dbAdapter instanceof Adapter) {
            throw new \RuntimeException('Database queue adapter required a valid Laminas\Db adapter');
        }
    }

    /**
     * @param QueueInterface $queue
     * @return int
     */
    public function count(QueueInterface $queue): int
    {
        $this->validate();

        $sql = $this->getSql();
        $select = $sql->select($this->getTable())
            ->columns(['num' => new Expression('COUNT(*)')])
            ->where(['queue' => $queue->getName()]);

        $stmt = $sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();
        $result->next();
        if ($result->valid()) {
            $row = $result->current();
            return (int) $row['num'];
        }

        return 0;
    }

    /**
     * @param QueueInterface $queue
     * @param JobInterface $job
     */
    public function enqueue(QueueInterface $queue, JobInterface $job)
    {
        $this->validate();

        $data = $this->createRecord($queue, $job);
        $sql = $this->getSql();
        $insert = $sql->insert($this->getTable())
            ->columns(array_keys($data))
            ->values($data);

        $sql->prepareStatementForSqlObject($insert)->execute();
    }

    /**
     * @param QueueInterface $queue
     * @param JobInterface $job
     * @return array
     */
    protected function createRecord(QueueInterface $queue, JobInterface $job): array
    {
        $currentTime = time();
        $availableAt = $currentTime + $job->getDelay();
        $createdAt = $currentTime;

        $record = [
            'uuid' => UuidOrderedTimeBinaryCodec::encode($job->getUUID()),
            'queue' => $queue->getName(),
            'priority' => $job->getPriority(),
            'payload' => $queue->getQueueManager()->createPayload($job),
            'availableAt' => $availableAt,
            'reservedAt' => null,
            'createdAt' => $createdAt,
        ];

        return $record;
    }

    /**
     * @param QueueInterface $queue
     * @return JobInterface|null
     */
    public function dequeue(QueueInterface $queue): ?JobInterface
    {
        $this->validate();

        $job = null;
        $this->getDbAdapter()->getDriver()->getConnection()->beginTransaction();
        try {
            $job = $this->doDequeueJob($queue);
            $this->getDbAdapter()->getDriver()->getConnection()->commit();
        } catch (\Exception $e) {
            $this->getDbAdapter()->getDriver()->getConnection()->rollback();
        }

        return $job;
    }

    /**
     * @param QueueInterface $queue
     * @return JobInterface|null
     */
    protected function doDequeueJob(QueueInterface $queue): ?JobInterface
    {
        $currentTime = time();
        $sql = $this->getSql();
        $select = $sql->select($this->getTable())
            ->where(function (Where $where) use ($queue, $currentTime) {
                $where->equalTo('queue', $queue->getName())->AND
                    ->NEST
                        ->NEST
                            ->isNull('reservedAt')->AND
                            ->lessThanOrEqualTo('availableAt', $currentTime)
                        ->UNNEST
                        ->OR
                        ->lessThanOrEqualTo(
                            'reservedAt',
                            $currentTime - $queue->getRetryAfter()
                        )
                    ->UNNEST;
            })
            ->order(['priority DESC', 'createdAt ASC'])
            ->limit(1)
            ->offset(0);

        // flag inserted by our select decorator to lock the row for update
        $select->lockForUpdate = true;

        $result = $sql->prepareStatementForSqlObject($select)->execute();
        $result->next();
        if ($result->valid()) {
            $row =  $result->current();
            $job = $queue->getQueueManager()->createJobFromPayload($row['payload']);

            $job->increment();
            $update = $sql->update($this->getTable())
                ->where(['uuid' => UuidOrderedTimeBinaryCodec::encode($job->getUUID())])
                ->set([
                    'payload' => $queue->getQueueManager()->createPayload($job),
                    'reservedAt' => time()
                ]);
            $sql->prepareStatementForSqlObject($update)->execute();
            return $job;
        }

        return null;
    }

    /**
     * @param QueueInterface $queue
     * @param JobInterface $job
     */
    public function acknowledge(QueueInterface $queue, JobInterface $job)
    {
        $this->delete($queue, $job);
    }

    /**
     * @param QueueInterface $queue
     * @param JobInterface $job
     */
    public function delete(QueueInterface $queue, JobInterface $job)
    {
        $this->validate();

        $sql = $this->getSql();
        $delete = $sql->delete($this->getTable())
            ->where(['uuid' => UuidOrderedTimeBinaryCodec::encode($job->getUUID())]);
        $sql->prepareStatementForSqlObject($delete)->execute();
    }

    /**
     * @param QueueInterface $queue
     * @return int
     */
    public function purge(QueueInterface $queue)
    {
        $this->validate();

        $sql = $this->getSql();
        $delete = $sql->delete($this->getTable())
            ->where(['queue' => $queue->getName()]);

        $stmt = $sql->prepareStatementForSqlObject($delete);
        $result = $stmt->execute();
        return $result->getAffectedRows();
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
     * @return DatabaseAdapter
     */
    public function setDbAdapter(Adapter $dbAdapter): DatabaseAdapter
    {
        $this->dbAdapter = $dbAdapter;
        return $this;
    }

    /**
     * @return Sql
     */
    public function getSql(): Sql
    {
        if (!$this->sql instanceof Sql) {
            $this->sql = new Sql($this->dbAdapter);
            $this->sql->getSqlPlatform()->setTypeDecorator(Select::class, new SelectDecorator());
        }
        return $this->sql;
    }

    /**
     * @param Sql $sql
     * @return DatabaseAdapter
     */
    public function setSql(Sql $sql): DatabaseAdapter
    {
        $this->sql = $sql;
        $this->sql->getSqlPlatform()->setTypeDecorator(Select::class, new SelectDecorator());
        return $this;
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
     * @return DatabaseAdapter
     */
    public function setTable(string $table): DatabaseAdapter
    {
        $this->table = $table;
        return $this;
    }
}
