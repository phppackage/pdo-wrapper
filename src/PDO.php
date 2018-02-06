<?php
declare(strict_types=1);

/*
 +-----------------------------------------------------------------------------+
 | PHPPackage - PDO Wrapper
 +-----------------------------------------------------------------------------+
 | Copyright (c)2018 (http://github.com/phppackage/pdo-wrapper)
 +-----------------------------------------------------------------------------+
 | This source file is subject to MIT License
 | that is bundled with this package in the file LICENSE.
 |
 | If you did not receive a copy of the license and are unable to
 | obtain it through the world-wide-web, please send an email
 | to your-email@example.com so we can send you a copy immediately.
 +-----------------------------------------------------------------------------+
 | Authors:
 |   Your Name <your-email@example.com>
 +-----------------------------------------------------------------------------+
 */

namespace PHPPackage\PDOWrapper;

class PDO extends \PDO
{
    /**
     * @var construct arguments
     */
    private $dsn, $username, $password;

    /**
     * @var array Default options for database connection.
     */
    private $options = array(
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false
    );

    /**
     * @var array PDO attribute keys.
     */
    private $attributes = array(
        'AUTOCOMMIT', 'ERRMODE', 'CASE', 'CLIENT_VERSION', 'CONNECTION_STATUS',
        'ORACLE_NULLS', 'PERSISTENT', 'PREFETCH', 'SERVER_INFO', 'SERVER_VERSION',
        'TIMEOUT', 'DRIVER_NAME'
    );

    /**
     * PDO construct, defaults to tmp sqlite file if no arguments are passed.
     *
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param array  $options
     */
    public function __construct(
        string $dsn = null,
        string $username = null,
        string $password = null,
        array  $options = []
    ) {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->options = $options+$this->options;

        if (is_null($this->dsn)) {
			$this->dsn = 'sqlite:/'.sys_get_temp_dir().'/PDOWrapper.db';
		}

        parent::__construct($this->dsn, $this->username, $this->password, $this->options);
    }

    /**
     * Get database name from dsn
     *
     * @throws RuntimeException
     * @return string
     */
    public function getDatabaseName(): string
    {
        // match database from dsn & set working vars
        if (!preg_match('/dbname=(\w+);/', $this->dsn, $results)) {
            throw new \RuntimeException('Could not match database name from dsn');
        }

        return $results[1];
    }

    /**
     * Create database
     *
     * @return bool
     */
    public function createDatabase($name): bool
    {
        if (!in_array($name, $this->databases())) {
            return (bool) $this->exec("
                CREATE DATABASE `$name`;
                CREATE USER '{$this->username}'@'%' IDENTIFIED BY '{$this->password}';
                GRANT ALL ON `$name`.* TO '{$this->username}'@'localhost';
                FLUSH PRIVILEGES;
            ");
        }
        return false;
    }

    /**
     * Enumarate PDO attributes
     *
     * @param string $key Pick out a attribute by key
     * @return mixed
     */
    public function info(string $key = null)
    {
        $return = [];
        foreach ($this->attributes as $value) {
            try {
                $return['PDO::ATTR_'.$value] = $this->getAttribute(constant('PDO::ATTR_'.$value));
            } catch (\PDOException $e) {
                $return['PDO::ATTR_'.$value] = null;
            }
        }
        return (!is_null($key) && isset($return[$key])) ? $return[$key] : $return;
    }

    /**
     * Returns an array of databases
     * @return mixed
     */
    public function databases(): array
    {
        $stmt = $this->query("SHOW DATABASES");

        $result = [];
        while ($row = $stmt->fetchColumn(0)) {
            $result[] = $row;
        }

        return $result;
    }

    /**
     * Returns an array of tables
     * @return array
     */
    public function tables(): array
    {
        $stmt = $this->query("SHOW TABLES");

        $result = [];
        while ($row = $stmt->fetchColumn(0)) {
            $result[] = $row;
        }

        return $result;
    }

    /**
     * Run query and return PDOStatement
     *
     * @param string $sql
     * @param array  $values
     * @throws InvalidArgumentException
     * @return mixed
     */
    public function run(string $sql, array $values = [])
    {
        if (empty($sql)) {
            throw new \InvalidArgumentException('1st argument cannot be empty');
        }

        if (empty($values)) {
            return $this->query($sql);
        }

        if (!is_array($values[0])) {
            $stmt = $this->prepare($sql);
            $stmt->execute($values);
			return $stmt;
        }

        return $this->multi($sql, $values);
    }

    /**
     * Execute multiple querys which returns row count
     *
     * @param string $sql
     * @param array  $values
     * @throws InvalidArgumentException
     * @return int
     */
    public function multi($sql, $values = []): int
    {
        if (empty($sql)) {
            throw new \InvalidArgumentException('1st argument cannot be empty');
        }

        if (empty($values[0]) || !is_array($values[0])) {
            throw new \InvalidArgumentException('2nd argument must be an array of arrays');
        }

        $stmt = $this->prepare($sql);

        $row_count = 0;
        foreach ($values as $value) {
            $stmt->execute($value);
            $row_count += $stmt->rowCount();
        }

        return $row_count;
    }

    /**
     * Quick queries
     * Allows you to run a query without chaining the return type manually. This allows for slightly shorter syntax.
     */

    public function row($query, $values = array()): array
    {
        return $this->run($query, $values)->fetch();
    }

    public function cell($query, $values = array()): string
    {
        return $this->run($query, $values)->fetchColumn();
    }

    public function all($query, $values = array()): array
    {
        return $this->run($query, $values)->fetchAll();
    }

    /**
     * Checks import/export system requirements.
     *  - Supports only mySQL
     *
     * @param string $method
     * @throws RuntimeException
     */
    private function checkImportExportRequirements(string $method)
    {
        if ($this->info('DRIVER_NAME') !== 'mysql') {
            new \RuntimeException('Driver not supported for '.$method.'()');
        }

        if (!function_exists('shell_exec')) {
            new \RuntimeException('shell_exec must be enabled for '.$method.'()');
        }

        if (empty(shell_exec('which gzip'))) {
            new \RuntimeException('gzip must be installed to use '.$method.'()');
        }

        if (empty(shell_exec('which zcat'))) {
            new \RuntimeException('zcat must be installed to use '.$method.'()');
        }

        if (empty(shell_exec('which mysqldump'))) {
            new \RuntimeException('mysqldump must be installed to use '.$method.'()');
        }
    }

    /**
     * Import database (using )
     *
     * @param string $file
     * @param bool   $backup Do backup before import
     * @throws RuntimeException
     * @return bool
     */
    public function import(string $file, $backup = true)
    {
        $this->checkImportExportRequirements('import');

        if (!file_exists($file)) {
            throw new \DomainException('Import file does not exist');
        }

        // set working vars
        $database = $this->getDatabaseName();
        $date = date_create()->format('Y-m-d_H:i:s');
        $dir = dirname($file);

        // backup current
        if ($backup) {
            $this->export($dir);
        }

        // restore
        `zcat {$dir}/{$file} | mysql --user={$this->username} --password={$this->password} {$database}`;

        return true;
    }

    /**
     * Export database using mysqldump
     *
     * @param string $destination Directory to store database exports
     * @throws DomainException
     * @return string
     */
    public function export($destination = './'): string
    {
        $this->checkImportExportRequirements('export');

        if (!is_dir($destination)) {
            throw new \DomainException('Export destination must be a directory');
        }

        // set working vars
        $database = $this->getDatabaseName();
        $date = date_create()->format('Y-m-d_H:i:s');
        $destination = rtrim($destination, '/');

        `mysqldump --add-drop-table --user={$this->username} --password={$this->password} --host=127.0.0.1 {$database} | gzip > {$destination}/{$date}.sql.gz &`;

        return $destination.'/'.$date.'.sql.gz';
    }


    /**
     * Magic caller, so can return a BadMethodCallException
     *
     * @param string $method
     * @param array  $arguments
     * @throws BadMethodCallException
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException('Call to undefined method '.__CLASS__.'::'.$method.'()');
        }

        // @codeCoverageIgnoreStart
        return $this->{$method}(...$arguments);
        // @codeCoverageIgnoreEnd
    }

}
