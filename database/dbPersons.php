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
include_once('dbinfo.php');
include_once(dirname(__FILE__) . '/../domain/Person.php');


/*
 * add a person to dbPersons table: if already there, return false
 */
function add_person($person) {
    $con = connect();
    $query = "SELECT * FROM dbpersons WHERE id = ?";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "s", $person->get_id());
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 0) {
        $insert_query = 'INSERT INTO dbpersons (
            id, first_name, last_name, phone_number, email, email_prefs,
            birthday, `t-shirt_size`, state, city, street_address, zip_code,
            emergency_contact_first_name, emergency_contact_phone, emergency_contact_relation,
            archived, password, contact_num, contact_method, type, status,
            photo_release, community_service, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        
        $stmt_insert = mysqli_prepare($con, $insert_query);
        mysqli_stmt_bind_param($stmt_insert, "sssssssssssssssisssssiis",
            $person->get_id(),
            $person->get_first_name(),
            $person->get_last_name(),
            $person->get_phone1(),
            $person->get_email(),
            $person->get_email_prefs(),
            $person->get_birthday(),
            $person->get_t_shirt_size(),
            $person->get_state(),
            $person->get_city(),
            $person->get_street_address(),
            $person->get_zip_code(),
            $person->get_emergency_contact_first_name(),
            $person->get_emergency_contact_phone(),
            $person->get_emergency_contact_relation(),
            $person->get_archived(),
            $person->get_password(),
            $person->get_contact_num(),
            $person->get_contact_method(),
            $person->get_type(),
            $person->get_status(),
            $person->get_photo_release(),
            $person->get_community_service(),
            $person->get_notes()
        );

        if (mysqli_stmt_execute($stmt_insert)) {
            mysqli_stmt_close($stmt_insert);
            mysqli_stmt_close($stmt);
            mysqli_close($con);
            return true;
        } else {
            die("Error: " . mysqli_error($con));
        }
    }

    mysqli_stmt_close($stmt);
    mysqli_close($con);
    return false;
}

function add_hours_to_person($person_id, $hours) {
    $con = connect();

    $hours_value = intval($hours);

    $query = "UPDATE dbpersons SET total_hours_volunteered = total_hours_volunteered + ? WHERE id = ?";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "is", $hours_value, $person_id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    mysqli_close($con);
    return $result;
}

function remove_person($id) {
    $con = connect();

    $query = 'SELECT * FROM dbpersons WHERE id = ?';
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "s", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    if ($result == null || mysqli_num_rows($result) == 0) {
        mysqli_close($con);
        return false;
    }

    $query = 'DELETE FROM dbpersons WHERE id = ?';
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "s", $id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    mysqli_close($con);
    return true;
}

/*
 * @return a Person from dbPersons table matching a particular id.
 * if not in table, return false
 */
function retrieve_person($id) {
    $con = connect();
    $query = "SELECT * FROM dbpersons WHERE id = ?";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "s", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) !== 1) {
        mysqli_stmt_close($stmt);
        mysqli_close($con);
        return false;
    }
    $result_row = mysqli_fetch_assoc($result);
    $thePerson = make_a_person($result_row);
    mysqli_stmt_close($stmt);
    mysqli_close($con);
    return $thePerson;
}

/*Kiosk test function*/
/*function retrieve_person($id) {
    $con = connect();
    $escaped_id = mysqli_real_escape_string($con, $id);
    $query = "SELECT * FROM dbpersons WHERE id = '" . $escaped_id . "' OR email = '" . $escaped_id . "' LIMIT 1";
    $result = mysqli_query($con, $query);
    if (mysqli_num_rows($result) !== 1) {
        mysqli_close($con);
        return false;
    }
    $result_row = mysqli_fetch_assoc($result);
    $thePerson = make_a_person($result_row);
    mysqli_close($con);
    return $thePerson;
}
*/

function change_password($id, $newPass) {
    $con = connect();
    $query = 'UPDATE dbpersons SET password = ? WHERE id = ?';
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "ss", $newPass, $id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($con);
    return $result;
}

function reset_password($id, $newPass) {
    $con = connect();
    $query = 'UPDATE dbpersons SET password = ?, force_password_change="1" WHERE id = ?';
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "ss", $newPass, $id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($con);
    return $result;
}

/*@@@ Thomas */

/* Check-in a user by adding a new row and with start_time to dbpersonhours */
function check_in($personID, $eventID, $start_time) {
    $con = connect();
    $query = "INSERT INTO dbpersonhours (personID, eventID, start_time) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "sss", $personID, $eventID, $start_time);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($con);
    return $result;
}

/* Check-out a user by adding their end_time to dbpersonhours */
function check_out($personID, $eventID, $end_time) {
    $con = connect();
    $query = "UPDATE dbpersonhours SET end_time = ? WHERE eventID = ? AND personID = ? AND end_time IS NULL";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "sss", $end_time, $eventID, $personID);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($con);
    return $result;
}

/* Return true if a given user is currently able to check-in to a given event */
function can_check_in($personID, $event_info) {
    $eventStart = strtotime($event_info['date'] . ' ' . $event_info['startTime']);
    $eventEnd = strtotime($event_info['date'] . ' ' . $event_info['endTime']);

    if (!(time() > $eventStart && time() < $eventEnd)) {
        return false;
    }

    if (!(check_if_signed_up($event_info['id'], $personID))) {
        return false;
    }

    if (can_check_out($personID, $event_info)) {
        return false;
    }

    return true;
}

/*
 * Toggle or set a user's archive status without moving them to another table.
 * Inactive => status = 'Inactive', archived = 1
 * Active   => status = 'Active', archived = 0
 */
function archive_volunteer($id, $new_status = null) {
    $con = connect();
    if (!$con) {
        return false;
    }

    if ($new_status === null) {
        $statusStmt = $con->prepare(
            'SELECT status FROM dbpersons WHERE id = ? AND id != "vmsroot" AND id != "vmskiosk" AND id != "SuperAdmin"'
        );
        if (!$statusStmt) {
            mysqli_close($con);
            return false;
        }

        $statusStmt->bind_param('s', $id);
        $statusStmt->execute();
        $statusStmt->bind_result($currentStatus);

        if (!$statusStmt->fetch()) {
            $statusStmt->close();
            mysqli_close($con);
            return false;
        }

        $statusStmt->close();
        $new_status = (strcasecmp(trim((string)$currentStatus), 'Inactive') === 0) ? 'Active' : 'Inactive';
    }

    $new_status = trim((string)$new_status);
    $archivedValue = (strcasecmp($new_status, 'Inactive') === 0) ? 1 : 0;

    $stmt = $con->prepare('UPDATE dbpersons SET status = ?, archived = ? WHERE id = ?');
    if (!$stmt) {
        mysqli_close($con);
        return false;
    }

    $stmt->bind_param('sis', $new_status, $archivedValue, $id);
    $result = $stmt->execute();

    $stmt->close();
    mysqli_close($con);

    return $result;
}

function get_community_service_volunteers_count($dateFrom, $dateTo) {
    $con = connect();

    $query = "SELECT COUNT(*) AS count FROM dbpersons
              WHERE is_community_service_volunteer = 1
              AND STR_TO_DATE(start_date, '%Y-%m-%d') BETWEEN ? AND ?";

    $stmt = $con->prepare($query);

    if (!$stmt) {
        die("MySQLi prepare() failed: " . $con->error);
    }

    if (!$stmt->bind_param("ss", $dateFrom, $dateTo)) {
        die("MySQLi bind_param() failed: " . $stmt->error);
    }

    if (!$stmt->execute()) {
        die("MySQLi execute() failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    mysqli_close($con);

    return $row['count'] ?? 0;
}

/* Return true if a user is able to check out from a given event (they have already checked in) */
function can_check_out($personID, $event_info) {
    $con = connect();
    $query = "SELECT * FROM dbpersonhours WHERE personID = ? AND eventID = ? AND end_time IS NULL";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "ss", $personID, $event_info['id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    mysqli_close($con);

    if ($row) {
        return true;
    }
    return false;
}

/* Return number of seconds a volunteer worked for a specific event */
function fetch_volunteering_hours($personID, $eventID) {
    $con = connect();
    $query = "SELECT start_time, end_time FROM dbpersonhours WHERE personID = ? AND eventID = ? AND end_time IS NOT NULL";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "ss", $personID, $eventID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $total_time = 0;

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $start_time = strtotime($row['start_time']);
            $end_time = strtotime($row['end_time']);
            $total_time += $end_time - $start_time;
        }
        mysqli_stmt_close($stmt);
        mysqli_close($con);
        return $total_time;
    }
    mysqli_stmt_close($stmt);
    mysqli_close($con);
    return -1;
}

/* Delete a single check-in/check-out pair as defined by the given parameters */
function delete_check_in($userID, $eventID, $start_time, $end_time) {
    $con = connect();
    $query = "DELETE FROM dbpersonhours WHERE personID = ? AND eventID = ? AND start_time = ? AND end_time = ? LIMIT 1";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "ssss", $userID, $eventID, $start_time, $end_time);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($con);
    return $result;
}

/*@@@ end Thomas */

/*
 * Updates the profile picture link of the corresponding
 * id.
*/
function update_profile_pic($id, $link) {
    $con = connect();
    $query = 'UPDATE dbpersons SET profile_pic = ? WHERE id = ?';
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "ss", $link, $id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($con);
    return $result;
}

/*
 * @return all rows from dbPersons table ordered by last name
 * if none there, return false
 */
function getall_dbPersons($name_from, $name_to, $venue) {
    $con = connect();
    $query = "SELECT * FROM dbpersons WHERE venue = ? AND last_name BETWEEN ? AND ? ORDER BY last_name,first_name";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "sss", $venue, $name_from, $name_to);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result == null || mysqli_num_rows($result) == 0) {
        mysqli_stmt_close($stmt);
        mysqli_close($con);
        return false;
    }

    $thePersons = array();
    while ($result_row = mysqli_fetch_assoc($result)) {
        $thePerson = make_a_person($result_row);
        $thePersons[] = $thePerson;
    }

    mysqli_stmt_close($stmt);
    mysqli_close($con);
    return $thePersons;
}

/*
  @return all rows from dbPersons
*/
function getall_persons() {
    $con = connect();
    $query = 'SELECT * FROM dbpersons WHERE id != "vmsroot"';
    $result = mysqli_query($con, $query);
    if ($result == null || mysqli_num_rows($result) == 0) {
        mysqli_close($con);
        return false;
    }

    $result = mysqli_query($con, $query);
    $thePersons = array();
    while ($result_row = mysqli_fetch_assoc($result)) {
        $thePerson = make_a_person($result_row);
        $thePersons[] = $thePerson;
    }

    mysqli_close($con);
    return $thePersons;
}

// new method for report generation GETTING THE TOTAL NEW VOLUNTEER COUNT: YALDA
function get_new_volunteers_count($dateFrom, $dateTo) {
    $con = connect();

    $query = "SELECT COUNT(*) AS count FROM dbpersons
              WHERE STR_TO_DATE(start_date, '%Y-%m-%d') BETWEEN ? AND ?";

    $stmt = $con->prepare($query);
    $stmt->bind_param("ss", $dateFrom, $dateTo);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    mysqli_close($con);
    return $row['count'] ?? 0;
}

// ensure only active volunteers are counted within groups
function get_group_volunteers_count($startDate, $endDate) {
    $con = connect();

    $query = "
        SELECT COUNT(DISTINCT ug.user_id) AS group_volunteers
        FROM user_groups ug
        INNER JOIN dbpersons dp ON ug.user_id = dp.id
        WHERE dp.archived = 0
        AND dp.status = 'Active'
        AND dp.start_date BETWEEN ? AND ?;
    ";

    $stmt = $con->prepare($query);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $stmt->bind_result($groupVolunteers);
    $stmt->fetch();
    $stmt->close();
    $con->close();

    return $groupVolunteers;
}

function make_a_person($result_row) {
    $thePerson = new Person(
        $result_row['id'] ?? null,
        $result_row['first_name'] ?? null,
        $result_row['last_name'] ?? null,
        $result_row['phone_number'] ?? null,
        $result_row['email'] ?? null,
        $result_row['email_prefs'] ?? null,
        $result_row['birthday'] ?? null,
        $result_row['t-shirt_size'] ?? null,
        $result_row['state'] ?? null,
        $result_row['city'] ?? null,
        $result_row['street_address'] ?? null,
        $result_row['zip_code'] ?? null,
        $result_row['emergency_contact_first_name'] ?? null,
        $result_row['emergency_contact_phone'] ?? null,
        $result_row['emergency_contact_relation'] ?? null,
        $result_row['archived'] ?? null,
        $result_row['password'] ?? null,
        $result_row['contact_num'] ?? null,
        $result_row['contact_method'] ?? null,
        $result_row['type'] ?? null,
        $result_row['status'] ?? null,
        $result_row['photo_release'] ?? null,
        $result_row['community_service'] ?? null,
        $result_row['notes'] ?? null
    );
    return $thePerson;
}

function getvolunteers_byevent($id) {
    $con = connect();
    $query = 'SELECT * FROM dbeventpersons JOIN dbpersons ON dbeventpersons.userID COLLATE utf8mb4_unicode_ci = dbpersons.id COLLATE utf8mb4_unicode_ci
    WHERE eventID = ?
';
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $thePersons = array();
    while ($result_row = mysqli_fetch_assoc($result)) {
        $thePerson = make_a_person($result_row);
        $thePersons[] = $thePerson;
    }
    mysqli_stmt_close($stmt);
    mysqli_close($con);
    return $thePersons;
}

// retrieve only those persons that match the criteria given in the arguments
function getonlythose_dbPersons($type, $status, $name, $day, $shift, $venue) {
    $con = connect();
    $type_pattern = "%" . $type . "%";
    $status_pattern = "%" . $status . "%";
    $name_pattern = "%" . $name . "%";
    $day_pattern = "%" . $day . "%";
    $shift_pattern = "%" . $shift . "%";
    $query = "SELECT * FROM dbpersons WHERE type LIKE ? AND status LIKE ? AND (first_name LIKE ? OR last_name LIKE ?) AND availability LIKE ? AND availability LIKE ? ORDER BY last_name,first_name";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "ssssss", $type_pattern, $status_pattern, $name_pattern, $name_pattern, $day_pattern, $shift_pattern);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $thePersons = array();
    while ($result_row = mysqli_fetch_assoc($result)) {
        $thePerson = make_a_person($result_row);
        $thePersons[] = $thePerson;
    }
    mysqli_stmt_close($stmt);
    mysqli_close($con);
    return $thePersons;
}

function get_people_for_export($attr, $first_name, $last_name, $type, $status, $start_date, $city, $zip, $phone, $email) {
    $first_name = "'" . $first_name . "'";
    $last_name = "'" . $last_name . "'";
    $status = "'" . $status . "'";
    $start_date = "'" . $start_date . "'";
    $city = "'" . $city . "'";
    $zip = "'" . $zip . "'";
    $phone = "'" . $phone . "'";
    $email = "'" . $email . "'";
    $select_all_query = "'.'";
    if ($start_date == $select_all_query) {
        $start_date = $start_date . " or start_date=''";
    }
    if ($email == $select_all_query) {
        $email = $email . " or email=''";
    }

    $type_query = "";
    if (!isset($type) || count($type) == 0) {
        $type_query = "'.'";
    } else {
        $type_query = implode("|", $type);
        $type_query = "'.*($type_query).*'";
    }

    error_log("query for start date is " . $start_date);
    error_log("query for type is " . $type_query);

    $con = connect();
    $query = "SELECT " . $attr . " FROM dbpersons WHERE
                first_name REGEXP " . $first_name .
        " and last_name REGEXP " . $last_name .
        " and (type REGEXP " . $type_query . ")" .
        " and status REGEXP " . $status .
        " and (start_date REGEXP " . $start_date . ")" .
        " and city REGEXP " . $city .
        " and zip REGEXP " . $zip .
        " and (phone1 REGEXP " . $phone . " or phone2 REGEXP " . $phone . " )" .
        " and (email REGEXP " . $email . ") ORDER BY last_name, first_name";
    error_log("Querying database for exporting");
    error_log("query = " . $query);
    $result = mysqli_query($con, $query);
    return $result;
}

//return an array of "last_name;first_name;hours"
function get_logged_hours($from, $to, $name_from, $name_to, $venue) {
    $con = connect();
    $query = "SELECT first_name,last_name,hours,venue FROM dbpersons WHERE venue = ? AND last_name BETWEEN ? AND ? ORDER BY last_name,first_name";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "sss", $venue, $name_from, $name_to);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $thePersons = array();
    while ($result_row = mysqli_fetch_assoc($result)) {
        if ($result_row['hours'] != "") {
            $shifts = explode(',', $result_row['hours']);
            $goodshifts = array();
            foreach ($shifts as $shift) {
                if (($from == "" || substr($shift, 0, 8) >= $from) && ($to == "" || substr($shift, 0, 8) <= $to)) {
                    $goodshifts[] = $shift;
                }
            }
            if (count($goodshifts) > 0) {
                $newshifts = implode(",", $goodshifts);
                array_push($thePersons, $result_row['last_name'] . ";" . $result_row['first_name'] . ";" . $newshifts);
            }
        }
    }
    mysqli_close($con);
    return $thePersons;
}

/*
            $person->get_id() . '","' .
            $person->get_start_date() . '","' .
            "n/a" . '","' .
            $person->get_first_name() . '","' .
            $person->get_last_name() . '","' .
            $person->get_street_address() . '","' .
            $person->get_city() . '","' .
            $person->get_state() . '","' .
            $person->get_zip_code() . '","' .
            $person->get_phone1() . '","' .
            $person->get_phone1type() . '","' .
            $person->get_emergency_contact_phone() . '","' .
            $person->get_emergency_contact_phone_type() . '","' .
            $person->get_birthday() . '","' .
            $person->get_email() . '","' .
            $person->get_emergency_contact_first_name() . '","' .
            'n/a' . '","' .
            $person->get_emergency_contact_relation() . '","' .
            'n/a' . '","' .
            $person->get_type() . '","' .
            $person->get_status() . '","' .
            'n/a' . '","' .
            $person->get_password() . '","' .
            'n/a' . '","' .
            'gender' . '","' .
            $person->get_tshirt_size() . '","' .
            'how_you_heard_of_stepva' . '","' .
            'sensory_sensitivities' . '","' .
            'disability_accomodation_needs' . '","' .
            $person->get_school_affiliation() . '","' .
            'race' . '","' .
            'preferred_feedback_method' . '","' .
            'hobbies' . '","' .
            'professional_experience' . '","' .
            $person->get_archived() . '","' .
            $person->get_emergency_contact_last_name() . '","' .
            $person->get_photo_release() . '","' .
            $person->get_photo_release_notes() . '");'
*/

// updates the required fields of a person's account
function update_person_required(
    $id,
    $first_name,
    $last_name,
    $t_shirt_size,
    $street_address,
    $city,
    $state,
    $zip_code,
    $email,
    $phone1,
    $email_consent,
    $emergency_contact_first_name,
    $emergency_contact_relation,
    $emergency_contact_phone
) {
    $query = "update dbpersons set
            first_name=?, last_name=?,
            `t-shirt_size`=?, street_address=?,
            city=?, state=?, zip_code=?,
            email=?, phone_number=?,
            emergency_contact_first_name=?,
            emergency_contact_relation=?,
            emergency_contact_phone=?,
            email_prefs=?
            where id=?";

    $connection = connect();
    $stmt = mysqli_prepare($connection, $query);
    if (!$stmt) {
        mysqli_close($connection);
        return false;
    }
    
    mysqli_stmt_bind_param(
        $stmt,
        "ssssssssssssss",
        $first_name,
        $last_name,
        $t_shirt_size,
        $street_address,
        $city,
        $state,
        $zip_code,
        $email,
        $phone1,
        $emergency_contact_first_name,
        $emergency_contact_relation,
        $emergency_contact_phone,
        $email_consent,
        $id
    );
    
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_commit($connection);
    mysqli_close($connection);
    return $result;
}

function find_users($name, $id, $phone, $zip, $type, $status) {
    if (!($name || $id || $phone || $zip || $type || $status)) {
        return [];
    }
    
    $conditions = [];
    $params = [];
    $types = '';
    
    if ($name) {
        if (strpos($name, ' ')) {
            $name_parts = explode(' ', $name, 2);
            $firstName = '%' . $name_parts[0] . '%';
            $lastName = '%' . $name_parts[1] . '%';
            $conditions[] = "first_name like ? and last_name like ?";
            $params[] = $firstName;
            $params[] = $lastName;
            $types .= 'ss';
        } else {
            $name_pattern = '%' . $name . '%';
            $conditions[] = "(first_name like ? or last_name like ?)";
            $params[] = $name_pattern;
            $params[] = $name_pattern;
            $types .= 'ss';
        }
    }
    if ($id) {
        $id_pattern = '%' . $id . '%';
        $conditions[] = "id like ?";
        $params[] = $id_pattern;
        $types .= 's';
    }
    if ($phone) {
        $phone_pattern = '%' . $phone . '%';
        $conditions[] = "phone_number like ?";
        $params[] = $phone_pattern;
        $types .= 's';
    }
    if ($zip) {
        $zip_pattern = '%' . $zip . '%';
        $conditions[] = "zip_code like ?";
        $params[] = $zip_pattern;
        $types .= 's';
    }
    if ($type) {
        $conditions[] = "type=?";
        $params[] = $type;
        $types .= 's';
    }
    if ($status) {
        $conditions[] = "status=?";
        $params[] = $status;
        $types .= 's';
    }
    
    $where_clause = implode(' and ', $conditions);
    $query = "select * from dbpersons where " . $where_clause . " order by last_name, first_name";
    
    $connection = connect();
    $stmt = mysqli_prepare($connection, $query);
    
    if ($params) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        mysqli_stmt_close($stmt);
        mysqli_close($connection);
        return [];
    }
    $raw = mysqli_fetch_all($result, MYSQLI_ASSOC);
    $persons = [];
    foreach ($raw as $row) {
        if ($row['id'] == 'vmsroot') {
            continue;
        }
        $persons[] = make_a_person($row);
    }
    mysqli_stmt_close($stmt);
    mysqli_close($connection);
    return $persons;
}

function searchUsers($query) {
    $conn = connect();

    $stmt = $conn->prepare("
        SELECT id 
        FROM dbpersons 
        WHERE id LIKE CONCAT('%', ?, '%')
           OR first_name LIKE CONCAT('%', ?, '%')
           OR last_name LIKE CONCAT('%', ?, '%')
        LIMIT 10
    ");

    $stmt->bind_param("sss", $query, $query, $query);
    $stmt->execute();

    $result = $stmt->get_result();
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row['id'];
    }

    $stmt->close();
    $conn->close();

    return $data;
}

function update_type($id, $role) {
    $con = connect();
    $query = 'UPDATE dbpersons SET type = ? WHERE id = ?';
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "ss", $role, $id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($con);
    return $result;
}

function update_notes($id, $new_notes) {
    $con = connect();
    $query = 'UPDATE dbpersons SET notes = ? WHERE id = ?';
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "ss", $new_notes, $id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($con);
    return $result;
}

date_default_timezone_set("America/New_York");

function fetch_no_shows() {
    $connection = connect();
    $query =
        "SELECT dbeventpersons.userID, COUNT(*) AS NoShowCount
            FROM dbeventpersons
            JOIN dbevents ON dbeventpersons.eventID = dbevents.id
            WHERE dbeventpersons.attended = 0
                AND CONCAT(dbevents.date, ' ', dbevents.endTime) < NOW()
            GROUP BY dbeventpersons.userID
            ORDER BY NoShowCount DESC
            ";

    $result = mysqli_query($connection, $query);
    if ($result) {
        $rows = mysqli_fetch_all($result);
    } else {
        echo "we have no result";
        die("Error: " . mysqli_error($connection));
    }
    mysqli_close($connection);
    return $rows;
}

function get_events_attended_by($personID) {
    $today = date("Y-m-d");
    $query = "select * from dbeventpersons, dbevents where userID=? and dbevents.id=dbeventpersons.eventID and date<=? and attended=1 order by date asc";
    $connection = connect();
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "ss", $personID, $today);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result) {
        require_once('include/time.php');
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
        mysqli_close($connection);
        foreach ($rows as &$row) {
            $row['duration'] = calculateHourDuration($row['startTime'], $row['endTime']);
        }
        unset($row);
        return $rows;
    } else {
        mysqli_stmt_close($stmt);
        mysqli_close($connection);
        return [];
    }
}

function get_event_from_id($eventID) {
    $connection = connect();
    $query = "SELECT name FROM dbevents WHERE id = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "i", $eventID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        mysqli_close($connection);
        return $row ? $row['name'] : null;
    } else {
        mysqli_stmt_close($stmt);
        mysqli_close($connection);
        return null;
    }
}

/* @@@ Thomas */
function get_attended_event_ids($personID) {
    $con = connect();
    $query = "SELECT DISTINCT eventID FROM dbpersonhours WHERE personID = ? ORDER BY eventID DESC";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "s", $personID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row['eventID'];
        }
        mysqli_free_result($result);
        mysqli_stmt_close($stmt);
        mysqli_close($con);
        return $rows;
    } else {
        mysqli_stmt_close($stmt);
        mysqli_close($con);
        return [];
    }
}

function get_check_in_outs($personID, $event) {
    $con = connect();
    $query = "SELECT start_time, end_time FROM dbpersonhours WHERE personID = ? and eventID = ? AND end_time IS NOT NULL";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "ss", $personID, $event);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        mysqli_free_result($result);
        mysqli_stmt_close($stmt);
        mysqli_close($con);
        return $rows;
    } else {
        mysqli_stmt_close($stmt);
        mysqli_close($con);
        return [];
    }
}
/*@@@ end Thomas */

function get_events_attended_by_2($personID) {
    $query = "SELECT personID, eventID, start_time, end_time FROM dbpersonhours WHERE personID = ?";

    $connection = connect();
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "s", $personID);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_close($connection);

        return $rows;
    } else {
        mysqli_close($connection);
        return [];
    }
}

function get_events_attended_by_and_date($personID, $fromDate, $toDate) {
    $query = "select * from dbEventVolunteers, dbEvents
                  where userID=? and eventID=id
                  and date<=? and date >= ?
                  order by date desc";
    $connection = connect();
    $stmt = mysqli_prepare($connection, $query);
    if (!$stmt) {
        mysqli_close($connection);
        return [];
    }
    mysqli_stmt_bind_param($stmt, "sss", $personID, $toDate, $fromDate);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    
    if ($result) {
        require_once('include/time.php');
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_close($connection);
        foreach ($rows as &$row) {
            $row['duration'] = calculateHourDuration($row['startTime'], $row['endTime']);
        }
        unset($row);
        return $rows;
    } else {
        mysqli_close($connection);
        return [];
    }
}

function get_events_attended_by_desc($personID) {
    $today = date("Y-m-d");
    $query = "select * from dbEventVolunteers, dbEvents
                  where userID=? and eventID=id
                  and date<=?
                  order by date desc";
    $connection = connect();
    $stmt = mysqli_prepare($connection, $query);
    if (!$stmt) {
        mysqli_close($connection);
        return [];
    }
    mysqli_stmt_bind_param($stmt, "ss", $personID, $today);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    
    if ($result) {
        require_once('include/time.php');
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_close($connection);
        foreach ($rows as &$row) {
            $row['duration'] = calculateHourDuration($row['startTime'], $row['endTime']);
        }
        unset($row);
        return $rows;
    } else {
        mysqli_close($connection);
        return [];
    }
}

function get_hours_volunteered_by($personID) {
    $events = get_events_attended_by($personID);
    $hours = 0;
    foreach ($events as $event) {
        $duration = $event['duration'];
        if ($duration > 0) {
            $hours += $duration;
        }
    }
    return $hours;
}

function get_hours_volunteered_by_and_date($personID, $fromDate, $toDate) {
    $events = get_events_attended_by_and_date($personID, $fromDate, $toDate);
    $hours = 0;
    foreach ($events as $event) {
        $duration = $event['duration'];
        if ($duration > 0) {
            $hours += $duration;
        }
    }
    return $hours;
}

function get_total_vol_hours($dateFrom, $dateTo) {
    $con = connect();

    $query = "
        SELECT SUM(totalHours) AS total_hours
        FROM dbshifts
        WHERE date BETWEEN ? AND ?";

    $stmt = $con->prepare($query);
    $stmt->bind_param("ss", $dateFrom, $dateTo);
    $stmt->execute();
    $result = $stmt->get_result();

    $row = $result->fetch_assoc();
    mysqli_close($con);

    return $row['total_hours'] ?? 0;
}

function get_name_from_id($id) {
    if ($id == 'vmsroot') {
        return 'System';
    }
    $query = "SELECT first_name, last_name FROM dbpersons WHERE id = ?";
    $connection = connect();
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "s", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!$result) {
        mysqli_stmt_close($stmt);
        mysqli_close($connection);
        return null;
    }

    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    mysqli_close($connection);
    return $row['first_name'] . ' ' . $row['last_name'];
}

function retrieveEmailsByIds(array $ids): array {
    $conn = connect();
    if (empty($ids)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('s', count($ids));

    $sql = "SELECT email FROM dbpersons WHERE id IN ($placeholders) AND email_prefs = 'true' AND email IS NOT NULL AND email != ''";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return [];
    }

    $stmt->bind_param($types, ...$ids);
    $stmt->execute();

    $result = $stmt->get_result();
    $emails = [];
    while ($row = $result->fetch_assoc()) {
        $emails[] = $row['email'];
    }

    $stmt->close();
    $conn->close();

    return array_unique($emails);
}

/* Love Thy Neighbor Users Page */
function getUsersForViewPage($search = '', $limit = 10, $offset = 0, $search_by = 'all', $status = 'all', $event_id = '') {
    global $con;
    $search = trim($search);
    $limit  = (int)$limit;
    $offset = (int)$offset;
    $users  = [];

    $allowed_search_by = ['all', 'name', 'username', 'email', 'phone'];
    if (!in_array($search_by, $allowed_search_by)) {
        $search_by = 'all';
    }

    if ($status === 'active') {
        $archive_condition = "(archived = 0 OR archived IS NULL)";
    } elseif ($status === 'archived') {
        $archive_condition = "archived = 1";
    } else {
        $archive_condition = null;
    }

    $params = [];
    $param_types = '';

    if ($search === '') {
        $where = $archive_condition ? "WHERE $archive_condition" : '';
    } else {
        $like = "%$search%";

        if ($search_by === 'name') {
            $search_condition = "(first_name LIKE ? OR last_name LIKE ? OR CONCAT(first_name, ' ', last_name) LIKE ?)";
            $params = [$like, $like, $like];
            $param_types = 'sss';
        } elseif ($search_by === 'email') {
            $search_condition = "email LIKE ?";
            $params = [$like];
            $param_types = 's';
        } elseif ($search_by === 'phone') {
            $search_condition = "phone_number LIKE ?";
            $params = [$like];
            $param_types = 's';
        } elseif ($search_by === 'username') {
            $search_condition = "dbpersons.id LIKE ?";
            $params = [$like];
            $param_types = 's';
        } else {
            $search_condition = "(first_name LIKE ? OR last_name LIKE ? OR CONCAT(first_name, ' ', last_name) LIKE ?
                                    OR email LIKE ? OR phone_number LIKE ? OR dbpersons.id LIKE ?)";
            $params = [$like, $like, $like, $like, $like, $like];
            $param_types = 'ssssss';
        }

        $where = $archive_condition
            ? "WHERE $search_condition AND $archive_condition"
            : "WHERE $search_condition";
    }

    $join = '';
    if (!empty($event_id)) {
        $join = "JOIN dbeventpersons ep ON ep.userID = dbpersons.id AND ep.eventID = ?";
        array_unshift($params, $event_id);
        $param_types = 's' . $param_types;
    }

    $sql = "SELECT dbpersons.id, first_name, last_name, email, phone_number, `type`, archived
                FROM dbpersons
                $join
                $where
                ORDER BY last_name ASC, first_name ASC
                LIMIT $limit OFFSET $offset";

    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) {
        return $users;
    }

    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $param_types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $users;
}

/* Love Thy Neighbor Users Page */
function get_person_by_id($id) {
    $con = connect();

    if (!$con) {
        die("Database connection failed: " . mysqli_connect_error());
    }

    $stmt = mysqli_prepare($con, "SELECT * FROM dbpersons WHERE id = ? LIMIT 1");

    if (!$stmt) {
        die("Prepare failed: " . mysqli_error($con));
    }

    mysqli_stmt_bind_param($stmt, "s", $id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);
    mysqli_close($con);

    return $user;
}

// count query for matching filters to results in Users Page
function getUserCount($search = '', $search_by = 'all', $status = 'all', $event_id = '') {
    global $con;
    $search = trim($search);

    $allowed_search_by = ['all', 'name', 'username', 'email', 'phone'];
    if (!in_array($search_by, $allowed_search_by)) {
        $search_by = 'all';
    }

    if ($status === 'active') {
        $archive_condition = "(archived = 0 OR archived IS NULL)";
    } elseif ($status === 'archived') {
        $archive_condition = "archived = 1";
    } else {
        $archive_condition = null;
    }

    $params = [];
    $param_types = '';

    if ($search === '') {
        $where = $archive_condition ? "WHERE $archive_condition" : '';
    } else {
        $like = "%$search%";

        if ($search_by === 'name') {
            $search_condition = "(first_name LIKE ? OR last_name LIKE ? OR CONCAT(first_name, ' ', last_name) LIKE ?)";
            $params = [$like, $like, $like];
            $param_types = 'sss';
        } elseif ($search_by === 'email') {
            $search_condition = "email LIKE ?";
            $params = [$like];
            $param_types = 's';
        } elseif ($search_by === 'phone') {
            $search_condition = "phone_number LIKE ?";
            $params = [$like];
            $param_types = 's';
        } elseif ($search_by === 'username') {
            $search_condition = "dbpersons.id LIKE ?";
            $params = [$like];
            $param_types = 's';
        } else {
            $search_condition = "(first_name LIKE ? OR last_name LIKE ? OR CONCAT(first_name, ' ', last_name) LIKE ?
                                    OR email LIKE ? OR phone_number LIKE ? OR dbpersons.id LIKE ?)";
            $params = [$like, $like, $like, $like, $like, $like];
            $param_types = 'ssssss';
        }

        $where = $archive_condition
            ? "WHERE $search_condition AND $archive_condition"
            : "WHERE $search_condition";
    }

    $join = '';
    if (!empty($event_id)) {
        $join = "JOIN dbeventpersons ep ON ep.userID = dbpersons.id AND ep.eventID = ?";
        array_unshift($params, $event_id);
        $param_types = 's' . $param_types;
    }

    $stmt = mysqli_prepare($con, "SELECT COUNT(*) as total FROM dbpersons $join $where");

    if (!$stmt) {
        return 0;
    }

    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $param_types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);
    return (int)$row['total'];
}

function getEventsByDate($con, $date) {
    $stmt = $con->prepare("SELECT id, name FROM dbevents WHERE DATE(`date`) = ?");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getVolunteerCount($con) {
    $sql = "SELECT COUNT(*) AS total FROM dbpersons WHERE type = 'volunteer'";
    $result = mysqli_query($con, $sql);
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}

function getAdminCount() {
    $con = connect();
    $sql = "SELECT COUNT(*) AS total FROM dbpersons WHERE type = 'admin'";
    $result = mysqli_query($con, $sql);
    $row = mysqli_fetch_assoc($result);
    mysqli_close($con);
    return $row['total'];
}

function getTotalUsers() {
    $con = connect();
    $sql = "SELECT COUNT(*) AS total FROM dbpersons";
    $result = mysqli_query($con, $sql);
    $row = mysqli_fetch_assoc($result);
    mysqli_close($con);
    return $row['total'];
}

//get whether or not the user is an admin or a volunteer.
function getUserType($id) {
    $con = connect();
    $query = "SELECT type FROM dbpersons WHERE id = ?";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "s", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    mysqli_close($con);
    if (isset($row['type'])) {
        return $row['type'];
    }
    return 0;
}

//set a user to be volunteer or admin.
function setUserType($id, $type) {
    if ($type !== "Volunteer" && $type !== "Admin") {
        return 0;
    }
    $con = connect();
    $query = "UPDATE dbpersons SET type = ? WHERE id = ?";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "ss", $type, $id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($con);
    return 1;
}

/*
// function get_verified_ids($user_id) {
//     $con = connect();
//     if (!$con) return [];
//
//     $query = "SELECT id_type, approved_at FROM user_verified_ids WHERE user_id = ? ORDER BY approved_at DESC";
//     $stmt = $con->prepare($query);
//
//     if ($stmt) {
//         $stmt->bind_param("s", $user_id);
//         $stmt->execute();
//         $result = $stmt->get_result();
//
//         $ids = [];
//         while ($row = $result->fetch_assoc()) {
//             $ids[] = $row;
//         }
//
//         $stmt->close();
//         mysqli_close($con);
//         return $ids;
//     }
//
//     mysqli_close($con);
//     return [];
// }
*/

//If archived is 1, set to 0. If archived is 0, set to 1.
function toggleArchiveStatus($id) {
    $con = connect();
    $query = "UPDATE dbpersons SET archived = 1 - archived WHERE id = ?";
    $stmt = $con->prepare($query);

    if (!$stmt) {
        die("Prepare failed: " . $con->error);
    }

    $stmt->bind_param("s", $id);
    $result = $stmt->execute();

    $stmt->close();
    mysqli_close($con);

    return $result;
}

function updateUsername()
{
    $con = connect();
    $query = "UPDATE dbpersons SET id = CONCAT(first_name, last_name, FLOOR(RAND() * 1000)) where id != 'vmsroot' and id != 'vmsKiosk'";
    $result = mysqli_query($con, $query);
    mysqli_close($con);
    return $result;
}