<?php
	namespace Dansnet;

	/**
	 * Model-Klasse für DAOs.
	 *
	 * @author Deniz Schmid
	 */
	class ValueObject {

		/**
		 * Werden die Attribute über den Konstruktor gesetzt, landen hier die Daten,
		 * für die kein Attribut im Objekt existiert.
		 * @var array
		 */
		private $_unknownData;

		/**
		 * Enthält die Rohdaten.
		 * @var array 
		 */
		private $_data;

		/**
		 * Setzt die Attribute eines Objekts. Daten, die kein passendes Attribut
		 * aufweisen, landen im Array "_unknownData".
		 * @param array $data
		 */
		public function __construct( $data=[] ) {
			$this->_unknownData = [];
			$this->_set($data);
		}

		/**
		 * Gibt die im Objekt nicht zugeordneten Daten zurück.
		 * @return array
		 */
		public function getUnknownData() {
			return $this->_unknownData;
		}

		/**
		 * Besetzt anhand eines assoziativen Arrays die Klassenattribute. Falls zu einem
		 * Schlüssel kein Attribut existiert, wird des Schlüssel-Werte-Paar im Array
		 * "_unknownData" gespeichert.
		 * @param array $data
		 */
		private function _set( $data=[] ) {
			$this->_data = $data;
			$reflection = new \ReflectionObject($this);
			foreach( $data as $name=>$value ) {
				try {
					$property = $reflection->getProperty($name);
					$property->setAccessible(true);
					$property->setValue($this, $value);
				} catch ( ReflectionException $ex ) {
					$this->_unknownData[$name] = $value;
					continue;
				}
			}
		}

		/**
		 * Gibt das Objekt im Json-Format zurück.
		 * @return string
		 */
		public function toJson() {
			return json_encode($this->_data);
		}

		/**
		 * Gibt das Objekt im Xml-Format zurück.
		 * @return string
		 */
		public function toXml( $rootName=NULL ) {
			if( empty($rootName) ) {
				$rootName = "root";
			}
			$dom = new DOMDocument('1.0', 'UTF-8');
			$dom->formatOutput = true;
			$root = $dom->createElement($rootName);
			$dom->appendChild($root);
			$array2xml = function ($node, $array, $parentNode) use ($dom, &$array2xml) {
				foreach($array as $key => $value) {
					if ( is_array($value) ) {
						$n = $dom->createElement($key);
						$node->appendChild($n);
						$array2xml($n, $value, $key);
					} else if( is_object($value) ) {
						$n = $dom->createElement($key);
						$node->appendChild($n);
						$array2xml($n, (array)$value, $key);
					} else {
						if( is_numeric($key) ) {
							$n = $dom->createElement(substr($parentNode, 0, -1));
							$n->nodeValue = $value;
							$node->appendChild($n);
						} else {
							$attr = $dom->createAttribute($key);
							$attr->value = $value;
							$node->appendChild($attr);
						}
					}
				}
			};
			$array2xml($root, $this->_data, $rootName);
			return $dom->saveXML();
		}

	}
