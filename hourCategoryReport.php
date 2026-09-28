<?php
session_cache_expire(30);
session_start();
ini_set("display_errors", 1);
error_reporting(E_ALL);
date_default_timezone_set("America/New_York");

// Ensure admin authentication
if (!isset($_SESSION['access_level']) || $_SESSION['access_level'] < 2) {
    header('Location: login.php');
    die();
}

require_once('database/dbinfo.php');

// Initialize date variables so warnings do not occur
$sdate = $_POST['sdate'] ?? '';
$edate = $_POST['edate'] ?? '';

$selectedRoles = $_POST['roles'] ?? [];

// Fetch all roles for checkbox options
$roles = [];
$con = connect();
$querey = "SELECT role_id, role FROM dbroles ORDER BY role_id ASC";
$stmt = $con->prepare($querey);
$stmt->execute();
$stmt->bind_result($roleId, $roleName);

while ($stmt->fetch()) {
    $roles[] = [$roleId, $roleName];
}
$con->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Hour Category Report | Love Thy Neighbor Community Food Pantry</title>
    <!-- <link href="css/base.css" rel="stylesheet"> -->
    <link href="css/layoutInfo.css" rel="stylesheet">
    <?php require_once('header.php'); ?>
</head>
<body>
    <div class="page-wrapper">
            <div class="info-card">
                <div class="info-header">
                    <h1>Generate Hour Category Report</h1>
                    <p>Use this tool to generate a PDF report showing volunteer hours categorized by type of service during a selected date range.</p>
                </div>
            <main>
                <div class="main-content-box">
                    <form method="POST" action="processHourCategoryReport.php" class="info-form">

                        <div style="margin-bottom: 1.5rem; margin-top: 1.5rem;">
                            <div class="Start date">
                                <label for="sdate">* Start Date </label>
                                <input type="date" id="sdate" name="sdate" value="<?php echo htmlspecialchars($sdate); ?>" required>
                            </div>

                            <div class="End date">
                                <label for="edate">* End Date </label>
                                <input type="date" id="edate" name="edate" value="<?php echo htmlspecialchars($edate); ?>" required>
                            </div>
                        </div>

                        <div style="margin-bottom: 1.5rem; margin-top: 1.5rem;">
                            <p style="font-weight: 600; margin-bottom: 0.75rem;">* Select Hour Categories</p>

                            <div style="margin-bottom: 0.75rem;">
                                <label class="checkbox-group">
                                    <input type="checkbox" id="check_all_roles">
                                    <span>Check All</span>
                                </label>
                            </div>

                            <div class="checkbox-group checkbox-grid">
                                <?php foreach ($roles as $role): ?>
                                    <label>
                                        <input
                                            type="checkbox"
                                            class="role-checkbox"
                                            name="roles[]"
                                            value="<?php echo htmlspecialchars($role[0]); ?>"
                                            <?php echo in_array((string)$role[0], array_map('strval', $selectedRoles), true) ? 'checked' : ''; ?>
                                        >
                                        <?php echo htmlspecialchars($role[1]); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div style="margin-bottom: 1.5rem; margin-top: 1.5rem;">
                            <label for="format" style="font-weight: 600;">File Format-</label>
                            <span style="color: #000;">PDF (.pdf)</span>
                            <input type="hidden" name="format" id="format" value="pdf">
                        </div>

                        <div style="text-align: center; margin-top: 2rem;">
                            <input type="hidden" value="<?php echo htmlspecialchars($_SESSION['_id']); ?>" name="admin" id="admin">
                            <input type="hidden" value="<?php echo htmlspecialchars(date("d-M-Y H:i:s e")); ?>" name="time" id="time">
                            <button type="submit" name="action" value="send" class="submit-btn">Generate Report</button>
                        </div>
                    </form>
                </div>

                <!-- <div style="text-align: center; margin-top: 2rem;">
                    <a href="index.php" class="button" style="display: inline-block; text-decoration: none; width: 41%;">Return to Dashboard</a>
                </div> -->
            </main>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        const checkAllBox = document.getElementById('check_all_roles');
        const roleCheckboxes = document.querySelectorAll('.role-checkbox');

        function syncCheckAllState() {
            const allChecked = Array.from(roleCheckboxes).length > 0 &&
                Array.from(roleCheckboxes).every(cb => cb.checked);
            checkAllBox.checked = allChecked;
        }

        checkAllBox.addEventListener('change', function () {
            roleCheckboxes.forEach(cb => {
                cb.checked = checkAllBox.checked;
            });
        });

        roleCheckboxes.forEach(cb => {
            cb.addEventListener('change', syncCheckAllState);
        });

        syncCheckAllState();
    </script>
</body>
</html>