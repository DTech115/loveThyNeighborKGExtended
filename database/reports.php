<?php
// Author: Daniel Leaman
// purpose: to provide functions for talking to the db to get data for the LTN reports
// date: 2026 04 10
// notes: I hope to provide good comments to help you use the functions


// -----------------------
// include statement(s)
//-------------------------------
include_once("dbinfo.php");

// ---------------------------------
// formats:
// 1. $sd and $ed stand for start date and end dates and will be in the format YYYY-MM-DD to be compatible with html forms
// 
// ----------------------------------



// -----------------------------------------
// functions for unique volunteer count
// ------------------------------------------
function countUniqueVolunteersForDateRange($sd,$ed)
// returns an int
{
    
    $con = connect();
    $querey = "SELECT count(DISTINCT `personID`) as c from `dbpersonhours` where `start_time` >= ? AND `end_time` < ?";
    $stmt = $con->prepare($querey);
    $stmt->bind_param('ss',$sd,$ed);
    $stmt->execute();
    $stmt->bind_result($count);
    
    while ($stmt->fetch())
        {
           $num = $count;
        }
    $con->close();
    return $num;
}
function volunteerUniqueEventsForDateRange($sd,$ed)
// returns a 2d array in the format [[string id, string first, string last, int events participated in, int before_may]...]
{
    $con = connect();

    $querey = "SELECT 
                    p.id,
                    p.first_name,
                    p.last_name,
                    COUNT(h.eventID) as `m`,
                    EXISTS (
                        SELECT 1
                        FROM `dbpersonhours` as `h2`
                        WHERE h2.personID = h.personID
                          AND h2.start_time < '2026-05-01'
                    ) as before_may
                FROM `dbpersonhours` as `h`
                LEFT JOIN `dbpersons` as `p` on h.personID = p.id
                WHERE h.start_time >= ? AND h.start_time < ?
                GROUP BY h.personID, p.id, p.first_name, p.last_name
                ORDER BY m DESC";

    $stmt = $con->prepare($querey);
    $stmt->bind_param('ss',$sd,$ed);
    $stmt->execute();
    $stmt->bind_result($id,$f,$l,$e,$beforeMay);

    $rows = [];
    while ($stmt->fetch())
    {
        $rows[] = [$id,$f,$l,$e,$beforeMay];
    }

    $con->close();
    return $rows;
}
//---------------------------------------
// functions for inactive volunteers
// --------------------------------------
function getInactiveVolunteers()
// no input as it is allways to be one year in the past
// returns a 2d array in the format [[str first, str last, str date of last event (yyyy-mm-dd)]...]
{
// var_dump(date('Y-m-d', strtotime('-1 year')));
    $con = connect();
    $querey = "SELECt p.id, p.first_name, p.last_name, DATE_FORMAT(max(h.end_time), '%Y-%m-%d') 
                FROM `dbpersons` AS `p` 
                LEFT JOIN `dbpersonhours` AS `h` ON p.id = h.personID 
                WHERE p.id NOT IN ( SELECT DISTINCT(h.personID) FROM dbpersonhours as h where h.end_time >= ? ) 
                GROUP BY p.id, p.first_name, p.last_name
                ORDER BY MAX(h.end_time) IS NULL, MAX(h.end_time) DESC;";

   //-----------------------------------------------
    //Similar query, but with no username included
    //-----------------------------------------------
   /* $querey = "SELECt p.first_name, p.last_name, DATE_FORMAT(max(h.end_time), '%Y-%m-%d') 
                FROM `dbpersons` as `p` left join 
                `dbpersonhours` as `h` on p.id = h.personID 
                WHERE p.id NOT IN ( SELECT DISTINCT(h.personID) FROM dbpersonhours as h where h.end_time >= ? ) 
                GROUP BY p.first_name, p.last_name
                ORDER BY MAX(h.end_time) IS NULL, MAX(h.end_time) DESC;";
    */
    $stmt = $con->prepare($querey);
    $oneyearago = date('Y-m-d', strtotime('-1 year'));
    $stmt->bind_param('s',$oneyearago);
    $stmt->execute();
    $stmt->bind_result($id, $f,$l,$d); //added $id
    $rows = [];
    while ($stmt->fetch())
    {
        $rows[] = [$id, $f,$l,$d];
    }
    $con->close();
    return $rows;
}
// --------------------------------------
// functions for total volunteer hours
// ---------------------------------------

// use function roleHoursForDateRange($sd,$ed) in dbperson hours to break it down by role
// it uses the format [[$r(str role),$h(int hours),$m(int minutes)]....]

function totalHoursForDateRange($sd,$ed)
// retunrs array in format [int hours, int minutes]
{
    $con = connect();
    $querey = "select sum(TIMESTAMPDIFF(MINUTE, h.start_time, h.end_time)) as `m` from dbpersonhours as h WHERE h.start_time >= ? AND h.end_time < ?";
    $stmt = $con->prepare($querey);
    $stmt->bind_param('ss',$sd,$ed);
    $stmt->execute();
    $stmt->bind_result($m);

    while ($stmt->fetch())
    {
       $minutes = $m;
    }
    $con->close();
    $h = intdiv((int)$minutes, 60);
    $m = $minutes % 60;
    return [$h,$m];
}
// ------------------------------------------
// functions for hour category summary
// ------------------------------------------
function hoursPerRoleAllTime($roles)
// $roles is to be an array of the role ids as ints
// returns a 2d array in the format [[$r(str role),$h(int hours),$m(int minutes)]....]
{
    for ($i = 0; $i < sizeof($roles); $i++) // makes sure that everything is an int to prevent injection 
        {
            $isi = is_int($roles[$i]);
            if (!$isi)
                {
                    return []; // if any element is not an int, return empty array
                }
        }
    $placeholders = implode(',', array_fill(0, count($roles), '?')); // generate a string of ?,?... equal to the number of ids in $roles

    $con = connect();
    $querey = "SELECT r.role, sum(TIMESTAMPDIFF(MINUTE, h.start_time, h.end_time)) as `m` FROM `dbpersonhours` as `h` left join `dbroles` as `r` on h.roleID = r.role_id WHERE r.role_id in (" . $placeholders . ") GROUP BY r.role order by m desc";
    $stmt = $con->prepare($querey);
    $stmt->execute($roles);
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

function hoursPerRoleWithDateRange($roles, $sd, $ed)
// $roles is to be an array of role ids as ints
// $sd and $ed are datetime strings
// returns a 2d array in the format [[$r(str role),$h(int hours),$m(int minutes)]....]
{
    if (!is_array($roles) || count($roles) === 0) {
        return [];
    }

    for ($i = 0; $i < sizeof($roles); $i++) {
        if (!is_int($roles[$i])) {
            return [];
        }
    }

    $placeholders = implode(',', array_fill(0, count($roles), '?'));

    $con = connect();
    $querey = "SELECT 
                    r.role,
                    COALESCE(SUM(TIMESTAMPDIFF(MINUTE, h.start_time, h.end_time)), 0) as `m`
               FROM `dbroles` as `r`
               LEFT JOIN `dbpersonhours` as `h`
                    ON h.roleID = r.role_id
                    AND h.start_time >= ?
                    AND h.end_time < ?
               WHERE r.role_id IN ($placeholders)
               GROUP BY r.role, r.role_id
               ORDER BY m DESC";

    $stmt = $con->prepare($querey);

    $types = 'ss' . str_repeat('i', count($roles));
    $params = array_merge([$sd, $ed], $roles);

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->bind_result($r, $tm);

    $rows = [];
    while ($stmt->fetch()) {
        $h = intdiv((int)$tm, 60);
        $m = ((int)$tm % 60);
        $rows[] = [$r, $h, $m];
    }

    $con->close();
    return $rows;
}

// ------------------------------------------
// functions for service letter report
// ------------------------------------------

function getVolunteerNameById($id)
// returns array in format [string first_name, string last_name]
// returns ['', ''] if not found
{
    $con = connect();
    $querey = "SELECT first_name, last_name FROM dbpersons WHERE id = ?";
    $stmt = $con->prepare($querey);
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $stmt->bind_result($f, $l);

    $row = ['', ''];
    while ($stmt->fetch())
    {
        $row = [$f, $l];
    }

    $con->close();
    return $row;
}

function getVolunteerServiceHoursForDateRange($id, $sd, $ed)
// returns array in format [int total_minutes, int volunteer_days]
{
    $con = connect();
    $querey = "SELECT 
                    COALESCE(SUM(TIMESTAMPDIFF(MINUTE, h.start_time, h.end_time)), 0) as total_minutes,
                    COUNT(DISTINCT DATE(h.start_time)) as volunteer_days
               FROM dbpersonhours as h
               WHERE h.personID = ?
                 AND h.start_time >= ?
                 AND h.start_time < ?";
    $stmt = $con->prepare($querey);
    $stmt->bind_param('sss', $id, $sd, $ed);
    $stmt->execute();
    $stmt->bind_result($tm, $vd);

    $row = [0, 0];
    while ($stmt->fetch())
    {
        $row = [(int)$tm, (int)$vd];
    }

    $con->close();
    return $row;
}

function getVolunteerRolesForDateRange($id, $sd, $ed)
// returns array in format [string role, string role, ...]
{
    $con = connect();
    $querey = "SELECT DISTINCT r.role
               FROM dbpersonhours as h
               LEFT JOIN dbroles as r ON h.roleID = r.role_id
               WHERE h.personID = ?
                 AND h.start_time >= ?
                 AND h.start_time < ?
                 AND r.role IS NOT NULL
               ORDER BY r.role ASC";
    $stmt = $con->prepare($querey);
    $stmt->bind_param('sss', $id, $sd, $ed);
    $stmt->execute();
    $stmt->bind_result($role);

    $rows = [];
    while ($stmt->fetch())
    {
        $rows[] = $role;
    }

    $con->close();
    return $rows;
}