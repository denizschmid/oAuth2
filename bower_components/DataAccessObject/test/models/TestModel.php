<?php			
	/**
	 * VORSICHT: 
	 * Diese Klasse wurde automatisch generiert. Änderungen werden bei der nächsten
	 * Erstellung überschrieben. Sollte das Model erweitert werden muss die Klasse
	 * abgeleitet werden.
	 */
	namespace Dansnet;
	

	class TestModel extends ValueObject /**/ {

		//private $_fromDatabaseTable;

		private $id;

		private $column1;

		private $column2;
	
		/*public function __construct( $data=[] ) {
			$this->_fromDatabaseTable = "test";
			$this->fromArray($data);
		}
			
		public function fromArray( $data ) {
			$reflect = new \ReflectionClass($this);
			$properties = $reflect->getProperties();
			foreach( $properties as $member ) {
				if( !array_key_exists($member->name, $data) ) continue;
				$this->{$member->name} = $data[$member->name];
			}
		}
			
		public function toArray( array $exclude=[] ) {
			$reflect = new \ReflectionClass($this);
			$properties = $reflect->getProperties();
			$data = [];
			foreach( $properties as $member ) {
				if( in_array($member->name, $exclude) ) continue;
				$data[$member->name] = $this->{$member->name};
			}
			return $data;
		}*/
			

		public function getId() {
			return $this->id;
		}

		public function setId( $id ) {
			$this->id = $id;
		}

		public function getColumn1() {
			return $this->column1;
		}

		public function setColumn1( $column1 ) {
			$this->column1 = $column1;
		}

		public function getColumn2() {
			return $this->column2;
		}

		public function setColumn2( $column2 ) {
			$this->column2 = $column2;
		}

			
	}