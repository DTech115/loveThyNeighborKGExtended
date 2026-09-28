<?php
session_start();

include_once 'database/dbpersonhours.php';
include_once 'database/dbRoles.php';

if (!isset($_SESSION['_id'])) {
    die("Error: User not logged in.");
}

function getRequestValue($keys, $default = null)
{
    foreach ($keys as $key) {
        if (isset($_POST[$key]) && $_POST[$key] !== '') {
            return $_POST[$key];
        }
        if (isset($_GET[$key]) && $_GET[$key] !== '') {
            return $_GET[$key];
        }
        if (isset($_SESSION[$key]) && $_SESSION[$key] !== '') {
            return $_SESSION[$key];
        }
    }
    return $default;
}

function showSuccessMessage($message, $redirectUrl = null)
{
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Success</title>
        <style>
            body {
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                background-color: #f4f4f4;
                font-family: Quicksand, sans-serif;
                margin: 0;
            }
            .success-container {
                text-align: center;
            }
            .checkmark {
                width: 80px;
                height: 80px;
                border-radius: 50%;
                background-color: #007bff;
                display: flex;
                justify-content: center;
                align-items: center;
                animation: popIn 0.5s ease-in-out;
                margin: 0 auto;
            }
            .checkmark:after {
                content: "✔";
                color: white;
                font-size: 40px;
                font-weight: bold;
            }
            .message {
                font-size: 20px;
                font-weight: bold;
                margin-top: 15px;
                opacity: 0;
                animation: fadeIn 0.8s forwards 0.5s;
            }
            @keyframes popIn {
                from { transform: scale(0); opacity: 0; }
                to { transform: scale(1); opacity: 1; }
            }
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
        </style>
    </head>
    <body>';

    echo '<div class="success-container">
            <div class="checkmark"></div>
            <div class="message">' . htmlspecialchars($message) . '</div>
          </div>';

    if ($redirectUrl) {
        echo '<script>
            setTimeout(function() {
                window.location.href = ' . json_encode($redirectUrl) . ';
            }, 1500);
        </script>';
    }

    echo '</body></html>';
    exit;
}

function buildCheckOutReturnUrl($eventid, $person_id = '', $message = '')
{
    $url = 'KioskviewOverallEventsKG.php?id=' . urlencode((string)$eventid);

    if ($message !== '') {
        $url .= '&msg=' . urlencode($message);
    }

    if ($person_id !== '') {
        $url .= '&q=' . urlencode($person_id);
    }

    return $url;
}

function buildCheckInReturnUrl($message = '')
{
    $url = 'KioskviewOverallEventsKG.php';

    if ($message !== '') {
        $url .= '?msg=' . urlencode($message);
    }

    return $url;
}

function findOpenCheckInRoleId($eventid, $person_id)
{
    $con = connect();

    $query = "SELECT roleID
              FROM dbpersonhours
              WHERE eventID = ?
                AND personID = ?
                AND start_time IS NOT NULL
                AND end_time IS NULL
              ORDER BY start_time DESC
              LIMIT 1";

    $stmt = $con->prepare($query);
    if (!$stmt) {
        mysqli_close($con);
        return 0;
    }

    $stmt->bind_param("is", $eventid, $person_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $roleId = 0;
    if ($result && $row = $result->fetch_assoc()) {
        $roleId = (int)$row['roleID'];
    }

    $stmt->close();
    mysqli_close($con);

    return $roleId;
}

function hasOpenCheckIn($eventid, $person_id)
{
    return findOpenCheckInRoleId($eventid, $person_id) > 0;
}

function getAssignedRoleIdForEvent($person_id, $eventid, $postedRoleId = 0)
{
    if (function_exists('getRolesForPersonEvent')) {
        $roles = getRolesForPersonEvent($person_id, $eventid);

        if (is_array($roles) && !empty($roles)) {
            $normalizedRoles = array();

            foreach ($roles as $role) {
                if (is_array($role)) {
                    if (isset($role['roleID'])) {
                        $normalizedRoles[] = (int)$role['roleID'];
                    } elseif (isset($role['role_id'])) {
                        $normalizedRoles[] = (int)$role['role_id'];
                    } elseif (isset($role['id'])) {
                        $normalizedRoles[] = (int)$role['id'];
                    }
                } else {
                    $normalizedRoles[] = (int)$role;
                }
            }

            $normalizedRoles = array_values(array_filter($normalizedRoles));

            if (!empty($normalizedRoles)) {
                if ($postedRoleId > 0 && in_array($postedRoleId, $normalizedRoles, true)) {
                    return $postedRoleId;
                }

                return (int)$normalizedRoles[0];
            }
        }
    }

    return $postedRoleId > 0 ? $postedRoleId : 0;
}

function resolveRoleId($eventid, $person_id, $postedRoleId = 0)
{
    $openRoleId = findOpenCheckInRoleId($eventid, $person_id);
    if ($openRoleId > 0) {
        return $openRoleId;
    }

    $assignedRoleId = getAssignedRoleIdForEvent($person_id, $eventid, $postedRoleId);
    if ($assignedRoleId > 0) {
        return $assignedRoleId;
    }

    return $postedRoleId > 0 ? $postedRoleId : 0;
}

function savePersonHoursCheckIn($eventid, $person_id, $roleid)
{
    $con = connect();

    $query = "INSERT INTO dbpersonhours (eventID, personID, roleID, start_time, end_time)
              VALUES (?, ?, ?, NOW(), NULL)
              ON DUPLICATE KEY UPDATE
                  start_time = NOW(),
                  end_time = NULL";

    $stmt = $con->prepare($query);
    if (!$stmt) {
        $error = mysqli_error($con);
        mysqli_close($con);
        die("Error preparing check-in query: " . $error);
    }

    $stmt->bind_param("isi", $eventid, $person_id, $roleid);
    $success = $stmt->execute();

    if (!$success) {
        $error = $stmt->error;
        $stmt->close();
        mysqli_close($con);
        die("Error saving dbpersonhours row: " . $error);
    }

    $stmt->close();
    mysqli_close($con);

    return true;
}

function closePersonHoursCheckIn($eventid, $person_id, $roleid)
{
    $con = connect();

    $query = "UPDATE dbpersonhours
              SET end_time = NOW()
              WHERE eventID = ?
                AND personID = ?
                AND roleID = ?
                AND start_time IS NOT NULL
                AND end_time IS NULL
              LIMIT 1";

    $stmt = $con->prepare($query);
    if (!$stmt) {
        $error = mysqli_error($con);
        mysqli_close($con);
        die("Error preparing check-out query: " . $error);
    }

    $stmt->bind_param("isi", $eventid, $person_id, $roleid);
    $success = $stmt->execute();

    if (!$success) {
        $error = $stmt->error;
        $stmt->close();
        mysqli_close($con);
        die("Error updating dbpersonhours row: " . $error);
    }

    $affectedRows = $stmt->affected_rows;

    $stmt->close();
    mysqli_close($con);

    return $affectedRows > 0;
}

$person_id = getRequestValue(array('user_id', 'userid', '_id'), $_SESSION['_id']);
$eventidRaw = getRequestValue(array('eventid', 'eventID', 'id'));
$postedRoleIdRaw = getRequestValue(array('roleid', 'roleID'), 0);

$eventid = (int)$eventidRaw;
$postedRoleId = (int)$postedRoleIdRaw;

if (!$person_id) {
    die("Error: Missing userid.");
}

if ($eventid <= 0) {
    die("Error: Missing eventid.");
}

$_SESSION['eventid'] = $eventid;
$_SESSION['id'] = $eventid;

$resolvedRoleId = resolveRoleId($eventid, $person_id, $postedRoleId);

if ($resolvedRoleId <= 0) {
    die("Error: Missing roleid.");
}

if (hasOpenCheckIn($eventid, $person_id)) {
    $openRoleId = findOpenCheckInRoleId($eventid, $person_id);

    if ($openRoleId > 0) {
        $resolvedRoleId = $openRoleId;
    }

    if (!isset($_POST['desc'])) {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Check Out</title>
            <style>
                body {
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                    background-color: #f4f4f4;
                    font-family: Quicksand, sans-serif;
                    margin: 0;
                }
                .checkout-container {
                    background: white;
                    padding: 20px;
                    border-radius: 10px;
                    box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
                    text-align: center;
                    width: 350px;
                }
                label {
                    font-weight: bold;
                    display: block;
                    margin-bottom: 10px;
                }
                textarea {
                    width: 100%;
                    height: 80px;
                    padding: 10px;
                    border: 2px solid #ccc;
                    border-radius: 5px;
                    font-size: 16px;
                    resize: none;
                    box-sizing: border-box;
                }
                button {
                    background-color: rgb(0,73,174);
                    color: white;
                    border: none;
                    padding: 10px 15px;
                    font-size: 16px;
                    cursor: pointer;
                    border-radius: 5px;
                    margin-top: 10px;
                }
            </style>
        </head>
        <body>
            <div class="checkout-container">
                <form method="POST" action="processCheckIn.php">
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($person_id); ?>">
                    <input type="hidden" name="eventid" value="<?php echo htmlspecialchars((string)$eventid); ?>">
                    <input type="hidden" name="roleid" value="<?php echo htmlspecialchars((string)$resolvedRoleId); ?>">
                    <label for="desc">Enter a description before checking out:</label>
                    <textarea name="desc" id="desc"></textarea>
                    <button type="submit">Check Out</button>
                </form>
            </div>
        
        </body>
        </html>
        <?php
        exit;
    }

    $closed = closePersonHoursCheckIn($eventid, $person_id, $resolvedRoleId);

    if (!$closed) {
        die("Error: No open dbpersonhours row was found to check out.");
    }

    showSuccessMessage(
        "Checked out successfully!",
        buildCheckOutReturnUrl($eventid, $person_id, "Checked out successfully!")
    );
} else {
    savePersonHoursCheckIn($eventid, $person_id, $resolvedRoleId);

    showSuccessMessage(
        "Checked in successfully!",
        buildCheckInReturnUrl("Checked in successfully!")
    );
}
?>