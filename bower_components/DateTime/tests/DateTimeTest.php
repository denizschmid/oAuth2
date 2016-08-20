<?php
include_once '../DateTime.php';

class DateTimeTest extends PHPUnit_Framework_TestCase {
    
    public function testDateParse() {
	$date = new Dansnet\DateTime("2016-05-01");
	$this->assertNotEquals(FALSE, $date);
	return $date;
    }
    
    public function testDateTimeParse() {
	$date = new Dansnet\DateTime("2016-05-01 23:49:13");
	$this->assertNotEquals(FALSE, $date);
	return $date;
    }
    
    /**
     * @depends testDateParse
     */
    public function testDateFormat( Dansnet\DateTime $date ) {
	$this->assertEquals("01.05.2016", $date->getDateGerman());
	$this->assertEquals("2016-05-01", $date->getDateInternational());
    }
    
    /**
     * @depends testDateTimeParse
     */
    public function testDateTimeFormat( Dansnet\DateTime $date ) {
	$this->assertEquals("23:49:13", $date->getTime());
	$this->assertEquals("23:49", $date->getShortTime());
    }
    
    /**
     * @depends testDateParse
     */
    public function testDateCalc( Dansnet\DateTime $date ) {
	$this->assertEquals("2016-05-02", $date->tomorrow()->getDateInternational());
	$this->assertEquals("2016-04-30", $date->yesterday()->getDateInternational());
	$this->assertEquals("2016-05-03", $date->calculate("+2 days")->getDateInternational());
    }

}