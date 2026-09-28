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

// Get current fiscal year
$currentMonth = date("m");
$currentYear = date("Y");
$fiscalYearStart = ($currentMonth >= 10) ? $currentYear : $currentYear - 1;
$fiscalYearEnd = $fiscalYearStart + 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Top Volunteer Report | Love Thy Neighbor Community Food Pantry</title>
    <!--<script src="js/data-filters.js" defer></script>-->
    <!-- <link href="css/base.css" rel="stylesheet"> -->
    <link href="css/layoutInfo.css" rel="stylesheet">
    <?php require_once('header.php'); ?>
</head>
<body>
    <?php require_once('database/dbEvents.php'); ?>
    <?php require_once('database/dbPersons.php'); ?>

    <div class="page-wrapper">
        <div class="info-card">
            <div class="info-header">
                <h1>Generate Top Volunteers Report</h1>
                <p>Use this tool to generate a list of the top ten volunteers by total hours. Reports are available in PDF, Excel, or CSV format.</p>
            </div>

            <form method="POST" action="processTop10Report.php" class="info-form">
                <div style="margin-bottom: 1.5rem; margin-top: 1.5rem;">
                    <label for="format" style="font-weight: 600;">File Format</label>
                    <select name="format" id="format">
                        <option value="pdf">PDF (.pdf)</option>
                        <option value="excel">Excel (.xls)</option>
                        <option value="csv">CSV (.csv)</option>
                    </select>
                </div>

                <div class="email-actions">
                    <input type="hidden" value="<?php echo $_SESSION['_id']; ?>" name="admin" id="admin">
                    <input type="hidden" value="<?php echo date("d-M-Y H:i:s e"); ?>" name="time" id="time">
                    <button type="submit" name="action" value="send" class="submit-btn">Generate Report</button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
