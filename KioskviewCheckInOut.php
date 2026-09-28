<?php
session_cache_expire(30);
session_start();

include 'database/dbPersons.php';
include 'database/dbpersonhours.php';
include 'database/dbRoles.php';

$loggedIn = false;
$accessLevel = 0;
$userID = null;

$eventid = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (isset($_SESSION['_id'])) {
    $loggedIn = true;
    $accessLevel = $_SESSION['access_level'] ?? 0;
    $userID = $_SESSION['_id'];
}

if (!isset($_SESSION['access_level']) || $_SESSION['access_level'] < 2) {
    header('Location: KioskLogin.php');
    die();
}

if ($eventid <= 0) {
    die("Error: Missing event id.");
}

$roles = get_roles();
$rolesById = array();

if (is_array($roles)) {
    foreach ($roles as $role) {
        if (is_array($role) && isset($role['role_id'])) {
            $rolesById[(int)$role['role_id']] = $role;
        }
    }
}

$defaultRoleId = 0;
if (!empty($roles) && isset($roles[0]['role_id'])) {
    $defaultRoleId = (int)$roles[0]['role_id'];
}

$selectedWalkInRoleId = isset($_GET['roleID']) ? (int)$_GET['roleID'] : $defaultRoleId;

if ($selectedWalkInRoleId <= 0 || !isset($rolesById[$selectedWalkInRoleId])) {
    $selectedWalkInRoleId = $defaultRoleId;
}

function findOpenCheckInRoleId($eventid, $personid)
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

    $stmt->bind_param("is", $eventid, $personid);
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

function normalizeAssignedRoleIds($personid, $eventid)
{
    $normalized = array();

    if (!function_exists('getRolesForPersonEvent')) {
        return $normalized;
    }

    $assignedRoleIds = getRolesForPersonEvent($personid, $eventid);

    if (!is_array($assignedRoleIds)) {
        return $normalized;
    }

    foreach ($assignedRoleIds as $role) {
        if (is_array($role)) {
            if (isset($role['roleID'])) {
                $normalized[] = (int)$role['roleID'];
            } elseif (isset($role['role_id'])) {
                $normalized[] = (int)$role['role_id'];
            } elseif (isset($role['id'])) {
                $normalized[] = (int)$role['id'];
            }
        } else {
            $normalized[] = (int)$role;
        }
    }

    $normalized = array_values(array_filter($normalized));

    return $normalized;
}

function getRoleNameFromMap($roleId, $rolesById)
{
    if ($roleId > 0 && isset($rolesById[$roleId]) && isset($rolesById[$roleId]['role'])) {
        return $rolesById[$roleId]['role'];
    }

    if ($roleId > 0) {
        return 'Role #' . $roleId;
    }

    return '';
}

function getEffectiveRoleData($personid, $eventid, $walkInRoleId, $rolesById)
{
    $openRoleId = findOpenCheckInRoleId($eventid, $personid);
    if ($openRoleId > 0) {
        return array(
            'role_id' => $openRoleId,
            'role_name' => getRoleNameFromMap($openRoleId, $rolesById),
            'checked_in' => true
        );
    }

    $assignedRoleIds = normalizeAssignedRoleIds($personid, $eventid);

    if (!empty($assignedRoleIds)) {
        $roleId = (int)$assignedRoleIds[0];
    } else {
        $roleId = (int)$walkInRoleId;
    }

    return array(
        'role_id' => $roleId,
        'role_name' => getRoleNameFromMap($roleId, $rolesById),
        'checked_in' => false
    );
}

function extractUsernameFromSearchResult($user)
{
    if (is_string($user)) {
        return trim($user);
    }

    if (!is_array($user)) {
        return '';
    }

    $possibleKeys = array(
        'username',
        '_id',
        'id',
        'personID',
        'personid',
        'userID',
        'userid',
        'email'
    );

    foreach ($possibleKeys as $key) {
        if (isset($user[$key]) && is_scalar($user[$key])) {
            $value = trim((string)$user[$key]);
            if ($value !== '') {
                return $value;
            }
        }
    }

    return '';
}

if (isset($_GET['ajax']) && $_GET['ajax'] === 'search') {
    header('Content-Type: application/json');

    $query = trim($_GET['query'] ?? '');
    $ajaxEventId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $ajaxWalkInRoleId = isset($_GET['roleid']) ? (int)$_GET['roleid'] : $defaultRoleId;

    if ($query === '' || $ajaxEventId <= 0) {
        echo json_encode(array());
        exit;
    }

    if ($ajaxWalkInRoleId <= 0 || !isset($rolesById[$ajaxWalkInRoleId])) {
        $ajaxWalkInRoleId = $defaultRoleId;
    }

    $searchResults = searchUsers($query);
    $payload = array();

    if (!is_array($searchResults)) {
        $searchResults = array();
    }

    foreach ($searchResults as $user) {
        $username = extractUsernameFromSearchResult($user);

        if ($username === '') {
            continue;
        }

        $effectiveRoleData = getEffectiveRoleData($username, $ajaxEventId, $ajaxWalkInRoleId, $rolesById);

        $payload[] = array(
            'username' => $username,
            'checked_in' => $effectiveRoleData['checked_in'],
            'role_id' => $effectiveRoleData['role_id'],
            'role_name' => $effectiveRoleData['role_name']
        );
    }

    echo json_encode($payload);
    exit;
}

$flashMessage = $_GET['msg'] ?? '';
$initialQuery = $_GET['q'] ?? '';

include 'infoBox.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Volunteer Check-In</title>
    <link href="css/normal_tw.css" rel="stylesheet">
    <style>
    

        .results-scroll-box {
            max-height: 20rem;
            overflow-y: auto;
            overflow-x: hidden;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
        }

        .results-scroll-box table {
            width: 100%;
            border-collapse: collapse;
        }

        .results-scroll-box thead th {
            position: sticky;
            top: 0;
            z-index: 2;
        }
        .main-content-box {
            background: #fff;
            width: 45rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>

<header class="hero-header">
    <div class="center-header">
        <h1>Volunteer Check-In</h1>
    </div>
</header>

<main>
    <div class="main-content-box w-[80%] p-8">
        <div class="text-center mb-8">
            <h2>Find Your Account to Check In/Out</h2>
            <p class="sub-text">If you already have a role for this event, it will be filled in automatically.</p>
        </div>

        <?php if ($flashMessage !== ''): ?>
            <div style="margin-bottom: 16px; padding: 12px; border-radius: 8px; background: #eaf6ea; color: #1f5f1f; font-weight: 600;">
                <?php echo htmlspecialchars($flashMessage); ?>
            </div>
        <?php endif; ?>

        <div class="space-y-6">
            <div>
                <label for="role-select" style="display:block; margin-bottom:8px; font-weight:600;">
                    Pick a role if you don't already have one!
                </label>
                <select id="role-select" class="form-input w-full">
                    <?php if (!empty($roles)): ?>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo (int)$role['role_id']; ?>" <?php echo ((int)$role['role_id'] === $selectedWalkInRoleId) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($role['role']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="0">No roles available</option>
                    <?php endif; ?>
                </select>
            </div>

            <input
                type="text"
                id="search-box"
                placeholder="Search by Username..."
                class="form-input w-full"
                value="<?php echo htmlspecialchars($initialQuery); ?>"
            >

            <div class="results-scroll-box">
                <table class="w-full" id="results-table">
                    <thead class="bg-[#C9AB81] text-black">
                        <tr>
                            <th class="text-left p-2">Username</th>
                            <th class="text-left p-2">Role</th>
                            <th class="text-left p-2">Status</th>
                            <th class="text-left p-2">Action</th>
                        </tr>
                    </thead>
                    <tbody id="search-results"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="info-section">
        <div class="blue-div"></div>
        <p class="info-text">
            Use this tool to find your volunteer account and securely check in or out.
        </p>
    </div>

    <div class="info-section">
        <p class="text-center text-gray-700">
            Don’t have an account?
            <a href="QuickVolunteerRegister.php" id="signup-link" class="text-[rgb(203,37,26)] font-semibold hover:underline">Sign Up Now</a>
        </p>
    </div>
</main>

<script>
function runSearch(query) {
    const resultsList = document.getElementById("search-results");
    const roleSelect = document.getElementById("role-select");
    const selectedWalkInRoleId = roleSelect ? roleSelect.value : "0";

    if (query.length < 1) {
        resultsList.innerHTML = "";
        return;
    }

    fetch(`KioskviewCheckInOut.php?ajax=search&query=${encodeURIComponent(query)}&id=<?php echo $eventid; ?>&roleid=${encodeURIComponent(selectedWalkInRoleId)}`)
        .then(response => response.text())
        .then(text => {
            let data = [];

            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error("Invalid JSON response:", text);
                throw new Error("Invalid JSON response");
            }

            resultsList.innerHTML = "";

            if (!Array.isArray(data) || data.length === 0) {
                return;
            }

            data.forEach(item => {
                const username = item.username || "";
                const checkedIn = !!item.checked_in;
                const roleId = parseInt(item.role_id, 10) || 0;
                const roleName = item.role_name && item.role_name.length > 0 ? item.role_name : "No Role";

                const row = document.createElement("tr");

                const usernameCell = document.createElement("td");
                usernameCell.className = "p-2";
                usernameCell.textContent = username;

                const roleCell = document.createElement("td");
                roleCell.className = "p-2";
                roleCell.textContent = roleName;

                const statusCell = document.createElement("td");
                statusCell.className = "p-2";
                statusCell.textContent = checkedIn ? "Checked In" : "Not Checked In";

                const actionCell = document.createElement("td");
                actionCell.className = "p-2";

                const form = document.createElement("form");
                form.method = "POST";
                form.action = "processCheckIn.php";

                const userInput = document.createElement("input");
                userInput.type = "hidden";
                userInput.name = "user_id";
                userInput.value = username;

                const eventInput = document.createElement("input");
                eventInput.type = "hidden";
                eventInput.name = "eventid";
                eventInput.value = "<?php echo $eventid; ?>";

                const roleInput = document.createElement("input");
                roleInput.type = "hidden";
                roleInput.name = "roleid";
                roleInput.value = roleId;

                const button = document.createElement("button");
                button.type = "submit";
                button.className = "blue-button";

                if (roleId > 0) {
                    button.textContent = checkedIn ? "Check Out" : "Check In";
                } else {
                    button.textContent = "No Role";
                    button.disabled = true;
                }

                form.appendChild(userInput);
                form.appendChild(eventInput);
                form.appendChild(roleInput);
                form.appendChild(button);

                actionCell.appendChild(form);

                row.appendChild(usernameCell);
                row.appendChild(roleCell);
                row.appendChild(statusCell);
                row.appendChild(actionCell);

                resultsList.appendChild(row);
            });
        })
        .catch(error => {
            console.error("Error fetching results:", error);
        });
}

document.getElementById("search-box").addEventListener("input", function () {
    runSearch(this.value.trim());
});

document.getElementById("role-select").addEventListener("change", function () {
    runSearch(document.getElementById("search-box").value.trim());
});

const signupLink = document.getElementById("signup-link");
if (signupLink) {
    signupLink.addEventListener("click", function (e) {
        const userConfirmed = confirm("Are you sure you want to sign up for an account?");
        if (!userConfirmed) {
            e.preventDefault();
        }
    });
}

window.addEventListener("load", function () {
    const existingValue = document.getElementById("search-box").value.trim();
    if (existingValue.length > 0) {
        runSearch(existingValue);
    }
});
</script>

</body>
</html>