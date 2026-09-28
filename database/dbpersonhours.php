<?php
/*
 * Author: Daniel Leaman
 * purpose: to manage the functions necessary to interact with the dbpersonhours table in the 
 *  database for LTN
 * 
*/

include_once('dbinfo.php');

/* this will retun nothing.
 * this should add a person to a scheduled event
 */ 
function registerPersonForEvent($eventid,$personid,$roleid)
{
    $con = connect();
    $querey = "INSERT IGNORE INTO `dbpersonhours` (`personID`, `eventID`, `roleID`, `start_time`, `end_time`) VALUES (?, ?, ?, NULL, NULL)";
    $stmt = $con->prepare($querey);
    if ($stmt)
        {
            $stmt->bind_param("sii", $personid,$eventid,$roleid);
            $stmt->execute();
        }
    mysqli_close($con);
}

//unregister for event I want it to un reg to no need role
function unregisterPersonForEvent($eventid,$personid)
{
    $con = connect();
    $querey = "DELETE FROM `dbpersonhours` WHERE `dbpersonhours`.`personID` = ? AND `dbpersonhours`.`eventID` = ?";
    $stmt = $con->prepare($querey);
    if ($stmt)
        {
            $stmt->bind_param("si", $personid,$eventid,);
            $stmt->execute();
        }
    mysqli_close($con);
}

//update start time
//this one sets it to now
function updateStartTime($eventid,$personid,$roleid)
{
    $con = connect();
    $querey = "UPDATE `dbpersonhours` SET `start_time` = NOW() WHERE `eventID` = ? AND `personID` = ? AND `roleID` = ?";
    $stmt = $con->prepare($querey);
    if ($stmt)
        {
            $stmt->bind_param("isi", $eventid,$personid,$roleid);
            $stmt->execute();
        }
    mysqli_close($con);
}

//update end time
function updateEndTime($eventid,$personid,$roleid)
{
    $con = connect();
    $querey = "UPDATE `dbpersonhours` SET `end_time` = NOW() WHERE `eventID` = ? AND `personID` = ? AND `roleID` = ?";
    $stmt = $con->prepare($querey);
    if ($stmt)
        {
            $stmt->bind_param("isi", $eventid,$personid,$roleid);
            $stmt->execute();
        }
    mysqli_close($con);
}

//calc person hours
function calcPersonHours($personid)
{
    $con = connect();
    $querey = "SELECT sum(TIMESTAMPDIFF(MINUTE, `start_time`, `end_time`)) FROM `dbpersonhours` WHERE `personID` = ?";
    $stmt = $con->prepare($querey);
    if ($stmt)
        {
            $stmt->bind_param("s", $personid);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $hours = $result->fetch_row();
            
            return (int)$hours[0];
        }
    mysqli_close($con);
}

//calculate the top 10 users with the highest number of volunteer hours
function calcTop10()
{
     $con = connect();
    $querey = "SELECT p.first_name,p.last_name, sum(TIMESTAMPDIFF(MINUTE, h.start_time, h.end_time)) AS `m` FROM `dbpersonhours` AS `h` LEFT JOIN `dbpersons` AS `p` ON h.personID = p.id GROUP BY p.id, p.first_name, p.last_name ORDER BY m DESC LIMIT 10";
    $stmt = $con->prepare($querey);
    $stmt->execute();
    $stmt->bind_result($f,$l,$tm);
    $rows = [];
    while ($stmt->fetch())
        {
            $h = intdiv((int)$tm, 60);
            $m = (int)$tm % 60;
            $rows[] = [$f,$l,$h,$m];
        }
    $con->close();
    return $rows;
}

function calcTotalHoursForRange($startDateTime, $endDateTime)
{
    $con = connect();
    $querey = "SELECT COALESCE(SUM(TIMESTAMPDIFF(MINUTE, `start_time`, `end_time`)), 0) FROM `dbpersonhours` WHERE `start_time` >= ? AND `start_time` < ? AND `end_time` IS NOT NULL";
    $stmt = $con->prepare($querey);

    if ($stmt) {
        $stmt->bind_param("ss", $startDateTime, $endDateTime);
        $stmt->execute();
        $result = $stmt->get_result();
        $minutes = $result->fetch_row();
        $stmt->close();
        $con->close();

        return ((int)$minutes[0]) / 60;
    }

    mysqli_close($con);
    return 0;
}

function roleHoursForDateRange($sd,$ed)
{
    $con = connect();
    $querey = "SELECT r.role, sum(TIMESTAMPDIFF(MINUTE, h.start_time, h.end_time)) AS `m` FROM `dbpersonhours` AS `h` LEFT JOIN `dbroles` AS `r` ON h.roleID = r.role_id WHERE h.start_time >= ? AND h.start_time <  ? GROUP BY r.role ORDER BY m DESC";
    $stmt = $con->prepare($querey);
    $stmt->bind_param('ss',$sd,$ed);
    $stmt->execute();
    $stmt->bind_result($r,$tm);
    $rows = [];
    while ($stmt->fetch())
        {
            $h = intdiv((int)$tm,60);
            $m = ((int)$tm % 60);
            $rows[] = [$r,$h,$m];
        }
    $con->close();
    return $rows;
}

//calc event hours

//get person event hours



/*
 $persons = array();
            while ($row = $result->fetch_assoc()) {
                $persons[] = $row;
            }
            mysqli_close($con);
            return $persons;
        
        mysqli_close($con);
    return [];
    */

//get event partisipants
function getEventParticipants($eventid)
{
    $con = connect();
    $querey = "SELECT DISTINCT`personID` FROM `dbpersonhours` WHERE `eventID` = ?";
    $stmt = $con->prepare($querey);
    if ($stmt)
        {
            $stmt->bind_param("i", $eventid);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $persons = array();
            $persons = $result->fetch_all();
            for ($i = 0; $i < sizeof($persons); $i++)
                {
                    $persons[$i] = $persons[$i][0];
                }
            return $persons;
        }
    mysqli_close($con);
}

//allows admins to adjust volunteer hours
function adjustVolunteerHours($eventid, $personid, $roleid, $startTime, $endTime) 
{
    $con = connect();

    $query = "
        INSERT INTO dbpersonhours (personID, eventID, roleID, start_time, end_time)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            start_time = ?,
            end_time = ?
    ";

    $stmt = $con->prepare($query);

    if (!$stmt) {
        mysqli_close($con);
        return false;
    }

    $stmt->bind_param(
        "siissss",
        $personid, $eventid, $roleid, $startTime, $endTime,
        $startTime, $endTime
    );

    $stmt->execute();

    $success = ($stmt->affected_rows >= 0);

    $stmt->close();
    mysqli_close($con);

    return $success;
}
function getActiveUsers(){
    $con = connect();
    $query = "SELECT DISTINCT `personID` FROM `dbpersonhours` join `dbevents` on `dbpersonhours`.`eventID` = `dbevents`.`id` where `dbevents`.`date` >= CURDATE()";    
    $result = mysqli_query($con,$query);
    mysqli_close($con);
    return $result;
    }
    
