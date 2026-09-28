<?php

require_once('database/dbinfo.php');
include_once(dirname(__FILE__).'/../domain/Event.php');
// include_once(dirname(__FILE__).'/../domain/Animal.php');
date_default_timezone_set("America/New_York");

function get_user_messages($userID) {
    $query = "select * from dbmessages
              where recipientID=?
              order by prioritylevel desc";
    $connection = connect();
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "s", $userID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        mysqli_stmt_close($stmt);
        mysqli_close($connection);
        return null;
    }
    $messages = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    
    foreach ($messages as &$message) {
        foreach ($message as $key => $value) {
            $message[$key] = htmlspecialchars($value);
        }
    }
    unset($message);
    mysqli_close($connection);
    return $messages;
}

function get_user_unread_messages($userID) {
    $query = "select * from dbmessages
              where recipientID=? AND wasread = 0
              order by time DESC, id DESC";
    $connection = connect();
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "s", $userID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        mysqli_stmt_close($stmt);
        mysqli_close($connection);
        return null;
    }
    $messages = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    
    foreach ($messages as &$message) {
        foreach ($message as $key => $value) {
            $message[$key] = htmlspecialchars($value);
        }
    }
    unset($message);
    mysqli_close($connection);
    return $messages;
}
function get_user_read_messages($userID) {
    $query = "select * from dbmessages
              where recipientID=? AND wasread = 1
              order by time DESC, id DESC";
    $connection = connect();
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "s", $userID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        mysqli_stmt_close($stmt);
        mysqli_close($connection);
        return null;
    }
    $messages = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    
    foreach ($messages as &$message) {
        foreach ($message as $key => $value) {
            $message[$key] = htmlspecialchars($value);
        }
    }
    unset($message);
    mysqli_close($connection);
    return $messages;
}

function get_user_unread_count($userID) {
    $query = "select count(*) from dbmessages 
        where recipientID=? and wasRead=0";
    $connection = connect();
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "s", $userID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        mysqli_stmt_close($stmt);
        mysqli_close($connection);
        return null;
    }

    $row = mysqli_fetch_row($result);
    mysqli_stmt_close($stmt);
    mysqli_close($connection);
    return intval($row[0]);
}

function get_message_by_id($id) {
    $query = "SELECT * FROM dbmessages WHERE id = ?";
    $connection = connect();
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
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
    if ($row == null) {
        return null;
    }
    foreach ($row as $key => $value) {
        $row[$key] = htmlspecialchars($value);
    }
    $row['body'] = str_replace("\r\n", "<br>", $row['body']);
    return $row;
}

function send_message($from, $to, $title, $body) {
    $time = date('Y-m-d-H:i');
    $connection = connect();
    $query = "insert into dbmessages
        (senderID, recipientID, title, body, time)
        values (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($connection, $query);
    if (!$stmt) {
        mysqli_close($connection);
        return null;
    }
    mysqli_stmt_bind_param($stmt, "sssss", $from, $to, $title, $body, $time);
    $result = mysqli_stmt_execute($stmt);
    if (!$result) {
        mysqli_stmt_close($stmt);
        mysqli_close($connection);
        return null;
    }
    $id = mysqli_insert_id($connection);
    mysqli_stmt_close($stmt);
    mysqli_close($connection);
    return $id; // get row id
}

function send_system_message($to, $title, $body) {
    send_message('vmsroot', $to, $title, $body);
}

function mark_read($id) {
    $query = "update dbmessages set wasRead=1 where id=?";
    $connection = connect();
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    if (!$result) {
        mysqli_close($connection);
        return false;
    }
    mysqli_close($connection);
    return true;
}

function mark_all_as_read($userID) {
    $query = "update dbmessages set wasRead=1 where recipientID=?";
    $connection = connect();
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "s", $userID);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    if (!$result) {
        mysqli_close($connection);
        return false;
    }
    mysqli_close($connection);
    return true;
}

// function message_all_users_of_types($from, $types, $title, $body) {
//     $types = implode(', ', $types);
//     $time = date('Y-m-d-H:i');
//     $query = "select id from dbpersons where type in ($types)";
//     $connection = connect();
//     $result = mysqli_query($connection, $query);
//     $rows = mysqli_fetch_all($result, MYSQLI_NUM);
//     foreach ($rows as $row) {
//         $to = $row[0];
//         $query = "insert into dbmessages (senderID, recipientID, title, body, time, wasRead, prioritylevel)
//                   values ('$from', '$to', '$title', '$body', '$time', 0, 0)";
//         $result = mysqli_query($connection, $query);
//     }
//     mysqli_close($connection);    
//     return true;
// }

function message_all_volunteers($from, $title, $body) {
    return message_all_users_of_types($from, ['"volunteer"'], $title, $body);
}

function system_message_all_volunteers($title, $body) {
    return message_all_users_of_types('vmsroot', ['"volunteer"'], $title, $body);
}

function message_all_admins($from, $title, $body) {
    return message_all_users_of_types($from, ['"admin"', '"superadmin"'], $title, $body);
}

function system_message_all_admins($title, $body) {
    return message_all_users_of_types('vmsroot', ['"admin"', '"superadmin"'], $title, $body);
}

function system_message_all_users_except($except, $title, $body) {
    $time = date('Y-m-d-H:i');
    $query = "select id from dbpersons where id!=?";
    $connection = connect();
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "s", $except);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = mysqli_fetch_all($result, MYSQLI_NUM);
    mysqli_stmt_close($stmt);
    foreach ($rows as $row) {
        $to = $row[0];
        $query = "insert into dbmessages (senderID, recipientID, title, body, time) values (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($connection, $query);
        $sender = 'vmsroot';
        mysqli_stmt_bind_param($stmt, "sssss", $sender, $to, $title, $body, $time);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    mysqli_close($connection);    
    return true;
}

//function to go through all users within the database of user accounts and send them a notification given a title and body 
function message_all_users($from, $title, $body) {
    $time = date('Y-m-d-H:i');
    $query = "select id from dbpersons";
    $connection = connect();
    $result = mysqli_query($connection, $query);
    $rows = mysqli_fetch_all($result, MYSQLI_NUM); //get all the users in the database dbPersons
    foreach ($rows as $row) { //for every user in db person, generate a notification
        $to = $row[0]; //get the user ID directly
        $query = "insert into dbmessages (senderID, recipientID, title, body, time)
                  values (?, ?, ?, ?, ?)"; //inserting the notification in that users inbox
        $stmt = mysqli_prepare($connection, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sssss", $from, $to, $title, $body, $time);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
    mysqli_close($connection);    
    return true;
}

function message_all_users_prio($from, $title, $body, $prio) {
    $time = date('Y-m-d-H:i');
    $query = "select id from dbpersons where id!=?";
    $connection = connect();
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "s", $from);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = mysqli_fetch_all($result, MYSQLI_NUM);
    mysqli_stmt_close($stmt);
    foreach ($rows as $row) {
        $to = json_encode($row);
        $to = substr($to,2,-2);
        $query = "insert into dbmessages (senderID, recipientID, title, body, time, prioritylevel) values (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "sssssi", $from, $to, $title, $body, $time, $prio);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    mysqli_close($connection);    
    return true;
}
function delete_message($id) {
    $query = "DELETE FROM dbmessages WHERE id = ?";
    $connection = connect();
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($connection);
    return $result;
}

function delete_all_messages_for_user($userId) {
    $query = "DELETE FROM dbmessages WHERE recipientID = ?";
    $connection = connect();
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "s", $userId);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($connection);
    return $result;
}

function delete_messages_by_ids($ids, $userID) {
    $ids_str = implode(',', array_map('intval', $ids));
    $query = "DELETE FROM dbmessages WHERE recipientID = ? AND id IN ($ids_str)";
    $connection = connect();
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "s", $userID);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($connection);
    return $result;
}

function get_last_3_messages($userID) {
    $query = "SELECT * FROM dbmessages 
              WHERE recipientID = ? 
              ORDER BY time DESC, id DESC 
              LIMIT 3";
    $connection = connect();
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "s", $userID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        mysqli_stmt_close($stmt);
        mysqli_close($connection);
        return [];
    }
    $messages = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    mysqli_close($connection);
    
    return $messages;
}