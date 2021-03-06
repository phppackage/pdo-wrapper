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
 | to lawrence@cherone.co.uk so we can send you a copy immediately.
 +-----------------------------------------------------------------------------+
 | Authors:
 |   Lawrence Cherone <lawrence@cherone.co.uk>
 +-----------------------------------------------------------------------------+
 */

namespace PHPPackage\PDOWrapper;

class PDO extends \PDO
{
    /**
     * @var string
     */
    private $dsn;
    private $username;
    private $password;

    /**
     * @var array Default options for database connection.
     */
    private $options = array(
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false
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
        return (new Database($this))->name($this->dsn);
    }

    /**
     * Create database
     *
     * @return bool
     */
    public function createDatabase(string $name): bool
    {
        return (new Database($this))->create($name, $this->username, $this->password);
    }
    
    /**
     * Returns an array of databases
     * @return mixed
     */
    public function databases(): array
    {
        return (new Database($this))->databases();
    }

    /**
     * Enumarate PDO attributes
     *
     * @param string $key Pick out a attribute by key
     * @return mixed
     */
    public function info(string $key = null)
    {
        return (new Database($this))->info($key);
    }

    /**
     * Returns an array of tables
     * @return array
     */
    public function tables(): array
    {
        return (new Database($this))->tables();
    }

    /**
     * Run query and return PDOStatement or row_count
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
    public function multi(string $sql, array $values = []): int
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
     * Return all rows in result set
     *
     * @param string $sql
     * @param array  $values
     * @return array
     */
    public function all(string $sql, array $values = []): array
    {
        return $this->run($sql, $values)->fetchAll();
    }
    
    /**
     * Return first row in result set
     *
     * @param string $sql
     * @param array  $values
     * @return array
     */
    public function row(string $sql, array $values = []): array
    {
        return $this->run($sql, $values)->fetch();
    }
    
    /**
     * Return first column cell in result set
     *
     * @param string $sql
     * @param array  $values
     * @return array
     */
    public function cell(string $sql, array $values = []): string
    {
        return $this->run($sql, $values)->fetchColumn();
    }

    /**
     * Checks system requirements for import/export methods
     *  - Supports only mySQL
     *
     * @param string $method
     * @throws RuntimeException
     * @return void
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
    public function import(string $file, bool $backup = true)
    {
        $this->checkImportExportRequirements('import');

        if (!file_exists($file)) {
            throw new \DomainException('Import file does not exist');
        }

        // set working vars
        $database = $this->getDatabaseName();
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
