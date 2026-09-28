<?php
session_start();
// Added
ini_set("display_errors", 1);
error_reporting(E_ALL);

$loggedIn = false;
$accessLevel = 0;
$username = null;

if (isset($_SESSION['_id'])) {
    $loggedIn = true;
    $accessLevel = $_SESSION['access_level'];
    $username = $_SESSION['_id']; // The username is stored here in $username
}

// Require admin privileges
if ($accessLevel < 1) {
    header('Location: login.php');
    die();
}
//End of added

// Create database connection HERE (so everything in this file can use it)
include_once('database/dbinfo.php'); 
$con=connect(); 

if (!$con) {
    die("Database connection failed: " . mysqli_connect_error());
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once('database/dbPersons.php'); ?>
    <title>Admin Reports | Love Thy Neighbor Community Food Pantry</title>
    <link rel="stylesheet" href="css/layoutInfo.css">
</head>
<body>

<?php include('header.php'); ?>

<div class="page-wrapper">
    <div class="reports-header">
        <h1>Reports Dashboard</h1>
        <p>Select a report to generate or view.</p>
    </div>

    <div class="reports-grid">

        <div class="report-card">
            <h2>Unique Volunteer Count</h2>
            <p>See how many different volunteers participated during a selected date range.</p>
            <a href="uniqueVolunteerReport.php" class="report-btn">Open Report</a>
        </div>

        <div class="report-card">
            <h2>Total Volunteer Hours</h2>
            <p>Calculate total volunteer hours for a selected time period.</p>
            <a href="totalHoursReport.php" class="report-btn">Open Report</a>
        </div>

        <div class="report-card">
            <h2>Top Volunteers</h2>
            <p>View the volunteers with the highest total number of hours.</p>
            <a href="topVolunteersReport.php" class="report-btn">Open Report</a>
        </div>

        <div class="report-card">
            <h2>Inactive Volunteers</h2>
            <p>Find volunteers who have not been active in one year.</p>
            <a href="inactiveVolunteersReport.php" class="report-btn">Open Report</a>
        </div>

        <div class="report-card">
            <h2>Hour Category Summary</h2>
            <p>See total hours by category, such as distribution or unloading.</p>
            <a href="hourCategoryReport.php" class="report-btn">Open Report</a>
        </div>

        <div class="report-card">
            <h2>Service-Hour Letter PDF</h2>
            <p>Generate an official PDF letter for a volunteer with verified service hours.</p>
            <a href="serviceLetterReport.php" class="report-btn">Open Report</a>
        </div>

        <!--<div class="report-card">
            <h2>Whiskey Valor Old Summary</h2>
            <p>Generate monthly or annual reports on volunteer activity. Reports are available in Excel or CSV format.</p>
            <a href="generateReport.php" class="report-btn">Open Report</a>
        </div>-->

    </div>
</div>
<?php include 'footer.php'; ?>
</body>
</html>
