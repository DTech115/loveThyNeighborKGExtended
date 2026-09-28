<?php
/*
 * Copyright 2013 by Jerrick Hoang, Ivy Xing, Sam Roberts, James Cook, 
 * Johnny Coster, Judy Yang, Jackson Moniaga, Oliver Radwan, 
 * Maxwell Palmer, Nolan McNair, Taylor Talmage, and Allen Tucker. 
 * This program is part of RMH Homebase, which is free software.  It comes with 
 * absolutely no warranty. You can redistribute and/or modify it under the terms 
 * of the GNU General Public License as published by the Free Software Foundation
 * (see <http://www.gnu.org/licenses/ for more information).
 * 
 */

/**
 * @version March 1, 2012
 * @author Oliver Radwan and Allen Tucker
 */

/* 
 * Created for Gwyneth's Gift in 2022 using original Homebase code as a guide
 */


include_once('dbinfo.php');
include_once(dirname(__FILE__).'/../domain/Event.php');
//Added to send emails to users when they are removed or signed up to an event.
//include_once(dirname(__FILE__).'/../email.php');
//removed because it breaks things

/*
 * add an event to dbEvents table: if already there, return false
 */

function add_event($event) {
    // if (!$event instanceof Event)
    //     die("Error: add_event type mismatch");
    $con=connect();
    $query = "SELECT * FROM dbevents WHERE id = ?";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "i", $event->getID());
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    //if there's no entry for this id, add it
    if ($result == null || mysqli_num_rows($result) == 0) {
        $insert_query = 'INSERT INTO dbevents VALUES(?, ?, ?, ?, ?, ?, ?, ?)';
        $stmt_insert = mysqli_prepare($con, $insert_query);
        mysqli_stmt_bind_param($stmt_insert, "issssiii",
            $event->getID(),
            $event->getName(),
            $event->getStartDate(),
            $event->getStartTime(),
            $event->getEndTime(),
            $event->getDescription(),
            $event->getCapacity(),
            $event->getArchived()
        );
        
        if (mysqli_stmt_execute($stmt_insert)) {
            mysqli_stmt_close($stmt_insert);
            mysqli_stmt_close($stmt);
            mysqli_close($con);
            return true;
        } else {
            mysqli_stmt_close($stmt_insert);
            mysqli_stmt_close($stmt);
            mysqli_close($con);
            return false;
        }
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($con);
    return false;
}

/*function fetch_event_name_by_id($id) {
    $connection = connect();
    $id = mysqli_real_escape_string($connection, $id);
    $query = "select name from dbevents where id = '$id'";
    $result = mysqli_query($connection, $query);
    $event = mysqli_fetch_assoc($result);
    if ($event) {
        require_once('include/output.php');
        $event = hsc($event);
        mysqli_close($connection);
        return $event;
    }
    mysqli_close($connection);
    return null;
}*/

function request_event_signup($event_name_str, $account_name, $role, $notes) {
    // This function is deprecated. Use create_app() in dbApplications.php for Retreat signups.
    // Kept for backwards compatibility only.
    $connection = connect();
    
    $query1 = "SELECT id FROM dbevents WHERE name = ?";
    $stmt = mysqli_prepare($connection, $query1);
    mysqli_stmt_bind_param($stmt, "s", $event_name_str);
    mysqli_stmt_execute($stmt);
    $result1 = mysqli_stmt_get_result($stmt);
    
    if (!$result1 || mysqli_num_rows($result1) === 0) {
        mysqli_stmt_close($stmt);
        mysqli_close($connection);
        return null;
    }

    $row = mysqli_fetch_assoc($result1);
    $eventID = $row['id'];
    mysqli_stmt_close($stmt);
    mysqli_close($connection);
    return $eventID;
}
function sign_up_for_event($eventID, $account_name, $role, $notes) {
    $connection = connect();
    
    // Use prepared statement instead of escaping
    $query1 = "SELECT id FROM dbevents WHERE id = ?";
    $stmt1 = mysqli_prepare($connection, $query1);
    mysqli_stmt_bind_param($stmt1, "i", $eventID);
    mysqli_stmt_execute($stmt1);
    $result1 = mysqli_stmt_get_result($stmt1);
    
    if (!$result1 || mysqli_num_rows($result1) === 0) {
        mysqli_stmt_close($stmt1);
        mysqli_close($connection);
        return null;
    }

    $row = mysqli_fetch_assoc($result1);
    $value = $row['id'];
    mysqli_stmt_close($stmt1);
   
    // Check for duplicate signup with prepared statement
    $query2 = "SELECT userID FROM dbeventpersons WHERE eventID = ? AND userID = ?";
    $stmt2 = mysqli_prepare($connection, $query2);
    mysqli_stmt_bind_param($stmt2, "is", $value, $account_name);
    mysqli_stmt_execute($stmt2);
    $result2 = mysqli_stmt_get_result($stmt2);
    $row2 = mysqli_fetch_assoc($result2);
    mysqli_stmt_close($stmt2);

    if ($row2) {
        // User already signed up
        mysqli_close($connection);
        return null;
    } else {       
        // Insert signup with prepared statement
        $query = "INSERT INTO dbeventpersons (eventID, userID, notes) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "iss", $value, $account_name, $notes);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        mysqli_close($connection);
        return $value;
    }
}

/* @@@ Thomas's work! */
/*
 * Check if a user is is signed up for an event. Return true or false.
 */
function check_if_signed_up($eventID, $userID) {
    // look up event+user pair
    $connection = connect();
    $query1 = "SELECT * FROM dbeventpersons WHERE eventID = ? AND userID = ?";
    $stmt = mysqli_prepare($connection, $query1);
    mysqli_stmt_bind_param($stmt, "is", $eventID, $userID);
    mysqli_stmt_execute($stmt);
    $result1 = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result1);
    mysqli_stmt_close($stmt);
    mysqli_close($connection);

    // check if a row was returned
    if ($row) {
        return True;
    } else {
        return False;
    }
}


/* @@@ Madison's work! */
/*
 * Check for all users signed up for an event. 
 */
function fetch_event_signups($eventID) {
    $connection = connect();
    $query = "SELECT userID, notes FROM dbeventpersons WHERE eventID = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "i", $eventID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!$result) {
        mysqli_stmt_close($stmt);
        mysqli_close($connection);
        die('Query failed: ' . mysqli_error($connection));
    }

    $signups = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $signups[] = $row;
    }

    mysqli_stmt_close($stmt);
    mysqli_close($connection);
    return $signups;
}

/*
 * Fetch pending signups for an event (rows in `dbpendingsignups`)
 * Returns array of rows with keys: username, role, notes
 */
function fetch_pending($eventID) {
    $connection = connect();
    $query = "SELECT username, role, notes FROM dbpendingsignups WHERE eventname = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "i", $eventID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!$result) {
        mysqli_stmt_close($stmt);
        mysqli_close($connection);
        die('Query failed: ' . mysqli_error($connection));
    }

    $signups = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $signups[] = $row;
    }

    mysqli_close($connection);
    return $signups;
}



function remove_user_from_event($event_id, $user_id) {    
    $query = "DELETE FROM dbeventpersons WHERE eventID = ? AND userID = ?";
    $connection = connect();
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "ss", $event_id, $user_id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($connection);
    return $result;
}

function fetch_user_upcoming_signups($user_id, $sortDirection = 'ASC', $limit = null) {
    $connection = connect();
    $normalizedSortDirection = strtoupper($sortDirection) === 'DESC' ? 'DESC' : 'ASC';

    $query = "
        SELECT
            e.id,
            e.name,
            e.date,
            e.startTime,
            e.endTime,
            e.location,
            GROUP_CONCAT(DISTINCT r.role ORDER BY r.role SEPARATOR ', ') AS roles
        FROM dbevents e
        INNER JOIN dbeventpersons ep ON e.id = ep.eventID
        LEFT JOIN dbroleevents re ON e.id = re.eventID
        LEFT JOIN dbroles r ON re.roleID = r.role_id
        WHERE ep.userID = ? AND ep.attended = 0
        GROUP BY e.id, e.name, e.date, e.startTime, e.endTime, e.location
        ORDER BY e.date $normalizedSortDirection, e.startTime $normalizedSortDirection";

    if ($limit !== null) {
        $query .= " LIMIT ?";
    }

    $stmt = mysqli_prepare($connection, $query);
    if (!$stmt) {
        mysqli_close($connection);
        return [];
    }

    if ($limit !== null) {
        mysqli_stmt_bind_param($stmt, "si", $user_id, $limit);
    } else {
        mysqli_stmt_bind_param($stmt, "s", $user_id);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $events = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $events[] = $row;
        }
    }

    mysqli_stmt_close($stmt);
    mysqli_close($connection);
    return $events;
}



/* @@@ Thomas's work! */
/*
 * Returns true if the given event is archived.
 */
/*function is_archived($id) {
    // look-up 'completed' in the event's DB entry
    $connection = connect();
    $query1 = "SELECT completed FROM dbevents WHERE id = '$id'";
    $result1 = mysqli_query($connection, $query1);
    $row = mysqli_fetch_assoc($result1);
    mysqli_close($connection);

    if ($row == NULL) return False; // no match for that event ID

    if ($row['completed'] == 'yes') {
        // event is archived
        return True;
    } else {
        return False;
    }
}*/
// Return true if the event has already ended -Brooke
function is_archived($eventID) {
    $con = connect();

    $query = "SELECT archived FROM dbevents WHERE id = ?";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "i", $eventID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!$result || mysqli_num_rows($result) === 0) {
        mysqli_stmt_close($stmt);
        mysqli_close($con);
        return false;
    } 
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    mysqli_close($con);

    $archived = (int)$row['archived'];

    if($archived === 1){
        return true;
    }

    return false;
}

//archive all events that took place before this moment.
function archive_old_events(){
    $con = connect();
    $query = "UPDATE dbevents SET archived = 1 WHERE date < DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND archived = 0";
    $result = mysqli_query($con, $query);

    return $result;
}

/*
 * Mark an event as archived in the DB by setting the 'archived' column to 'yes'.
 */
function archive_event($id) {
    $con = connect();
    $query = "UPDATE dbevents SET archived = 1 WHERE id = ?";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($con);
    return $result;
}

/*
 * Mark an event as not archived in the DB by setting the 'archived' column to 'no'.
 */
function unarchive_event($id) {
    $con = connect();
    $query = "UPDATE dbevents SET archived = 0 WHERE id = ?";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($con);
    return $result;
}

/* end of Thomas's work*/

/**/

/*
 * remove an event from dbEvents table.  If already there, return false
 */

function remove_event($id) {
    $con = connect();
    $query = 'SELECT * FROM dbevents WHERE id = ?';
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result == null || mysqli_num_rows($result) == 0) {
        mysqli_stmt_close($stmt);
        mysqli_close($con);
        return false;
    }
    
    $query = 'DELETE FROM dbevents WHERE id = ?';
    $stmt2 = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt2, "i", $id);
    $result = mysqli_stmt_execute($stmt2);
    mysqli_stmt_close($stmt);
    mysqli_stmt_close($stmt2);
    mysqli_close($con);
    return true;
}


/*
 * @return an Event from dbEvents table matching a particular id.
 * if not in table, return false
 */

function retrieve_event($id) {
    $con = connect();
    $query = "SELECT * FROM dbevents WHERE id = ?";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) !== 1) {
        mysqli_stmt_close($stmt);
        mysqli_close($con);
        return false;
    }
    $result_row = mysqli_fetch_assoc($result);
    $theEvent = make_an_event($result_row);
    mysqli_stmt_close($stmt);
    return $theEvent;
}

function retrieve_event2($id) {
    $con = connect();
    $query = "SELECT * FROM dbevents WHERE id = ?";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) !== 1) {
        mysqli_stmt_close($stmt);
        mysqli_close($con);
        return false;
    }
    $result_row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
//    var_dump($result_row);
    return $result_row;
}

function retrieve_event3() {
    $con = connect();
    $today = date('Y-m-d');
    $now = date('H:i:s');

    $query = "SELECT * FROM dbevents
              WHERE archived = 0
                AND (date > ? OR (date = ? AND endTime >= ?))
              ORDER BY date ASC, startTime ASC";

    $stmt = mysqli_prepare($con, $query);
    if (!$stmt) {
        mysqli_close($con);
        return [];
    }
    
    mysqli_stmt_bind_param($stmt, "sss", $today, $today, $now);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    $theEvents = array();

    if ($result) {
        while ($result_row = mysqli_fetch_assoc($result)) {
            $theEvents[] = $result_row;
        }
    }

    mysqli_close($con);
    return $theEvents;
}


// not in use, may be useful for future iterations in changing how events are edited (i.e. change the remove and create new event process)
function update_event_date($id, $new_event_date) {
	$con=connect();
	$query = 'UPDATE dbevents SET event_date = ? WHERE id = ?';
	$stmt = mysqli_prepare($con, $query);
	mysqli_stmt_bind_param($stmt, "si", $new_event_date, $id);
	$result = mysqli_stmt_execute($stmt);
	mysqli_stmt_close($stmt);
	mysqli_close($con);
	return $result;
}

function make_an_event($result_row) {
	/*
	 ($en, $v, $sd, $description, $ev))
	 */
    $theEvent = new Event(
                    $result_row['id'],
                    $result_row['name'],       
                    //type: $result_row['type'],            
                    startDate: $result_row['date'],
                    startTime: $result_row['startTime'],
                    endTime: $result_row['endTime'],
                    endDate: $result_row['date'],
                    description: $result_row['description'],
                    capacity: $result_row['capacity'],
                    location: $result_row['location'],
                    archived: $result_row['archived'] ?? 0
                
                    //affiliation: $result_row['affiliation'],
                    //branch: $result_row['branch'],
                    //access: $result_row['access'],   -Brooke believes this can be deleted later
                    //completed: $result_row['completed']
                    
                ); 
    return $theEvent;
}

function get_all_events() {
    $con=connect();
    //$query = "SELECT * FROM dbevents" . 
            //" ORDER BY completed";
    $query = "SELECT * FROM dbevents ORDER BY date ASC, startTime ASC";
    $result = mysqli_query($con,$query);
    $theEvents = array();
    while ($result_row = mysqli_fetch_assoc($result)) {
        $theEvent = make_an_event($result_row);
        $theEvents[] = $theEvent;
    }
    mysqli_close($con);
    return $theEvents;
 }
 function auto_Archive() {
    $con = connect();
    $query = "UPDATE dbevents SET archived = 1 WHERE date < CURDATE() - INTERVAL 5 DAY AND archived = 0";
    $result = mysqli_query($con, $query);
    mysqli_close($con);
    return $result;
}
 
 function Kiosk_get_all_events_sorted_by_date_not_archived() {
    $con=connect();
    $query = "SELECT * FROM dbevents WHERE archived = 0 ORDER BY date ASC, startTime ASC";
    $result = mysqli_query($con,$query);
    $theEvents = array();
    while ($result_row = mysqli_fetch_assoc($result)) {
        $theEvent = make_an_event($result_row);
        $theEvents[] = $theEvent;
    }
    mysqli_close($con);
    return $theEvents;
 }

// -Brooke changed it to fit with the updated dbevents database better 
function get_all_events_sorted_by_date_not_archived() {
    $con = connect();
    $today = date('Y-m-d');
    $now = date('H:i');

    $query = "SELECT * FROM dbevents WHERE date > ? OR (date = ? AND endTime >= ?) ORDER BY date ASC, startTime ASC";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "sss", $today, $today, $now);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $theEvents = array();

    while ($result_row = mysqli_fetch_assoc($result)) {
        $theEvent = make_an_event($result_row);
        $theEvents[] = $theEvent;
    }

    mysqli_stmt_close($stmt);
    mysqli_close($con);
    return $theEvents;
}

 /*function get_all_events_sorted_by_date_and_archived() {
    $con=connect();
    $query = "SELECT * FROM dbevents" .
            " WHERE completed = 'Y'" .
            " ORDER BY startDate ASC";
    $result = mysqli_query($con,$query);
    $theEvents = array();
    while ($result_row = mysqli_fetch_assoc($result)) {
        $theEvent = make_an_event($result_row);
        $theEvents[] = $theEvent;
    }
    mysqli_close($con);
    return $theEvents;
 }*/

// -Brooke changed this to fit with Love Thy Neighbor KG
function get_all_events_sorted_by_date_and_archived() {
    $con = connect();

    $today = date('Y-m-d');
    $now = date('H:i');

    $query = "SELECT * FROM dbevents WHERE date < ? OR (date = ? AND endTime < ?) ORDER BY date ASC, startTime ASC";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "sss", $today, $today, $now);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $theEvents = array();

    while ($result_row = mysqli_fetch_assoc($result)) {
        $theEvent = make_an_event($result_row);
        $theEvents[] = $theEvent;
    }

    mysqli_stmt_close($stmt);
    mysqli_close($con);
    return $theEvents;
}

// retrieve only those events that match the criteria given in the arguments
function getonlythose_dbEvents($name, $day, $venue) {
   $con=connect();
   $name_pattern = "%" . $name . "%";
   $venue_val = $venue;
   $query = "SELECT * FROM dbevents WHERE event_name LIKE ? AND event_name LIKE ? AND venue = ? ORDER BY event_name";
   $stmt = mysqli_prepare($con, $query);
   mysqli_stmt_bind_param($stmt, "sss", $name_pattern, $name_pattern, $venue_val);
   mysqli_stmt_execute($stmt);
   $result = mysqli_stmt_get_result($stmt);
   $theEvents = array();
   while ($result_row = mysqli_fetch_assoc($result)) {
       $theEvent = make_an_event($result_row);
       $theEvents[] = $theEvent;
   }
   mysqli_close($con);
   return $theEvents;
}

/*function fetch_events_in_date_range($start_date, $end_date) {
    $connection = connect();
    $start_date = mysqli_real_escape_string($connection, $start_date);
    $end_date = mysqli_real_escape_string($connection, $end_date);
    $query = "select * from dbevents
              where startDate >= '$start_date' and endDate <= '$end_date'
              order by startTime asc";
    $result = mysqli_query($connection, $query);
    if (!$result) {
        mysqli_close($connection);
        return null;
    }
    require_once('include/output.php');
    $events = array();
    while ($result_row = mysqli_fetch_assoc($result)) {
        $key = $result_row['startDate'];
        if (isset($events[$key])) {
            $events[$key] []= hsc($result_row);
        } else {
            $events[$key] = array(hsc($result_row));
        }
    }
    mysqli_close($connection);
    return $events;
}*/

function fetch_events_in_date_range($start_date, $end_date) {
    $connection = connect();
    $query = "SELECT * FROM dbevents WHERE date >= ? AND date <= ? ORDER BY date ASC, startTime ASC";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!$result) {
        mysqli_stmt_close($stmt);
        mysqli_close($connection);
        return null;
    }

    require_once('include/output.php');
    $events = array();

    while ($result_row = mysqli_fetch_assoc($result)) {
        $key = $result_row['date'];
        if (isset($events[$key])) {
            $events[$key] [] = hsc($result_row);
        } else {
            $events[$key] = array(hsc($result_row));
        }
    }

    mysqli_stmt_close($stmt);
    mysqli_close($connection);
    return $events;
}

function fetch_events_on_date($startDate, $loggedIn) {
    $connection = connect();
    if ($loggedIn) {
        $query = "select * from dbevents where startDate = ? order by startTime asc";
    }
    else {
        $query = "select * from dbevents where startDate = ? and access = 'Public' order by startTime asc";
    }
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "s", $startDate);
    mysqli_stmt_execute($stmt);
    $results = mysqli_stmt_get_result($stmt);
    if (!$results) {
        mysqli_stmt_close($stmt);
        mysqli_close($connection);
        return null;
    }
    require_once('include/output.php');
    $events = [];
    foreach ($results as $row) {
        $events []= hsc($row);
    }
    mysqli_stmt_close($stmt);
    mysqli_close($connection);
    return $events;
}

function fetch_event_by_id($id) {
    $connection = connect();
    $query = "select * from dbevents where id = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $event = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    if ($event) {
        // require_once('include/output.php');
        // $event = hsc($event);
        mysqli_close($connection);
        return $event;
    }
    mysqli_close($connection);
    return null;
}
// JUST ADDED
function fetch_num_signups($id) {
    $connection = connect();
    $query = "select count(*) as RowCount from dbeventpersons where eventID = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $event = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    if ($event) {
        require_once('include/output.php');
        $event = hsc($event);
        mysqli_close($connection);
        return $event;
    }
    mysqli_close($connection);
    return null;
}

function fetch_num_attendees($id) {
    $connection = connect();
    $query = "select count(*) as RowCount from dbeventpersons where eventID = ? and attended=1";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $event = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    if ($event) {
        require_once('include/output.php');
        $event = hsc($event);
        mysqli_close($connection);
        return $event;
    }
    mysqli_close($connection);
    return null;
}

function create_event($event) {
    $connection = connect();
    $name = $event["name"];
    $date = $event["date"];
    $startTime = $event["start-time"];    
    $endTime = $event["end-time"];
    $description = $event["description"];
    //$type = "Normal";
    $capacity = isset($event["capacity"]) ? (int)$event["capacity"] : 0;

    if (isset($event["location"])) {
        $location = $event["location"];
    } else {
        $location = "";
    }

    //$access = 'Public';
    //$completed = 'N';

    //$series_id = isset($event['series_id'])
        //? mysqli_real_escape_string($connection, $event['series_id'])
        //: null;

    //$query = "
        //insert into dbevents (name, startDate, startTime, endTime, endDate, access, description, capacity, completed, location, type, series_id)
        //values ('$name', '$date', '$startTime', '$endTime', '$date', '$access', '$description', $capacity, '$completed', '$location', '$type', " .($series_id ? "'$series_id'" : "NULL") . ")
    //";
    $archived = 0;
    $query = "
        INSERT INTO dbevents (name, date, startTime, endTime, description, capacity, location, archived)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ";
    
    $stmt = mysqli_prepare($connection, $query);
    if (!$stmt) {
        echo mysqli_error($connection);
        mysqli_close($connection);
        return null;
    }
    
    mysqli_stmt_bind_param($stmt, "sssssisi", $name, $date, $startTime, $endTime, $description, $capacity, $location,  $archived);
    $result = mysqli_stmt_execute($stmt);
    
    if (!$result) {
        echo mysqli_error($connection);
        mysqli_stmt_close($stmt);
        mysqli_close($connection);
        return null;
    }
    
    $id = mysqli_insert_id($connection);
    mysqli_stmt_close($stmt);
    mysqli_commit($connection);
    mysqli_close($connection);
    return $id;
}

function add_services_to_event($eventID, $serviceIDs) {
    $connection = connect();
    foreach($serviceIDs as $serviceID) {
        $query = "insert into dbeventsservices (eventID, serviceID) values (?, ?)";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "ii", $eventID, $serviceID);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        if (!$result) {
            return null;
        }
        $id = mysqli_insert_id($connection);
    }
    mysqli_commit($connection);
    return $id;
}

function update_event($eventID, $eventDetails) {
    $connection = connect();
    $id = $eventDetails["id"];
    $name = $eventDetails["name"];
    #$abbrevName = $eventDetails["abbrev-name"];
    $date = $eventDetails["date"];
    $startTime = $eventDetails["start-time"];
    #$restricted = $eventDetails["restricted"];
    $endTime = $eventDetails["end-time"];
    $description = $eventDetails["description"];
    $capacity = $eventDetails["capacity"];
    #$completed = $eventDetails["completed"];
    #$restricted_signup = $eventDetails["restricted_signup"];
    $location = $eventDetails["location"];
    //$services = $eventDetails["service"];
    
    #$completed = $eventDetails["completed"];
    #$query = "
       # update dbEvents set name='$name', abbrevName='$abbrevName', date='$date', startTime='$startTime', restricted='$restricted', description='$description', locationID='$location', completed='$completed'
       # where id='$eventID'
    #";
   # $query = "
    #    update dbevents set id='$id', name='$name', date='$date', startTime='$startTime', endTime='$endTime', description='$description', capacity='$capacity', completed='$completed', event_type='$event_type', restricted_signup='$restricted_signup'
    #    where id='$eventID'
    #";
    $query = "
        update dbevents set id='$id', name='$name', date='$date', startTime='$startTime', endTime='$endTime', description='$description', location='$location', capacity=$capacity
        where id='$eventID'
    ";
    $result = mysqli_query($connection, $query);
    // update_services_for_event($eventID, $services);
    mysqli_commit($connection);
    mysqli_close($connection);
    return $result;
}

function update_event2($eventID, $eventDetails) {
    $connection = connect();
    $id = $eventDetails["id"];
    $name = $eventDetails["name"];
    #$abbrevName = $eventDetails["abbrevName"];
    $date = $eventDetails["date"];
    $startTime = $eventDetails["startTime"];
    $endTime = $eventDetails["endTime"];
    $description = $eventDetails["description"];
    $capacity = $eventDetails["capacity"];
    $completed = $eventDetails["completed"];
    $restricted_signup = $eventDetails["restricted_signup"];
    #$query = "
    #    update dbEvents set name='$name', abbrevName='$abbrevName', date='$date', startTime='$startTime', endTime='$endTime', description='$description', locationID='$location', capacity='$capacity', animalId='$animalID', completed='$completed'
    #    where id='$eventID'
    #";
    $query = "
        update dbevents set id='$id', name='$name', date='$date', startTime='$startTime', endTime='$endTime', description='$description', capacity='$capacity', completed='$completed', restricted_signup='$restricted_signup'
        where id='$eventID'
    ";
    $result = mysqli_query($connection, $query);
    //update_services_for_event($eventID, $services);
    mysqli_commit($connection);
    mysqli_close($connection);
    return $result;
}

function update_services_for_event($eventID, $serviceIDs) {
    $connection = connect();

    $current_services = get_services($eventID);
    foreach($current_services as $curr_serv) {
        $curr_servIDs[] = $curr_serv['id'];
    }

    // add new services
    foreach($serviceIDs as $serviceID) {
        if (!in_array($serviceID, $curr_servIDs)) {
            $query = "insert into dbeventsservices (eventID, serviceID) values (?, ?)";
            $stmt = mysqli_prepare($connection, $query);
            mysqli_stmt_bind_param($stmt, "ii", $eventID, $serviceID);
            $result = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
    // remove old services
    foreach($curr_servIDs as $curr_serv) {
        if (!in_array($curr_serv, $serviceIDs)) {
            $query = "delete from dbeventsservices where serviceID=?";
            $stmt = mysqli_prepare($connection, $query);
            mysqli_stmt_bind_param($stmt, "i", $curr_serv);
            $result = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
    mysqli_commit($connection);
    return;
}

function find_event($nameLike) {
    $connection = connect();
    $name_pattern = "%" . $nameLike . "%";
    $query = "select * from dbevents where name like ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "s", $name_pattern);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        mysqli_stmt_close($stmt);
        return null;
    }
    $all = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    mysqli_close($connection);
    return $all;
}

function fetch_events_in_date_range_as_array($start_date, $end_date) {
    $connection = connect();
    $query = "select * from dbevents where date >= ? and date <= ? order by date, startTime asc";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        mysqli_stmt_close($stmt);
        mysqli_close($connection);
        return null;
    }
    $events = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    mysqli_close($connection);
    return $events;
}

function fetch_all_events() {
    $connection = connect();
    $query = "select * from dbevents
              order by date, startTime asc";
    $result = mysqli_query($connection, $query);
    if (!$result) {
        mysqli_close($connection);
        return null;
    }
    $events = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_close($connection);
    return $events;
}

function get_animal($id) {
    $connection = connect();
    $query = "select * from dbanimals where id=?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        mysqli_stmt_close($stmt);
        mysqli_close($connection);
        return [];
    }
    $animal = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    mysqli_close($connection);
    return $animal;
}

function get_description($id) {
    $connection = connect();
    $query = "select description from dbevents where id=?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        mysqli_stmt_close($stmt);
        mysqli_close($connection);
        return [];
    }
    $description = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_close($connection);
    return $description;
}
  

function get_location($id) {
    $connection = connect();
    $query = "select * from dblocations
              where id=?";
    $stmt = mysqli_prepare($connection, $query);
    if (!$stmt) {
        mysqli_close($connection);
        return [];
    }
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        mysqli_stmt_close($stmt);
        mysqli_close($connection);
        return [];
    }
    $location = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    mysqli_close($connection);
    return $location;
}

function get_services($eventID) {
    $connection = connect();
    $query = "select * from dbservices AS serv JOIN dbeventsservices AS es ON es.serviceID = serv.id
              where es.eventID='$eventID'";
    $result = mysqli_query($connection, $query);
    if (!$result) {
        return [];
    }
    $services = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_close($connection);
    return $services;
}

//TODO DELETE
// function attach_event_training_media($eventID, $url, $format, $description) {
//     return attach_media($eventID, 'training', $url, $format, $description);
// }

// function attach_post_event_media($eventID, $url, $format, $description) {
//     return attach_media($eventID, 'post', $url, $format, $description);
// }

// function detach_media($mediaID) {
//     $query = "delete from dbeventmedia where id='$mediaID'";
//     $connection = connect();
//     $result = mysqli_query($connection, $query);
//     mysqli_close($connection);
//     if ($result) {
//         return true;
//     }
//     return false;
// }

function delete_event($id) {
    $query = "delete from dbevents where id=?";
    $connection = connect();
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    $result = mysqli_stmt_execute($stmt);
    $result = boolval($result);
    mysqli_stmt_close($stmt);
    mysqli_close($connection);
    return $result;
}

function cancel_event($event_id, $account_name) {
    $query = "DELETE from dbeventpersons where userID = ? AND eventID = ?";
    $connection = connect();
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "ss", $account_name, $event_id);
    $result = mysqli_stmt_execute($stmt);
    $result = boolval($result);
    mysqli_stmt_close($stmt);
    mysqli_close($connection);
    return $result;
}
/**
 * Approve a signup given a single event and a username
 * @param mixed $event_id The ID for the associated event
 * @param mixed $account_name The username for the associated account
 * @param mixed $position The position that the user applied for(DEPRECIATED)
 * @param mixed $notes Any notes for why the application was approved.
 * @return bool|mysqli_result
 */
function approve_signup($event_id, $account_name, $position, $notes) {
    $connection = connect();

    // 1. Delete from Pending (Using userID)
    $query = "DELETE FROM dbpendingsignups WHERE userID = ? AND eventname = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "ss", $account_name, $event_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // 2. Add to Active
    $query2 = "INSERT INTO dbeventpersons (eventID, userID, position, notes) VALUES (?, ?, ?, ?)";
    $stmt2 = mysqli_prepare($connection, $query2);
    mysqli_stmt_bind_param($stmt2, "isss", $event_id, $account_name, $position, $notes);
    $result2 = mysqli_stmt_execute($stmt2);
    mysqli_stmt_close($stmt2);
    
    mysqli_commit($connection);
    
    if ($result2) {
         emailHandler($event_id, $account_name, 2, "Sign-up Approved.");
    }
    
    // mysqli_close($connection); // Optional, depending on if you reuse connection
    return $result2;
}

function approve_multiple_signups($event_id, $account_names, $notes = '') {
    $approved = 0;
    if (!is_array($account_names) || empty($account_names)) return 0;

    foreach ($account_names as $account_name) {
        $ok = approve_signup($event_id, $account_name, 'Volunteer', $notes);
        if ($ok) {
            $approved++;
        }
    }
    return $approved;
}

/**
 * Reject a single sign up
 * @param mixed $event_id The Event ID
 * @param mixed $account_name The Account ID/Username
 * @param mixed $position The position or 'account type' of the user who applied.
 * @param mixed $notes Any notes on the rejection.
 * @return bool True if successfull, false if the rejection failed
 */
function reject_signup($event_id, $account_name, $position, $notes) {
    $query = "DELETE from dbpendingsignups where username = ? AND eventname = ?";
    $connection = connect();
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "ss", $account_name, $event_id);
    $result = mysqli_stmt_execute($stmt);
    $result = boolval($result);
    mysqli_stmt_close($stmt);
    /*if ($result == true) Wrong number for email
    {
        emailHandler($event_id, $account_name, 2, "Sign-up DENIED.");
    }*/
    return $result;
}

function complete_event($id) {
    $event = retrieve_event2($id);
    $animal = get_animal($event["animalID"])[0];
    $date = $event["date"];
    $event["completed"] = "yes";

    $services = get_services($event["id"]);
    $length = count($services);

    for ($i = 0; $i < $length; $i++) { 
        $check = $services[$i]['name'];
        $dur = $services[$i]['duration_years'];
        if(stripos($check, "spay") !== false || stripos($check, "neuter") !== false){
            $animal["spay_neuter_done"] = "yes";
            $animal["spay_neuter_date"] = $date;
        }
        else if(stripos($check, "rabie") !== false){
            $animal["rabies_given_date"] = $date;
            $animal["rabies_due_date"] = date('Y-m-d', strtotime($date."+".$dur." years"));
        }
        else if(stripos($check, "heartworm") !== false){
            $animal["heartworm_given_date"] = $date;
            $animal["heartworm_due_date"] = date('Y-m-d', strtotime($date."+".$dur." years"));
        }
        else if(stripos($check, "distemper 1") !== false){
            $animal["distemper1_given_date"] = $date;
            $animal["distemper1_due_date"] = date('Y-m-d', strtotime($date."+".$dur." years"));
        }
        else if(stripos($check, "distemper 2") !== false){
            $animal["distemper2_given_date"] = $date;
            $animal["distemper2_due_date"] = date('Y-m-d', strtotime($date."+".$dur." years"));
        }
        else if(stripos($check, "distemper 3") !== false){
            $animal["distemper3_given_date"] = $date;
            $animal["distemper3_due_date"] = date('Y-m-d', strtotime($date."+".$dur." years"));
        }
        else if(stripos($check, "microchip") !== false){
            $animal["microchip_done"] = "yes";
        }
        else{
            $animal["notes"] = $animal["notes"]." | ".$check.": ".$date;
        }
    
    }
//    var_dump($event);
    $result = update_animal2($animal);
    $result = update_event2($event["id"], $event);
    return $result;
}

function update_animal2($animal) {
    $connection = connect();
    $id = $animal['id'];
	$odhsid = $animal["odhs_id"];
    $name = $animal["name"];
	$breed = $animal["breed"];
    $age = $animal["age"];
    $gender = $animal["gender"];
    $notes = $animal["notes"];
    $spay_neuter_done = $animal["spay_neuter_done"];
	$spay_neuter_date = $animal["spay_neuter_date"];
    if (empty($animal["spay_neuter_date"])) {
        $spay_neuter_date = '0000-00-00';
    }
    $rabies_given_date = $animal["rabies_given_date"];
    if (empty($animal["rabies_given_date"])) {
        $rabies_given_date = '0000-00-00';
    }
	$rabies_due_date = $animal["rabies_due_date"];
    if (empty($animal["rabies_due_date"])) {
        $rabies_due_date = '0000-00-00';
    }
    $heartworm_given_date = $animal["heartworm_given_date"];
    if (empty($animal["heartworm_given_date"])) {
        $heartworm_given_date = '0000-00-00';
    }
	$heartworm_due_date = $animal["heartworm_due_date"];
    if (empty($animal["heartworm_due_date"])) {
        $heartworm_due_date = '0000-00-00';
    }
	$distemper1_given_date = $animal["distemper1_given_date"];
    if (empty($animal["distemper1_given_date"])) {
        $distemper1_given_date = '0000-00-00';
    }
	$distemper1_due_date = $animal["distemper1_due_date"];
    if (empty($animal["distemper1_due_date"])) {
        $distemper1_due_date = '0000-00-00';
    }
	$distemper2_given_date = $animal["distemper2_given_date"];
    if (empty($animal["distemper2_given_date"])) {
        $distemper2_given_date = '0000-00-00';
    }
	$distemper2_due_date = $animal["distemper2_due_date"];
    if (empty($animal["distemper2_due_date"])) {
        $distemper2_due_date = '0000-00-00';
    }
	$distemper3_given_date = $animal["distemper3_given_date"];
    if (empty($animal["distemper3_given_date"])) {
        $distemper3_given_date = '0000-00-00';
    }
	$distemper3_due_date = $animal["distemper3_due_date"];
    if (empty($animal["distemper3_due_date"])) {
        $distemper3_due_date = '0000-00-00';
    }
	$microchip_done = $animal["microchip_done"];
    $query = "
        UPDATE dbanimals set odhs_id=?, name=?, breed=?, age=?, gender=?, notes=?, spay_neuter_done=?, spay_neuter_date=?, rabies_given_date=?, rabies_due_date=?, heartworm_given_date=?, heartworm_due_date=?, distemper1_given_date=?, distemper1_due_date=?, distemper2_given_date=?, distemper2_due_date=?, distemper3_given_date=?, distemper3_due_date=?, microchip_done=?
        where id=?
        ";
    $stmt = mysqli_prepare($connection, $query);
    if (!$stmt) {
        mysqli_close($connection);
        return null;
    }
    mysqli_stmt_bind_param(
        $stmt,
        "sssssssssssssssssssi",
        $odhsid,
        $name,
        $breed,
        $age,
        $gender,
        $notes,
        $spay_neuter_done,
        $spay_neuter_date,
        $rabies_given_date,
        $rabies_due_date,
        $heartworm_given_date,
        $heartworm_due_date,
        $distemper1_given_date,
        $distemper1_due_date,
        $distemper2_given_date,
        $distemper2_due_date,
        $distemper3_given_date,
        $distemper3_due_date,
        $microchip_done,
        $id
    );
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    if (!$result) {
        mysqli_close($connection);
        return null;
    }
    mysqli_commit($connection);
    mysqli_close($connection);
    return $id;
}

//There was a question mark followed by a > here

/** 
 * Gets the access level for each event to see if sign-up needs approval or not.
 * @param $event_id The id what we're querying. 
 * @return bool if true then the event requires approval for sign-up. if false then it does not.
 */
    function fetch_signup_status(int $event_id): bool
{
    $connection = connect();
    
    $query = "SELECT access FROM dbevents WHERE id = ?";
    $stmt = mysqli_prepare($connection, $query);
    if (!$stmt) {
        mysqli_close($connection);
        return false;
    }
    mysqli_stmt_bind_param($stmt, "i", $event_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Fetch the row
    $eventStatusRow = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    mysqli_close($connection);
    
    // Return true/false based on the comparison
    return ($eventStatusRow && $eventStatusRow['access'] == "Approval_Needed");
}

 function getPAttendance($eventID) {
    $conn=connect();

    $sql = "SELECT userID FROM dbeventpersons WHERE eventID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $eventID);
    $stmt->execute();
    $result = $stmt->get_result();

    $userIDs = [];

    while ($row = $result->fetch_assoc()) {
        $userIDs[] = $row['userID'];
    }

    return $userIDs;
}

function getEventCount($search = '', $search_by = 'all', $status = 'all', $date_from = '', $date_to = '') {
    global $con;

    $search = trim($search);

    $allowed_search_by = ['all', 'name', 'date', 'location'];
    if (!in_array($search_by, $allowed_search_by, true)) {
        $search_by = 'all';
    }

    $allowed_statuses = ['all', 'active', 'archived'];
    if (!in_array($status, $allowed_statuses, true)) {
        $status = 'all';
    }

    $sql = "SELECT COUNT(*) AS total FROM dbevents WHERE 1";
    $params = [];
    $types = '';

    // status filter
    if ($status === 'active') {
        $sql .= " AND archived = 0";
    } elseif ($status === 'archived') {
        $sql .= " AND archived = 1";
    }

    // search filter
    if ($search !== '') {
        $like = "%{$search}%";

        if ($search_by === 'name') {
            $sql .= " AND name LIKE ?";
            $params[] = $like;
            $types .= 's';
        } elseif ($search_by === 'date') {
            $sql .= " AND date LIKE ?";
            $params[] = $like;
            $types .= 's';
        } elseif ($search_by === 'location') {
            $sql .= " AND location LIKE ?";
            $params[] = $like;
            $types .= 's';
        } else {
            $sql .= " AND (name LIKE ? OR date LIKE ? OR location LIKE ?)";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $types .= 'sss';
        }
    }

    // date range filter
    if ($date_from !== '') {
        $sql .= " AND date >= ?";
        $params[] = $date_from;
        $types .= 's';
    }

    if ($date_to !== '') {
        $sql .= " AND date <= ?";
        $params[] = $date_to;
        $types .= 's';
    }

    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) {
        return 0;
    }

    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);
    return (int)($row['total'] ?? 0);
}


function getEventsForViewPage($search = '', $search_by = 'all', $status = 'all', $date_from = '', $date_to = '', $limit = 10, $offset = 0) {
    global $con;

    $search = trim($search);
    $limit = max(1, (int)$limit);
    $offset = max(0, (int)$offset);

    $allowed_search_by = ['all', 'name', 'date', 'location'];
    if (!in_array($search_by, $allowed_search_by, true)) {
        $search_by = 'all';
    }

    $allowed_statuses = ['all', 'active', 'archived'];
    if (!in_array($status, $allowed_statuses, true)) {
        $status = 'all';
    }

    $sql = "SELECT * FROM dbevents WHERE 1";
    $params = [];
    $types = '';

    // status filter
    if ($status === 'active') {
        $sql .= " AND archived = 0";
    } elseif ($status === 'archived') {
        $sql .= " AND archived = 1";
    } 

    // search filter
    if ($search !== '') {
        $like = "%{$search}%";

        if ($search_by === 'name') {
            $sql .= " AND name LIKE ?";
            $params[] = $like;
            $types .= 's';
        } elseif ($search_by === 'date') {
            $sql .= " AND date LIKE ?";
            $params[] = $like;
            $types .= 's';
        } elseif ($search_by === 'location') {
            $sql .= " AND location LIKE ?";
            $params[] = $like;
            $types .= 's';
        } else {
            $sql .= " AND (name LIKE ? OR date LIKE ? OR location LIKE ?)";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $types .= 'sss';
        }
    }

    // date range filter
    if ($date_from !== '') {
        $sql .= " AND date >= ?";
        $params[] = $date_from;
        $types .= 's';
    }

    if ($date_to !== '') {
        $sql .= " AND date <= ?";
        $params[] = $date_to;
        $types .= 's';
    }

    $sql .= " ORDER BY date ASC, startTime ASC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';

    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) {
        return [];
    }

    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $events = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $events[] = new Event(
            $row['id'],
            $row['name'],
            $row['date'],
            $row['startTime'],
            $row['endTime'],
            $row['date'],
            $row['description'],
            $row['capacity'],
            $row['location'],
            $row['archived'] ?? 0
        );
    }

    mysqli_stmt_close($stmt);
    return $events;
}
