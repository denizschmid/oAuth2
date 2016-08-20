<?php

	namespace Dansnet\Webservice;
	use Dansnet\DateTime;
	
	/**
	 * OAuth2 authentifiziert Web-Anfragen anhand eines Access-Tokens. Die Erstellung
	 * dieses Tokens funktioniert durch die Session-Authentifizierung (Login in der
	 * Benutzerverwaltung), der Legitimation zum Zugriff auf die Benutzerdaten,
	 * und den oAuth2 Parameter (redirect_uri, client_id, scope usw.).
	 */
	class OAuth2 extends \OAuth2Base {
		
		/**
		 * Datenbankverbidung
		 * @var \Dansnet\DataAccessObject
		 */
		private $_connection;
		
		/**
		 * Setzt die Datenbankverbidung
		 * @param \Dansnet\DataAccessObject $connection
		 * @param type $config
		 */
		public function __construct(\Dansnet\DataAccessObject $connection, $config = array() ) {
			parent::__construct($config);
			$this->_connection = $connection;
		}
		
		/**
		 * Meldet den Benutzer anhand dessen Namen und Passwort an. Stimmen die Daten
		 * nicht mit den gespeicherten Daten überein, schlägt der Login-Versuch fehl.
		 * Ist der Vorgang erfolgreich wird die Session zurückgegeben.
		 * @param string $name
		 * @param string $password
		 * @return string|boolean Session oder FALSE im Fehlerfall
		 */
		public function login( $name, $password ) {
			$this->_connection->setTable("user");
			$user = $this->_connection->findFirst(["name"=>$name]);
			if( empty($user) || !password_verify($password, $user["password"]) ) {
				return FALSE;
			}
			$this->_connection->setTable("user_session");
			$expire = new DateTime();
			$session = $this->genAccessToken();
			return $this->_connection->save([
				"userid" => $user["id"], 
				"session" => $session,
				"expire" => $expire->tomorrow()
			]);
		}
		
		/**
		 * Prüft, ob die Session aus der Web-Anfrage ($_REQUEST["session"]) gültig ist.
		 * @return boolean TRUE, falls gültig, sonst FALSE
		 */
		public function isSessionValid() {
			if( !isset($_REQUEST["session"]) ) {
				return FALSE;
			}
			$this->_connection->setTable("user_session");
			$session = $this->_connection->findFirst([
				"session" => $_REQUEST["session"]
			]);
			if( empty($session) ) {
				return FALSE;
			}
			$now = new DateTime();
			return $session["expire"] > $now;
		}
		
		/**
		 * Leitet die Anfrage zum Login-Formular weiter.
		 */
		public function doRedirectLogin() {
			header("Location: ../../login.php?".http_build_query($_REQUEST));
			exit;
		}
		
		/**
		 * Leitet die Anfrage zum Formular zur Legitimation zum Auslesen der Daten
		 * weiter.
		 */
		public function doRedirectAccept() {
			header("Location: ../../accept.php?".http_build_query($_REQUEST));
			exit;
		}
		
		/**
		 * Prüft, ob die übergebenen Client-Daten korrekt sind.
		 * @param string $clientId
		 * @param string $clienSecret
		 * @return boolean TRUE, falls Daten korrekt, sonst FALSE
		 */
		protected function checkClientCredentials( $clientId, $clienSecret = NULL ) {
			$client = $this->getClient($clientId);
			if( $client === FALSE ) return FALSE;
			if( $clienSecret === NULL ) {
				return $client !== FALSE;
			}
			return $clienSecret == $client["client_secret"];
		}
		
		/**
		 * Ermittelt die Client-Daten anhand einer Client-ID.
		 * @param string $clientId
		 * @return array
		 */
		protected function getClient( $clientId ) {
			$data = [
				"client_id" => $clientId
			];
			$this->_connection->setTable("client");
			return $this->_connection->findFirst($data);
		}
		
		/**
		 * Ermittlet die Redirect-URI anhand einer Client-ID.
		 * @param string $clientId
		 * @return string|boolean Redirect-URI oder FALSE im Fehlerfall
		 */
		protected function getRedirectUri( $clientId ) {
			$client = $this->getClient($clientId);
			if( $client === FALSE ) {
				return FALSE;
			}
			return isset($client["redirect_uri"]) && $client["redirect_uri"] ? $client["redirect_uri"] : NULL;
		}
		
		/**
		 * Ermittelt die Daten des Access-Tokens.
		 * @param string $accessToken
		 * @return array|boolean Token-Daten oder FALSE im Fehlerfall
		 */
		public function getAccessToken( $accessToken ) {
			$data = [
				"token" => $accessToken
			];
			$this->_connection->setTable("token");
			$token = $this->_connection->findFirst($data);
			return $token !== FALSE ? $token : NULL;
		}
		
		/**
		 * Ermittelt die Access-Token-Daten anhand einer Client-ID.
		 * @param string $clientId
		 * @return array|boolean Token-Daten oder FALSE im Fehlerfall
		 */
		private function getAccessTokenByClient( $clientId ) {
			$data = [
				"client_id" => $clientId
			];
			$this->_connection->setTable("token");
			$token = $this->_connection->findFirst($data);
			return $token !== FALSE ? $token : NULL;
		}
		
		/**
		 * Ermittelt die Daten des Access-Tokens zur Visualisierung.
		 * @param string $accessToken
		 * @return array|boolean Token-Daten oder FALSE im Fehlerfall
		 */
		public function getTokenInfo() {
			$error = ["error" => "invalid_token"];
			if( !isset($_GET["access_token"]) ) return $error;
			$token = $this->getAccessToken($_GET["access_token"]);
			if( $token === NULL ) return $error;
			$client = $this->getClient($token["client_id"]);
			if( $client === FALSE ) return $error;
			return [
				"audience"	=> $client["client_id"],
				"userid"	=> $client["user_id"],
				"scope"		=> $token["scope"],
				"expires"	=> $token["expires"] - time()
				
			];
		}
		
		/**
		 * Speichert den Access-Token für einen Client.
		 * @param string $accessToken
		 * @param string $clientId
		 * @param integer $expires
		 * @param string $scope
		 */
		protected function setAccessToken( $accessToken, $clientId, $expires, $scope = NULL ) {
			$data = [
				"client_id" => $clientId,
				"token" => $accessToken,
				"expires" => $expires,
				"scope" => $scope
			];
			$this->_connection->setTable("token");
			$this->_connection->setColumnId("client_id");
			$this->_connection->save($data);
		}
		
		/**
		 * Ermittelt die gültigen Antworttypen des Tokens.
		 * @return array
		 */
		protected function getSupportedAuthResponseTypes() {
			return array(
				OAUTH2_AUTH_RESPONSE_TYPE_ACCESS_TOKEN
			);
		}
		
	}

