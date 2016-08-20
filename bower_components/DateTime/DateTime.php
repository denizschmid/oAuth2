<?php
    namespace Dansnet;
    
    class DateTime {
        
        private static $_formats = [
            "Y-m-d",
            "Y-m-d H:i",
            "Y-m-d H:i:s",
            "d-m-Y",
            "d-m-Y H:i",
            "d-m-Y H:i:s",
            "d.m.Y",
            "d.m.Y H:i",
            "d.m.Y H:i:s"
        ];
        
        private static $_formatDefaultDate = "Y-m-d";
        private static $_formatDefaultTime = "H:i:s";
        private static $_formatDefaultShortTime = "H:i";
        private static $_formatDefaultDateTime = "Y-m-d H:i:s";
        
        private static $_formatDateGerman = "d.m.Y";
        private static $_formatDateInternational = "Y-m-d";
        
        private $_date;
        
        /**
         * Erzeugt ein neues Date Objekt. Als Parameter kan ein DateTime-Objekt,
         * ein String oder Nichts angegeben werden. Im Falle eines Strings wird
         * das Datum automatisch geparst. Falls nicht übergeben wird, wird ein 
         * Objekt mit dem jetzigen Zeitstempel erzeugt.
         * 
         * @param mixed $date
         * @return void 
         */
        public function __construct( $date=NULL ) {
            if( empty($date) ) {
                $this->_date = new \DateTime();
            } else if ( is_a($date, "DateTime") ) {
                $this->_date = $date;
            } else {
                $this->_date = static::parse($date); 
            }

        }

        /**
         * Parst einen String in ein Datum. Die gültigen Formate sind:
         * <ul>
         *  <li>Y-m-d</li>
         *  <li>Y-m-d H:i</li>
         *  <li>Y-m-d H:i:s</li>
         *  <li>d-m-Y</li>
         *  <li>d-m-Y H:i</li>
         *  <li>d-m-Y H:i:s</li>
         *  <li>d.m.Y</li>
         *  <li>d.m.Y H:i</li> 
         *  <li>d.m.Y H:i:s</li>
         * </ul>
         * 
         * @param mixed $date
         * @return DateTime|boolean
         */
        public static function parse( $date ) {
            foreach( static::$_formats as $format ) {
                $parsed = date_create_from_format($format, $date);
                if( $parsed !== FALSE ) {
                    return $parsed;
                }
            }
            return FALSE;
        }
        
	/**
	 * Gibt das Datum im gewünschten Format aus. Wird kein Format definiert,
	 * so wird das Datum nach dem internationalen Format [Y-m-d H-i-s] ausgegeben.
	 * 
	 * @param string $format
	 * @return string
	 */
        public function getDate( $format="" ) {
            if( empty($format) ) {
                return date_format($this->_date, static::$_formatDefaultDate);
            }
            return date_format($this->_date, $format);
        }
        
	/**
	 * Gibt den Zeitbestandteil des Datums im Format [H:i:s] zurück.
	 * 
	 * @return string
	 */
        public function getTime() {
            return date_format($this->_date, static::$_formatDefaultTime);
        }
        
	/**
	 * Gibt den Zeitbestandteil des Datums im Format [H:i] zurück.
	 * 
	 * @return type
	 */
        public function getShortTime() {
            return date_format($this->_date, static::$_formatDefaultShortTime);
        }
        
	/**
	 * Gibt den Zeitstampel im internationalen Format [Y-m-d H-i-s] aus.
	 * 
	 * @return string
	 */
        public function getDateTime() {
            return date_format($this->_date, static::$_formatDefaultDateTime);
        }
        
	/**
	 * Gibt das Datum im deutschen Format [d.m.Y] aus.
	 * 
	 * @return string
	 */
        public function getDateGerman() {
            return date_format($this->_date, static::$_formatDateGerman);
        }
        
	/**
	 * Gibt den Zeitstampel im deutschen Format [d.m.Y H:i:s] aus.
	 * 
	 * @return string
	 */
        public function getDateTimeGerman() {
            return date_format($this->_date, $this->concatDateTime(static::$_formatDateGerman, static::$_formatDefaultTime));
        }
        
	/**
	 * Gibt das Datum im internationalen Format [Y-m-d] aus.
	 * 
	 * @return string
	 */
        public function getDateInternational() {
            return date_format($this->_date, static::$_formatDateInternational);
        }
        
	/**
	 * Gibt den Zeitstampel im internationalen Format [Y-m-d H:i:s] aus.
	 * 
	 * @return string
	 */
        public function getDateTimeInternational() {
            return date_format($this->_date, $this->concatDateTime(static::$_formatDateInternational, static::$_formatDefaultTime));
        }
        
	/**
	 * Führt Berechnungen anhand des Zeitstampels durch.
	 * 
	 * @see http://php.net/manual/de/datetime.modify.php
	 * @param string $modify
	 * @return Dansnet\DateTime
	 */
        public function calculate( $modify ) {
            $newDate = clone $this->_date;
            return new DateTime($newDate->modify($modify));
        }
        
	/**
	 * Gibt das morgige Datum zurück. Die Uhrzeit bleibt unberührt.
	 * 
	 * @param string $modify
	 * @return Dansnet\DateTime
	 */
        public function tomorrow() {
            $newDate = clone $this->_date;
            return new DateTime($newDate->modify("+1 day"));
        }
        
	/**
	 * Gibt das gestrige Datum zurück. Die Uhrzeit bleibt unberührt.
	 * 
	 * @param string $modify
	 * @return Dansnet\DateTime
	 */
        public function yesterday() {
            $newDate = clone $this->_date;
            return new DateTime($newDate->modify("-1 day"));
        }
        
	/**
	 * Führt den Datums- und Zeitbestandteil zusammen, damit der sich ergebende
	 * String als Zeitstampel parsen lässt.
	 * 
	 * @param string $date
	 * @param string $time
	 * @return string
	 */
        private function concatDateTime( $date, $time ) {
            return $date." ".$time;
        }
        
        public function __toString() {
            return $this->getDateTimeInternational();
        }
        
    }
        
