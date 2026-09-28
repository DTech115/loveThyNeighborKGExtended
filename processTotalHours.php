<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_cache_expire(30);
session_start();

if (!isset($_SESSION['access_level']) || $_SESSION['access_level'] < 2) {
    header('Location: login.php');
    die();
}

require_once('database/dbPersons.php');
require_once('database/dbEvents.php');
require_once('database/dbpersonhours.php');

$sdate = $_POST['sdate'];
$edate = $_POST['edate'];

$format = $_POST['format'] ?? 'csv';
$reportData = [];
$reportData = roleHoursForDateRange($sdate,$edate);
$time = time();

// CSV EXPORT
if ($format === 'csv') {
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=top10_report_{$time}.csv");
    header("Pragma: no-cache");
    header("Expires: 0");

    $output = fopen('php://output', 'w');
    fputcsv($output, ["top10_report_{$time}.csv"]);

    // Column Headers
    fputcsv($output, ["Role", "Hours"]);

    // Data
    foreach ($reportData as $p) {
        fputcsv($output, [
           $p[0],
            $p[1],
            $p[2]
            
        ]);
    }
    fclose($output);
    exit();
}

// EXCEL EXPORT
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=totalHours-daterange_report_{$time}.xls");
header("Pragma: no-cache");
header("Expires: 0");

echo "<html><head><meta charset='UTF-8'></head><body>";
echo "<table border='1' style='border-collapse: collapse; font-family: Arial, sans-serif; text-align: center;'>";

// Report Title
echo "<tr><th colspan='3' style='font-size: 18px; background-color: #004488; color: white; padding: 30px;'>Roles and hours for date range</th></tr>";

// Column Headers
echo "<tr>
        <th style='background-color: #88CCEE; padding: 150px;'>Role</th>
        <th style='background-color: #AA4499; padding: 150px;'>Hours</th>
        <th style='background-color: #AA4499; padding: 150px;'>Minutes</th>
      </tr>";

// Data Rows
foreach ($reportData as $p) {
    echo "<tr>
            <td style='background-color: #EAEAEA; padding: 150px; text-align: center;'>$p[0]</td>
            <td style='padding: 150px;'>{$p[1]}</td>
            <td style='padding: 150px;'>{$p[2]}</td>
          </tr>";
}

echo "</table>";
echo "</body></html>";
exit();

?>
