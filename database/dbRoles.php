<?php
include_once('dbinfo.php');

/**
 * Role management functions.
 */

function add_role($role_name, $role_description) {
    $con = connect();
    $stmt = $con->prepare("INSERT INTO dbroles (`role`, `role_description`) VALUES (?, ?)");
    if (!$stmt) {
        mysqli_close($con);
        return false;
    }
    $stmt->bind_param("ss", $role_name, $role_description);
    $ok = $stmt->execute();
    $id = $con->insert_id;
    $stmt->close();
    mysqli_close($con);
    return $ok ? $id : false;
}

function update_role_description($role_id, $role_description) {
    $con = connect();
    $stmt = $con->prepare("UPDATE dbroles SET role_description = ? WHERE role_id = ?");
    if (!$stmt) {
        mysqli_close($con);
        return false;
    }
    $stmt->bind_param("si", $role_description, $role_id);
    $ok = $stmt->execute();
    $stmt->close();
    mysqli_close($con);
    return $ok;
}
// --------------------------------------
// Daniel's person_roles functions
// (No, I was not the one who broke with the naming plan)
// 
// I tried to make the names as intuitive as possible
// ------------------------------------
// function getRolesForPersonEvent($personId, $eventId)
// // this returns the roleID, not the name.
// {
    
//     $con = connect();
//     $stmt = $con->prepare("SELECT `role_id` FROM `person_roles` WHERE `person_id` = ? AND `event_id` = ?");
//     $stmt->bind_param("si", $personId, $eventId);
//     $stmt->execute();
//     $stmt->bind_result($row);
//     $rows = [];
//     while ($stmt->fetch())
//         {
//             $rows[] = $row;
//         }
//     $con->close();
//     return $rows;
// }
function getRolesForPerson($personID)
//this will return an array of the uniquie roles that a person has done
{
    $con = connect();
    $stmt = $con->prepare("SELECT DISTINCT `role_id` FROM `person_roles` WHERE `person_id` = ?");
    $stmt->bind_param("s", $personID);
    $stmt->execute();
    $stmt->bind_result($row);
    $rows = [];
    while ($stmt->fetch())
        {
            $rows[] = $row;
        }    
    $con->close();
    return $rows;
}

// function addPersonRoleToEvent($personID,$roleID,$eventID)
// // no return adds to the table 
// {
//     $con = connect();
//     $stmt = $con->prepare("INSERT INTO `person_roles` (`person_id`, `role_id`, `event_id`) VALUES (?, ?, ?)");
//      $stmt->bind_param("sii", $personID,$roleID,$eventID);
//     $stmt->execute();
//     $con->close();
// }

// function addPersonRoleToEvent($personID, $roleID, $eventID) {
//     $con = connect();

//     $check = $con->prepare("
//         SELECT 1
//         FROM person_roles
//         WHERE person_id = ? AND role_id = ? AND event_id = ?
//         LIMIT 1
//     ");

//     if (!$check) {
//         mysqli_close($con);
//         return false;
//     }

//     $check->bind_param("sii", $personID, $roleID, $eventID);
//     $check->execute();
//     $result = $check->get_result();

//     if ($result && $result->num_rows > 0) {
//         $check->close();
//         mysqli_close($con);
//         return true;
//     }

//     $check->close();

//     $stmt = $con->prepare("
//         INSERT INTO person_roles (person_id, role_id, event_id)
//         VALUES (?, ?, ?)
//     ");

//     if (!$stmt) {
//         mysqli_close($con);
//         return false;
//     }

//     $stmt->bind_param("sii", $personID, $roleID, $eventID);
//     $ok = $stmt->execute();

//     $stmt->close();
//     mysqli_close($con);

//     return $ok;
// }

// function removePersonRoleFromEvent($personID,$roleID,$eventID)
// // no return, just removes
// {
//     $con = connect();
//     $stmt = $con->prepare("DELETE FROM `person_roles` WHERE `person_id` = ? AND `role_id` = ? AND `event_id` = ?");
//     $stmt->bind_param("sii", $personID,$roleID,$eventID);
//     $stmt->execute();
//     $con->close();
// }

function getPersonsForRoleEvent($roleID,$eventID)
// this returns the personID
{
    $con = connect();
    $stmt = $con->prepare("SELECT `person_id` FROM `person_roles` WHERE `role_id` = ? AND `event_id` = ?");
    $stmt->bind_param("ii", $roleID, $eventID);
    $stmt->execute();
    $stmt->bind_result($row);
    $rows = [];
    while ($stmt->fetch())
        {
            $rows[] = $row;
        }    
    $con->close();
    return $rows;
    
}

function getPersonsForRole($roleID)
// returns an array of personIDs
{
    $con = connect();
    $stmt = $con->prepare("SELECT DISTINCT `person_id` FROM `person_roles` WHERE `role_id` = ?");
    $stmt->bind_param("i", $roleID);
    $stmt->execute();
    $stmt->bind_result($row);
    $rows = [];
    while ($stmt->fetch())
        {
            $rows[] = $row;
        }    
    $con->close();
    return $rows;
}


//---------------------------------------
// end Daniel's functions
// -------------------------------

// function delete_role($role_id) { //no longer use this one
//     $con = connect();

//     $stmt = $con->prepare("DELETE FROM person_roles WHERE role_id = ?");
//     if ($stmt) {
//         $stmt->bind_param("i", $role_id);
//         $stmt->execute();
//         $stmt->close();
//     }

//     $stmt = $con->prepare("DELETE FROM dbroles WHERE role_id = ?");
//     if (!$stmt) {
//         mysqli_close($con);
//         return false;
//     }
//     $stmt->bind_param("i", $role_id);
//     $ok = $stmt->execute();
//     $stmt->close();
//     mysqli_close($con);
//     return $ok;
// }

function get_roles() {
    $con = connect();
    $result = mysqli_query($con, "SELECT `role_id`, `role`, `role_description` FROM `dbroles` ORDER BY `role`");
    if (!$result) {
        mysqli_close($con);
        return [];
    }
    $roles = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $roles[] = $row;
    }
    mysqli_close($con);
    return $roles;
}

function get_role_description($role_id) {
    $con = connect();
    $stmt = $con->prepare("SELECT role_description FROM dbroles WHERE role_id = ?");
    if (!$stmt) {
        mysqli_close($con);
        return null;
    }
    $stmt->bind_param("i", $role_id);
    $stmt->execute();
    $stmt->bind_result($desc);
    $stmt->fetch();
    $stmt->close();
    mysqli_close($con);
    return $desc;
}