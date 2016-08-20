<?php

	require_once "../ValueObject.php";
	
	class Student extends ValueObject {
		
		private $name;
		public $alter;
		private $parent;
		
		public function getName() {
			return $this->name;
		}
		
	}
	
	class ValueObjectTest extends PHPUnit_Framework_TestCase {
		
		public function testValueObject( ) {
			$data = [
				"name" => "Max Mustermann",
				"alter" => 20,
				"hobbies" => ["Fußball", "Tennis"],
				"parent" => (object)["name"=>"Dad"]
			];
			$object = new Student($data);
			$this->assertNotNull($object);
			$this->assertEquals("Max Mustermann", $object->getName());
			$this->assertEquals(20, $object->alter);
			$this->assertEquals(["hobbies"=>["Fußball", "Tennis"]], $object->getUnknownData());
		}		
		
	}
