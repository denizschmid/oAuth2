<?php

	namespace Dansnet;
	
	/**
	 * DataAccessObject bietet eine PDO-Datenbankschnittstelle, die nicht nur einfache
	 * Queries ausführen kann, sondern erweitert diese mit komplexeren Such- und
	 * Manipulationsfunktionen.
	 */
    class DataAccessObject extends DatabaseConnector {

		/**
		 * Tabellennamen des Objekts in der Datenbank
		 * @var string
		 */
		protected $table;

		/**
		 * Die Spalte des Primary-Keys der Tabelle
		 * @var string 
		 */
		private $columnId = "id";

		/**
		 * Rückgabe der Abfrage, Standard ist assoziatives Array
		 * @var integer 
		 */
		private $fetch = \PDO::FETCH_ASSOC;

		/**
		 * Konstruktor mit Basiseinstellungen
		 *
		 * @param string $table	Name der Tabelle.
		 * @param boolean $printSql	Gibt jedes auszuführende SQL-Statement zu 
		 *							Debug-Zwecken aus, wenn TRUE
		 *							(SQL-Statements werden ausgegeben mittels:
		 *							<div class='PrintSql'></div>)
		 * @param string $tablePrefix Zu verwendendes Tabellenpräfix (werden 
		 *							  in einer Datenbank mehrere gleiche Tabellen 
		 *							  mit unterschiedlichem Präfix verwendet, 
		 *							  wie zum Beispiel "user1_counter", 
		 *							  "user2_counter" etc., so kann das jeweilige		
		 *							  Präfix hier angegeben werden. Die SQL-
		 *							  Abfragen können anschließend zum Beispiel 
		 *							  im allgemeinen Format 'SELECT * FROM #_counter' 
		 *							  verfasst werden, um auf die jeweils richtige 
		 *							  Tabelle zuzugreifen)
		 *							  In diesem Fall wäre $TablePrefix='user1_' zu setzen.
		 */
		public function __construct( $table, $printSql = FALSE, $tablePrefix = "" ) {
			parent::__construct($printSql, $tablePrefix);
			$this->table = $table;
		}
		
		/**
		 * Ändert die Tabellenverknüpfung der Verbindung. Alle Abfragen laufen
		 * über den neuen Tabellennamen.
		 * @param string $table
		 */
		public function setTable( $table ) {
			$this->table = $table;
		}
		
		protected function _joinDefinition() {
			return "";
		}

		/**
		 * Ermittelt die Anzahl an Datensätzen in der Tabelle.
		 *
		 * @return integer|FALSE
		 */
		public function count() {
			return $this->SqlGetFirstLine("SELECT COUNT(*) AS size FROM $this->table", $this->fetch)["size"];
		}

		/**
		 * Prüft, ob ein Datensatz existiert.
		 *
		 * @param array $data Die Daten, nach denen geprüft werden soll.
		 * @return boolean|FALSE
		 */
		public function exists( array $data ) {
			$result = $this->find($data);
			if( $result === FALSE ) {
				return FALSE;
			}
			return sizeof($result) > 0;
		}

		/**
		 * Prüft, ob ein Datensatz existiert und genau einmal vorkommt.
		 *
		 * @param array $data Die Daten, nach denen geprüft werden soll.
		 * @return boolean|FALSE
		 */
		public function isUnique( $data ) {
			$result = $this->find($data);
			if( $result === FALSE ) {
			return FALSE;
			}
			return sizeof($result) == 1;
		}

		/**
		* Ermittelt alle Datensätze aus der Tabelle.
		*
		* @param string $order Die Sortierung der Tabelle.
		* @param integer $limit	Anzahl der Datensätze, die ermittelt werden 
		*						sollen
		* @return array|FALSE
		*/
		public function getAll( $order="", $limit=-1 ) {
			return $this->findPage([], $order, $limit, 0);
		}

		/**
		 * Ermittelt den 1. Datensatz.
		 *
		 * @param array	$data Die Daten, nach denen gefiltert werden soll.
		 * @return boolean|FALSE
		 */
		public function findFirst( $data ) {
			$data = $this->find($data);
			if( $data === FALSE ) {
				return FALSE;
			} else if( sizeof($data) < 1 ) {
				return [];
			}
			return $data[0];
		}
		
		/**
		 * Sucht Datensätze nach Suchkriterien.
		 *
		 * @param array	$data Die Daten, nach denen gefiltert werden soll.
		 * @param string $order	Die Sortierung der Tabelle.
		 * @param integer $limit Anzahl der Datensätze, die ermittelt werden 
		 *						 sollen
		 * @return boolean|FALSE
		 */
		public function find( $data, $order="", $limit=-1 ) {
			return $this->findPage($data, $order, $limit, 0);
		}

		/**
		 * Ermittelt eine bestimmte Anzahl an Datensätzen anhand des Filters aus 
		 * der Tabelle ab einem Startpunkt. Simuliert eine Art Paging-Funktion.
		 *
		 * @param array	$data Die Daten, nach denen gefiltert werden soll.
		 * @param string $order	Die Sortierung der Tabelle.
		 * @param integer $limit Anzahl der Datensätze, die ermittelt werden 
		 *						 sollen
		 * @param integer $start Startposition, ab da die Datensätze ermittelt 
		 *						 werden
		 * @return array|FALSE
		 */
		public function findPage( $data, $order="", $limit=-1, $start=0, $extended=false ) {
			
			$whereString = "";
			foreach( array_keys($data) as $column ) {
				$whereString .= ( empty($whereString) ? "WHERE " : " AND " )."$column=:$column";
			}
			
			$orderString = "";
			if( !empty($order) ) {
				$orderString = "ORDER BY $order";
			}
			
			$joinString = $extended ? $this->_joinDefinition() : "";
			
			$query = "SELECT * FROM $this->table $joinString $whereString $orderString LIMIT $limit OFFSET $start";
			$this->SqlPrepareStatement($query, $this->fetch);
			foreach( $data as $column=>$value ) {
				$this->SqlBindPreparedValue(":$column", $value, $this->getPDOType($value));
			}
			
			if( ($result = $this->SqlGetPreparedLines()) === null ) {
				return [];
			}
			return $result;
		}

		/**
		 * Ermittelt einen Datensatz anhand seiner ID.
		 *
		 * @param integer $id ID des Datensatzes
		 * @return array|FALSE
		 */
		public function getById( $id, $extended=false ) {
			$joinString = $extended ? $this->_joinDefinition() : "";
			$this->SqlPrepareStatement("SELECT * FROM $this->table $joinString WHERE $this->table.$this->columnId=?", $this->fetch);
			$data = $this->SqlGetPreparedLines($id);
			if( sizeof($data) !== 1) {
				return FALSE;
			}
			return $data[0];
		}

		/**
		 * Legt einen Datensatz neu an oder aktualisiert einen Datensatz. Ist im
		 * übergebenen Daten-Array eine ID enthalten, dessen Spaltenname über die
		 * Variable $columnId definiert ist, so wird der Datensatz mit dieser ID
		 * aktualisiert. Ansonsten wird dieser neu angelegt.
		 *
		 * @param array $data Die Daten, die gespeichert werden sollen.
		 * @return array|FALSE
		 */
		public function save( array $data ) {
			if(array_key_exists($this->columnId, $data) ) {
				$result = $this->getById($data[$this->columnId]);
				if( $result !== FALSE ) {
					return $this->update($data);
				}
			}
			return $this->create($data);
		}

		/**
		 * Legt einen neuen Datensatz an.
		 *
		 * @param array $data Die Daten des Datensatzes, der angelegt werden soll.
		 * @return array|FALSE Die Daten des angelegten Datensatzes oder FALSE 
		 *                     im Fehlerfall
		 */
		public function create( $data ) {
			
			// Prüfe Parameter, damit kein "FATAL ERROR" beim Binding entsteht
			if( !$this->checkBindingParameters($data) ) {
				return FALSE;
			}
			
			$columnString = "";
			$valueString = "";
			foreach( array_keys($data) as $column ) {
				$columnString .= ( empty($columnString) ? $column    : ", $column" );
				$valueString  .= ( empty($valueString)  ? ":$column" : ", :$column" );
			}
			$query = "INSERT INTO $this->table ($columnString) VALUES ($valueString)";
			$this->SqlPrepareStatement($query, $this->fetch);

			foreach( $data as $column=>$value ) {
				$this->SqlBindPreparedValue(":$column", $value, $this->getPDOType($value));
			}
			
			if( $this->SqlGetPreparedLines() !== FALSE ) {
				return $this->getById($this->SqlGetLastInsertId());
			} else {
				return FALSE;
			}
		}

		/**
		 * Aktualisiert einen Datensatz anhand der ID.
		 *
		 * @param array $data Die Daten des Datensatzes, der aktualisiert werden
		 *					  soll, samt ID.
		 * @return array|FALSE Die Daten des angelegten Datensatzes oder FALSE 
		 *                     im Fehlerfall
		 */
		public function update( $data ) {
			
			// Prüfe Parameter, damit kein "FATAL ERROR" beim Binding entsteht
			if( !$this->checkBindingParameters($data) || empty($data[$this->columnId]) ) {
				return FALSE;
			}
			
			$updateString = "";
			foreach( $data as $column=>$value ) {
				if( $column === $this->columnId ) continue;
				$updateString .= ( empty($updateString) ? "" : ", ")."$column=:$column";
			} 
			$query = "UPDATE $this->table SET $updateString WHERE $this->columnId=:$this->columnId";
			$this->SqlPrepareStatement($query, $this->fetch);

			foreach( $data as $column=>$value ) {
				$this->SqlBindPreparedValue(":$column", $value, $this->getPDOType($value));
			}

			if( $this->SqlGetPreparedLines() !== FALSE ) {
				return $this->getById($data[$this->columnId]);
			} else {
				return FALSE;
			}
		}

		/**
		 * Löscht einen Datensatz anhand seiner ID. Wir ein leerer Wert als ID 
		 * übergeben, wird als Sicherheit FALSE zurückgegeben, da sonst die ge-
		 * samte Tabelle gelöscht würde.
		 *
		 * @param array $id	ID des Datensatzes, der gelöscht werden soll.
		 * @return array|FALSE
		 */
		public function delete( $id ) {
			if( empty($id) || !$this->isUnique(["$this->columnId"=>$id]) ) {
			return FALSE;
			}
			$query = "DELETE FROM $this->table WHERE $this->columnId=?";
			$this->SqlPrepareStatement($query, $this->fetch);
			if( $this->SqlGetPreparedLines($id) !== FALSE ) {
			if( $this->getById($id) === NULL ) {
				return TRUE;
			}
			}
			return FALSE;
		}

		/**
		 * Löscht alle Datensätze aus der Tabelle und setzt alle Sequenzen aus 
		 * dieser zurück. Das bedeuted, dass z.B. AUTO-INCREMENT-Spalten wieder
		 * mit dem Index 1 beginnen.
		 *
		 * @return boolean
		 */
		public function deleteAll() {
			if( $this->SqlExecute("DELETE FROM $this->table") === FALSE ) {
			return FALSE;
			}
			return $this->removeSequences();
		}

		/**
		 * Setzt den Namen der ID-Spalte aus der Tabelle. Diese wird für die 
		 * Abfragen aus dieser Klasse verwendet. Standard ist hier "id".
		 *
		 * @param string $column Name der ID-Spalte aus der Tabelle.
		 * @return array|FALSE
		 */
		public function setColumnId( $column ) {
			$this->columnId = $column;
		}
		
		/**
		 * Ermittelt die Spalten-Metadaten einer Tabelle.
		 * @return array
		 */
		public function getTableColumns() {
			return $this->SqlGetLines("PRAGMA table_info($this->table)");
		}
		
		/**
		 * Prüft, ob die Eingangsdaten (assoziatives Array) dem Tabellenschema
		 * entsprechen. Es wird abgegelichen, ob die eingehenden Keys als Tabellen-
		 * spalte enthalten sind.
		 * @param array $params
		 * @return boolean
		 */
		public function checkBindingParameters( $params ) {
			$columns = array_column($this->getTableColumns(), "name");
			foreach( array_keys($params) as $key ) {
				if( !in_array($key, $columns) ) {
					return false;
				}
			} 
			return true;
		}

		/**
		 * Setzt alle Sequenzen der Tabelle zurück. Dadurch beginnt der Index der
		 * AUTO-INCREMENT-Sequenzen wieder bei 1.
		 *
		 * @return array|FALSE
		 */
		private function removeSequences() {
			$query = "DELETE FROM sqlite_sequence WHERE name='$this->table'";
			return $this->SqlExecute($query);
		}

		/**
		 * Ermittelt den PDO-Datentyp einer Variable. Dieser Datentyp ist wichtig
		 * beim Einsatz von Prepared-Statements.
		 *
		 * @param mixed $param Parameter, dessen Datentyp ermittelt werdens soll.
		 * @return array|FALSE
		 */
		private function getPDOType( $param ) {
			return is_integer($param) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
		}
		
		/**
		 * Setzt den Tabellennamen, auf der die Queries ausgeführt werden.
		 * @return string
		 */
		public function getTable() { 
			return $this->table;
		}
		
    } 

    /****************************************************************************
     * Datenbank-Wrapper für MySQL, Postgres und SQLite-Datenbanktypen
     * Wichtig: Die jeweiligen Treiber müssen auf dem Zielsystem vorhanden sein!
     ***************************************************************************/
    class DatabaseConnector
    {
	    // PDO-Datenbankobjekt für sämtliche Verbindungen
	    private $_DatabaseObject = NULL; 
	    // Angabe, ob alle auszuführenden SQL-Statements zu Debug-Zwecken
	    // ausgegeben werden sollen
	    private $_PrintSql = false;
	    // Tabellenpräfix, durch den ein evtl. Platzhalter ersetzt werden soll
	    private $_TablePrefix = '';
	    // Enthält jeweils die Fehlermeldung des zuletzt aufgetretenen Fehlers
	    private $_LastErrorMessage = '';
	    // Temporärer Zwischenspeichern für partiell zu übertragende SQL-Ergebnisse
	    private $_PDOStatement = NULL;
	    // Temporärer Zwischenspeichern für PreparedStatements
	    protected $_PDOStatementPrepared = NULL;
	    // Temporärer Zwischenspeicher für die Zeitmessung von GetExecutionTime()
	    private $_ExecutionStart = 0;
	    // Ergebnis der Laufzeit-Berechnung von GetExecutionTime()
	    private $_ExecutionTime = 0;
	    // Notwendig, um auch verschachtelte Transaktionen zuzulassen		
	    protected $_TransactionLevel = 0;
	    // Angabe der Verbindungstypen, die sicher verschachtelte Transaktionen
	    // erlauben (SAVEPOINTS werden unterstützt)
	    // Hinweis: MySQL unterstützt Transaktionen nicht bei MyISAM-Tabellen!
	    protected $_NestedTransactionDrivers = 
		    array('pgsql', 'mysql', 'sqlite');




	    # Konstruktoren >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>

	    /*************************************************************************
	     * __construct ()
	     *
	     * Konstruktor mit Basiseinstellungen
	     *
	     * @param PrintSql    Gibt jedes auszuführende SQL-Statement zu Debug-
	     *                    Zwecken aus, wenn TRUE
	     *                    (SQL-Statements werden ausgegeben mittels:
	     *                    <div class='PrintSql'></div>)
	     * @param TablePrefix Zu verwendendes Tabellenpräfix (werden in einer Da-
	     *                    tenbank mehrere gleiche Tabellen mit unterschied-
	     *                    lichem Präfix verwendet, wie zum Beispiel
	     *                    "user1_counter", "user2_counter" etc., so kann das 
	     *                    jeweilige Präfix hier angegeben werden. Die SQL-
	     *                    Abfragen können anschließend zum Beispiel im all-
	     *                    gemeinen Format 'SELECT * FROM #_counter' verfasst 
	     *                    werden, um auf die jeweils richtige Tabelle zuzu-
	     *                    greifen)
	     *                    In diesem Fall wäre $TablePrefix='user1_' zu setzen.
	     ************************************************************************/
	    public function __construct ($PrintSql = FALSE, $TablePrefix = '')
	    {
		    $this->_PrintSql = $PrintSql;
		    $this->_TablePrefix = $TablePrefix;
	    }

	    # Konstruktoren <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<




	    # Private Methoden >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>

	    /*************************************************************************
	     * splitHostPort ()
	     *
	     * Nimmt eine Host-Angabe incl. Port entgegen und gibt sie gesplittet
	     * in einem Array zurück
	     *
	     * @param HostPort Hostangabe incl. Port (z. B. "localhost:3030")
	     *
	     * @return Assoziatives Array mit den Feldern ['host'] und ['port']
	     ************************************************************************/
	    private function splitHostPort ($HostPort)
	    {
		    $strPort = '';

		    // Wurde eine vollständige URL incl. z. B. "http://..." übergeben,
		    // werden hier der Einfachheit halber kurzzeitig Zeichen vertauscht,
		    // um mittels explode nach ":" splitten zu können. 
		    // Wird am Ende vor der Rückgabe wieder getauscht.
		    $HostPort = str_replace('://', '%DBTMP%', $HostPort);

		    $arrExplode = explode (':', $HostPort, 2);

		    $iCount = count($arrExplode);
		    if ($iCount == 2)
		    {
			    $strHost = $arrExplode[0];
			    $strPort = $arrExplode[1];
		    }
		    else if ($iCount == 1)
		    {
			    $strHost = $arrExplode[0];
		    }

		    $strHost = str_replace('%DBTMP%', '://', $strHost);
		    return array ('host' => $strHost, 'port' => $strPort);
	    }


	    /*************************************************************************
	     * isNestableTransaction ()
	     *
	     * Prüft, ob der derzeit verwendete Datenbanktreiber verschachtelte
	     * Transaktionen unterstützt
	     *
	     * @return TRUE, wenn verschachtelte Transaktionen unterstützt werden,
	     *         ansonsten FALSE
	     ************************************************************************/
	    private function isNestableTransaction ()
	    {
		    return in_array($this->GetDBType(), $this->_NestedTransactionDrivers);
	    }


	    /*************************************************************************
	     * replacePrefix ()
	     * 
	     * Ersetzt den eingestellten Präfix-Platzhalter durch das eingestellte
	     * Präfix
	     *
	     * @param SqlQuery SQL-Abfrage mit optional verwendetem Präfix-Platzhalter
	     *                 (default: '#_')
	     * @param Prefix   Prefix-Platzhalter, der in der Abfrage verwendet wurde
	     *
	     * @return String mit übergebener SQL-Abfrage mit ersetztem Präfix-Platz-
	     *         halter
	     ************************************************************************/
	    private function replacePrefix ($SqlQuery, $Prefix = '#_')
	    {
		    return str_replace($Prefix, $this->_TablePrefix, $SqlQuery);
	    }


	    /*************************************************************************
	     * startExecutionTimer ()
	     * 
	     * Startet die Zeitmessung für die Ausführungslaufzeit von Abfragen
	     ************************************************************************/
	    private function startExecutionTimer ()
	    {
		    $this->_ExecutionTime = 0;
		    $this->_ExecutionStart = microtime(true);
	    }


	    /*************************************************************************
	     * stopExecutionTimer ()
	     * 
	     * Stoppt die Zeitmessung für die Ausführungslaufzeit von Abfragen
	     ************************************************************************/
	    private function stopExecutionTimer ()
	    {
		    $this->_ExecutionTime = microtime(true) - $this->_ExecutionStart;
	    }


	    /*************************************************************************
	     * SqlGetPDOObject ()
	     * 
	     * Führt ein übergebenes SQL-Statement auf der Datenbank aus und gibt
	     * das Ergebnis als weiterverarbeitbares PDOObject zurück
	     * 
	     * @param $SqlQuery Auszuführendes SQL-Statement mit optional verwendetem 
	     *                  Präfix-Platzhalter
	     * @param $FetchMethod Optionaler Abfragetyp für das aktuelle Statement
	     *                     (Standard ist PDO::FETCH_BOTH; ansonsten 
	     *                     Konstante der Sammlung PDO::FECH_xxx)
	     * 
	     * @return Ergebnis der Abfrage als weiterverarbeitbares PDOObject oder
	     *         FALSE im Fehlerfall
	     ************************************************************************/
	    private function sqlGetPDOObject ($SqlQuery, $FetchMethod = \PDO::FETCH_BOTH)
	    {
		    $this->_LastErrorMessage = '';
		    $resultReturn = FALSE;
		    $this->startExecutionTimer();

		    if (!$this->IsConnected())
		    {
			    $this->_LastErrorMessage = 'no database connected';
			    return FALSE;
		    }

		    try
		    {
			    if ($this->_PrintSql === TRUE)
			    {
				    echo "<div id='PrintSql'>$SqlQuery</div>";
			    }

			    if (($resultReturn = 
				    $this->_DatabaseObject->query(
					    $this->replacePrefix($SqlQuery), $FetchMethod)) === FALSE)
			    {
				    $arrTemp = $this->_DatabaseObject->errorInfo();
				    $this->_LastErrorMessage = $arrTemp[2];

				    return FALSE;
			    }

			    $this->stopExecutionTimer();
			    return $resultReturn;
		    }
		    catch (PDOException $e)
		    {
			    $this->_LastErrorMessage = $e->getMessage();
			    return FALSE;
		    }
	    }

	    # Private Methoden <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<




	    # Public Methoden >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>

		    # Herstellen/Trennen einer Datenbankverbindung >>>>>>>>>>>>>>>>>>>>>>>>

		    /**********************************************************************
		     * Connect ()
		     *
		     * Stellt eine neue Datenbankverbindung mit den PDO-Standardparametern
		     * her. Diese Methode sollte nur verwendet werden, wenn detailliertes
		     * PDO-Wissen existiert.
		     * Ansonsten sollten die jeweiligen Hilfsmethoden ConnectXYZ() verwen-
		     * det werden.
		     *
		     * @param Dsn           String mit dem PDO-DSN (Data Source Name), der 
		     *                      die Informationen über die zu verbindende Daten-
		     *                      bank enthält
		     * @param User          Optionaler String mit Benutzernamen des PDO-DSN
		     * @param Password      Optionaler String mit dem Benutzerpasswort des 
		     *                      PDO-DSN
		     * @param DriverOptions Optionales Key-Value-Array mit Treiber-spezifi-
		     *                      schen Verbindungsoptionen
		     *
		     * @return TRUE, falls die Verbindung hergestellt werden konnte, anson-
		     *         sten FALSE
		     *********************************************************************/
		    public function Connect ($Dsn, $User = NULL, $Password = NULL, 
				    $DriverOptions = NULL)
		    {
			    $this->_LastErrorMessage = '';

			    try 
			    {
				    // Alle Parameter
				    if ($DriverOptions !== NULL)
				    {
					    $this->_DatabaseObject = new PDO ($Dsn, $User, $Password, 
						    $DriverOptions);
				    }
				    // Nur User und Password
				    else if ($Password !== NULL)
				    {
					    $this->_DatabaseObject = new PDO ($Dsn, $User, $Password);
				    }
				    // Nur User
				    else if ($User !== NULL)
				    {
					    $this->_DatabaseObject = new PDO ($Dsn, $User);
				    }
				    // Nur DSN
				    else
				    {
					    $this->_DatabaseObject = new \PDO ($Dsn);
				    }
			    }
			    catch (PDOException $e)
			    {
				    $this->_LastErrorMessage = $e->getMessage();
				    return FALSE;
			    }

			    return TRUE;
		    }


		    /**********************************************************************
		     * ConnectSqlite3 ()
		     *
		     * Hilfsfunktion zum Herstellen einer SQLite3-Datenbankverbindung.
		     * Der dementsprechende Treiber muss auf dem Zielsystem vorhanden sein!
		     *
		     * @param Filename Dateiname der zu öffnenden Datenbankdatei
		     *
		     * @return TRUE, wenn die Verbindung hergestellt werden konnte, anson-
		     *         sten FALSE
		     *********************************************************************/
		    public function ConnectSqlite3 ($Filename, $Timeout = -1)
		    {
			    $bResult = $this->Connect("sqlite:$Filename");

			    if ($bResult === TRUE && $Timeout > -1)
			    {
				    $this->_DatabaseObject->setAttribute(\PDO::ATTR_TIMEOUT, $Timeout);
			    }

			    return $bResult;
		    }


		    /**********************************************************************
		     * ConnectSqlite2 ()
		     *
		     * Hilfsfunktion zum Herstellen einer älteren SQLite2-Datenbankverbindung.
		     * Der dementsprechende Treiber muss auf dem Zielsystem vorhanden sein!
		     *
		     * @param Filename Dateiname der zu öffnenden Datenbankdatei
		     *
		     * @return TRUE, wenn die Verbindung hergestellt werden konnte, anson-
		     *         sten FALSE
		     *********************************************************************/
		    public function ConnectSqlite2 ($Filename, $Timeout = -1)
		    {
			    $bResult = $this->Connect("sqlite2:$Filename");

			    if ($bResult === TRUE && $Timeout > -1)
			    {
				    $this->_DatabaseObject->setAttribute(\PDO::ATTR_TIMEOUT, $Timeout);
			    }

			    return $bResult;
		    }


		    /**********************************************************************
		     * ConnectMysql ()
		     *
		     * Hilfsfunktion zum Herstellen einer MySql-Datenbankverbindung.
		     * Der dementsprechende Treiber muss auf dem Zielsystem vorhanden sein!
		     *
		     * @param Host     Zu verbindender Hostname/IP-Adresse. Soll ein spe-
		     *                 zieller Port verwendet werden, kann dieser durch ":"
		     *                 getrennt angegeben werden (z. B. "localhost:3030")
		     * @param User     Benutzername für die Databankverbindung
		     * @param Password Benutzerpassword für die Datenbankverbindung
		     * $param Database Name der Datenbank, zu der die Verbindung hergestellt
		     *                 werden soll
		     *
		     * @return TRUE, wenn die Verbindung hergestellt werden konnte, anson-
		     *         sten FALSE
		     *********************************************************************/
		    public function ConnectMysql ($Host, $User, $Password, $Database, 
			    $Timeout = -1)
		    {
			    $arrOptions = NULL;

			    // Wurde im Host-Parameter ein Port mitgegeben?
			    $arrHostPort = $this->splitHostPort($Host);
			    $strHost     = $arrHostPort['host'];
			    $strPort     = $arrHostPort['port'];

			    $strDsn = "mysql:host=$strHost;dbname=$Database";

			    if ($strPort != '')
			    {
				    $strDsn .= ";port=$strPort";
			    }

			    // Der UTF-8-Zeichensatz wird je nach PHP-Version unterschiedlich
			    // gesetzt.
			    // PHP-Version < 5.3.6
			    if (version_compare(PHP_VERSION, '5.3.6') < 0)
			    {
				    $arrOptions = array (
					    \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8');
			    }
			    // PHP-Versio >= 5.3.6
			    else
			    {
				    $strDsn .= ";charset=utf8";
			    }

			    $bResult =  $this->Connect($strDsn, $User, $Password, $arrOptions);

			    if ($bResult === TRUE && $Timeout > -1)
			    {
				    $this->_DatabaseObject->setAttribute(\PDO::ATTR_TIMEOUT, $Timeout);
			    }

			    return $bResult;
		    }


		    /**********************************************************************
		     * ConnectPostgresql ()
		     *
		     * Hilfsfunktion zum Herstellen einer PostgreSQL-Datenbankverbindung.
		     * Der dementsprechende Treiber muss auf dem Zielsystem vorhanden sein!
		     *
		     * @param Host     Zu verbindender Hostname/IP-Adresse. Soll ein spe-
		     *                 zieller Port verwendet werden, kann dieser durch ":"
		     *                 getrennt angegeben werden (z. B. "localhost:3030")
		     * @param User     Benutzername für die Databankverbindung
		     * @param Password Benutzerpassword für die Datenbankverbindung
		     * $param Database Name der Datenbank, zu der die Verbindung hergestellt
		     *                 werden soll
		     *
		     * @return TRUE, wenn die Verbindung hergestellt werden konnte, anson-
		     *         sten FALSE
		     *********************************************************************/
		    public function ConnectPostgresql ($Host, $User, $Password, $Database,
			    $Timeout = -1)
		    {
			    $arrOptions = NULL;

			    // Wurde im Host-Parameter ein Port mitgegeben?
			    $arrHostPort = $this->splitHostPort($Host);
			    $strHost     = $arrHostPort['host'];
			    $strPort     = $arrHostPort['port'];

			    $strDsn = "pgsql:host=$strHost;dbname=$Database";

			    if ($strPort != '')
			    {
				    $strDsn .= ";port=$strPort";
			    }

			    $bResult =  $this->Connect($strDsn, $User, $Password, $arrOptions);

			    if ($bResult === TRUE && $Timeout > -1)
			    {
				    $this->_DatabaseObject->setAttribute(\PDO::ATTR_TIMEOUT, $Timeout);
			    }

			    return $bResult;
		    }


		    /**********************************************************************
		     * Disconnect ()
		     * 
		     * Schließt die aktuelle Datenbankverbindung, falls eine besteht
		     *********************************************************************/
		    public function Disconnect ()
		    {
			    $this->_DatabaseObject = NULL;
		    }

		    # Herstellen/Trennen einer Datenbankverbindung <<<<<<<<<<<<<<<<<<<<<<<<


		    # Verbindungsinformationen >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>

		    /**********************************************************************
		     * GetDBType ()
		     *
		     * Liefert den Namen des aktuell verwendeten Datenbank-Treibers (z. B.
		     * "mysql"), sofern aktuell eine Verbindung besteht
		     *
		     * @return String mit dem Namen des Datenbanktreibers oder FALSE im
		     *         Fehlerfall
		     *********************************************************************/
		    public function GetDBType ()
		    {
			    $this->_LastErrorMessage = '';

			    if (!$this->IsConnected())
			    {
				    $this->_LastErrorMessage = 'no database connected';
				    return FALSE;
			    }

			    try
			    {
				    $dbType =  
					    $this->_DatabaseObject->getAttribute(\PDO::ATTR_DRIVER_NAME);
			    }
			    catch (PDOException $e)
			    {
				    $this->_LastErrorMessage = $e->getMessage();
				    return FALSE;
			    }

			    if ($dbType === NULL)
			    {
				    $this->_LastErrorMessage = 
					    'pdo attribute PDO::ATTR_DRIVER_NAME not available';
				    return FALSE;
			    }

			    return $dbType;
		    }


		    /**********************************************************************
		     * GetLastError ()
		     * 
		     * Liefert den zuletzt aufgetretenen Fehler
		     *
		     * @return String mit der Fehlermeldung des zuletzt aufgetretenen 
		     *         Fehlers
		     *********************************************************************/
		    public function GetLastError ()
		    {
			    return $this->_LastErrorMessage;
		    }


		    /**********************************************************************
		     * IsConnected ()
		     *
		     * Gibt Auskunft darüber, ob zur Zeit eine Datenbankverbindung besteht
		     * oder nicht
		     *
		     * @return TRUE, wenn eine Datenbankverbindung besteht, ansonsten FALSE
		     *********************************************************************/
		    public function IsConnected ()
		    {
			    return ($this->_DatabaseObject !== NULL);
		    }


		    /**********************************************************************
		     * GetAvailableDrivers ()
		     * 
		     * Gibt Auskunft darüber, welche PDO-Treiber vom aktuellen System 
		     * unterstützt werden.
		     * 
		     * @return Array mit den Treiber-Namen der auf dem System vorhandenen
		     *         PDO-Treiber oder FALSE, falls keine Treiber-Namen ermittelt
		     *         werden konnte 
		     *********************************************************************/
		    public function GetAvailableDrivers ()
		    {
			    try
			    {
				    $arrDrivers = pdo_drivers();
			    }
			    catch (PDOException $e)
			    {
				    $this->_LastErrorMessage = $e->getMessage();
				    return FALSE;
			    }

			    if (count($arrDrivers) == 0)
			    {
				    $this->_LastErrorMessage = 'no drivers found';
				    return FALSE;
			    }

			    return $arrDrivers;
		    }

		    # Verbindungsinformationen <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<


		    # Transaktionssteuerung >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>

		    /**********************************************************************
		     * TransactionStart ()
		     *
		     * Startet eine Transaktion, sofern eine Datenbankverbindung besteht.
		     * Sofern der aktuelle Datenbanktreiber verschachtelte Transaktionen
		     * unterstützt, können solche verschachtelten Transaktionen gestartet
		     * werden.
		     *
		     * @return TRUE, wenn erfolgreich eine Transaktion gestartet wurde, 
		     *         FALSE, wenn beim Starten der Transaktion ein Fehler auftrat
		     *         oder keine Datenbankverbindung besteht
		     *********************************************************************/
		    public function TransactionStart ()
		    {
			    $this->_LastErrorMessage = '';

			    if (!$this->IsConnected())
			    {
				    $this->_LastErrorMessage = 'no database connected';
				    return FALSE;
			    }

			    try
			    {
				    // Der aktuelle Datenbanktreiber unterstützt keine verschachtelte 
				    // Transaktionen oder es handelt sich um die erste zu startende 
				    // Transaktion...
				    if (!$this->isNestableTransaction() || $this->_TransactionLevel === 0)
				    {
					    // Es läuft bereits eine Transaktion bei nicht verschachtelbaren
					    // Transaktionen
					    if ($this->_TransactionLevel > 0)
					    {
						    $this->_LastErrorMessage = 'transaction already active';
						    return FALSE;
					    }

					    if ($this->_DatabaseObject->beginTransaction() === FALSE)
					    {
						    $arrTemp = $this->_DatabaseObject->errorInfo();
						    $this->_LastErrorMessage = $arrTemp[2];
						    return FALSE;
					    }
				    }
				    // ... es handelt sich um eine verschachtelte Transaktion, die auch
				    // vom Treiber unterstützt wird.
				    else 
				    {
					    $this->ExecuteSql('SAVEPOINT LEVEL{$this->_TransactionLevel}');
				    }

				    $this->_TransactionLevel++;

			    }
			    catch (PDOException $e)
			    {
				    $this->_LastErrorMessage = $e->getMessage();
				    return FALSE;
			    }

			    return TRUE;
		    }


		    /**********************************************************************
		     * GetTransactionLevel ()
		     * 
		     * Gibt den Level der laufenden Transaktionen zurück.
		     * 
		     * @return 0 wenn keine Transaktion läuft, 
		     *         >0 wenn mindestens eine oder mehrere verschachtelte 
		     *         Transaktionen laufen
		     *********************************************************************/
		    public function GetTransactionLevel ()
		    {
			    return $this->_TransactionLevel;
		    }


		    /**********************************************************************
		    * TransactionRollback ()
		    *
		    * Führt einen Rollback auf der aktuellen/letzten laufenden Transaktion
		    * aus, sofern eine Datenbankverbindung besteht und mindestens eine
		    * Transaktion gestartet wurde.
		    * Sofern der aktuelle Datenbanktreiber verschachtelte Transaktionen
		    * unterstützt und diese verwendet wurden, kann hiermit die jeweils
		    * zuletzt gestartete Transaktion zurückgesetzt werden.
		    *
		    * @return TRUE, wenn erfolgreich ein Rollback durchgeführt wurde, 
		    *         FALSE, wenn beim Durchführen des Rollbacks ein Fehler 
		    *         auftrat, keine Transaktion läuft oder keine Datenbankver-
		    *	        bindung besteht
		    **********************************************************************/
		    public function TransactionRollback ()
		    {
			    $this->_LastErrorMessage = '';

			    if (!$this->IsConnected())
			    {
				    $this->_LastErrorMessage = 'no database connected';
				    return FALSE;
			    }

			    if ($this->_TransactionLevel == 0)
			    {
				    $this->_LastErrorMessage = 'no active transaction';
				    return FALSE;
			    }

			    try
			    {
				    // Der aktuelle Datenbanktreiber unterstützt keine verschachtelte 
				    // Transaktionen oder es handelt sich um die erste zu startende 
				    // Transaktion...
				    if (!$this->isNestableTransaction() || $this->_TransactionLevel == 1)
				    {
					    if ($this->_DatabaseObject->rollBack() === FALSE)
					    {
						    $arrTemp = $this->_DatabaseObject->errorInfo();
						    $this->_LastErrorMessage = $arrTemp[2];
						    return FALSE;
					    }
				    }
				    else
				    {
					    $this->ExecuteSql('ROLLBACK TO SAVEPOINT LEVEL{$this->(_TransactionLevel-1)}');
				    }

				    $this->_TransactionLevel--;
			    }
			    catch (PDOException $e)
			    {
				    $this->_LastErrorMessage = $e->getMessage();
				    return FALSE;
			    }

			    return TRUE;
		    }


		    /**********************************************************************
		     * TransactionCommit ()
		     *
		     * Führt einen Commit auf der aktuellen/letzten laufenden Transaktion
		     * aus, sofern eine Datenbankverbindung besteht und mindestens eine
		     * Transaktion gestartet wurde.
		     * Sofern der aktuelle Datenbanktreiber verschachtelte Transaktionen
		     * unterstützt und diese verwendet wurden, kann hiermit die jeweils
		     * zuletzt gestartete Transaktion committet werden.
		     *
		     * @return TRUE, wenn erfolgreich ein Commit durchgeführt wurde, 
		     *         FALSE, wenn beim Durchführen des Commits ein Fehler 
		     *         auftrat, keine Transaktion läuft oder keine Datenbankver-
		     *	        bindung besteht
		     *********************************************************************/
		    public function TransactionCommit ()
		    {
			    $this->_LastErrorMessage = '';

			    if (!$this->IsConnected())
			    {
				    $this->_LastErrorMessage = 'no database connected';
				    return FALSE;
			    }

			    if ($this->_TransactionLevel == 0)
			    {
				    $this->_LastErrorMessage = 'no active transaction';
				    return FALSE;
			    }

			    try
			    {
				    // Der aktuelle Datenbanktreiber unterstützt keine verschachtelte 
				    // Transaktionen oder es handelt sich um die erste zu startende 
				    // Transaktion...
				    if (!$this->isNestableTransaction() || $this->_TransactionLevel == 1)
				    {
					    if ($this->_DatabaseObject->commit() === FALSE)
					    {
						    $arrTemp = $this->_DatabaseObject->errorInfo();
						    $this->_LastErrorMessage = $arrTemp[2];
						    return FALSE;
					    }
				    }
				    else
				    {
					    $this->ExecuteSql('RELEASE SAVEPOINT LEVEL{$this->(_TransactionLevel-1)}');
				    }

				    $this->_TransactionLevel--;
			    }
			    catch (PDOException $e)
			    {
				    $this->_LastErrorMessage = $e->getMessage();
				    return FALSE;
			    }

			    return TRUE;
		    }

		    # Transaktionssteuerung <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<


		    # SQL-Query-Ausführung >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>

		    /**********************************************************************
		     * SqlPrepareStatement ()
		     * 
		     * Bereitet ein Statement (im Normalfall) mit Platzhaltern ("?") vor, 
		     * damit dieses zu späteren Zeitpunkten wiederholt mit z. B. unterschied-
		     * lichen Parametern ausgeführt werden kann.
		     * (z. B. "SELECT * FROM table WHERE id > ?")
		     * 
		     * @param $SqlQuery Vorzubereitendes SQL-Statement für die spätere
		     *                  Ausführung
		     * @param $FetchMethod Optionaler Abfragetyp für alle auf diesem Statement
		     *                     basierenden Abfragen
		     *                     (Standard ist PDO::FETCH_BOTH; ansonsten 
		     *                     Konstante der Sammlung PDO::FECH_xxx)
		     * 
		     * @return TRUE, wenn das Statement erfolgreich vorbereitet werden
		     *         konnte, ansonsten FALSE
		     *********************************************************************/
		    public function SqlPrepareStatement ($SqlQuery, $FetchMethod = PDO::FETCH_BOTH)
		    {
			    $this->_LastErrorMessage = '';

			    try
			    {
				    if ($this->IsConnected())
				    {
					    $Result = ($this->_PDOStatementPrepared =
					      $this->_DatabaseObject->prepare($SqlQuery)) !== FALSE;

					    if ($Result === FALSE)
					    {
						    $this->_PDOStatementPrepared = NULL;
					    }
					    else
					    {
					      // Setzen des übergebenen FETCH_MODE für alle auf diesem 
					    // Statement basierenden Abfragefunktionen
	  $this->_PDOStatementPrepared->setFetchMode($FetchMethod);
					    }

					    return $Result;
				    }
				    else
				    {
					    $this->_LastErrorMessage = 'no database connected';
				    }                
			    } 
			    catch (PDOException $e) 
			    {
				    $this->_LastErrorMessage = $e->getMessage();
			    }

			    return FALSE;
		    }

		    /*************************************************************************
		     * SqlPrepareBindParam()
		     * 
		     * Bindet eine Variable per Referenz an einen in der mittels 
		     * SqlPrepareStatement() vorbereiteten Abfrage verwendeten 
		     * Platzhalter (z. B. ":name").
		     * Der Inhalt der Variable wird hierbei erst ausgewertet, wenn Daten
		     * angefordert werden.
		     * 
		     * @param $Parameter In der mittels SqlPrepareStatement() vorbereiteten
		     *                   Abfrage verwendeter Platzhalter (z. B. ":name") oder
		     *                   der (1-basierte!) Index falls Platzhalter ("?") ver-
		     *                   wendet wurden.
		     * @param $Variable  Variable, mit der der verwendete Platzhalter ver-
		     *                   bunden werden soll.
		     * @param $DataType  Optionaler Datentyp des Parameters als 
		     *                   PDO::PARAM_* Konstante (Default: PDO::PARAM_STR).
		     *                   Bestimmt, wie innerhalb der Abfrage mit der überge-
		     *                   benen Variable umgegangen wird
		     * @return TRUE, wenn die Variable erfolgreich gebunden werden konnte,
		     *         ansonsten FALSE
		     ************************************************************************/
		    public function SqlBindPreparedParam ($Parameter, &$Variable, 
			    $DataType = PDO::PARAM_STR)
		    {
			    $bResult = FALSE;

			    try
			    {
				    $bResult = $this->_PDOStatementPrepared->bindParam(
				    $Parameter, $Variable, $DataType);
			    }
			    catch (PDOException $e) 
			    {
				    $this->_LastErrorMessage = $e->getMessage();
			    }

			    return $bResult;
		    }

		    /*************************************************************************
		     * SqlPrepareBindValue()
		     * 
		     * Bindet den aktuellen Wert einer Variable an einen in der mittels 
		     * SqlPrepareStatement() vorbereiteten Abfrage verwendeten 
		     * Platzhalter (z. B. ":name").
		     * Der Inhalt der Variable wird hierbei bereits ausgewertet, wenn diese
		     * Methode ausgeführt wird.
		     * 
		     * @param $Parameter In der mittels SqlPrepareStatement() vorbereiteten
		     *                   Abfrage verwendeter Platzhalter (z. B. ":name") oder
		     *                   der (1-basierte!) Index falls Platzhalter ("?") ver-
		     *                   wendet wurden.
		     * @param $Value     Variable, deren Wert in die vorbereitete Abfrage
		     *                   übernommen werden soll.
		     * @param $DataType  Optionaler Datentyp des Parameters als 
		     *                   PDO::PARAM_* Konstante (Default: PDO::PARAM_STR).
		     *                   Bestimmt, wie innerhalb der Abfrage mit der überge-
		     *                   benen Variable umgegangen wird
		     * @return TRUE, wenn der Wert der Variable erfolgreich gebunden werden 
		     *         konnte, ansonsten FALSE
		     ************************************************************************/
		    public function SqlBindPreparedValue ($Parameter, $Value, 
			    $DataType = PDO::PARAM_STR)
		    { 
			    $bResult = FALSE;

			    try
			    {
				    $bResult = $this->_PDOStatementPrepared->bindParam(
				    $Parameter, $Value, $DataType);
			    }
			    catch (PDOException $e) 
			    {
				    $this->_LastErrorMessage = $e->getMessage();
			    }

			    return $bResult;
		    }

		    /**********************************************************************
		     * SqlExecute ()
		     * 
		     * Führt eine Datenbankabfrage auf der verbundenen Datenbank aus, die
		     * keine Rückgabe erwartet (z. B. "UPDATE #__counter SET count = count + 1").
		     * Wurde im Konstruktor $PrintSql auf TRUE gesetzt, wird die auszu-
		     * führende Abfrage mit dem CSS-class-Stil 'PrintSql' ausgegeben.
		     * 
		     * @param SqlQuery SQL-Abfrage mit optional verwendetem Präfix-Platz
		     *                 halter
		     * 
		     * @return Anzahl der veränderten Zeilen oder FALSE im Fehlerfall
		     *********************************************************************/
		    public function SqlExecute ($SqlQuery)
		    {
			    $this->_LastErrorMessage = '';
			    $iAffectedRows = FALSE;
			    $this->startExecutionTimer();

			    try 
			    {
				    if ($this->IsConnected())
				    {
					    if ($this->_PrintSql === TRUE)
					    {
						    echo "<div class='PrintSql'>$SqlQuery</div>";
					    }

					    if (($iAffectedRows = $this->_DatabaseObject->exec(
						    $this->replacePrefix($SqlQuery))) === FALSE)
					    {
						    $arrTemp = $this->_DatabaseObject->errorInfo();
						    $this->_LastErrorMessage = $arrTemp[2];
					    }
				    }
			    }
			    catch (PDOException $e)
			    {
				    $this->_LastErrorMessage = $e->getMessage();
				    return FALSE;
			    }

			    $this->stopExecutionTimer();
			    return $iAffectedRows;
		    }


		    /**********************************************************************
		     * SqlGetLastInsertId ()
		     * 
		     * Gibt die ID des zuletzt eingefügten Datensatzes zurück.
		     * Hinweis: Der verwendete Datenbanktreiber muss die Funktionalität
		     *          unterstützen
		     * 
		     * @param $Column Name der die ID beinhaltenden Spalte (optional;
		     *                Standard ist ''. Wird nur für bestimmte Treiber
		     *                benätigt (z. B. ältere Postgres-Treiber)       
		     * @return ID, des zuletzt eingefügten Datensatzes oder FALSE im 
		     *         Fehlerfall
		     *********************************************************************/
		    public function SqlGetLastInsertId ($Column = '')
		    {
			    $this->_LastErrorMessage = '';

			    if (!$this->IsConnected())
			    {
				    $this->_LastErrorMessage = 'no database connected';
				    return FALSE;
			    }

			    try
			    {
				    return $Column != '' ?
					    $this->_DatabaseObject->lastInsertId($Column) :
					    $this->_DatabaseObject->lastInsertId();
			    }
			    catch (PDOException $e)
			    {
				    $this->_LastErrorMessage = $e->getMessage();
				    return FALSE;
			    }
		    }


		    /**********************************************************************
		     * SqlGetLines ()
		     * 
		     * Führt ein übergebenes SQL-Statement auf der Datenbank aus und gibt
		     * das Ergebnis als Array zurück, auf dessen Spalten per Index 
		     * ("$result[0][2]") oder Spaltentitel ("$result[0]['name']") 
		     * zugegriffen werden kann
		     * 
		     * @param $SqlQuery Auszuführendes SQL-Statement mit optional verwendetem 
		     *                  Präfix-Platzhalter
		     * @param $FetchMethod Optionaler Abfragetyp für das aktuelle Statement
		     *                     (Standard ist PDO::FETCH_BOTH; ansonsten 
		     *                     Konstante der Sammlung PDO::FECH_xxx)
		     * 
		     * @return Ergebnis der Abfrage als Array, NULL falls kein Ergebnis
		     *         gefunden wurde oder FALSE im Fehlerfall
		     *********************************************************************/
		    public function SqlGetLines ($SqlQuery, $FetchMethod = \PDO::FETCH_BOTH)
		    {
			    $resultReturn = $this->sqlGetPDOObject($SqlQuery, $FetchMethod);

			    if ($resultReturn !== FALSE)
			    {
				    // Umwandlung des PDOStatements in das zurückzugebende Array
				    $arrRet = array();

				    foreach ($resultReturn as $Entry)
				    {
					    $arrRet[] = $Entry;
				    }

				    return count($arrRet) == 0 ? NULL : $arrRet;
			    }

			    return FALSE;
		    }


		    /**********************************************************************
		     * SqlGetLinesAsObject ()
		     * 
		     * Führt ein übergebenes SQL-Statement auf der Datenbank aus und gibt
		     * das Ergebnis als Array aus Objekten zurück, das die Spalten als
		     * zugreifbare Klassenattribute darstellt. Bsp.:
		     * "$result[0]->name" oder "$result[0]->wohnort" 
		     * 
		     * @param $SqlQuery Auszuführendes SQL-Statement mit optional verwendetem 
		     *                  Präfix-Platzhalter
		     * @param $FetchMethod Optionaler Abfragetyp für das aktuelle Statement
		     *                     (Standard ist PDO::FETCH_BOTH; ansonsten 
		     *                     Konstante der Sammlung PDO::FECH_xxx)
		     * 
		     * @return Ergebnis der Abfrage als Array von Objekten, die die Ergeb-
		     *         nisspalten als zugreifbare Klassenattribute darstellen oder 
		     *         FALSE im Fehlerfall
		     *********************************************************************/
		    public function SqlGetLinesAsObject ($SqlQuery, $FetchMethod = \PDO::FETCH_BOTH)
		    {
			    $arrResult = $this->SqlGetLines($SqlQuery, $FetchMethod);

			    if ($arrResult !== FALSE && $arrResult !== NULL)
			    {
				    // Umwandlung des zurückbekommenen Arrays in Rückgabe-Objekte
				    $arrRet = array();

				    if (count($arrResult) > 0)
				    {
					    // Zurückgelieferte Spalten (Keys) der ersten Zeile merken
					    $arrKeys = array_keys($arrResult[0]);

					    foreach ($arrResult as $Entry)
					    {
						    $tmpClass = new ResultObject ();
						    foreach ($arrKeys as $Key)
						    {
							    $tmpClass->$Key = $Entry[$Key];
						    }

						    $arrRet[] = $tmpClass;
					    }
				    }

				    return $arrRet;
			    }

			    return $arrResult;
		    }


		    /**********************************************************************
		     * SqlGetPreparedLines ()
		     * 
		     * Führt ein zuvor über SqlPrepareStatement() vorbereitetes SQL-Satement
		     * mit den übergebenen Parametern aus und liefert das Ergebnis als Array
		     * aus Objekten zurück, auf dessen Spalten per Index 
		     * ("$result[0][2]") oder Spaltentitel ("$result[0]['name']") 
		     * zugegriffen werden kann (je nach FETCH_MODE in SqlPrepare())
		     * 
		     * @param ... Beliebige Anzahl an Parametern, die die jeweiligen
		     *            "?"-Platzhalter der mittels SqlPrepareStatement() 
		     *            vorbereiteten Abfrage ersetzen oder ein Array,
		     *            welches alle einzusetzenden Werte in der richtigen Reihen-
		     *            folge beinhaltet.
		     * 
		     * @return Ergebnis der Abfrage als Array, NULL falls kein Ergebnis
		     *         gefunden wurde oder FALSE im Fehlerfall
		     *********************************************************************/
		    public function SqlGetPreparedLines (/* ... */)
		    {
			    $this->startExecutionTimer();

			    if ($this->_PDOStatementPrepared != NULL)
			    {
				    // übergebene Parameter in ein Array umwandeln
				    $arrParamsPlain = func_get_args();
				    $arrParams = array();

				    // Als nächstes werden die übergebenen Parameter in das Ausgabe-
				    // Array gespeichert. Hierbei werden auch evtl. als Array übergebene
				    // Parameter in ihre Bestandteile zerlegt (nur eine Ebene tief).
				    for ($i = 0; $i < count($arrParamsPlain); $i++)
				    {
				      if (!is_array($arrParamsPlain[$i]))
				      {
					$arrParams[] = $arrParamsPlain[$i];
				      }
				      else
				      {
					for ($j = 0; $j < count($arrParamsPlain[$i]); $j++)
					{
					  $arrParams[] = $arrParamsPlain[$i][$j];
					}
				      }
				    }

				    // Ist das Parameter-Array leer, muss der execute()-Methode NULL
				    // übergeben werden
				    if (count($arrParams) == 0)
				    {
					    $arrParams = NULL;
				    }

				    if ($this->_PDOStatementPrepared->execute($arrParams))
				    {	
					    if (($returnMixed = 
						    $this->_PDOStatementPrepared->fetchAll()) !== FALSE)
					    {
						    $this->stopExecutionTimer();
						    return count($returnMixed) == 0 ? NULL : $returnMixed;
					    }

					    $arrTemp = $this->_PDOStatementPrepared->errorInfo();

					    // fetch() liefert bei einem leeren Ergebnis FALSE zurück,
					    // obwohl kein Fehler aufgetreten ist. Dies muss hier
					    // berücksichtigt werden.
					    if ($arrTemp[0] == "00000")
					    {
						    $this->stopExecutionTimer();
						    return NULL;
					    }

					    $this->_LastErrorMessage = $arrTemp[2];
				    }
				    else
				    {
					    $arrTemp = $this->_PDOStatementPrepared->errorInfo();
					    $this->_LastErrorMessage = $arrTemp[2];
				    }
			    }

			    $this->stopExecutionTimer();
			    return FALSE;
		    }


		    /**********************************************************************
		     * SqlGetPreparedLinesAsObject ()
		     * 
		     * Führt ein zuvor über SqlPrepareStatement() vorbereitetes SQL-Satement
		     * mit den übergebenen Parametern aus und liefert das Ergebnis als 
		     * Array aus Objekten zurück, das die Spalten als
		     * zugreifbare Klassenattribute darstellt. Bsp.:
		     * "$result[0]->name" oder "$result[0]->wohnort" (je nach FETCH_MODE in
		     * SqlPrepareStatement())
		     * 
		     * @param ... Beliebige Anzahl an Parametern, die die jeweiligen
		     *            "?"-Platzhalter der mittels SqlPrepareStatement() 
		     *           vorbereiteten Abfrage ersetzen oder ein Array,
		     *            welches alle einzusetzenden Werte in der richtigen Reihen-
		     *            folge beinhaltet.
		     * 
		     * @return Ergebnis der Abfrage als Array von Objekten, die die Ergeb-
		     *         nisspalten als zugreifbare Klassenattribute darstellen,
		     *         NULL falls kein Ergebnis gefunden wurde oder FALSE im Fehlerfall
		     *********************************************************************/
		    public function SqlGetPreparedLinesAsObject (/* ... */)
		    {
			    $arrResult = call_user_func_array(
				    array($this, "SqlGetPreparedLines"), func_get_args());

			    if ($arrResult !== FALSE && $arrResult !== NULL)
			    {
				    // Umwandlung des zurückbekommenen Arrays in Rückgabe-Objekte
				    $arrRet = array();

				    if (count($arrResult) > 0)
				    {
					    // Zurückgelieferte Spalten (Keys) der ersten Zeile merken
					    $arrKeys = array_keys($arrResult[0]);

					    foreach ($arrResult as $Entry)
					    {
						    $tmpClass = new ResultObject ();
						    foreach ($arrKeys as $Key)
						    {
							    $tmpClass->$Key = $Entry[$Key];
						    }

						    $arrRet[] = $tmpClass;
					    }
				    }

				    return $arrRet;
			    }

			    return $arrResult;
		    }


		    /**********************************************************************
		     * SqlGetFirstLine ()
		     * 
		     * Liefert die erste Zeile des Abfrageergebnisses der übergebenen SQL-
		     * Abfrage.
		     * Hinweis: Muss immer aufgerufen werden, bevor SqlGetNextLine[AsObject]() 
		     *          aufgerufen wird!
		     * 
		     * @param $SqlQuery Auszuführendes SQL-Statement mit optional verwendetem 
		     *                  Präfix-Platzhalter
		     * 
		     * @return Erste Zeile des Abfrageergebnisses als Array auf das
		     *         per Index-Nummer oder assoziativ zugegriffen werden kann,
		     *         NULL, falls kein Ergebnis vorhanden ist oder FALSE im 
		     *         Fehlerfall
		     *********************************************************************/
		    public function SqlGetFirstLine ($SqlQuery)
		    {
			    if ($this->_PDOStatement != NULL)
			    {
				    $this->_PDOStatement->closeCursor();
			    }

			    if (($this->_PDOStatement = $this->sqlGetPDOObject($SqlQuery)) !== FALSE)
			    {
				    if (($returnMixed = $this->_PDOStatement->fetch(\PDO::FETCH_ASSOC, 
					    \PDO::FETCH_ORI_FIRST)) !== FALSE)
				    {
					    return $returnMixed;
				    }

				    $arrTemp = $this->_PDOStatement->errorInfo();

				    // fetch() liefert bei einem leeren Ergebnis FALSE zurück,
				    // obwohl kein Fehler aufgetreten ist. Dies muss hier
				    // berücksichtigt werden.
				    if ($arrTemp[0] == "00000")
				    {
					    return NULL;
				    }

				    $this->_LastErrorMessage = $arrTemp[2];
			    }

			    return FALSE;
		    }


		    /**********************************************************************
		     * SqlGetFirstPreparedLine ()
		     * 
		     * Liefert die erste Zeile der per SqlPrepareStatement() vorbereiteten
		     * SQL-Abfrage.
		     * Hinweis: Muss immer aufgerufen werden, bevor 
		     *          SqlGetNextPreparedLine[AsObject]() aufgerufen wird!
		     * 
		     * @param ... Beliebige Anzahl an Parametern, die die jeweiligen
		     *            "?"-Platzhalter der mittels SqlPrepareStatement() 
		     *           vorbereiteten Abfrage ersetzen
		     * 
		     * @return Erste Zeile des Abfrageergebnisses als Array auf das
		     *         per Index-Nummer oder assoziativ zugegriffen werden kann,
		     *         NULL, falls kein Ergebnis vorhanden ist oder FALSE im 
		     *         Fehlerfall
		     *********************************************************************/
		    public function SqlGetFirstPreparedLine (/* ... */)
		    {
			    if ($this->_PDOStatementPrepared != NULL)
			    {
				    // übergebene Parameter in ein Array umwandeln
				    $arrParams = func_get_args();

				    if ($this->_PDOStatementPrepared->execute($arrParams))
				    {
					    if (($returnMixed = 
						    $this->_PDOStatementPrepared->fetch(PDO::FETCH_BOTH,
								      PDO::FETCH_ORI_FIRST)) !== FALSE)
					    {
						    return count($returnMixed) == 0 ? NULL : $returnMixed;
					    }

					    $arrTemp = $this->_PDOStatementPrepared->errorInfo();

					    // fetch() liefert bei einem leeren Ergebnis FALSE zurück,
					    // obwohl kein Fehler aufgetreten ist. Dies muss hier
					    // berücksichtigt werden.
					    if ($arrTemp[0] == "00000")
					    {
						    return NULL;
					    }

					    $this->_LastErrorMessage = $arrTemp[2];
				    }
				    else
				    {
					    $arrTemp = $this->_PDOStatementPrepared->errorInfo();
					    $this->_LastErrorMessage = $arrTemp[2];
				    }
			    }

			    return FALSE;
		    }


		    /**********************************************************************
		     * SqlGetFirstPreparedLineAsObject ()
		     * 
		     * Liefert die erste Zeile der per SqlPrepareStatement() vorbereiteten
		     * SQL-Abfrage.
		     * Hinweis: Muss immer aufgerufen werden, bevor SqlGetNextLine[AsObject]() 
		     *          aufgerufen wird!
		     * 
		     * @param ... Beliebige Anzahl an Parametern, die die jeweiligen
		     *            "?"-Platzhalter der mittels SqlPrepareStatement() 
		     *            vorbereiteten Abfrage ersetzen
		     * 
		     * @return Erste Zeile des Abfrageergebnisses als Objekt, das die 
		     *         Ergebnisspalten als zugreifbare Klassenattribute darstellt,
		     *         NULL falls kein Ergebnis geliefert wurde oder FALSE im 
		     *         Fehlerfall
		     *********************************************************************/
		    public function SqlGetFirstPreparedLineAsObject (/* ... */)
		    {
			    $arrResult = call_user_func_array(
				    array($this, "SqlGetFirstPreparedLine"), func_get_args());

			    if ($arrResult !== FALSE)
			    {
				    if (count($arrResult) > 0)
				    {
					    // Zurückgelieferte Spalten (Keys) der ersten Zeile merken
					    $arrKeys = array_keys($arrResult);

					    $tmpClass = new ResultObject ();

					    foreach ($arrKeys as $Key)
					    {
						    $tmpClass->$Key = $arrResult[$Key];
					    }

					    return $tmpClass;
				    }
				    else
				    {
					    return NULL;
				    }
			    }

			    return FALSE;
		    }


		    /**********************************************************************
		     * SqlGetFirstLineAsObject ()
		     * 
		     * Liefert die erste Zeile des Abfrageergebnisses der übergebenen SQL-
		     * Abfrage.
		     * Hinweis: Muss immer aufgerufen werden, bevor SqlGetNextLine[AsObject]() 
		     *          aufgerufen wird!
		     * 
		     * @param $SqlQuery Auszuführendes SQL-Statement mit optional verwendetem 
		     *                  Präfix-Platzhalter
		     * 
		     * @return Erste Zeile des Abfrageergebnisses als Objekt, das die 
		     *         Ergebnisspalten als zugreifbare Klassenattribute darstellt,
		     *         NULL falls kein Ergebnis geliefert wurde oder FALSE im 
		     *         Fehlerfall
		     *********************************************************************/
		    public function SqlGetFirstLineAsObject ($SqlQuery)
		    {
			    $arrResult = $this->SqlGetFirstLine($SqlQuery);

			    if ($arrResult !== FALSE)
			    {
				    if (count($arrResult) > 0)
				    {
					    // Zurückgelieferte Spalten (Keys) der ersten Zeile merken
					    $arrKeys = array_keys($arrResult);

					    $tmpClass = new ResultObject ();

					    foreach ($arrKeys as $Key)
					    {
						    $tmpClass->$Key = $arrResult[$Key];
					    }

					    return $tmpClass;
				    }
				    else
				    {
					    return NULL;
				    }
			    }

			    return FALSE;
		    }


		    /**********************************************************************
		     * SqlGetNextLine ()
		     * 
		     * Liefert die nächste Zeile des Abfrageergebnisses der zuvor über-
		     * gebenen SQL-Abfrage.
		     * Hinweis: SqlGetFirstLine[AsObject]() muss vorher aufgerufen werden!
		     * 
		     * @return Folgezeile des Abfrageergebnisses als mixed-Objekt auf das
		     *         per Index-Nummer oder assoziativ zugegriffen werden kann,
		     *         NULL, falls kein Ergebnis mehr vorhanden ist oder FALSE im 
		     *         Fehlerfall
		     *********************************************************************/
		    public function SqlGetNextLine ()
		    {
			    if ($this->_PDOStatement == NULL)
			    {
				    $this->_LastErrorMessage = 'SqlGetFirstLine() not executed';
				    return FALSE;
			    }

			    try
			    {
				    if (($returnMixed = $this->_PDOStatement->fetch(PDO::FETCH_BOTH, 
						    PDO::FETCH_ORI_NEXT)) === FALSE)
				    {
					    // fetch() gibt sowohl im Fehlerfall als auch dann, wenn keine
					    // Daten mehr zum Zurückliefern vorhanden sind, ein FALSE zu-
					    // rück. Hier wird die Rückgabe unterschieden zwischen einem
					    // Fehler (FALSE) und keinen Daten mehr (NULL)
					    $arrTemp = $this->_PDOStatement->errorInfo();
					    if (($this->_LastErrorMessage = $arrTemp[2]) === NULL)
					    {
						    // Manuelles Setzen des "keine Daten mehr"-Rückgabewertes
						    // und zurücksetzen des DB-Cursors
						    $returnMixed = NULL;
						    $this->_PDOStatement->closeCursor();
						    $this->_PDOStatement = NULL;
					    }
				    }

				    return $returnMixed;
			    }
			    catch (PDOException $e)
			    {
				    $this->_LastErrorMessage = $e->getMessage();
				    return FALSE;
			    }
		    }


		    /**********************************************************************
		     * SqlGetNextPreparedLine ()
		     * 
		     * Liefert die nächste Zeile des Abfrageergebnisses der per
		     * SqlPrepareStatement() vorbereiteten SQL-Abfrage.
		     * Hinweis: SqlGetFirstPreparedLine[AsObject]() muss vorher aufgerufen 
		     *          werden!
		     * 
		     * @return Folgezeile des Abfrageergebnisses als mixed-Objekt auf das
		     *         per Index-Nummer oder assoziativ zugegriffen werden kann,
		     *         NULL, falls kein Ergebnis mehr vorhanden ist oder FALSE im 
		     *         Fehlerfall
		     *********************************************************************/
		    public function SqlGetNextPreparedLine ()
		    {
			    if ($this->_PDOStatementPrepared == NULL)
			    {
				    $this->_LastErrorMessage = 'SqlStatement not prepared';
				    return FALSE;
			    }

			    try
			    {
				    if (($returnMixed = $this->_PDOStatementPrepared->fetch(PDO::FETCH_BOTH, 
						    PDO::FETCH_ORI_NEXT)) === FALSE)
				    {
					    // fetch() gibt sowohl im Fehlerfall als auch dann, wenn keine
					    // Daten mehr zum Zurückliefern vorhanden sind, ein FALSE zu-
					    // rück. Hier wird die Rückgabe unterschieden zwischen einem
					    // Fehler (FALSE) und keinen Daten mehr (NULL)
					    $arrTemp = $this->_PDOStatementPrepared->errorInfo();
					    if (($this->_LastErrorMessage = $arrTemp[2]) === NULL)
					    {
						    // Manuelles Setzen des "keine Daten mehr"-Rückgabewertes
						    // und zurücksetzen des DB-Cursors
						    $returnMixed = NULL;
					    }
				    }

				    return $returnMixed;
			    }
			    catch (PDOException $e)
			    {
				    $this->_LastErrorMessage = $e->getMessage();
				    return FALSE;
			    }
		    }


		    /**********************************************************************
		     * SqlGetNextLineAsObject ()
		     * 
		     * Liefert die näüchste Zeile des Abfrageergebnisses der zuvor über-
		     * gebenen SQL-Abfrage.
		     * Hinweis: SqlGetFirstLine[AsObject]() muss vorher aufgerufen werden!
		     * 
		     * @return Erste Zeile des Abfrageergebnisses als Objekten, das die 
		     *         Ergebnisspalten als zugreifbare Klassenattribute darstellt,
		     *         NULL, falls kein Ergebnis geliefert wurde oder FALSE im 
		     *         Fehlerfall
		     *********************************************************************/
		    public function SqlGetNextLineAsObject ()
		    {
			    $arrResult = $this->SqlGetNextLine();

			    if ($arrResult !== FALSE)
			    {
				    if (count($arrResult) > 0)
				    {
					    // Zurückgelieferte Spalten (Keys) der ersten Zeile merken
					    $arrKeys = array_keys($arrResult);

					    $tmpClass = new ResultObject ();

					    foreach ($arrKeys as $Key)
					    {
						    $tmpClass->$Key = $arrResult[$Key];
					    }

					    return $tmpClass;
				    }
				    else
				    {
					    return NULL;
				    }
			    }

			    return FALSE;
		    }


		    /**********************************************************************
		     * SqlGetNextPreparedLineAsObject ()
		     * 
		     * Liefert die nächste Zeile des Abfrageergebnisses der zuvor über-
		     * gebenen SQL-Abfrage.
		     * Hinweis: SqlGetFirstLine[AsObject]() muss vorher aufgerufen werden!
		     * 
		     * @return Erste Zeile des Abfrageergebnisses als Objekten, das die 
		     *         Ergebnisspalten als zugreifbare Klassenattribute darstellt,
		     *         NULL, falls kein Ergebnis geliefert wurde oder FALSE im 
		     *         Fehlerfall
		     *********************************************************************/
		    public function SqlGetNextPreparedLineAsObject ()
		    {
			    $arrResult = $this->SqlGetNextPreparedLine();

			    if ($arrResult !== FALSE)
			    {
				    if (count($arrResult) > 0)
				    {
					    // Zurückgelieferte Spalten (Keys) der ersten Zeile merken
					    $arrKeys = array_keys($arrResult);

					    $tmpClass = new ResultObject ();

					    foreach ($arrKeys as $Key)
					    {
						    $tmpClass->$Key = $arrResult[$Key];
					    }

					    return $tmpClass;
				    }
				    else
				    {
					    return NULL;
				    }
			    }

			    return FALSE;
		    }


		    /**********************************************************************
		     * SqlGetExecutionTime ()
		     * 
		     * Gibt die Dauer der zuletzt ausgeführten Abfrage in Millisekunden
		     * zurück.
		     * 
		     * @return Dauer der zuletzt ausgeführten Abfrage in Millisekunden bzw.
		     *         FALSE im Fehlerfall oder falls die aktuelle Abfrage noch
		     *         nicht abgeschlossen ist oder fehlerhaft beendet wurde
		     *********************************************************************/
		    public function SqlGetExecutionTime ()
		    {
			    if ($this->_ExecutionTime == 0)
			    {
				    $this->_LastErrorMessage = 'no execution time available';
				    return FALSE;
			    }

			    try
			    {
				    return (int)($this->_ExecutionTime * 1000);
			    }
			    catch (Exception $e)
			    {
				    $this->_LastErrorMessage = $e->getMessage();
				    return FALSE;
			    }

		    }

		    /**********************************************************************
		     * SqlHasResult ()
		     * 
		     * Ermittelt, ob das übergebene SELECT-Statement mindestens ein
		     * Ergebnis zurückgibt. Kann z. B. für darauf folgende UPDATE-
		     * Statements verwendet werden, um vorher zu ermitteln, ob es den
		     * zu aktualisierenden Datensatz in der Datenbank gibt.
		     * 
		     * Hinweis! 
		     * Da diese Funktion unter anderem die Werte false und FALSE zurück-
		     * liefern kann, sollte bei if-Anweisungen stets mit "===" oder 
		     * "!==" gearbeitet werden.
		     * 
		     * @return true, wenn das übergebene SELECT-Statement mindestens
		     *         ein Ergebnis zurückliefern würde.
		     *         false, wenn das übergebene SELECT-Statement kein Ergebnis
		     *         zurückliefern würde.
		     *         FALSE, falls ein Fehler während der Ausführung aufgetreten
		     *         ist.
		     *********************************************************************/
		    public function SqlHasResult ( $SqlQuery )
		    {
			     $resultReturn = $this->sqlGetPDOObject($SqlQuery);

			     if ($resultReturn !== FALSE)
			     {
				      if ($resultReturn->fetch() === false)
						    return false;
				      else
						    return true;
			     }

			     return FALSE;
		    }

		    # SQL-Query-Ausführung <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<

	    # Public Methoden <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<

    }	




    /****************************************************************************
     * Platzhalterklasse für die Rückgabe der Methode SqlGetLinesAsObject(),
     * SqlGetFirstLineAsObject() und SqlGetNextLine() mit sämtlichen Datenbank-
     * spalten als Klassenattribute
     ***************************************************************************/
    class ResultObject {}