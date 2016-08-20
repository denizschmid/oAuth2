#DateTime
DateTime is a simple PHP DateTime wrapper that extends the DateTime functionality with parsing, format and calculation functions.

##Installation
Install this package with bower
```bash
  bower install denizschmid/DateTime
```

##Parsing
The constructor has the ability to parse a string with common Date, DateTime or Time patterns. If no date or time is consigned the current date or time will be added to the consigned part of the string.

###Example: 
```php 
$date = new Dansnet\DateTime("2016-06-01");             // 2016-06-01 with current time
$date2 = new Dansnet\DateTime("01.06.16");              // 2016-06-01 with current time
$datetime = new Dansnet\DateTime("01.06.16 13:00");     // 2016-06-01 13:00:00
$datetime2 = new Dansnet\DateTime("2016-06-01 13:00");  // 2016-06-01 13:00:00
$time = new Dansnet\DateTime("13:00");                  // 13:00:00 with current date
```

##Formatting
The Dansnet\DateTime-Object serves some formatting functions without wasting much time to generate outputs with standardized date or time formats such as the international [ISO 8601](https://de.wikipedia.org/wiki/ISO_8601) format `JJJJ-MM-TT`. If you want to output a certain format it is possible to commit your individual format (see also [date formats](http://php.net/manual/de/function.date.php)).

###Example: 
```php 
$date = new Dansnet\DateTime("2016-06-01 12:49");             
$date->getInternationalDate();                          // 2016-06-01
$date->getGermanDate();                                 // 01.06.16 
$date->getInternationalDateTime();                      // 2016-06-01 12:49
$date->getGermanDateTime();                             // 01.06.16 12:49
$date->getDate("Y-m-d H:i");                            // 2016-06-01 12:49
```

##Calculation
It is very simple to calculate with dates e.g. to get the date of the next day or substract a time interval from a certain date (see also [date modify](http://php.net/manual/de/datetime.modify.php)).

###Example: 
```php 
$date = new Dansnet\DateTime("2016-06-01 12:49");             
$date->tommorrow();                                     // 2016-06-02
$date->yesterday();                                     // 2016-05-31
$date->calculate("+10 days");                           // 2016-06-11
```
