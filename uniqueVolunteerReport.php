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

// Initialize date variables so warnings do not occur
$sdate = $_POST['sdate'] ?? '';
$edate = $_POST['edate'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Unique Volunteer Report | Love Thy Neighbor Community Food Pantry</title>
    <!-- <link href="css/base.css" rel="stylesheet"> -->
    <link href="css/layoutInfo.css" rel="stylesheet">
    <?php require_once('header.php'); ?>
</head>
<body>
    <!-- These probably are not needed here yet, but leaving them is fine -->
    <?php require_once('database/dbEvents.php'); ?>
    <?php require_once('database/dbPersons.php'); ?>
    <div class="page-wrapper">
        <div class="info-card">
            <div class="info-header">
                <h1>Generate Unique Volunteer Report</h1>
                <p>Use this tool to generate a PDF report showing volunteers who participated during a selected date range, along with the number of events each volunteer participated in.</p>
            </div>

            <main>
                <!-- <div class="main-content-box"> -->
                    <form method="POST" action="processUniqueVolunteerReport.php" class="info-form">
                        <div class="form-group">
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
                            <label for="format" style="font-weight: 600;">File Format-</span>
                            <span style="color: #000; ">PDF (.pdf)</span>
                            <input type="hidden" name="format" id="format" value="pdf">
                        </div>

                        <div style="text-align: center; margin-top: 2rem;">
                            <input type="hidden" value="<?php echo $_SESSION['_id']; ?>" name="admin" id="admin">
                            <input type="hidden" value="<?php echo date("d-M-Y H:i:s e"); ?>" name="time" id="time">
                            <button type="submit" name="action" value="send" class="submit-btn">Generate Report</button>
                        </div>
                    </form>
                <!-- </div> -->

                <!-- <div style="text-align: center; margin-top: 2rem;">
                    <a href="index.php" class="button" style="display: inline-block; text-decoration: none; width: 41%;">Return to Dashboard</a>
                </div> -->
            </main>
        </div>
    </div>
</body>
</html>

<?php include 'footer.php'; ?>
</body>
</html>
