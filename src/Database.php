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

class Database
{
    /**
     * @var \PHPPackage\PDOWrapper\PDO
     */
    private $pdo;
    
    /**
     * @var array PDO attribute keys.
     */
    private $attributes = array(
        'AUTOCOMMIT', 'ERRMODE', 'CASE', 'CLIENT_VERSION', 'CONNECTION_STATUS',
        'ORACLE_NULLS', 'PERSISTENT', 'PREFETCH', 'SERVER_INFO', 'SERVER_VERSION',
        'TIMEOUT', 'DRIVER_NAME'
    );

    /**
     * @param \PHPPackage\PDOWrapper\PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    
    /**
     * Enumarate PDO attributes
     *
     * @param string $key Pick out a attribute by key
     * @return mixed
     */
    public function attribute(string $key = null)
    {
        $key = 'PDO::ATTR_'.strtoupper($key);

        try {
            return $this->pdo->getAttribute(constant($key));
        } catch (\PDOException $e) {
            return;
        }
    }
    
    /**
     * Enumarate PDO attributes
     *
     * @param string $key Pick out a attribute by key
     * @return mixed
     */
    public function info(string $key = null)
    {
        if (!is_null($key)) {
            return $this->attribute($key);
        }
        
        $return = [];
        foreach ($this->attributes as $value) {
            $return['PDO::ATTR_'.$value] = $this->attribute($value);
        }
        
        return $return;
    }

    /**
     * Get database name from dsn
     *
     * @throws RuntimeException
     * @return string
     */
    public function name(string $dsn): string
    {
        // match database from dsn & set working vars
        if (!preg_match('/dbname=(\w+);/', $dsn, $results)) {
            throw new \RuntimeException('Could not match database name from dsn');
        }

        return $results[1];
    }

    /**
     * Create database
     *
     * @return bool
     */
    public function create(string $name, $username, $password): bool
    {
        if (!in_array($name, $this->databases())) {
            return (bool) $this->pdo->exec("
                CREATE DATABASE `$name`;
                CREATE USER '{$username}'@'%' IDENTIFIED BY '{$password}';
                GRANT ALL ON `$name`.* TO '{$username}'@'%';
                FLUSH PRIVILEGES;
            ");
        }
        return false;
    }

    /**
     * Returns an array of databases
     * @return mixed
     */
    public function databases(): array
    {
        $stmt = $this->pdo->query("SHOW DATABASES");

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
        $stmt = $this->pdo->query("SHOW TABLES");

        $result = [];
        while ($row = $stmt->fetchColumn(0)) {
            $result[] = $row;
        }

        return $result;
    }
}
