<?php

	require_once "../DataAccessObject.php";
	require_once "../ModelCreator.php";
	use Dansnet\DataAccessObject;
	use Dansnet\ModelCreator;
	
	class ModelCreatorTest extends PHPUnit_Framework_TestCase {
		
		public function testInitDatabase() {
			$database = new DataAccessObject("test");
			$database->ConnectSqlite3(":memory:");
			$this->assertNotNull($database);
			return $database;
		}
		
		/**
		 * @depends testInitDatabase
		 */
		public function testCreateModel( Dansnet\DatabaseConnector $database ) {
			$database->SqlExecute("CREATE TABLE test(
					ID INTEGER PRIMARY KEY   AUTOINCREMENT,
					column1 text,
					column2 text
			);");
			$database->save(["column1"=>"value1","column2"=>"value1"]);
			$database->save(["column1"=>"value2","column2"=>"value2"]);
			$database->save(["column1"=>"value3","column2"=>"value3"]);
			$database->save(["column1"=>"value4","column2"=>"value4"]);
			$database->save(["column1"=>"value5","column2"=>"value5"]);
			$database->save(["id"=>5, "column1"=>"value6","column2"=>"value6"]);
			$options = [
				"name"    => "Test",
				//"extends" => "DataAccessObject",
				//"require" => "../DataAccessObject.php",
				"namespace" => "Dansnet"
			];
			$creator = new ModelCreator($database, "models");
			$creator->createModelFromSchema("test", $options);
			$this->assertTrue(file_exists("./models/TestModel.php"));
			return $database;
		}
		
		/**
		 * @depends testCreateModel
		 */
		public function testModelFromArray() {
			require_once "./models/TestModel.php";
			$data = [
				"column1" => "test",
				"column2" => "test2"
			];
			$model = new Dansnet\TestModel($data);
			$this->assertNotNull($model);
			$this->assertEquals("test", $model->getColumn1());
			$this->assertEquals("test2", $model->getColumn2());
		}
		
		/**
		 * @depends testCreateModel
		 */
		public function testModelFromDatabase( Dansnet\DatabaseConnector $database ) {
			require_once "./models/TestModel.php";
			$data = $database->SqlGetFirstLine("SELECT * FROM test WHERE id=5");
			$model = new Dansnet\TestModel($data);
			$this->assertNotNull($model);
			$this->assertEquals(5, $model->getID());
			$this->assertEquals("value6", $model->getColumn1());
			$this->assertEquals("value6", $model->getColumn2());
		}
		
	}
