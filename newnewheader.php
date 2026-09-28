<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


require_once('database/dbPersons.php');

$firstName = $_SESSION['f_name'] ?? '';
$lastName = $_SESSION['l_name'] ?? '';
//$userName = trim($firstName . ' ' . $lastName);
$userName = trim($firstName);

if ($userName === '') {
    $userName = 'Guest';
}

if (!isset($pageTitle)) {
    $pageTitle = "LOVE THY NEIGHBOR";
}

if (!isset($currentDate)) {
    $currentDate = date("F j, Y");
}
?>

<div class="header">
    <div class="header-container">

        <div class="header-left">
            <div class="date"><?php echo $currentDate; ?></div>
        </div>
        <div class="header-center">
            <div class="header-top">
                <img src="images/LoveThyNeighbor_logo2.jpeg" class="logo" alt="Logo">

                <div class="text-group">
                    <h1 class="main-title">LOVE THY NEIGHBOR</h1>
                    <p class="sub-title">King George County Community Food Pantry</p>
                </div>
            </div>
        </div>
        <div class="header-right">
            <a href="logout.php" class="logout-link">
                <i class="fas fa-arrow-right-from-bracket logout-icon"></i>
                <span class="logout-text">Log Out</span>
            </a>
        </div>

    </div>
</div>