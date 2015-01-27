<?
class calendar {

private $conn;							// database connection
var $cd;								// current date; calendar date date
var $monthnumber;						// this month, numeric
var $year;								// this year
var $now;								// time of now
var $today;								// Y-m-d of now
var $numdays;							// days in month
var $dayofweek;							// first day of the month day of week
var $sd;								// start date
var $ed;								// end date
var $database;							// event database name
var $numweeks;							// number of weeks in the month
var $timeSlots;							// int - number of time slots for daytime calendar
var $visibleDays;						// a variable to store if a week has any visible days in it
var $closed=array();					// an array of closed dates
var $closeddescription=array();			// an array of descriptions that match the closed date

var $weekStart = 'Sun';					// Starting day of the week. Only "Sun" and "Mon" available
var $calendarType='regular';			// Calendar type
										//		types are "regular" and "daytime"

var $events = array();					// an array of events in the calendar
var $posts = array();					// an array that stores an array of searched values to pass through when next/prev clicked
var $buf = 0;							// this is padding for the beginning of the month; empty day boxes before the first
										
var $checkbookings = false;				// Whether to check if an event is booked
var $bookingdb = '';					// database that bookings are stored in

var $showdays = array('Sun','Mon','Tue','Wed','Thu','Fri','Sat');
var $header_days = 'D';					// 'l' for full name, 'D' for first three letters

var $show_pagination = true;			// show/hide the pagination


var $bookings_array = array();			// array of cabins booked
var $checkbookings_array = false;		// Whether to check if an event is booked by $bookings_array

var $calendarsize = 'regular';			// Calendar size: small or regular - small shows just number in a 28px square box. no event name

/**
 ** Public
	Construct the Class
 **
**/
function __construct($date,$db='events') {
	$this->_checkConn();
	//set variables
	$this->cd = $date;
	$this->now = date('U');
	$this->today = date('Y-m-d',$this->now);
	$this->database = $db;
	
	$this->month = date('F',$this->cd); 
	$this->monthnumber = date('n',$this->cd); 
	$this->year = date('Y',$this->cd);
	$this->numdays = date('t',$this->cd);
	
	$this->dayofweek = date("N", mktime(0, 0, 0, $this->monthnumber, 1, $this->year));
	
	if($this->dayofweek == 7) $this->dayofweek = 0;
	
	$this->sd = date('Y-m-d',mktime(0, 0, 0, $this->monthnumber, 1, $this->year));
	$this->ed = date('Y-m-d',mktime(0, 0, 0, $this->monthnumber, $this->numdays, $this->year));
	
	$this->numweeks = $this->_getWeeks($this->cd);

}

/**
 ** Public
	Load Events
 **
**/

public function getEvents($type='',$db='events') {
	$this->database = $db;
	$typesql = '';
	if($type != '') $typesql = 'AND type = "'.$type.'"';
	$this->events = $this->conn->select("*", $this->database, "WHERE publish = 'Y' $typesql ORDER BY start_time ASC");
}


/**
 ** Public
	Push Events to calendar
 **
**/

public function pushEvents($events = array(),$searchvals=array()) {
	$this->events = $events;
	$this->posts = $searchvals;
}


/**
 **
	Limit days in the calendar
 **
**/

public function setDays($dayarray=array()) {
	$this->showdays = $dayarray;
}

/**
 **
	Set Calendar size
 **
**/

public function setSize($size='regular') {
	$this->calendarsize = $size;
}


/**
 **
	Limit days in the calendar
 **
**/

public function setTimeSlots($num) {
	$this->timeSlots = $num;
}


/**
 **
	Set any closed days
 **
**/
public function getClosedDays($dbname) {
	$days = $this->conn->select('*',$dbname);
	foreach($days as $day) {
		$this->closed[] = $day['date'];
		$this->closeddescription[$day['date']] = $day['description'];
	}
}


/**
 **
	Sets the starting day of the week
 **
**/

public function setWeekStart($day) {
	$this->weekStart = $day;
	// bring sunday to the back of the showdays array
	// WARNING: setDays must be set before this function
	if($this->weekStart != 'Sun' && $this->showdays[0] == 'Sun') {
		array_shift($this->showdays);
		$this->showdays[] = 'Sun';
	}
}

/**
 **
	Set Booking Database Info
 **
**/

public function checkBookings($db) {
	$this->bookingdb = $db;
	$this->checkbookings = true;
}


/**
 **
	Set array of bookings
 **
**/

public function bookings($bookings) {
	
	$this->checkbookings_array = true;
	
	$booked = array();
	$booked['in'] = array();
	$booked['out'] = array();
	$booked['full'] = array();
	
	foreach($bookings as $book) {
		array_push($booked['in'],$book['in_date']);
		array_push($booked['out'],$book['out_date']);
		$d = date('Y-m-d',strtotime("+1 day",strtotime($book['in_date'])));;
		while($d < $book['out_date']) {
			array_push($booked['full'],$d);
			$d = date('Y-m-d',strtotime("+1 day",strtotime($d)));
		}
	}
	$this->bookings_array = $booked;
}


/**
 **
	Function to build the calendar
 **
**/
public function buildCalendar($type='regular') {
	$this->calendarType = $type;
	
	$cal = '<div class="calendar-wrapper '.$this->calendarType.'">';
	$cal .= $this->_pagination();
	
	$this->calendarType == 'regular' ? $cal .= $this->_headers() : 0;
	
	$w=0;
	
	while($w < $this->numweeks) {
		$cal .= $this->_weeks($w);
		$w++;
	}
	
	$cal .= '</div>';
	$cal .= $this->_buildCalendarDetail();
	return $cal;
}

/**
 **
	Creates Day headers on calendar
 **
**/
private function _headers() {
	$str = '<div class="dayheaderblock">';
	$tot = count($this->showdays)-1;

	foreach($this->showdays as $k=>$v) {
		$dayname = date($this->header_days,strtotime($v));
		if($k == 0) $class = ' first'; else if($k == $tot) $class = ' last'; else $class = '';
		$str .= '<div class="dayheader'.$class.'">'.$dayname.'</div>';
	}
	$str .= '<div class="cl"></div>
		</div>';
	return $str;
}


/**
 **
	Adds the Pagination and month name to the top of the calendar
 **
**/

private function _pagination() {
	$prev = strtolower(date('F-Y',strtotime("-1 month",strtotime(date('Y-m',$this->cd).'-1'))));
	$next = strtolower(date('F-Y',strtotime("+1 month",strtotime(date('Y-m',$this->cd).'-1'))));
	$posts='';
	
	foreach($this->posts as $k=>$v) $posts .= '&'.$k.'='.$v;
	
	if($this->show_pagination) {
		$str = '<div class="cal-paginate">
			<a class="cal-btn prev" href="'.$_SERVER['SCRIPT_URL'].'?d='.$prev.$posts.'"><span></span></a>
			<div class="month">'.$this->month.'&nbsp;'.$this->year.'</div>
			<a class="cal-btn next" href="'.$_SERVER['SCRIPT_URL'].'?d='.$next.$posts.'"><span></span></a>
		<div class="cl"></div>
		</div>
		<div class="cl"></div>';
	} else {
		$str = '<div class="cal-paginate">
			<div class="month">'.$this->month.'&nbsp;'.$this->year.'</div>
		<div class="cl"></div>
		</div>
		<div class="cl"></div>';
	}
	
	return $str;
}

/**
 **
	Creates the week frames
 **
**/
private function _weeks($week) {
	
	if($week == 0) $wc = ' firstweek';
	else if($week == $this->numweeks-1) $wc = ' lastweek';
	else $wc = '';
	
	
	
	$str = '<div class="weekframe'.$wc.' '.$this->calendarType.'">';
	$daynumber = 1;
	$this->visibleDays = 0;
	for ($d=(($week*7)+1); $d<(($week*7)+8); $d++) {
		
		
		
		($d == (($week*7)+1)) ? $first = true : $first = false;
		($d == (($week*7)+7)) ? $last = true : $last = false;	
		
		if($first) $class = $wc.' first';
		else if($last) $class = $wc.' last';
		else $class = $wc;
		
		($this->weekStart == 'Sun') ? $wb=1 : $wb = 0;
		
		if($this->dayofweek == 0 && $this->weekStart != 'Sun') $wb = 7;
				
		if($d < $this->dayofweek+$wb) $this->buf += 1;
				
		$str .= $this->_days($d,$class);
	}
	$str .= '</div>';
	if($this->visibleDays > 0) $str .= '<div class="weekbreak'.$wc.' '.$this->calendarType.'"></div>';
	
	return $str;
}


/**
 **
	Build the days in each week
 **
**/
private function _days($d,$c) {
	$day = $d-$this->buf;
	if($day == 0) return $this->_blankday($day,$c); // the padding before the first day of the month
	else if($day <= $this->numdays) return $this->_day($day,$c);
	else return $this->_blankday($day,$c);
}


/**
 **
	Creates a blank day box.
		Used for padding before the first day of the month and end of the last day of the month
 **
**/
private function _blankday($day,$c) {
	if($day == $this->numdays+1) $c .= ' dayafterlast';
	return '<div class="daybox blank'.$c.' day-none '.$this->calendarType.'"></div>';
}


/**
 **
	Creates the daybox for each day of the month
 **
**/
private function _day($day,$c) {
	if($day == 1) $c.' firstday';
	if($day == $this->numdays) $c .= ' lastday';
    $dstr = $this->year.'-'.$this->monthnumber.'-'.$day;
	$dname = date('D',strtotime($dstr));
	$dmonth = date('M',strtotime($dstr));
    
    $fday = date('Y-m-d',strtotime($dstr));
	
	$c .= ' day-'.strtolower($dname);
    if(array_key_exists('full',$this->bookings_array)) {
        if(in_array($fday,$this->bookings_array['full'])) $c .= ' booked';
        if(in_array($fday,$this->bookings_array['in'])) $c .= ' cibooked';
        if(in_array($fday,$this->bookings_array['out'])) $c .= ' cobooked';
    }
	
	if(!in_array($dname,$this->showdays)) $c .= ' hideday';
	else $this->visibleDays++;
	
	$closed = false;
	if(in_array(date('Y-m-d',strtotime($this->year.'-'.$this->monthnumber.'-'.$day)),$this->closed)) $closed = true;
	
	if($closed) $class = ' closed';
	else $class = '';
	
	
	if($this->checkbookings_array) {
		$class .= ' '.$this->_checkBookingArray(date('Y-m-d',strtotime($this->year.'-'.$this->monthnumber.'-'.$day)));
	}
	
		
	$str = '<div class="daybox'.$c.' '.$this->calendarType.'">';
	$str .= '<div class="daydatebox '.$this->calendarType.'">
			<div class="dayname">'.$dname.'</div>
			<div class="daymonth">'.$dmonth.'</div>
			<div class="daynumber">'.$day.'</div>
			</div>
			<div class="dayevents '.$this->calendarType.$class.'">';
			if(!$closed) $str .= $this->_findEvents($day);
			else $str .= '<span class="closed">'.$this->closeddescription[date('Y-m-d',strtotime($this->year.'-'.$this->monthnumber.'-'.$day))].'</span>';
	$str .= '</div>';
	$str .= '</div>';
	return $str;
}


/**
 **
	Gets number of weeks in the month
 **
**/
private function _getWeeks() {
	$weeks = ceil($this->numdays/7);
	$totblocks = $this->numdays+$this->dayofweek;
	if($totblocks > (7*$weeks)) $weeks+= 1;
	return $weeks;
}


/**
 **
	Checks if the event is on this day
 **
**/
private function _findEvents($d){
	$str = '';
	$dayevents = 0;
		
	foreach($this->events as $event) {
		
		if($event['frequency'] != '')
			$days = explode(',',$event['frequency']);
		else $days = array();
		$date = strtotime($this->year.'-'.$this->monthnumber.'-'.$d);
		
		
					
		if(array_key_exists('start_date',$event)) {
			if(strtotime($event['start_date'])) $es = strtotime($event['start_date']);
			else $es = $event['start_date'];	
			
			if(strtotime($event['end_date'])) $ed = strtotime($event['end_date']);
			else $ed = strtotime($event['end_date']);	
		} else if(array_key_exists('date',$event)) {
			if(strtotime($event['date'])) $es = strtotime($event['date']);
			else $es = $event['date'];
			$ed = $es;
		}
				
		$build = false;			
		if(($ed != '' && ($date >= $es && $date <= $ed)) || (($es > 0 && $ed == '') && $date == $es)) {
			
			if(!empty($days) && is_numeric($days[0])) {
				foreach($days as $k=>$v) {
					$get = $this->conn->id('frequency',$v);
					if($get > 0) $days[$k] = $get['abbr'];
				}
			}
							
			if(in_array(date('D',$date),$days) || empty($days)) $build = true;
		}
		if($build) $str .= $this->_buildEvent($event,$d);
	}

	
	return $str;

}

/**
 **
	Build Event String
 **
**/
private function _buildEvent($event,$d) {
	$gd = array('e'=>$event['id'],'o'=>$this->database,'d'=>strtotime($this->year.'-'.$this->monthnumber.'-'.$d));
	$e = $event['id'];
	$o = $this->database;
	
	$en = $d;
	
	$d = strtotime($this->year.'-'.$this->monthnumber.'-'.$d);
	
	$this->calendarType == 'daytime' ? $width = 'style="width:'.((100/$this->timeSlots)-4).'%"' : $width = '';
	$booked = false;
	$bookclass = '';
	$eventname = $event['event'];
	if($this->checkbookings) {
		$booked = $this->_checkBooked($gd);
		if($booked) {
			$bookclass = 'booked';
			$eventname = 'Booked';
		} 
	}
	
	if($this->calendarsize == 'small') $eventname = $en;
	
	//$link = '/registration?e=$e&o=$o&d=$d';
	$link = '';
	
	
	$ev = $this->conn->id($o,$e);
	if($ev['type'] == 'Birthdays' && !$booked) $link = '/birthday.php?e='.$e.'&d='.$d;
	else if(array_key_exists('link',$event) && $event['link'] != '') $link = $event['link'];
	else $link = 'javascript:void(0);';
	
	
	$str = "<a href='$link'  $width class='event $bookclass'>";
	$str .= '<div class="eventname">'.$eventname.'</div>';
	$str .= '</a>';
	return $str;
}


/**
 **
	Build Calendar Detail Div
 **
**/
private function _buildCalendarDetail() {
	return "<div id='calendar-detail'></div>";
}



/**
 **
	Check if event is booked
 **
**/
private function _checkBooked($info) {
	$check = $this->conn->select('*',$this->bookingdb,'WHERE event_id = "'.$info['e'].'" AND date = "'.date('Y-m-d',$info['d']).'"');
		
	if(count($check) > 0) return true; else return false;
}



/**
 **
	Check if this day is booked based on the $bookings_array
 **
**/
private function _checkBookingArray($cd) {
	$ci = '';
	if(in_array($cd,$this->bookings_array['full'])) $cl = 'booked';
	if(in_array($cd,$this->bookings_array['in'])) $cl = 'cibooked';
	if(in_array($cd,$this->bookings_array['out'])) $cl = 'cobooked';
	return $ci;
}



private function _checkConn() {
	global $db;		
			
	if($db instanceof common)
		$this->conn = $db;
	
	else $this->conn = new common();
}


	
}

?>
