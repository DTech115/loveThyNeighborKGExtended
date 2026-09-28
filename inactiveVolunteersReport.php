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
    <title>Inactive Volunteers Report | Love Thy Neighbor Community Food Pantry</title>
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
                <h1>Generate Inactive Volunteers Report</h1>
                <p>Use this tool to generate a PDF report showing volunteers who have not participated in an event the past year, along with the date of their last participation.</p>
            </div>
            <!-- <div class="center-header">
                <h1 style="color:black;">Generate Inactive Volunteers Report</h1>
            </div> -->

            <!-- <section class="section-box">
                <p style="margin-top: 1rem; text-align:center;">
                    Use this tool to generate a PDF report showing volunteers who have not participated in an event the past year, along with the date of their last participation.
                </p>
            </section> -->

            <main>
                <!-- <div class="main-content-box"> -->
                    <form method="POST" action="processInactiveVolunteersReport.php">
                        <div style="margin-bottom: 1.5rem; margin-top: 1.5rem;">
                            <label for="format" style="font-weight: 600;">File Format-</span>
                            <span style="color: #000; ">PDF (.pdf)</span>
                            <input type="hidden" name="format" id="format" value="pdf">
                        </div>
        
                        <div style="text-align: center; margin-top: 2rem;">
                            <input type="hidden" value="<?php echo $_SESSION['_id']; ?>" name="admin" id="admin">
                            <input type="hidden" value="<?php echo date("d-M-Y H:i:s e"); ?>" name="time" id="time">
                            <input type="submit" value="Generate Report" class="submit-btn">
                        </div>
                    </form>
                <!-- </div> -->

                <!-- <div style="text-align: center; margin-top: 2rem;">
                    <a href="index.php" class="button" style="display: inline-block; text-decoration: none; width: 41%;">Return to Dashboard</a>
                </div> -->
            </main>
        </div>
    </div>
<?php include 'footer.php'; ?>
</body>
</html>

