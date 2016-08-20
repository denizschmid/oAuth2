<?php

	require_once "../DataAccessObject.php";
	use Dansnet\DataAccessObject;
	
	class DataAccessObjectTest extends PHPUnit_Framework_TestCase {
		
		public function testInitDataAccessObject() {
			$database = new DataAccessObject("test");
			$database->ConnectSqlite3(":memory:");
			$this->assertNotNull($database);
			return $database;
		}
		
		/**
		 * @depends testInitDataAccessObject
		 */
		public function testCreateSchema( DataAccessObject $database ) {
			$database->SqlExecute("CREATE TABLE test(
					id INTEGER PRIMARY KEY AUTOINCREMENT,
					column1 text,
					column2 text
			);");
			return $database;
		}
		
		/**
		 * @depends testCreateSchema
		 */
		public function testInsert( DataAccessObject $database ) {
			$this->assertTrue(is_array($database->create(["column1"=>"value1","column2"=>"value1"])));
			$this->assertTrue(is_array($database->create(["column1"=>"value2","column2"=>"value2"])));
			$this->assertTrue(is_array($database->create(["column1"=>"value3","column2"=>"value3"])));
			$this->assertTrue(is_array($database->create(["column1"=>"value4","column2"=>"value4"])));
			$this->assertTrue(is_array($database->create(["column1"=>"value5","column2"=>"value5"])));
			$this->assertTrue(is_array($database->create(["column1"=>"value4","column2"=>"value5"])));
			$this->assertTrue(is_array($database->create(["id"=>7, "column1"=>"value6","column2"=>"value6"])));
			$this->assertFalse($database->create(["columnXXX"=>"value5","column2"=>"value5"]));
			$this->assertEquals(7, sizeof($database->getAll()));
			return $database;
		}
		
		/**
		 * @depends testInsert
		 */
		public function testSave( DataAccessObject $database ) {
			$this->assertTrue(is_array($database->save(["column1"=>"value8","column2"=>"value8"])));
			$this->assertTrue(is_array($database->save(["column1"=>"value9","column2"=>"value9"])));
			$this->assertTrue(is_array($database->save(["column1"=>"value10","column2"=>"value10"])));
			$this->assertTrue(is_array($database->save(["column1"=>"value11","column2"=>"value11"])));
			$this->assertTrue(is_array($database->save(["column1"=>"value12","column2"=>"value12"])));
			$this->assertTrue(is_array($database->save(["column1"=>"value13","column2"=>"value13"])));
			$this->assertTrue(is_array($database->save(["id"=>1, "column1"=>"value0815","column2"=>"value0816"])));
			$this->assertEquals(13, sizeof($database->getAll()));
			return $database;
		}
		
		/**
		 * @depends testSave
		 */
		public function testUpdate( DataAccessObject $database ) {
			$this->assertTrue(is_array($database->update(["id"=>5,"column1"=>"value0815","column2"=>"value0816"])));
			$this->assertFalse($database->update(["id"=>2,"columnXXX"=>"value0815","column2"=>"value0816"]));
			$this->assertEquals(13, sizeof($database->getAll()));
			$this->assertEquals("value0815", $database->getById(5)["column1"]);
			$this->assertEquals("value0816", $database->getById(5)["column2"]);
			$this->assertEquals("value2", $database->getById(2)["column1"]);
			$this->assertEquals("value2", $database->getById(2)["column2"]);
			return $database;
		}
		
		/**
		 * @depends testSave
		 */
		public function testMetaData( DataAccessObject $database ) {
			$this->assertEquals(3, sizeof($database->getTableColumns()));
		}
		
		/**
		 * @depends testSave
		 */
		public function testBinding( DataAccessObject $database ) {
			$this->assertTrue($database->checkBindingParameters(["column1"=>"value3"]));
			$this->assertFalse($database->checkBindingParameters(["column5"=>"value3"]));
		}
		
		/**
		 * @depends testSave
		 */
		public function testChecks( DataAccessObject $database ) {
			$this->assertEquals(13, sizeof($database->getAll()));
			$this->assertTrue($database->isUnique(["column1"=>"value10"]));
			$this->assertFalse($database->isUnique(["column1"=>"value0815"]));
		}
		
		/**
		 * @depends testSave
		 */
		public function testfind( DataAccessObject $database ) {
			$this->assertEquals(1, sizeof($database->find(["column1"=>"value3"])));
			$this->assertEquals(3, sizeof($database->findPage([], "", 3)));
			$this->assertEquals(3, sizeof($database->findPage([], "", 3, 3)));
			$this->assertEquals("value3", $database->find(["id"=>3])[0]["column1"]);
			$this->assertEquals(["column1"=>"value4","column2"=>"value4","id"=>4], $database->findFirst(["column1"=>"value4"]));
			$this->assertEquals(0, sizeof($database->findPage(["column1"=>"value40"])));
			$this->assertEquals(0, sizeof($database->findFirst(["column1"=>"value40"])));
		}
		
		
		
	}
