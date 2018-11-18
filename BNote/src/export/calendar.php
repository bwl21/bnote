<?php

/**
 * Creates an iCalendar output of a data set.
 * @author matti
 *
 */

// conncet to application
$dir_prefix = "../../";
include $dir_prefix . "dirs.php";
include $dir_prefix . $GLOBALS["DIR_DATA"] . "systemdata.php";
include $dir_prefix . $GLOBALS["DIR_DATA"] . "applicationdataprovider.php";

$GLOBALS["DIR_WIDGETS"] = $dir_prefix . $GLOBALS["DIR_WIDGETS"];
require_once($GLOBALS["DIR_WIDGETS"] . "error.php");
require_once($GLOBALS["DIR_WIDGETS"] . "iwriteable.php");

/*
 * The user login is NOT checked in this case, because otherwise
 * it is not possible to include the .ics in an external application
 * easily.
 */

// SETUP
$timezone = "Europe/Berlin"; // timezone in which the datetimes are specified
$timezone_on = true; // set to true to turn the timezone setting on.

// Build Database Connection
$system_data = new Systemdata($dir_prefix);
global $system_data;

$db = $system_data->dbcon;
require_once($dir_prefix . $GLOBALS["DIR_DATA"] . "abstractdata.php");
require_once($dir_prefix . $GLOBALS["DIR_DATA"] . "fieldtype.php");
require_once($dir_prefix . $GLOBALS["DIR_DATA_MODULES"] . "startdata.php");
$startdata = new StartData($dir_prefix);
$adp = $startdata->adp();

/**
 * define a function to write the timecodes correctly
 * @param String $datetime Date Time String in Format: YYYY-MM-DD HH:ii:ss
 * @return String in format YYYYMMDDTHHiissZ  
 */
function convertTime($datetime) {
	$year = substr($datetime, 0, 4);
	$month = substr($datetime, 5, 2);
	$day = substr($datetime, 8, 2);
	$hour = substr($datetime, 11, 2);
	$min = substr($datetime, 14, 2);
	
	return $year . $month . $day . "T" . $hour . $min . "00Z";
}

// read system config
$organizer = $system_data->getCompany();

// set username
if(isset($_GET["user"])) {
	$userid = $db->getCell($db->getUserTable(), "id", "login = '" . $_GET["user"] . "'");
	$_SESSION["user"] = $userid;
}
else if(isset($_SESSION["user"])) {
	$userid = $_SESSION["user"];
}
else {
	$userid = null;
}

// set content format
header( "Content-type:text/calendar charset=utf-8" );
echo "BEGIN:VCALENDAR\r\n";
echo "VERSION:2.0\r\n";
echo "PRODID:" . $system_data->getSystemURL() . "\r\n";

// add timezone definition
echo "BEGIN:VTIMEZONE\r\n";
echo "TZID:Europe/Berlin\r\n";
echo "TZURL:http://tzurl.org/zoneinfo-outlook/Europe/Berlin\r\n";
echo "X-LIC-LOCATION:Europe/Berlin\r\n";
echo "BEGIN:DAYLIGHT\r\n";
echo "TZOFFSETFROM:+0100\r\n";
echo "TZOFFSETTO:+0200\r\n";
echo "TZNAME:CEST\r\n";
echo "DTSTART:19700329T020000\r\n";
echo "RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU\r\n";
echo "END:DAYLIGHT\r\n";
echo "BEGIN:STANDARD\r\n";
echo "TZOFFSETFROM:+0200\r\n";
echo "TZOFFSETTO:+0100\r\n";
echo "TZNAME:CET\r\n";
echo "DTSTART:19701025T030000\r\n";
echo "RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU\r\n";
echo "END:STANDARD\r\n";
echo "END:VTIMEZONE\r\n";

/*
 * REHEARSALS
 */
if($userid == null || $userid < 1) {
	// get all rehearsals
	$query = "SELECT rehearsal.id as id, begin, end, ";
	$query .= " rehearsal.notes, name, street, city ";
	$query .= " FROM rehearsal, location, address";
	$query .= " WHERE location = location.id AND address = address.id";
	$rehearsals = $db->getSelection($query);
}
else {
	// get user's rehearsals including those from phases
	$rehearsals = $startdata->getUsersRehearsals();	
}

// write them
for($i = 1; $i < count($rehearsals); $i++) {
	echo "BEGIN:VEVENT\r\n";
	echo "SUMMARY:Probe $organizer\r\n";
	echo "ORGANIZER:$organizer\r\n";
	
	if($timezone_on) {
		echo "DTSTART;TZID=$timezone:" . convertTime($rehearsals[$i]["begin"]) . "\r\n";
		echo "DTEND;TZID=$timezone:" . convertTime($rehearsals[$i]["end"]) . "\r\n";
	} else {
		echo "DTSTART:" . convertTime($rehearsals[$i]["begin"]) . "\r\n";
		echo "DTEND:" . convertTime($rehearsals[$i]["end"]) . "\r\n";
	}
	
	if($rehearsals[$i]["name"] != "") { 
		echo "LOCATION:" . $rehearsals[$i]["name"] . " - " .
			$rehearsals[$i]["street"] . "\\, " . $rehearsals[$i]["city"] . "\r\n";
	}
	else if($rehearsal[$i]["location"] != "") {
		// fetch rehearsal location
		$query = "SELECT l.name, a.street, a.city ";
		$query .= "FROM location l JOIN address a ON l.address = a.id ";
		$query .= "WHERE l.id = " . $rehearsal[$i]["location"];
		$addy = $db->getRow($query);
		echo "LOCATION:" . $addy["name"] . " - " . $addy["street"] . "\\, " . $addy["city"] . "\r\n";
	}
	
	// get songs to practise
	$query = "SELECT title ";
	$query .= "FROM song s, rehearsal_song rs ";
	$query .= "WHERE rs.rehearsal = " . $rehearsals[$i]["id"] . " AND s.id = rs.song ";
	$query .= "ORDER BY title";
	$songs = $db->getSelection($query);
	
	// conductor
	$notes = "";
	if(isset($rehearsals[$i]["conductor"]) && $rehearsals["conductor"] != null) {
		$notes = "Dirigent: " . $adp->getConductorname($rehearsals[$i]["conductor"]) . "\r\n";
	}
	
	// write songs to practise in notes
	$notes .= "Bitte folgende Stücke üben: ";
	for($j = 1; $j < count($songs); $j++) {
		$notes .= $songs[$j]["title"] . "\\, ";
	}
	if(count($songs) > 1) {
		$notes = substr($notes, 0, strlen($notes)-2);
	}
	else {
		$notes .= "keine";
	}
	
	// participants
	$query = "SELECT c.id, c.surname, c.name, c.email, ru.participate, ru.reason";
	$query .= " FROM rehearsal_user ru, user u, contact c";
	$query .= " WHERE ru.rehearsal = " . $rehearsals[$i]["id"] . " AND ru.user = u.id AND u.contact = c.id" ;

	$contacts = $db->getSelection($query);
	
	unset($contacts[0]);
	
	// ids for filterting contacts without response
	$contactIDs = array();
	$participantsNo = array();
	$participantsYes = array();
	$participantsMaybe = array();

	foreach($contacts as $j => $contact)
	{
		foreach($contact as $ck => $cv) {
			if(is_numeric($ck)) {
				unset($contact[$ck]);
			}
		}
		array_push($contactIDs, $contact["id"]);

		if ($contact["participate"] == 0)
		{
			array_push($participantsNo, $contact);
		}
		else if ($contact["participate"] == 1)
		{
			array_push($participantsYes, $contact);
		}
		else if ($contact["participate"] == 2)
		{
			array_push($participantsMaybe, $contact);
		}
	}


	// get contacts without response (filter other contacts)
	array_push($contactIDs, PHP_INT_MAX);
	$contactIDsString = join(',',$contactIDs);

	$query = "SELECT c.id, c.surname, c.name, c.email";
	$query .= " FROM rehearsal_contact rc JOIN contact c ON rc.contact = c.id";
	$query .= " WHERE rc.rehearsal = " . $rehearsals[$i]["id"] . " AND rc.contact NOT IN (" . $contactIDsString .")";
	$participantsNoResponse = $db->getSelection($query);
	unset($participantsNoResponse[0]);

	foreach($participantsNoResponse as $j => $contact) {
		foreach($contact as $ck => $cv) {
			if(is_numeric($ck)) {
				unset($participantsNoResponse[$j][$ck]);
			}
		}
	}
	
	foreach($participantsYes as $j => $contact)
	{
		$line = "ATTENDEE;PARTSTAT=ACCEPTED;ROLE=REQ-PARTICIPANT;CN=" . $contact["name"] . " " . $contact["surname"] . ":MAILTO:" . $contact["email"] . "\r\n";
 		echo $line;
	}
	foreach($participantsNo as $j => $contact)
	{
		$line = "ATTENDEE;PARTSTAT=DECLINED;ROLE=REQ-PARTICIPANT;CN=" . $contact["name"] . " " . $contact["surname"] . ":MAILTO:" . $contact["email"] . "\r\n";
 		echo $line;
	}
	foreach($participantsMaybe as $j => $contact)
	{
		$line = "ATTENDEE;PARTSTAT=TENTATIVE;ROLE=REQ-PARTICIPANT;CN=" . $contact["name"] . " " . $contact["surname"] . ":MAILTO:" . $contact["email"] . "\r\n";
 		echo $line;
	}
	foreach($participantsNoResponse as $j => $contact)
	{
		$line = "ATTENDEE;ROLE=REQ-PARTICIPANT;CN=" . $contact["name"] . " " . $contact["surname"] . ":MAILTO:" . $contact["email"] . "\r\n";
 		echo $line;
	}
	
	$notes = str_replace("\n","\\n", $notes);
	$notes =	str_replace("\r","", $notes);
	echo "DESCRIPTION:$notes\r\n";
	echo "END:VEVENT\r\n";
}

/*
 * CONCERTS
 */
if($userid == null || $userid < 1) {
	// get all concerts
	$query = "SELECT title, begin, end, ";
	$query .= " concert.notes, name, street, city, state, country ";
	$query .= " FROM concert, location, address";
	$query .= " WHERE location = location.id AND address = address.id";
	$concerts = $db->getSelection($query);
}
else {
	// get all concert including those from phases
	$concerts = $startdata->getUsersConcerts();
}

// write them
for($i = 1; $i < count($concerts); $i++) {
	echo "BEGIN:VEVENT\r\n";
	echo "SUMMARY:" . $concerts[$i]["title"] . "\r\n";
	echo "ORGANIZER:$organizer\r\n";
	
	if($timezone_on) {
		echo "DTSTART;TZID=$timezone:" . convertTime($concerts[$i]["begin"]) . "\r\n";
		echo "DTEND;TZID=$timezone:" . convertTime($concerts[$i]["end"]) . "\r\n";
	} else {
		echo "DTSTART:" . convertTime($concerts[$i]["begin"]) . "\r\n";
		echo "DTEND:" . convertTime($concerts[$i]["end"]) . "\r\n";
	}
	
	if($userid == null || $userid < 1) {
		$location = $concerts[$i]["name"] . " (" .$concerts[$i]["street"] . "\\, ";
		$location .= $concerts[$i]["city"] . ")";
	}
	else {
		$location = $concerts[$i]["location_name"] . " (" . $concerts[$i]["location_street"] . "\\, ";
		$location .= $concerts[$i]["location_city"] . ")";
	}
	
	
	$query = "SELECT c.id, c.surname, c.name, c.email, cu.participate, cu.reason";
	$query .= " FROM concert_user cu, user u, contact c";
	$query .= " WHERE cu.concert = " . $concerts[$i]["id"] . " AND cu.user = u.id AND u.contact = c.id" ;
	$contacts = $db->getSelection($query);
	unset($contacts[0]);

	// ids for filterting contacts without response
	$contactIDs = array();
	$participantsNo = array();
	$participantsYes = array();
	$participantsMaybe = array();
				
	foreach($contacts as $j => $contact) 
	{
		foreach($contact as $ck => $cv) {
			if(is_numeric($ck)) {
				unset($contact[$ck]);
			}
		}
		array_push($contactIDs, $contact["id"]);
		
		if ($contact["participate"] == 0)
		{
			array_push($participantsNo, $contact);
		}
		else if ($contact["participate"] == 1)
		{
			array_push($participantsYes, $contact);
		}
		else if ($contact["participate"] == 2)
		{
			array_push($participantsMaybe, $contact);
		}
	}

	// get contacts without response (filter other contacts)
	array_push($contactIDs, PHP_INT_MAX);
	$contactIDsString = join(',',$contactIDs);  
	
	$query = "SELECT c.id, c.surname, c.name, c.email";
	$query .= " FROM concert_contact cc JOIN contact c ON cc.contact = c.id";
	$query .= " WHERE cc.concert = " . $concerts[$i]["id"] . " AND cc.contact NOT IN (" . $contactIDsString .")";
	$participantsNoResponse = $db->getSelection($query);
	unset($participantsNoResponse[0]);
	
	foreach($participantsNoResponse as $j => $contact) 
	{
		foreach($contact as $ck => $cv) {
			if(is_numeric($ck)) {
				unset($participantsNoResponse[$j][$ck]);
			}
		}
	}
	
	foreach($participantsYes as $j => $contact)
	{
		$line = "ATTENDEE;PARTSTAT=ACCEPTED;ROLE=REQ-PARTICIPANT;CN=" . $contact["name"] . " " . $contact["surname"] . ":MAILTO:" . $contact["email"] . "\r\n";
 		echo $line;
	}
	foreach($participantsNo as $j => $contact)
	{
		$line = "ATTENDEE;PARTSTAT=DECLINED;ROLE=REQ-PARTICIPANT;CN=" . $contact["name"] . " " . $contact["surname"] . ":MAILTO:" . $contact["email"] . "\r\n";
 		echo $line;
	}
	foreach($participantsMaybe as $j => $contact)
	{
		$line = "ATTENDEE;PARTSTAT=TENTATIVE;ROLE=REQ-PARTICIPANT;CN=" . $contact["name"] . " " . $contact["surname"] . ":MAILTO:" . $contact["email"] . "\r\n";
 		echo $line;
	}
	foreach($participantsNoResponse as $j => $contact)
	{
		$line = "ATTENDEE;ROLE=REQ-PARTICIPANT;CN=" . $contact["name"] . " " . $contact["surname"] . ":MAILTO:" . $contact["email"] . "\r\n";
 		echo $line;
	}
	
	echo "LOCATION:" . $location . "\r\n";
	
	// compile description
	$comment = "";
	if(isset($concerts[$i]["outfit"]) && $concerts[$i]["outfit"] != "") {
		$comment = "Outfit: " . $concerts[$i]["outfit"] . "\r\n\r\n";
	}
	if(isset($concerts[$i]["meetingtime"]) && $concerts[$i]["meetingtime"] != "") {
		$comment .= "Treffpunkt: " . $concerts[$i]["meetingtime"] . "\r\n\r\n";
	}
	$comment .= $concerts[$i]["notes"];

	$program = $concerts[$i]["program_id"];
	if (!empty($program)) {
		$query = "SELECT s.title FROM program_song ps ";
		$query .= "JOIN song s ON ps.song = s.id WHERE ps.program = $program ORDER BY ps.rank ASC";
		
		$songs = $db->getSelection($query);
		unset($songs[0]);
	
		$setlist = "\r\n\r\nProgramm: \r\n";
		
		foreach($songs as $j => $song) {
			$setlist .= $song["title"] . "\r\n";
		}
		
		$comment .= $setlist;
	}

	$comment = str_replace("\n","\\n", $comment);
	$comment =	str_replace("\r","", $comment);
		echo "DESCRIPTION:" . $comment	 . "\r\n";
	echo "END:VEVENT\r\n";
}

/*
 * RESERVATIONS
 */
$reservations = $startdata->getReservations();

// write them
for($i = 1; $i < count($reservations); $i++) {
	echo "BEGIN:VEVENT\r\n";
	echo "SUMMARY:Reservierung " . $reservations[$i]["name"] . "\r\n";
	echo "ORGANIZER:$organizer\r\n";

	if($timezone_on) {
		echo "DTSTART;TZID=$timezone:" . convertTime($reservations[$i]["begin"]) . "\r\n";
		echo "DTEND;TZID=$timezone:" . convertTime($reservations[$i]["end"]) . "\r\n";
	} else {
		echo "DTSTART:" . convertTime($reservations[$i]["begin"]) . "\r\n";
		echo "DTEND:" . convertTime($reservations[$i]["end"]) . "\r\n";
	}
	
	echo "LOCATION:" . $reservations[$i]["locationname"] . "\r\n";
	echo "COMMENT:" . $reservations[$i]["notes"] . "\r\n";
	echo "END:VEVENT\r\n";
}

/*
 * RESERVATIONS
 */
$appointments = $startdata->getAppointments();

// write them
for($i = 1; $i < count($appointments); $i++) {
	$appointment = $appointments[$i];
	echo "BEGIN:VEVENT\r\n";
	echo "SUMMARY:" . $appointments[$i]["name"] . "\r\n";
	echo "ORGANIZER:$organizer\r\n";

	if($timezone_on) {
		echo "DTSTART;TZID=$timezone:" . convertTime($appointment["begin"]) . "\r\n";
		echo "DTEND;TZID=$timezone:" . convertTime($appointment["end"]) . "\r\n";
	} else {
		echo "DTSTART:" . convertTime($appointment["begin"]) . "\r\n";
		echo "DTEND:" . convertTime($appointment["end"]) . "\r\n";
	}

	echo "LOCATION:" . $appointment["locationname"] . ", " . $appointment["street"] . ", " . $appointment["zip"] . " " . $appointment["city"] . "\r\n";
	echo "COMMENT:" . $appointment["notes"] . "\r\n";
	echo "END:VEVENT\r\n";
}

/*
 * TOURS
 * Only write them if the user name is set.
 */
if($userid != null && $userid > 0) {
	// get all tours for this user
	$contact = $system_data->getUsersContact($userid);
	$cid = $contact["id"];
	$query = "SELECT t.*
			FROM tour t JOIN tour_contact tc ON tc.tour = t.id
			WHERE tc.contact = $cid";
	$tours = $db->getSelection($query);

	// write them
	for($i = 1; $i < count($tours); $i++) {
		$tour = $tours[$i];
		echo "BEGIN:VEVENT\r\n";
		echo "SUMMARY:" . $tour["name"] . "\r\n";
		echo "ORGANIZER:$organizer\r\n";
	
		if($timezone_on) {
			echo "DTSTART;TZID=$timezone:" . convertTime($tour["start"]) . "\r\n";
			echo "DTEND;TZID=$timezone:" . convertTime($tour["end"]) . "\r\n";
		} else {
			echo "DTSTART:" . convertTime($tour["begin"]) . "\r\n";
			echo "DTEND:" . convertTime($tour["end"]) . "\r\n";
		}
		echo "LOCATION:\r\n";
		echo "COMMENT:" . $tour["notes"] . "\r\n";
		echo "END:VEVENT\r\n";
	}	
}

// finish
echo "END:VCALENDAR\r\n";
?>