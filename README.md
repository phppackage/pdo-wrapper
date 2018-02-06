## PDO Wrapper

[![Build Status](https://travis-ci.org/phppackage/pdo-wrapper.svg?branch=master)](https://travis-ci.org/phppackage/pdo-wrapper)
[![StyleCI](https://styleci.io/repos/120492220/shield?branch=master)](https://styleci.io/repos/120492220)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/phppackage/pdo-wrapper/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/phppackage/pdo-wrapper/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/phppackage/pdo-wrapper/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/phppackage/pdo-wrapper/code-structure/master/code-coverage)
[![Packagist Version](https://img.shields.io/packagist/v/phppackage/pdo-wrapper.svg?style=flat-square)](https://github.com/phppackage/pdo-wrapper/releases)
[![Packagist Downloads](https://img.shields.io/packagist/dt/phppackage/pdo-wrapper.svg?style=flat-square)](https://packagist.org/packages/phppackage/pdo-wrapper)

Yet another PDO wrapper which extends the PDO class and adds some additional suger.

## Install

Require this package with composer using the following command:

``` bash
$ composer require phppackage/pdo-wrapper
```

### Usage example:

    <?php
    require 'vendor/autoload.php';
    
    use PHPPackage\PDOWrapper\PDO;

    // connect, a standard PDO constructor
    $db = new PDO(
        'sqlite::memory:',
        'test_username',
        'test_password', 
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );
    
    // or default to an sqlite file
    $db = new PDO();
    
    // get database info
    $info = $pdo->info();
    
    // get databases
    $databases = $pdo->databases();
    
    // get tables
    $tables = $pdo->tables();
    
    // create a database
    $pdo->createDatabase('test');
    
    // get database name (from dsn)
    $name = $pdo->getDatabaseName();
    
    // export database (mysql only)
    $filename = $pdo->export('./'); // ./ = destination folder
    
    // import database (mysql only)
    $pdo->import('./backup.sql.gz');
    
    // create
    $pdo->run('INSERT INTO table_name (name) VALUES (?)', ['foo']);
    
    // create - multi
    $pdo->run('INSERT INTO table_name (name) VALUES (?)', [['foo'], ['bar'], ['baz']]);

    // retrieve - PDOStatement
    $stmt = $pdo->run('SELECT * FROM table_name');
    $stmt = $pdo->run('SELECT * FROM table_name WHERE id = ?', [1]);
    $stmt = $pdo->run('SELECT * FROM table_name WHERE id = :id', ['id' => 1]);

    // retrieve - single row
    $result = $pdo->row('SELECT * FROM table_name WHERE id = ?', [1]);
    $result = $pdo->row('SELECT * FROM table_name WHERE id = :id', ['id' => 1]);
    
    // retrieve - single cell
    $result = $pdo->cell('SELECT column FROM table_name WHERE id = ?', [1]);
    $result = $pdo->cell('SELECT column FROM table_name WHERE id = :id', ['id' => 1]);
    
    // retrieve - all array
    $result = $pdo->all('SELECT * FROM table_name');
    $result = $pdo->all('SELECT * FROM table_name WHERE id = ?', [1]);
    $result = $pdo->all('SELECT * FROM table_name WHERE id = :id', ['id' => 1]);
    
    // update
    $pdo->run('UPDATE table_name SET column = ? WHERE id = ?', ['foo', 1]);
    
    // delete
    $pdo->run('DELETE FROM table_name WHERE id = ?', [1]);
    
    // .. and all other standard PDO functionality

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.


## Credits

 - [Lawrence Cherone](http://github.com/lcherone)
 - [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
