<?php

	require_once "../DataAccessObject.php";
	use Dansnet\DataAccessObject;
	
	class StudentDataAccessObject extends DataAccessObject {
		
		public function __construct() {
			parent::__construct("student");
		}
		
		protected function _joinDefinition() {
			return 
			"  INNER JOIN student_vorlesung sv ON sv.student_id=student_id"
			." INNER JOIN vorlesung v ON v.id=sv.vorlesung_id";
		}
		
	}
	
	class DataAccessObjectTest extends PHPUnit_Framework_TestCase {
		
		public function testInitDataAccessObject() {
			$database = new StudentDataAccessObject();
			$database->ConnectSqlite3(":memory:");
			$this->assertNotNull($database);
			return $database;
		}
		
		/**
		 * @depends testInitDataAccessObject
		 */
		public function testCreateSchema( DataAccessObject $database ) {
			$database->SqlExecute("CREATE TABLE student(
					id INTEGER PRIMARY KEY AUTOINCREMENT,
					name TEXT,
					alt INTEGER
			);");
			$database->SqlExecute("CREATE TABLE student_vorlesung(
					id INTEGER PRIMARY KEY AUTOINCREMENT,
					student_id INTEGER,
					vorlesung_id INTEGER
			);");
			$database->SqlExecute("CREATE TABLE vorlesung(
					id INTEGER PRIMARY KEY AUTOINCREMENT,
					name TEXT
			);");
			$database->SqlExecute("INSERT INTO vorlesung (id, name) VALUES(1, 'Mathematik');");
			$database->SqlExecute("INSERT INTO vorlesung (id, name) VALUES(1, 'Informatik');");
			$database->SqlExecute("INSERT INTO vorlesung (id, name) VALUES(1, 'BWL');");
			$this->assertTrue(is_array($database->create(["id"=>1, "name"=>"Max Mustermann","alt"=>20])));
			$this->assertTrue(is_array($database->create(["id"=>2, "name"=>"Fritz Müller","alt"=>24])));
			$this->assertTrue(is_array($database->create(["id"=>3, "name"=>"Marvin Müller","alt"=>23])));
			$this->assertTrue(is_array($database->create(["id"=>4, "name"=>"Simone Pechstein Mustermann","alt"=>"20"])));
			$database->SqlExecute("INSERT INTO student_vorlesung (student_id,vorlesung_id) VALUES(1,1);");
			$database->SqlExecute("INSERT INTO student_vorlesung (student_id,vorlesung_id) VALUES(1,2);");
			$database->SqlExecute("INSERT INTO student_vorlesung (student_id,vorlesung_id) VALUES(1,3);");
			$database->SqlExecute("INSERT INTO student_vorlesung (student_id,vorlesung_id) VALUES(2,1);");
			$database->SqlExecute("INSERT INTO student_vorlesung (student_id,vorlesung_id) VALUES(2,2);");
			$database->SqlExecute("INSERT INTO student_vorlesung (student_id,vorlesung_id) VALUES(3,3);");
			return $database;
		}
		
		/**
		 * @depends testInitDataAccessObject
		 */
		public function testJoin( DataAccessObject $database ) {
			var_dump($database->getById(1, true));
		}		
		
	}
