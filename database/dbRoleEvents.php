<?php

include_once('dbinfo.php');

/**
*   Role-Events functions
*/

// for now, adds given eventID & roleID with capacity to this db for future use
// Daniel modified to have a default value for notes so that it would not break any current uses,
// but allows us to add it when we make it.
function addRoleToEvent($eventID, $roleID, $capacity, $notes = "") {
    $con = connect();

    $query = "INSERT INTO dbroleevents (eventID, roleID, capacity, notes) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "iis", $eventID, $roleID, $capacity, $notes);
    $result = mysqli_stmt_execute($stmt);

    if (!$result) {
        mysqli_stmt_close($stmt);
        mysqli_close($con);
        return null;
    }

    mysqli_commit($con);
    mysqli_stmt_close($stmt);
    mysqli_close($con);
    return true;
}

// need function to delete 
function removeRoleEvent($roleID,$eventID)
{
    $con = connect();
    $stmt = $con->prepare("DELETE FROM `dbroleevents` WHERE `roleID` = ? AND `eventID` = ?");
    $stmt->bind_param("ii", $roleID, $eventID);
    $stmt->execute();
    $con->close();
}

// notes feild geters / setters
// these will be used to update the per-event description, ususally the time info
function getNotesForRoleEvent($roleID,$eventID)
// returns a string
{
    $con = connect();
    $stmt = $con->prepare("SELECT `notes` FROM `dbroleevents` WHERE `roleID` = ? AND `eventID` = ?");
    $stmt->bind_param("ii", $roleID, $eventID);
    $stmt->execute();
    $stmt->bind_result($notes);
    $stmt->fetch();
    $con->close();
    return $notes;
}

function updateNotesForRoleEvent($notes,$roleID,$eventID)
// no return
{
    $con = connect();
    $stmt = $con->prepare("UPDATE `dbroleevents` SET `notes` = ? WHERE `roleID` = ? AND `eventID` = ?");
    $stmt->bind_param("sii", $notes, $roleID, $eventID);
    $stmt->execute();
    $con->close();
}





// ===== might need to change to accommodate dbRoles better? =====
//
// for now, grabs the eventID, roleID, cap, & role description
// function getRolesForEvent($eventID) {
//     $con = connect();
//     // role_id is coming from dbroles.sql
//     $query = "SELECT re.eventID, re.roleID, re.capacity, r.role, r.role_description
//                 FROM dbroleevents re 
//                 JOIN dbroles r 
//                 ON re.roleID = r.role_id     
//                 WHERE re.eventID = ?";

//     $stmt = mysqli_prepare($con, $query);
//     mysqli_stmt_bind_param($stmt, "i", $eventID);
//     mysqli_stmt_execute($stmt);
//     $result = mysqli_stmt_get_result($stmt);

//     $theRoleEvents = array();

//     while ($resultRow = mysqli_fetch_assoc($result)) {
//         $roleEvent = array(
//             "eventID" => $resultRow['eventID'],
//             "roleID" => $resultRow['roleID'],
//             "capacity" => $resultRow['capacity'],
//             "role_name" => $resultRow['role'],
//             "role_description" => $resultRow['role_description']
//             //"currentSignups" => $resultRow['currentSignups']
//         );

//         $theRoleEvents[] = $roleEvent;
//     }

//     mysqli_stmt_close($stmt);
//     mysqli_close($con);

//     return $theRoleEvents;
// }

function getRolesForEvent($eventID) {
    $con = connect();

    $stmt = $con->prepare("
        SELECT
            re.eventID AS eventID,
            re.roleID AS roleID,
            re.capacity AS capacity,

            r.role AS role_name,
            r.role_description AS role_description,
            r.shift_group AS shift_group,

            COUNT(pr.person_id) AS current_signups,
            (re.capacity - COUNT(pr.person_id)) AS remaining_spots

        FROM dbroleevents re

        JOIN dbroles r
            ON re.roleID = r.role_id

        LEFT JOIN person_roles pr
            ON pr.event_id = re.eventID
            AND pr.role_id = re.roleID

        WHERE re.eventID = ?

        GROUP BY
            re.eventID,
            re.roleID,
            re.capacity,
            r.role,
            r.role_description,
            r.shift_group

        ORDER BY
            r.shift_group,
            r.role
    ");

    if (!$stmt) {
        mysqli_close($con);
        return [];
    }

    $stmt->bind_param("i", $eventID);
    $stmt->execute();

    $result = $stmt->get_result();

    $roles = [];

    while ($row = $result->fetch_assoc()) {
        $roles[] = $row;
    }

    $stmt->close();
    mysqli_close($con);

    return $roles;
}

function getRoleForEvent($eventID, $roleID) {
    $con = connect();

    $stmt = $con->prepare("
        SELECT
            re.eventID AS eventID,
            re.roleID AS roleID,
            re.capacity AS capacity,
            r.role AS role_name,
            r.role_description AS role_description,
            r.shift_group AS shift_group,
            COUNT(pr.person_id) AS current_signups,
            (re.capacity - COUNT(pr.person_id)) AS remaining_spots
        FROM dbroleevents re
        JOIN dbroles r
            ON re.roleID = r.role_id
        LEFT JOIN person_roles pr
            ON pr.event_id = re.eventID
            AND pr.role_id = re.roleID
        WHERE re.eventID = ? AND re.roleID = ?
        GROUP BY
            re.eventID,
            re.roleID,
            re.capacity,
            r.role,
            r.role_description,
            r.shift_group
        LIMIT 1
    ");

    if (!$stmt) {
        mysqli_close($con);
        return null;
    }

    $stmt->bind_param("ii", $eventID, $roleID);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;

    $stmt->close();
    mysqli_close($con);

    return $row;
}

function addPersonRoleToEvent($personID, $roleID, $eventID) {
    $con = connect();

    $check = $con->prepare("
        SELECT 1
        FROM person_roles
        WHERE person_id = ? AND role_id = ? AND event_id = ?
        LIMIT 1
    ");

    if (!$check) {
        mysqli_close($con);
        return false;
    }

    $check->bind_param("sii", $personID, $roleID, $eventID);
    $check->execute();
    $result = $check->get_result();

    if ($result && $result->num_rows > 0) {
        $check->close();
        mysqli_close($con);
        return true;
    }

    $check->close();

    $stmt = $con->prepare("
        INSERT INTO person_roles (person_id, role_id, event_id)
        VALUES (?, ?, ?)
    ");

    if (!$stmt) {
        mysqli_close($con);
        return false;
    }

    $stmt->bind_param("sii", $personID, $roleID, $eventID);
    $ok = $stmt->execute();

    $stmt->close();
    mysqli_close($con);

    return $ok;
}

// grabs capacity from given role-event
function getRoleEventCapacity($eventID, $roleID) {
    $con = connect();

    $query = "SELECT capacity FROM dbroleevents WHERE eventID = ? AND roleID = ?";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "ii", $eventID, $roleID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    mysqli_close($con);

    if (!$row) {
        return null;
    }

    return $row['capacity'];
}

// updates the capacity from a given role-event
function updateRoleEventCapacity($eventID, $roleID, $capacity) {
    $con = connect();

    $query = "UPDATE dbroleevents SET capacity = ? WHERE eventID = ? AND roleID = ?";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "iii", $capacity, $eventID, $roleID);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$result) {
        mysqli_close($con);
        return null;
    }

    mysqli_commit($con);
    mysqli_close($con);

    return true;
}

// adds the total role capacity[s] for an event
function getEventCapacity($eventID) {
    $con = connect();

    $query = "SELECT SUM(capacity) AS totalCap FROM dbroleevents WHERE eventID = ?";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "i", $eventID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    mysqli_close($con);

    if (!$row || $row['totalCap'] === null) {
        return 0;
    }

    return $row['totalCap'];
}

// Save the selected roles
function save_event_roles($eventID, $roles) {
    $connection = connect();
    $eventID = (int)$eventID;

    foreach ($roles as $roleID => $count) {
        $roleID = (int)$roleID;
        $count = (int)$count;

        if ($count > 0) {
            $query = "
                INSERT INTO dbroleevents (eventID, roleID, capacity)
                VALUES (?, ?, ?)
            ";
            $stmt = mysqli_prepare($connection, $query);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "iii", $eventID, $roleID, $count);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }
    }

    mysqli_close($connection);
}

function personAlreadyHasRoleForEvent($personID, $roleID, $eventID) {
    $con = connect();

    $stmt = $con->prepare("
        SELECT 1
        FROM person_roles
        WHERE person_id = ? AND role_id = ? AND event_id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        mysqli_close($con);
        return false;
    }

    $stmt->bind_param("sii", $personID, $roleID, $eventID);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result && $result->num_rows > 0;

    $stmt->close();
    mysqli_close($con);

    return $exists;
}

function personHasShiftGroupForEvent($personID, $eventID, $shift_group) {
    $con = connect();

    $stmt = $con->prepare("
        SELECT 1
        FROM person_roles pr
        JOIN dbroles r ON pr.role_id = r.role_id
        WHERE pr.person_id = ? AND pr.event_id = ? AND r.shift_group = ?
        LIMIT 1
    ");

    if (!$stmt) {
        mysqli_close($con);
        return false;
    }

    $stmt->bind_param("sis", $personID, $eventID, $shift_group);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result && $result->num_rows > 0;

    $stmt->close();
    mysqli_close($con);

    return $exists;
}

function removePersonRolesFromEvent($personID, $eventID) {
    $con = connect();

    $stmt = $con->prepare("
        DELETE FROM person_roles
        WHERE person_id = ? AND event_id = ?
    ");

    if (!$stmt) {
        mysqli_close($con);
        return false;
    }

    $stmt->bind_param("si", $personID, $eventID);
    $ok = $stmt->execute();

    $stmt->close();
    mysqli_close($con);

    return $ok;
}

function getRolesForPersonEvent($personID, $eventID)
{
    $con = connect();

    $stmt = $con->prepare("
        SELECT r.role
        FROM person_roles pr
        JOIN dbroles r ON pr.role_id = r.role_id
        WHERE pr.person_id = ?
        AND pr.event_id = ?
    ");

    $stmt->bind_param("si", $personID, $eventID);
    $stmt->execute();
    $result = $stmt->get_result();

    $roles = [];

    while ($row = $result->fetch_assoc()) {
        $roles[] = $row['role'];
    }

    $con->close();
    return $roles;
}

function get_event_role_capacities($eventID) {
    $con = connect();
    $stmt = $con->prepare("
        SELECT roleID, capacity
        FROM dbroleevents
        WHERE eventID = ?
    ");
    $stmt->bind_param("i", $eventID);
    $stmt->execute();
    $result = $stmt->get_result();


    $caps = [];
    while ($row = $result->fetch_assoc()) {
        $caps[(int)$row['roleID']] = (int)$row['capacity'];
    }


    $stmt->close();
    $con->close();
    return $caps;
}


function get_event_role_signup_counts($eventID) {
    $con = connect();
    $stmt = $con->prepare("
        SELECT role_id, COUNT(*) AS assigned_count
        FROM person_roles
        WHERE event_id = ?
        GROUP BY role_id
    ");
    $stmt->bind_param("i", $eventID);
    $stmt->execute();
    $result = $stmt->get_result();


    $counts = [];
    while ($row = $result->fetch_assoc()) {
        $counts[(int)$row['role_id']] = (int)$row['assigned_count'];
    }


    $stmt->close();
    $con->close();
    return $counts;
}


function count_people_signed_up_for_role_event($eventID, $roleID) {
    $con = connect();
    $stmt = $con->prepare("
        SELECT COUNT(*)
        FROM person_roles
        WHERE event_id = ? AND role_id = ?
    ");
    $stmt->bind_param("ii", $eventID, $roleID);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();


    $stmt->close();
    $con->close();


    return (int)$count;
}


function upsert_role_event_capacity($eventID, $roleID, $capacity) {
    $con = connect();


    $check = $con->prepare("
        SELECT COUNT(*)
        FROM dbroleevents
        WHERE eventID = ? AND roleID = ?
    ");
    $check->bind_param("ii", $eventID, $roleID);
    $check->execute();
    $check->bind_result($exists);
    $check->fetch();
    $check->close();


    if ($exists > 0) {
        $stmt = $con->prepare("
            UPDATE dbroleevents
            SET capacity = ?
            WHERE eventID = ? AND roleID = ?
        ");
        $stmt->bind_param("iii", $capacity, $eventID, $roleID);
    } else {
        $stmt = $con->prepare("
            INSERT INTO dbroleevents (eventID, roleID, capacity)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iii", $eventID, $roleID, $capacity);
    }


    $success = $stmt->execute();
    $stmt->close();
    $con->close();


    return $success;
}




function get_role_name_by_id($roleID) {
    $con = connect();
    $stmt = $con->prepare("
        SELECT role
        FROM dbroles
        WHERE role_id = ?
    ");
    $stmt->bind_param("i", $roleID);
    $stmt->execute();
    $stmt->bind_result($roleName);
    $stmt->fetch();


    $stmt->close();
    $con->close();


    return $roleName ?: '';
}

?>