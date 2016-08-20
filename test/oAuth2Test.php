<?php

	require_once "../bower_components/DataAccessObject/DataAccessObject.php";
	require_once "../bower_components/DateTime/DateTime.php";
	require_once "../OAuth2Base.php";
	require_once "../OAuth2.php";
	
	use Dansnet\DataAccessObject;
	use Dansnet\Webservice\OAuth2;
	
	class ValidatorTest extends PHPUnit_Framework_TestCase  {
		
		public function testoAuthDBInit() {
			$db = new DataAccessObject("token");
			$db->ConnectSqlite3(":memory:");
			$this->assertNotNull($db);
			$sql = "CREATE TABLE 'user' ('id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 'name' TEXT NOT NULL, 'firstname' TEXT, 'lastname' TEXT, 'password' TEXT NOT NULL, 'email' TEXT);";
			$sql .= "CREATE TABLE 'user_session' ('id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 'userid' INTEGER NOT NULL, 'session' TEXT NOT NULL, 'expire' DATETIME NOT NULL);";
			$sql .= "CREATE TABLE 'client' ('id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,'client_id' TEXT NOT NULL,'client_secret' TEXT, 'name' TEXT,'redirect_uri' TEXT,'user_id' INTEGER);";
			$sql .= "CREATE TABLE 'token' ('id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,'client_id' INTEGER NOT NULL, 'token' INTEGER NOT NULL, 'expires' INTEGER NOT NULL, 'scope' TEXT, 'cr_timestamp'  INTEGER NOT NULL DEFAULT CURRENT_TIMESTAMP  );";
			$sql .= "INSERT INTO \"user\" (\"id\",\"name\",\"firstname\",\"lastname\",\"password\",\"email\") VALUES ('1','d.s','D','S','$2y$10$2uDSMylrRT9ZmC0bKuognu9cD8Sc9a/ZPZDi1H/SFXEscZgp/Uzb.','dsd@gmail.com');";
			$this->assertEquals(1, $db->SqlExecute($sql));
			$auth = new OAuth2($db);
			return $auth;
		}
		
		/**
		 * @depends testoAuthDBInit
		 */
		public function testLogin( OAuth2 $auth ) {
			$this->assertGreaterThan(0, sizeof($auth->login("d.s", "test")));
			$this->assertFalse($auth->login("s.d", "test"));
			$this->assertFalse($auth->login("d.s", "test2"));
		}
		
		/**
		 * @depends testoAuthDBInit
		 */
		public function testSession( OAuth2 $auth ) {
			$_REQUEST["session"] = "xyz";
			$this->assertFalse($auth->isSessionValid());
			$this->assertGreaterThan(0, sizeof(($_REQUEST["session"] = $auth->login("d.s", "test")["session"])));
			$this->assertTrue($auth->isSessionValid());
		}

		
	}

