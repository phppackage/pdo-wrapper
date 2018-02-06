<?php

namespace PHPPackage\PDOWrapper;

use PHPUnit\Framework\{
    TestCase, Assert
};

class PDOTest extends TestCase
{
    use \phpmock\phpunit\PHPMock;

    const TEST_CLASS = 'PHPPackage\PDOWrapper\PDO';

    /**
     * @covers PHPPackage\PDOWrapper\PDO::__construct
     */
    public function testObjectInstanceOf()
    {
        $pdo = new PDO();
        $this->assertInstanceOf(self::TEST_CLASS, $pdo);
    }

    /**
     * @covers PHPPackage\PDOWrapper\PDO::__construct
     */
    public function testClassProperties()
    {
        $pdo = new PDO();

        // properties
        $this->assertClassHasAttribute('dsn', self::TEST_CLASS);
        $this->assertClassHasAttribute('username', self::TEST_CLASS);
        $this->assertClassHasAttribute('password', self::TEST_CLASS);
        $this->assertClassHasAttribute('options', self::TEST_CLASS);
        $this->assertClassHasAttribute('attributes', self::TEST_CLASS);

        // properties types
        $this->assertInternalType('string', Assert::readAttribute($pdo, 'dsn'));
        $this->assertInternalType('null', Assert::readAttribute($pdo, 'username'));
        $this->assertInternalType('null', Assert::readAttribute($pdo, 'password'));
        $this->assertInternalType('array', Assert::readAttribute($pdo, 'options'));
        $this->assertInternalType('array', Assert::readAttribute($pdo, 'attributes'));
    }

    /**
     * @covers PHPPackage\PDOWrapper\PDO::__construct
     */
    public function testClassConstruct()
    {
        // init with some non defaults
        $pdo = new PDO(
            'sqlite::memory:',
            'test_username',
            'test_password', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING
            ]
        );

        // get set values
        $dsn      = Assert::readAttribute($pdo, 'dsn');
        $username = Assert::readAttribute($pdo, 'username');
        $password = Assert::readAttribute($pdo, 'password');
        $options  = Assert::readAttribute($pdo, 'options');

        // test equals
        $this->assertEquals('sqlite::memory:', $dsn);
        $this->assertEquals('test_username', $username);
        $this->assertEquals('test_password', $password);
        $this->assertEquals([
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_WARNING,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ], $options);
    }

    /**
     * @covers PHPPackage\PDOWrapper\PDO::__call
     */
    public function testBadMethodCall()
    {
        $pdo = new PDO();

        try {
            $pdo->nonexistent_method();
        } catch (\Exception $e) {
            $this->assertInstanceOf('BadMethodCallException', $e);
            $this->assertEquals('Call to undefined method '.self::TEST_CLASS.'::nonexistent_method()', $e->getMessage());
        }
    }

    /**
     * @covers PHPPackage\PDOWrapper\PDO::createDatabase
     * @covers PHPPackage\PDOWrapper\PDO::databases
     */
    public function testCreateDatabase()
    {
        $pdo = new PDO('mysql:host=127.0.0.1', 'root', '');

        // drop database if exists
        if (in_array('test', $pdo->databases())) {
            $pdo->exec('DROP DATABASE test');
        }
        
        // true on create
        $this->assertTrue($pdo->createDatabase('test'));
        
        // false when already created
        $this->assertFalse($pdo->createDatabase('test'));
    }

    /**
     * @covers PHPPackage\PDOWrapper\PDO::getDatabaseName
     */
    public function testGetDatabaseName()
    {
        $pdo = new PDO('mysql:dbname=test;host=127.0.0.1', 'root', '');

        $name = $pdo->getDatabaseName();

        $this->assertEquals('test', $name);
    }

    /**
     * @covers PHPPackage\PDOWrapper\PDO::getDatabaseName
     */
    public function testGetDatabaseNameException()
    {
        $pdo = new PDO('mysql:host=127.0.0.1', 'root', '');

        try {
            $pdo->getDatabaseName();
        } catch (\Exception $e) {
            $this->assertInstanceOf('RuntimeException', $e);
            $this->assertEquals('Could not match database name from dsn', $e->getMessage());
        }
    }
    
    /**
     * @covers PHPPackage\PDOWrapper\PDO::checkImportExportRequirements
     * @covers PHPPackage\PDOWrapper\PDO::info
     */
    public function testCheckImportExportRequirements()
    {
        $pdo = new PDO('mysql:dbname=test;host=127.0.0.1', 'root', '');
        
        // open private method
        $class = new \ReflectionClass($pdo);
        $method = $class->getMethod('checkImportExportRequirements');
        $method->setAccessible(true);
        
        $function_exists = $this->getFunctionMock(__NAMESPACE__, "function_exists");
        $function_exists->expects($this->at(0))->willReturnCallback(
            function ($cmd) {
                $this->assertTrue(is_string($cmd));
                return true;
            }
        );
        $function_exists->expects($this->at(1))->willReturnCallback(
            function ($cmd) {
                $this->assertTrue(is_string($cmd));
                return false;
            }
        );
        
        $shell_exec = $this->getFunctionMock(__NAMESPACE__, "shell_exec");
        $shell_exec->expects($this->at(0))->willReturnCallback(
            function ($cmd) {
                $this->assertTrue(is_string($cmd));
                return true;
            }
        );
        $shell_exec->expects($this->at(1))->willReturnCallback(
            function ($cmd) {
                $this->assertTrue(is_string($cmd));
                return false;
            }
        );

        // all true
        $method->invoke($pdo, 'import');

        try {
            // all false
            $method->invoke($pdo, 'import');
        } catch (\Exception $e) {
            $this->assertInstanceOf('RuntimeException', $e);
        }
    }

    /**
     * @covers PHPPackage\PDOWrapper\PDO::checkImportExportRequirements
     * @covers PHPPackage\PDOWrapper\PDO::export
     */
    public function testExport()
    {
        $pdo = new PDO('mysql:dbname=test;host=127.0.0.1', 'root', '');

        $file = $pdo->export('./');

        $this->assertFileExists($file);

        unlink($file);
    }

    /**
     * @covers PHPPackage\PDOWrapper\PDO::checkImportExportRequirements
     * @covers PHPPackage\PDOWrapper\PDO::export
     */
    public function testExportInvalidDestination()
    {
        $pdo = new PDO('mysql:dbname=test;host=127.0.0.1', 'root', '');

        try {
            $pdo->export('./foobar');
        } catch (\Exception $e) {
            $this->assertInstanceOf('DomainException', $e);
            $this->assertEquals('Export destination must be a directory', $e->getMessage());
        }
    }

    /**
     * @covers PHPPackage\PDOWrapper\PDO::checkImportExportRequirements
     * @covers PHPPackage\PDOWrapper\PDO::export
     * @covers PHPPackage\PDOWrapper\PDO::import
     */
    public function testImport()
    {
        $pdo = new PDO('mysql:dbname=test;host=127.0.0.1', 'root', '');

        // first export a test file
        $file = $pdo->export('./');

        $this->assertFileExists($file);

        $this->assertTrue($pdo->import($file), 'Could not import exported file');

        unlink($file);
    }

    /**
     * @covers PHPPackage\PDOWrapper\PDO::checkImportExportRequirements
     * @covers PHPPackage\PDOWrapper\PDO::import
     */
    public function testImportInvalidFile()
    {
        $pdo = new PDO('mysql:dbname=test;host=127.0.0.1', 'root', '');

        try {
            $pdo->import('./foobar.sql.gz');
        } catch (\Exception $e) {
            $this->assertInstanceOf('DomainException', $e);
            $this->assertEquals('Import file does not exist', $e->getMessage());
        }
    }

    /**
     * @covers PHPPackage\PDOWrapper\PDO::__construct
     * @covers PHPPackage\PDOWrapper\PDO::info
     */
    public function testInfo()
    {
        $pdo = new PDO();

        $info = $pdo->info();

        // its an array
        $this->assertInternalType('array', $info);

        // all attributes should be checked
        foreach (Assert::readAttribute($pdo, 'attributes') as $value) {
            $this->assertTrue(in_array('PDO::ATTR_'.$value, $info));
        }

        // default error mode
        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $pdo->info('PDO::ATTR_ERRMODE'));
    }

    /**
     * @covers PHPPackage\PDOWrapper\PDO::__construct
     */
    public function testCanInvokePDOMethods()
    {
        $schema = '
        CREATE TABLE IF NOT EXISTS table_name (
            "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            "name" VARCHAR
        );
        INSERT INTO table_name (name) VALUES ("Test")
        ';

        $pdo = new PDO();

        //
        $row_count = $pdo->exec($schema);

        //
        $this->assertEquals(1, $row_count);

        // file was created
        $this->assertFileExists(sys_get_temp_dir().'/PDOWrapper.db');

        // query
        $stmt = $pdo->query('SELECT * FROM table_name');

        // check is PDOStatement object
        $this->assertInstanceOf('\PDOStatement', $stmt);

        // loop results
        foreach ($stmt as $row) {
            $this->assertTrue(is_array($row));
            $this->assertTrue(($row['name'] == 'Test'));
        }

        // clean up
        $pdo->exec('DROP TABLE table_name');
    }

    /**
     * Does not work for SQlite, so it should throw exception or be an array
     *
     * @covers PHPPackage\PDOWrapper\PDO::databases
     */
    public function testDatabases()
    {
        $pdo = new PDO();

        try {
            $result = $pdo->databases();

            $this->assertTrue(is_array($result));
        } catch (\PDOException $e) {
            $this->assertInstanceOf('PDOException', $e);
            $this->assertEquals('SQLSTATE[HY000]: General error: 1 near "SHOW": syntax error', $e->getMessage());
        }
    }

    /**
     * @covers PHPPackage\PDOWrapper\PDO::tables
     */
    public function testTables()
    {
        // with mysql,  it should be an array
        $pdo = new PDO('mysql:dbname=test;host=127.0.0.1', 'root', '');
        
        $schema = '
        CREATE TABLE IF NOT EXISTS `test` (
          `id` int(11) unsigned NOT NULL,
          PRIMARY KEY (`id`)
        ) DEFAULT CHARSET=utf8;';

        //
        $pdo->exec($schema);

        try {
            $result = $pdo->tables();

            $this->assertTrue(is_array($result));
        } catch (\PDOException $e) {
            $this->assertInstanceOf('PDOException', $e);
            $this->assertEquals('SQLSTATE[HY000]: General error: 1 near "SHOW": syntax error', $e->getMessage());
        }
        
        // sqlite, it should throw exception or be an array
        $pdo = new PDO();

        try {
            $result = $pdo->tables();

            $this->assertTrue(is_array($result));
        } catch (\PDOException $e) {
            $this->assertInstanceOf('PDOException', $e);
            $this->assertEquals('SQLSTATE[HY000]: General error: 1 near "SHOW": syntax error', $e->getMessage());
        }
    }
    
    /**
     * @covers PHPPackage\PDOWrapper\PDO::__construct
     * @covers PHPPackage\PDOWrapper\PDO::exec
     * @covers PHPPackage\PDOWrapper\PDO::run
     * @covers PHPPackage\PDOWrapper\PDO::multi
     */
    public function testRun()
    {
        $schema = '
        CREATE TABLE IF NOT EXISTS table_name (
            "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            "name" VARCHAR
        );
        INSERT INTO table_name (name) VALUES ("Test")
        ';

        $pdo = new PDO();

        //
        $row_count = $pdo->exec($schema);

        //
        $this->assertEquals(1, $row_count);

        // file was created
        $this->assertFileExists(sys_get_temp_dir().'/PDOWrapper.db');

        // do method run() test - PDOStatement
        $stmt = $pdo->run('SELECT * FROM table_name');
        $this->assertInstanceOf('\PDOStatement', $stmt);
        
        // loop results
        foreach ($stmt as $row) {
            $this->assertTrue(is_array($row));
            $this->assertTrue(($row['name'] == 'Test'));
        }
        
        // do method run() test - with params
        $stmt = $pdo->run('SELECT * FROM table_name WHERE id = ?', [1]);
        $this->assertInstanceOf('\PDOStatement', $stmt);
        
        // loop results
        foreach ($stmt as $row) {
            $this->assertTrue(is_array($row));
            $this->assertTrue(($row['name'] == 'Test'));
        }
        
        // do method run() test - with multi insert
        $row_count = $pdo->run('INSERT INTO table_name (name) VALUES (?)', [['foo'], ['bar'], ['baz']]);
        $this->assertEquals(3, $row_count);
        
        // do method run() test - InvalidArgumentException
        try {
            $pdo->run('');
        } catch (\Exception $e) {
            $this->assertInstanceOf('InvalidArgumentException', $e);
            $this->assertEquals('1st argument cannot be empty', $e->getMessage());
        }

        // clean up
        $pdo->exec('DROP TABLE table_name');
    }
    
    /**
     * @covers PHPPackage\PDOWrapper\PDO::__construct
     * @covers PHPPackage\PDOWrapper\PDO::exec
     * @covers PHPPackage\PDOWrapper\PDO::multi
     */
    public function testMulti()
    {
        $schema = '
        CREATE TABLE IF NOT EXISTS table_name (
            "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            "name" VARCHAR
        );
        INSERT INTO table_name (name) VALUES ("Test")
        ';

        $pdo = new PDO();

        //
        $row_count = $pdo->exec($schema);

        //
        $this->assertEquals(1, $row_count);

        // file was created
        $this->assertFileExists(sys_get_temp_dir().'/PDOWrapper.db');

        // test method multi()
        $row_count = $pdo->multi('INSERT INTO table_name (name) VALUES (?)', [['foo'], ['bar'], ['baz']]);
        $this->assertEquals(3, $row_count);
        
        // test method multi() - InvalidArgumentException
        try {
            $pdo->multi('');
        } catch (\Exception $e) {
            $this->assertInstanceOf('InvalidArgumentException', $e);
            $this->assertEquals('1st argument cannot be empty', $e->getMessage());
        }
        
        // test method multi() - InvalidArgumentException
        try {
            $pdo->multi('INSERT INTO table_name (name) VALUES (?)', []);
        } catch (\Exception $e) {
            $this->assertInstanceOf('InvalidArgumentException', $e);
            $this->assertEquals('2nd argument must be an array of arrays', $e->getMessage());
        }

        // clean up
        $pdo->exec('DROP TABLE table_name');
    }

    /**
     * @covers PHPPackage\PDOWrapper\PDO::__construct
     * @covers PHPPackage\PDOWrapper\PDO::exec
     * @covers PHPPackage\PDOWrapper\PDO::run
     * @covers PHPPackage\PDOWrapper\PDO::row
     */
    public function testRow()
    {
        $schema = '
        CREATE TABLE IF NOT EXISTS table_name (
            "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            "name" VARCHAR
        );
        INSERT INTO table_name (name) VALUES ("Test")
        ';

        $pdo = new PDO();

        //
        $row_count = $pdo->exec($schema);

        //
        $this->assertEquals(1, $row_count);

        // file was created
        $this->assertFileExists(sys_get_temp_dir().'/PDOWrapper.db');
        
        // test method row()
        $row = $pdo->row('SELECT * FROM table_name');
        $this->assertTrue(is_array($row));
        $this->assertTrue(($row['name'] == 'Test'));

        // test method row()
        $row = $pdo->row('SELECT * FROM table_name WHERE id = ?', [1]);
        $this->assertTrue(is_array($row));
        $this->assertTrue(($row['name'] == 'Test'));

        // clean up
        $pdo->exec('DROP TABLE table_name');
    }

    /**
     * @covers PHPPackage\PDOWrapper\PDO::__construct
     * @covers PHPPackage\PDOWrapper\PDO::exec
     * @covers PHPPackage\PDOWrapper\PDO::run
     * @covers PHPPackage\PDOWrapper\PDO::cell
     */
    public function testCell()
    {
        $schema = '
        CREATE TABLE IF NOT EXISTS table_name (
            "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            "name" VARCHAR
        );
        INSERT INTO table_name (name) VALUES ("Test")
        ';

        $pdo = new PDO();

        //
        $row_count = $pdo->exec($schema);

        //
        $this->assertEquals(1, $row_count);

        // file was created
        $this->assertFileExists(sys_get_temp_dir().'/PDOWrapper.db');

        // test method cell()
        $value = $pdo->cell('SELECT name FROM table_name WHERE id = ? LIMIT 1', [1]);
        
        $this->assertEquals('Test', $value);

        // clean up
        $pdo->exec('DROP TABLE table_name');
    }
    
    /**
     * @covers PHPPackage\PDOWrapper\PDO::__construct
     * @covers PHPPackage\PDOWrapper\PDO::exec
     * @covers PHPPackage\PDOWrapper\PDO::run
     * @covers PHPPackage\PDOWrapper\PDO::all
     */
    public function testAll()
    {
        $schema = '
        CREATE TABLE IF NOT EXISTS table_name (
            "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            "name" VARCHAR
        );
        INSERT INTO table_name (name) VALUES ("Test")
        ';

        $pdo = new PDO();

        //
        $row_count = $pdo->exec($schema);

        //
        $this->assertEquals(1, $row_count);

        // file was created
        $this->assertFileExists(sys_get_temp_dir().'/PDOWrapper.db');

        // test method all()
        $result = $pdo->all('SELECT name FROM table_name');
        
        // loop results
        foreach ($result as $row) {
            $this->assertTrue(is_array($row));
            $this->assertTrue(($row['name'] == 'Test'));
        }

        // clean up
        $pdo->exec('DROP TABLE table_name');
    }

    /**
     *
     */
    public static function tearDownAfterClass()
    {
        unlink(sys_get_temp_dir().'/PDOWrapper.db');
    }
}
