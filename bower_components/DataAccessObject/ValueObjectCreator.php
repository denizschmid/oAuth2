<?php

	namespace Dansnet;

	require_once "DataAccessObject.php";

	class ValueObjectCreator {
		
		private $connection;
		private $directory;
		
		public function __construct( DatabaseConnector $connection, $directory ) {
			$this->connection = $connection;
			$this->directory = $directory;
		}

		/**
		 * Erzeugt die Model aus einem Datenbankschema
		 * Folgende Optionen sind verfügbar:
		 * - name: Name der Klasse
		 * - extends: Elternklasse, von der das Model erbt
		 * - require: Datei, die inkludiert werden soll (nur nötig, wenn kein autoload) 
		 * 
		 * @param string $table
		 * @param array $options
		 */
		public function createModelFromSchema( $table, array $options=[] ) {
			if( !file_exists($this->directory) ) {
				echo "Das Verzeichnis $this->directory existiert nicht.";
				return FALSE;
			}
			if( !is_writable($this->directory) ) {
				echo "Das Verzeichnis $this->directory kann nicht beschrieben werden.";
				return FALSE;
			}
			$options["name"] = array_key_exists("name", $options) ? $options["name"] : $table;
			$options["table"] = $table;
			$columns = $this->getColumnNames($table);
			$class = $this->getModelTemplate();
			$this->replaceModelDefinition($class, $options);
			$this->replaceModelRequirements($class, $options);
			$this->replaceModelMembers($class, $columns);
			$this->replaceModelGetterSetter($class, $columns);
			//return $class;
			file_put_contents($this->directory."/".$options["name"]."Model.php", $class);
		}
		
		/**
		 * Ermittelt die Spaltennamen einer Tabelle.
		 * @param string $table
		 * @return array
		 */
		public function getColumnNames( $table ) {
			$result = $this->connection->SqlGetLines("SELECT * FROM sqlite_master WHERE tbl_name = '$table'");
			$colnames = array() ;
			$sql = $result[0]["sql"];
			$r = preg_match("/\(\s*(\S+)[^,)]*/", $sql, $m, PREG_OFFSET_CAPTURE) ;
			while ($r) {
				array_push( $colnames, str_replace("'", "", $m[1][0]) ) ;
				$r = preg_match("/,\s*(\S+)[^,)]*/", $sql, $m, PREG_OFFSET_CAPTURE, $m[0][1] + strlen($m[0][0]) ) ;
			}
			return $colnames;
		}
		
		public function replaceModelDefinition( &$template, array $options ) {
			$extends = array_key_exists("extends", $options) ? "extends ".$options["extends"] : "";
			$namespace = array_key_exists("namespace", $options) ? "namespace ".$options["namespace"].";" : "";
			$template = str_replace("#[CLASS]#", $options["name"], $template);
			$template = str_replace("#[TABLE]#", $options["table"], $template);
			$template = str_replace("#[EXTENDS]#", $extends, $template);
			$template = str_replace("#[NAMESPACE]#", $namespace, $template);
			return $template;
		}
		
		public function replaceModelRequirements( &$template, array $options ) {
			$require = array_key_exists("require", $options) ? $options["require"] : "";
			if( !empty($require) ) {
				$require = "require_once('$require');";
			}
			$template = str_replace("#[REQUIRE]#", $require, $template);
			return $template;
		} 
		
		public function replaceModelMembers( &$template, $columns ) {
			$members = "";
			foreach( $columns as $column ) {
				$members .= "\n\t\tprivate $$column;\n";
			}
			$template = str_replace("#[MEMBERS]#", $members, $template);
			return $template;
		}
		
		public function replaceModelGetterSetter( &$template, $columns ) {
			$members = "";
			foreach( $columns as $column ) {
				$members .= "\n\t\tpublic function get".ucfirst($column)."() {\n";
				$members .= "\t\t\treturn \$this->$column;\n";
				$members .= "\t\t}\n";
				$members .= "\n\t\tpublic function set".ucfirst($column)."( \$$column ) {\n";
				$members .= "\t\t\t\$this->$column = \$$column;\n";
				$members .= "\t\t}\n";
			}
			$template = str_replace("#[GETTERSETTER]#", $members, $template);
			return $template;
		}
		
		private function getModelTemplate() {
			return <<<TEMPLATE
<?php			
	/**
	 * VORSICHT: 
	 * Diese Klasse wurde automatisch generiert. Änderungen werden bei der nächsten
	 * Erstellung überschrieben. Sollte das Model erweitert werden muss die Klasse
	 * abgeleitet werden.
	 */
	#[NAMESPACE]#
	#[REQUIRE]#

	class #[CLASS]#Model extends ValueObject /*#[EXTENDS]#*/ {

		//private \$_fromDatabaseTable;
#[MEMBERS]#	
		/*public function __construct( \$data=[] ) {
			\$this->_fromDatabaseTable = "#[TABLE]#";
			\$this->fromArray(\$data);
		}
			
		public function fromArray( \$data ) {
			\$reflect = new \\ReflectionClass(\$this);
			\$properties = \$reflect->getProperties();
			foreach( \$properties as \$member ) {
				if( !array_key_exists(\$member->name, \$data) ) continue;
				\$this->{\$member->name} = \$data[\$member->name];
			}
		}
			
		public function toArray( array \$exclude=[] ) {
			\$reflect = new \\ReflectionClass(\$this);
			\$properties = \$reflect->getProperties();
			\$data = [];
			foreach( \$properties as \$member ) {
				if( in_array(\$member->name, \$exclude) ) continue;
				\$data[\$member->name] = \$this->{\$member->name};
			}
			return \$data;
		}*/
			
#[GETTERSETTER]#
			
	}
TEMPLATE;
		}

}
