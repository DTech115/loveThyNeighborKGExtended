<?php
    // Template for new VMS pages. Base your new page on this one

    // Make session information accessible, allowing us to associate
    // data with the logged-in user.
    session_cache_expire(30);
    session_start();

    $loggedIn = false;
    $accessLevel = 0;
    $userID = null;
    if (isset($_SESSION['_id'])) {
        $loggedIn = true;
        // 0 = not logged in, 1 = standard user, 2 = manager (Admin), 3 super admin (TBI)
        $accessLevel = $_SESSION['access_level'];
        $userID = $_SESSION['_id'];
    }
    // admin-only access
    if ($accessLevel < 2) {
        header('Location: index.php');
        die();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Account Management Page</title>
  <link href="css/management_tw.css" rel="stylesheet">


<?php
$tailwind_mode = true;
require_once('header.php');
?>
<style>
        .date-box {
            background: #004AAD; /*Love Thy Neighbor Blue */ 
            padding: 7px 30px;
            border-radius: 50px;
            box-shadow: -4px 4px 4px rgba(0, 0, 0, 0.25) inset;
            color: white;
            font-size: 24px;
            font-weight: 700;
            text-align: center;
        }
	    
        .dropdown {
	    padding-right: 50px;
	    }
        
        body{
        /* background-color: #1F1F21; */
        }
        .button-section button{
        background-color: #FFFF; 
        color: #004AAD; /*Love Thy Neighbor Blue */ 
        }

        .button-left-gray{
        background-color: #004AAD; /*Love Thy Neighbor Blue */
        }

        .button-section .button-icon{
        filter: invert(1);
        }

    .top-bar {
      background-color: #004AAD;   /* Love Thy Neighbor Blue */
      height: 150px;             /* height of the bar */
      width: 100%;              /* full width */
      position: fixed;
    }
</style> 

</head>

<body>

  <!-- Larger Hero Section -->
  <!--<header class="hero-header"></header>-->
  <header class="top-bar"></header>

  <!-- Main Content -->
  <main style="margin-top: 100px;">
    <div class="sections">

      <!-- Buttons Section -->
      <div class="button-section">
        <button onclick="window.location.href='VolunteerRegister.php';">
	  <div class="button-left-gray"></div>
	  <div>Register New User</div>
	  <img class="button-icon" src="images/add-person.svg" alt="Person Icon">
        </button>

        <button onclick="window.location.href='personSearch.php';">
	  <div class="button-left-gray"></div>
	  <div>Search Registered Users</div>
	  <img class="button-icon" src="images/person-search.svg" alt="Person Icon">
        </button>

    <button onclick="window.location.href='noShows.php';">
	  <div class="button-left-gray"></div>
	  <div>View No Shows?</div>
	  <img class="button-icon h-10 w-10 left-5" src="images/clipboard-regular.svg" alt="Person Icon">
    </button>


    <button onclick="window.location.href='deleteUserSearch.php';">
        <div class="button-left-gray"></div>
        <div>Delete User</div>
        <img class="button-icon h-10 w-10 left-5" src="images/trash.svg" alt="Person Icon">
    </button>
	
	<div class="text-center mt-6">
        	<a href="index.php" class="return-button">Return to Dashboard</a>
	</div>
		
     </div>

      <!-- Text Section -->
      <div class="text-section">
        <h1>User Account Management</h1>
        <div class="div-blue"></div>
        <p>
          Welcome to the user management hub. From this menu, you will have access to operations such as creating, deleting, and searching accounts. More features soon to be implemented.
        </p>
      </div>

    </div>
  </main>
</body>
</html>

