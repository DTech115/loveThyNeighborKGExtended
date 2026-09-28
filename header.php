<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('America/New_York');
require_once('database/dbPersons.php');
require_once('database/dbMessages.php');

$userID = $_SESSION['_id'] ?? null;   // change this after print_r
$unread_count = 0;

if ($userID) {
    $unread_count = get_user_unread_count($userID);
}

/*
 * Copyright 2013 by Allen Tucker. 
 * This program is part of RMHP-Homebase, which is free software.  It comes with 
 * absolutely no warranty. You can redistribute and/or modify it under the terms 
 * of the GNU General Public License as published by the Free Software Foundation
 * (see <http://www.gnu.org/licenses/ for more information).
 * 
if (date("H:i:s") > "18:19:59") {
	require_once 'database/dbShifts.php';
	auto_checkout_missing_shifts();
}
 */

// check if we are in locked mode, if so,
// user cannot access anything else without 
// logging back in
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;700&family=Quicksand:wght@300;400;500;700&display=swap" rel="stylesheet"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="header.css">
</head>
    <?php
    //Log-in security
    //If they aren't logged in, display our log-in form.
    $showing_login = false;
    if (!isset($_SESSION['logged_in'])) {
		echo('<div class="navbar">
        <!-- Left Section: Logo & Nav Links -->
        <div class="left-section">
            <div class="logo-container">
                <a href="index.php"><img src="images/ltn-logo1-circle.jpg" alt="Logo"></a>
            </div>
            <div class="nav-links">
                <div class="nav-item">
                    <a href="index.php" class="nav-link">Home</a>
                </div>
                <div class="nav-item">
                    <a href="calendar.php" class="nav-link">Events Calendar</a>
                </div>
            </div>
        </div>

        <!-- Right Section: Date & Icon -->
        <div class="right-section">
            <div class="nav-links">
                <div class="nav-item">
                    <div class="icon">
                        <img src="images/usaicon.png" alt="User Icon" class="icon-img in-nav-img">
                        <div class="dropdown">
                            <a href="signup.php" class="dropdown-link"><div>Create Account</div></a>
                            <a href="login.php" class="dropdown-link"><div>Log in</div></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>');

    } else if ($_SESSION['logged_in']) {

        /*         * Set our permission array.
         * anything a guest can do, a volunteer and manager can also do
         * anything a volunteer can do, a manager can do.
         *
         * If a page is not specified in the permission array, anyone logged into the system
         * can view it. If someone logged into the system attempts to access a page above their
         * permission level, they will be sent back to the home page.
         */
        //pages guests are allowed to view
        // LOWERCASE
        /*
        *  For A guest can log in, go to WVF's home page,  
        * -Evan
        */
        $permission_array['index.php'] = 0; // WVF Home page
        $permission_array['logout.php'] = 0; //WVF - Logout page ain
        $permission_array['volunteerregister.php'] = 0; //WVF - Alter to registering for account
        $permission_array['dashboard.php'] = 1; //WVF - Might be good to alter this for registered users to be able to see registered events and where they can edit user info 
        $permission_array['calendar.php'] = 1; // - Brooke made sure calendar is visible to volunteers and up
        $permission_array['eventsearch.php'] = 1; 
        $permission_array['changepassword.php'] = 1;
        $permission_array['editprofile.php'] = 1; 
        $permission_array['inbox.php'] = 1; //WVF - Not for registered users, since they want emails. But would be good for 'suggestions' for ADMINS to see 
        $permission_array['date.php'] = 1; 
        $permission_array['event.php'] = 0; 
        $permission_array['viewprofile.php'] = 1; 
        $permission_array['viewnotification.php'] = 1;
        $permission_array['volunteerreport.php'] = 1; //WVF - Attendance Report?
        $permission_array['viewmyupcomingevents.php'] = 1;
        $permission_array['volunteerviewgroup.php'] = 1; 
	    $permission_array['viewcheckinout.php'] = 1;
        $permission_array['viewresources.php'] = 1;
        $permission_array['volunteerviewgroupmembers.php'] = 1;
        //pages only managers can view
        $permission_array['viewallevents.php'] = 0; //WVF - For admins to do view 
        $permission_array['personsearch.php'] = 2;
        $permission_array['personedit.php'] = 0; // changed to 0 so that applicants can apply
        $permission_array['log.php'] = 2;    //Brooke needs to find this later
        $permission_array['reports.php'] = 2;
        $permission_array['modifyuserrole.php'] = 2;
        $permission_array['addevent.php'] = 2; //WVF - Admin Event work!
        $permission_array['editevent.php'] = 2; //WVF - Admin Event work!
        $permission_array['report.php'] = 2; // WVF TODO: Look to see how these reports can be reworked to do attendance report
        $permission_array['reportspage.php'] = 2;
        $permission_array['resetpassword.php'] = 2;
        $permission_array['eventsuccess.php'] = 2;
        $permission_array['viewsignuplist.php'] = 2;
        $permission_array['vieweventsignups.php'] = 2;
        $permission_array['resources.php'] = 2;
        $permission_array['uploadresources.php'] = 2;        
        $permission_array['deleteresources.php'] = 2;
        $permission_array['volunteermanagement.php'] = 2;
        $permission_array['eventmanagement.php'] = 2;
        $permission_array['checkedinvolunteers.php'] = 2;
        $permission_array['generatereport.php'] = 2; //adding this to the generate report page
        $permission_array['generateemaillist.php'] = 2; //adding this to the generate report page
        $permission_array['clockoutbulk.php'] = 2;
        $permission_array['clockOut.php'] = 2;
        $permission_array['edithours.php'] = 2;
        $permission_array['eventlist.php'] = 1;   
        $permission_array['eventsignup.php'] = 1;
        $permission_array['eventfailure.php'] = 1;
        $permission_array['signupsuccess.php'] = 1;
        $permission_array['edittimes.php'] = 1;
        $permission_array['adminviewingevents.php'] = 2;
        $permission_array['requestfailed.php'] = 1;
        $permission_array['settimes.php'] = 1;
        $permission_array['eventfailurebaddeparturetime.php'] = 1;
        $permission_array['createemail.php'] = 2;
        $permission_array['createemail.php'] = 2;
        $permission_array['viewdrafts.php'] = 2;  // Not sure if we want normal users to be able to send emails
        $permission_array['editdrafts.php'] = 2;
        $permission_array['processattendees.php'] = 2;
        $permission_array['viewdata.php'] = 2;
        $permission_array['deleteusersearch.php'] = 2;
        $permission_array['viewoveralluserskg.php'] = 2;
        $permission_array['viewoveralleventskg.php'] = 0;
        $permission_array['adjusteventhours.php'] = 2;
        $permission_array['viewallreports.php'] = 2;
        $permission_array['KioskviewOverallEventsKG.php'] = 4;
        $permission_array['totalhoursreport.php'] = 2;
        $permission_array['topvolunteersreport.php'] = 2;
        $permission_array['archive_people_list.php'] = 2;
        $permission_array['uniquevolunteerreport.php'] = 2;
        $permission_array['inactivevolunteersreport.php'] = 2;
        $permission_array['hourcategoryreport.php'] = 2;
        $permission_array['serviceletterreport.php'] = 2;
        

        // LOWERCASE



        //Check if they're at a valid page for their access level.
        $current_page = strtolower(substr($_SERVER['PHP_SELF'], strrpos($_SERVER['PHP_SELF'], '/') + 1));
        $current_page = substr($current_page, strpos($current_page,"/"));
        
        if($permission_array[$current_page]>$_SESSION['access_level']){
            //in this case, the user doesn't have permission to view this page.
            //we redirect them to the index page.
            echo "<script type=\"text/javascript\">window.location = \"index.php\";</script>";
            //note: if javascript is disabled for a user's browser, it would still show the page.
            //so we die().
            die();
        }
        //This line gives us the path to the html pages in question, useful if the server isn't installed @ root.
        $path = strrev(substr(strrev($_SERVER['SCRIPT_NAME']), strpos(strrev($_SERVER['SCRIPT_NAME']), '/')));
		$venues = array("portland"=>"RMH Portland"); // Is this used anywhere? Do we need it? -Blue
        
        //they're logged in and session variables are set.

        $firstName = $_SESSION['f_name'] ?? '';

        if (!isset($currentDate)) {
            $currentDate = date("F j, Y");
        }
    }
    ?>
    <?php if ($_SESSION['access_level'] >= 2): ?> 
        <div class="header">
            <div class="header-container">

                <div class="header-left">
                    <a href="calendar.php" class="calendar-icon">
                        <i class="fas fa-calendar-alt calendar-icon"></i> 
                    </a>
                        <div class="date"><?php echo $currentDate; ?></div>
                </div>
                <div class="header-center">
                    <div class="header-top">
                        <a href="index.php">
                            <img src="images/LoveThyNeighbor_logo2.jpeg" class="logo" alt="Logo">
                        </a>

                        <div class="text-group">
                            <h1 class="main-title">LOVE THY NEIGHBOR</h1>
                            <p class="sub-title">King George County Community Food Pantry</p>
                        </div>
                    </div>

                    <div class="header-nav">
                        <a href="calendar.php"
                        class="<?= basename($_SERVER['PHP_SELF']) == 'calendar.php' ? 'active' : '' ?>">
                        Calendar
                        </a>
                        <a href="index.php"
                        class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                        Dashboard
                        </a>
                        
                        <a href="viewOverallUsersKG.php"
                        class="<?= basename($_SERVER['PHP_SELF']) == 'viewOverallUsersKG.php' ? 'active' : '' ?>">
                        User
                        </a>

                        <a href="viewOverallEventsKG.php"
                        class="<?= basename($_SERVER['PHP_SELF']) == 'viewOverallEventsKG.php' ? 'active' : '' ?>">
                        Event
                        </a>
                        <a href="Archive_People_List.php"
                        class="<?= basename($_SERVER['PHP_SELF']) == 'Archive_People_List.php' ? 'active' : '' ?>">
                        Archive
                        </a>

                        <a href="viewMyUpcomingEvents.php"
                        class="<?= basename($_SERVER['PHP_SELF']) == 'viewMyUpcomingEvents.php' ? 'active' : '' ?>">
                        Signed-Up
                        </a>

                        <a href="createEmail.php"
                        class="<?= basename($_SERVER['PHP_SELF']) == 'createEmail.php' ? 'active' : '' ?>">
                        Email
                        </a>
                        <a href="viewAllReports.php"
                        class="<?= basename($_SERVER['PHP_SELF']) == 'viewAllReports.php' ? 'active' : '' ?>">
                        Report
                        </a>
                        <a href="inbox.php"
                        class="<?= basename($_SERVER['PHP_SELF']) == 'inbox.php' ? 'active' : '' ?>">
                        Notification
                        <?php if ($unread_count > 0): ?>
                            <span class="notif-badge">
                                <?php echo $unread_count; ?>
                            </span>
                        <?php endif; ?>
                        </a>
                        <a href="KioskviewOverallEventsKG.php"
                        class="<?= basename($_SERVER['PHP_SELF']) == 'KioskviewOverallEventsKG.php' ? 'active' : '' ?>">
                        Kiosk
                        </a>
                        
                    </div>
                </div>

                <div class="header-right">
                    <i class="fas fa-user-circle profile-icon"></i> 
                    <div class="profile-name">
                        <?php echo htmlspecialchars($firstName); ?>
                    </div>
                

                    <div class="dropdown">
                        <a href="viewProfile.php"
                        class="<?= basename($_SERVER['PHP_SELF']) == 'viewProfile.php' ? 'active' : '' ?>">
                        View Profile
                        </a>

                        <a href="editProfile.php"
                        class="<?= basename($_SERVER['PHP_SELF']) == 'editProfile.php' ? 'active' : '' ?>">
                        Edit Profile
                        </a>

                        <a href="changePassword.php"
                        class="<?= basename($_SERVER['PHP_SELF']) == 'changePassword.php' ? 'active' : '' ?>">
                        Change Password
                        </a>
                        
                        <a href="logout.php"
                        class="<?= basename($_SERVER['PHP_SELF']) == 'logout.php' ? 'active' : '' ?>">
                        Log Out
                        </a>

                    </div>
                </div>

            </div>
    <?php elseif ($_SESSION['access_level'] == 1):?>
        <div class="header">
            <div class="header-container">

                <div class="header-left">
                    <a href="calendar.php" class="calendar-icon">
                        <i class="fas fa-calendar-alt calendar-icon"></i> 
                    </a>
                        <div class="date"><?php echo $currentDate; ?></div>
                </div>

                <div class="header-center">
                    <div class="header-top">
                        <a href="index.php">
                            <img src="images/LoveThyNeighbor_logo2.jpeg" class="logo" alt="Logo">
                        </a>

                        <div class="text-group">
                            <h1 class="main-title">LOVE THY NEIGHBOR</h1>
                            <p class="sub-title">King George County Community Food Pantry</p>
                        </div>
                    </div>

                    <div class="header-nav">
                        <a href="calendar.php"
                        class="<?= basename($_SERVER['PHP_SELF']) == 'calendar.php' ? 'active' : '' ?>">
                        Calendar
                        </a>

                        <a href="index.php"
                        class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                        Dashboard
                        </a>

                        <a href="viewOverallEventsKG.php"
                        class="<?= basename($_SERVER['PHP_SELF']) == 'viewOverallEventsKG.php' ? 'active' : '' ?>">
                        Event
                        </a>

                        <a href="viewMyUpcomingEvents.php"
                        class="<?= basename($_SERVER['PHP_SELF']) == 'viewMyUpcomingEvents.php' ? 'active' : '' ?>">
                        Signed-Up
                        </a>

                        <a href="inbox.php"
                        class="<?= basename($_SERVER['PHP_SELF']) == 'inbox.php' ? 'active' : '' ?>">
                        Notification
                        </a>
                        
                    </div>
                </div>

                <div class="header-right">
                    <i class="fas fa-user-circle profile-icon"></i> 
                    <div class="profile-name">
                        <?php echo htmlspecialchars($firstName); ?>
                    </div>

                    <div class="dropdown">
                        <a href="viewProfile.php"
                        class="<?= basename($_SERVER['PHP_SELF']) == 'viewProfile.php' ? 'active' : '' ?>">
                        View Profile
                        </a>

                        <a href="editProfile.php"
                        class="<?= basename($_SERVER['PHP_SELF']) == 'editProfile.php' ? 'active' : '' ?>">
                        Edit Profile
                        </a>

                        <a href="changePassword.php"
                        class="<?= basename($_SERVER['PHP_SELF']) == 'changePassword.php' ? 'active' : '' ?>">
                        Change Password
                        </a>
                        
                        <a href="logout.php"
                        class="<?= basename($_SERVER['PHP_SELF']) == 'logout.php' ? 'active' : '' ?>">
                        Log Out
                        </a>
                    </div>
                </div>

            </div>
    </header>
    <?php endif; ?>
    
