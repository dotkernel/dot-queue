<?php
/**
 * @see https://github.com/dotkernel/dot-queue/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-queue/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Dot\Queue\Console\Command;

use Dot\Console\Command\AbstractCommand;
use Laminas\Console\Adapter\AdapterInterface;
use ZF\Console\Route;

/**
 * Class FailedTableCommand
 * @package Dot\Queue\Console\Command
 */
class FailedTableCommand extends AbstractCommand
{
    /**
     * @param Route $route
     * @param AdapterInterface $console
     * @return int
     */
    public function __invoke(Route $route, AdapterInterface $console)
    {
        $stubFile = dirname(__DIR__, 3) . '/stubs/failed_jobs_table_migration.stub';

        if (!file_exists($stubFile)) {
            $console->writeLine(sprintf('Stub file `%s` does not exist', $stubFile));
            return 0;
        }

        $namespace = $route->getMatchedParam('namespace', 'Data\\Database\\Migrations');
        $tableName = $route->getMatchedParam('table-name', 'failed_jobs');
        $tableClassName = str_replace(' ', '', ucwords(str_replace(['-', '_'], [' ', ' '], $tableName)));
        $path = trim($route->getMatchedParam('path', 'data/database/migrations'), '/');

        $file = file_get_contents($stubFile);
        $file = str_replace(
            ['{{NAMESPACE}}', '{{TABLE_CLASS_NAME}}', '{{TABLE_NAME}}'],
            [$namespace, $tableClassName, $tableName],
            $file
        );

        $filename = getcwd() . '/' . $path . '/';
        $timestamp = \date('YmdHis', time());
        $filename .= $timestamp . '_create_' . $tableName . '_table.php';
        file_put_contents($filename, $file);
        $console->writeLine("Migration file $filename created");
        return 0;
    }
}
