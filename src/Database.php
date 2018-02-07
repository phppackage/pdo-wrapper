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
     * @var PHPPackage\PDOWrapper\PDO
     */
    private $pdo;

    /**
     *
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
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
        if (!in_array($name, $this->all())) {
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
    public function all(): array
    {
        $stmt = $this->pdo->query("SHOW DATABASES");

        $result = [];
        while ($row = $stmt->fetchColumn(0)) {
            $result[] = $row;
        }

        return $result;
    }
}
