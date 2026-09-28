<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    session_cache_expire(30);
    session_start();

    date_default_timezone_set("America/New_York");
    
    include_once('database/dbPersons.php');
    include_once('domain/Person.php');
    // Get date?
    if (isset($_SESSION['_id'])) {
        $person = retrieve_person($_SESSION['_id']);

        if (!$person || !($person instanceof Person)) {
            // Handle invalid session user or missing DB record safely.
            header('Location: login.php');
            die();
        }

        $notRoot = $person->get_id() != 'vmsroot';
        $notKisok = $person->get_id() != 'vmskiosk';
    } else {
        header('Location: login.php');
        die();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="./css/base.css" rel="stylesheet">
    <title>Dashboard | Love Thy Neighbor Community Food Pantry Volunteer Management</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Quicksand, sans-serif;
            /* background-color: #1F1F21; */
        }

        h2 {
        	font-weight: normal;
            font-size: 30px;
        }

        .full-width-bar {
            width: 100%;
            background: rgb(0, 74, 173); /*Love Thy Neighbor Blue*/
            padding: 17px 5%;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }
        .full-width-bar-sub {
            width: 100%;
            /* background: #1F1F21; */
            padding: 17px 5%;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }

        .content-box {
            flex: 1 1 280px; /* Adjusts width dynamically */
            max-width: 375px;
            padding: 10px 2px; /* Altered padding to make closer */
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            position: relative;
        }

        .content-box-sub {
            flex: 1 1 300px; /* Adjusts width dynamically */
            max-width: 470px;
            padding: 10px 10px; /* Altered padding to make closer */
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            position: relative;
        }

        .content-box img {
            width: 100%;
            height: auto;
            /* background: white; */
            border-radius: 5px;
            border-bottom-right-radius: 50px;
            border: 0.5px solid #828282;
        }

        .content-box-sub img {
            width: 105%;
            height: auto;
            /* background: white; */
            border-radius: 5px;
            border-bottom-right-radius: 50px;
            border: 1px solid #828282;
        }

        .small-text {
            position: absolute;
            top: 20px;
            left: 30px;
            font-size: 14px;
            font-weight: 700;
            color: #3A3A3A;
        }

        .large-text {
            position: absolute;
            top: 40px;
            left: 30px;
            font-size: 22px;
            font-weight: 700;
            color: black;
            max-width: 90%;
        }

        .large-text-sub {
            position: absolute;
            /*top: 120px;*/
            top: 60%;
            left: 10%;
            font-size: 22px;
            font-weight: 700;
            color: black;
            max-width: 90%;
        }

        .graph-text {
            position: absolute;
            top: 75%;
            left: 10%;
            font-size: 14px;
            font-weight: 700;
            color: #C9AB81;
            max-width: 90%;
        }

        /* Navbar Container */
        .navbar {
            width: 100%;
            height: 95px;
            position: fixed;
            top: 0;
            left: 0;
            background: #C9AB81;
            box-shadow: 0px 2px 8px rgba(0, 0, 0, 0.25);
            display: flex;
            align-items: center;
            padding: 0 20px;
            z-index: 1000;
        }

        /* Left Section: Logo & Nav Links */
        .left-section {
            display: flex;
            align-items: center;
            gap: 30px; /* Space between logo and links */
        }

        /* Logo */
        .logo-container {
            background: #C9AB81;
            padding: 10px 20px;
            border-radius: 50px;
            box-shadow: 0px 4px 4px rgba(0, 0, 0, 0.25) inset;
        }

        .logo-container img {
            width: 128px;
            height: 52px;
            display: block;
        }

        /* Navigation Links */
        .nav-links {
            display: flex;
            gap: 20px;
        }

        .nav-links div {
            font-size: 24px;
            font-weight: 700;
            color: black;
            cursor: pointer;
        }

        /* Right Section: Date & Icon */
        .right-section {
            margin-left: auto; /* Pushes right section to the end */
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .date-box {
            background: #C9AB81;
            padding: 10px 30px;
            border-radius: 50px;
            box-shadow: -4px 4px 4px rgba(0, 0, 0, 0.25) inset;
            color: white;
            font-size: 24px;
            font-weight: 700;
            text-align: center;
        }

        .icon {
            width: 47px;
            height: 47px;
            /*background: #292D32;*/
            border-radius: 50%;

        }

        /* Button Control */
        .arrow-button {
            position: absolute;
            bottom: 30px;
            right: 30px;
            background: transparent;
            border: none;
            font-size: 20px;
            cursor: pointer;
            transition: transform 0.3s ease;

        }

        .arrow-button:hover {
            transform: translateX(5px); /* Moves the arrow slightly on hover */
        }
    .circle-arrow-button {
        position: absolute;
        bottom: 30px;
        display: flex;
        align-items: center;
        gap: 10px;
        background: transparent;
        border: none;
        font-size: 20px;
        font-family: Quicksand, sans-serif;
        font-weight: bold;
        color: black;
        cursor: pointer;
        transition: transform 0.3s ease;

    }

    .circle {
        width: 30px;
        height: 30px;
        background-color: rgb(0, 74, 173); /* Love Thy Neighbor Blue */
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        transition: transform 0.3s ease;
    }

    .circle-arrow-button:hover {
        background-color:transparent !important;
    }

    .circle-arrow-button:hover .circle {
        transform: translateX(5px); /* Moves the circle slightly on hover */
    }
.colored-box {
    display: inline-block; /* Ensures it wraps tightly around the text */
    background-color: #C9AB81; /* Change to any color */
    color: white; /* Text color */
    padding: 1px 5px; /* Adds space inside the box */
    border-radius: 5px; /* Optional: Rounds the corners */
    font-weight: bold; /* Optional: Makes text bold */
}


        /* Footer */
        .footer {
            width: 100%;
            background: #C9AB81;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 30px 50px;
            flex-wrap: wrap;
        }

        /* Left Section */
        .footer-left {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .footer-logo {
            width: 150px; /* Adjust logo size */
            margin-bottom: 15px;
        }

        /* Social Media Icons */
        .social-icons {
            display: flex;
            gap: 15px;
        }

        .social-icons a {
            color: white;
            font-size: 20px;
            transition: color 0.3s ease;
        }

        .social-icons a:hover {
            color: #dcdcdc;
        }

        /* Right Section */
        .footer-right {
            display: flex;
            gap: 50px;
            flex-wrap: wrap;
            align-items: flex-start;
        }

        .footer-section {
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 10px;
            color: #C9AB81;
            font-family: Inter, sans-serif;
            font-size: 16px;
            font-weight: 500;
        }

        .footer-topic {
            font-size: 18px;
            font-weight: bold;
        }

        .footer a {
            color: white;
            text-decoration: none;
            transition: background 0.2s ease, color 0.2s ease;
            padding: 5px 10px;
            border-radius: 5px;
        }

        .footer a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #dcdcdc;
        }

        /* Icon Overlay */
        .background-image {
            width: 100%;
            border-radius: 10px;
        }

        .icon-overlay {
            position: absolute;
            top: 40px; /* Adjust as needed */
            left: 50%;
            transform: translateX(-50%);
            background: rgba(255, 255, 255, 0.8); /* Optional background for better visibility */
            padding: 10px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .icon-overlay img {
            width: 40px; /* Adjust size as needed */
            height: 40px;
            opacity: 0.9;
        }

        .content-box-test:hover .icon-overlay img {
            transform: scale(1.1) rotate(5deg);
            transition: transform 0.5s ease, fill 0.5s ease;
        }

        
        

    
        .content-box-test {
            position: relative;
            background-color: #004AAD;  /* Love Thy Neighbor Blue /*

            border-radius: 12px;
            padding: 20px;
            color: black;                 /* default text color */
            flex: 1 1 280px;
            max-width: 375px;
            min-height: 250px;            /* keeps all boxes same height even without bg image */
            }


        .content-box-test .large-text-sub,
        .content-box-test .graph-text {
            color: black;
            }


        .background-image {
        display: none;
        }

        
        .full-width-bar-sub{
            /* background-color: #1F1F21 !important; */ 
            }


        /* Responsive Design */
   </style>
<!--BEGIN TEST, UPLOAD AND NOTIFICATIONS CHANGED-->
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            document.querySelector(".extra-info").style.maxHeight = "0px"; // Ensure proper initialization
        });
        function toggleInfo(event) {
            event.stopPropagation(); // Prevents triggering the main button click
            let info = event.target.nextElementSibling;
            let isVisible = info.style.maxHeight !== "0px";
            info.style.maxHeight = isVisible ? "0px" : "100px";
            event.target.innerText = isVisible ? "↓" : "↑";
        }
    </script>
<!--END TEST-->
</head>
<?php require 'header.php';?>

  

  <!-- Icon Container -->
<div style="position: absolute; top: 110px; right: 30px; z-index: 999; display: flex; flex-direction: row; gap: 30px; align-items: center; text-align: center;">
</div>
    <!-- Dummy content to enable scrolling -->
    <div style="margin-top: 0px; padding: 30px 20px;">
        <h2><b>Welcome to the Kiosk Check In!</b> </h2>
    </div>

    <!-- <div class="full-width-bar">
    <div class="content-box">
    <img src="images/LoveThyNeighbor_wood.jpg" style="filter: drop-shadow(8px 8px 12px rgba(0,0,0,0.5));"/>  
        <div class="small-text">Make a difference.</div>
        <div class="large-text">Login</div>
        <div class="nav-buttons">
            <button class="nav-button" onclick="window.location.href='kiosklogin.php'">
                <span class="arrow"><img src="images/view-profile.svg" style="width: 40px; border-radius:5px; border-bottom-right-radius: 20px;"></span>
                <span class="text">Login</span>
            </button>
        </div>
    </div>
    
    <div class="content-box">
        <img src="images/LoveThyNeighbor_wood.jpg" style="filter: drop-shadow(8px 8px 12px rgba(0,0,0,0.5));"/> <!-- wooden container (Brooke) -->
        <div class="small-text">Let’s have some fun!</div>
        <div class="large-text">My Events</div>
        <!-- <div class="nav-buttons">
            <button class="nav-button" onclick="window.location.href='viewAllEvents.php'">
                <span class="arrow"><img src="images/new-event.svg" style="width: 40px; border-radius:5px; border-bottom-right-radius: 10px;"></span>
                <span class="text">Sign-Up</span>
            </button>
            <button class="nav-button" onclick="window.location.href='viewMyUpcomingEvents.php'">
                <span class="arrow"><img src="images/list-solid.svg" style="width: 40px; border-radius:5px; border-bottom-right-radius: 10px;"></span>
                <span class="text">Upcoming</span>
            </button>
            
        </div> -->
    </div>

    
    </div>

    <div style="margin-top: 50px; padding: 0px 80px;">
        <h2><b>Options:</h2>
    </div>
    <div class="full-width-bar-sub">
        <div class="content-box-test" onclick="window.location.href='QuickVolunteerRegister.php'">
            <div class="icon-overlay">
                <img style="border-radius: 5px;" src="images/add-person.svg" alt="Calendar Icon">
            </div>
            
            <div class="large-text-sub" style="color:#FFFFFF;">Register</div>
            <div class="graph-text" style="color:#FFFFFF;">Don't have an account? Register here.</div>
            <button class="arrow-button" style="color:#FFFFFF;">→</button>
        </div>

        <div class="content-box-test" onclick="window.location.href='kiosklogin.php'">
            <div class="icon-overlay">
                <img style="border-radius: 5px;" src="images/User Icon.svg" alt="Calendar Icon">
            </div>
            
            <div class="large-text-sub" style="color:#FFFFFF;">Login</div>
            <div class="graph-text" style="color:#FFFFFF;">Already have an account with us? Login here.</div>
            <button class="arrow-button" style="color:#FFFFFF;">→</button>
        </div>

        <!--change onclick once a kioskViewEvents exists: onclick="window.location.href='viewAllEvents.php?start_date='<'?= date('Y-m-d') ?>&end_date='<'?= date('Y-m-d') ?>&hide_filter=1'"-->
        <div class="content-box-test" onclick="window.location.href='calendar.php'">
            <div class="icon-overlay">
                <img style="border-radius: 5px;" src="images/list-solid.svg" alt="Calendar Icon">
            </div>
            <!-- <img class="background-image" src="images/blank-white-background.jpg" /> -->
            <div class="large-text-sub" style="color:#FFFFFF;">Today's events</div>
            <div class="graph-text" style="color:#FFFFFF;">
                See what we're doing on <?php echo date("F j, Y"); ?>.
            </div>
            <button class="arrow-button" style="color:#FFFFFF;">→</button>
        </div> 

        <!-- <div class="content-box-test" onclick="window.location.href='calendar.php'">
            <div class="icon-overlay">
                <img style="border-radius: 5px;" src="images/view-calendar.svg" alt="Calendar Icon">
            </div>
            
            <div class="large-text-sub" style="color:#FFFFFF;">Calendar</div>
            <div class="graph-text" style="color:#FFFFFF;">See upcoming events/trainings.</div>
            <button class="arrow-button" style="color:#FFFFFF;">→</button>
        </div> -->
        
        


        

    </div>

<div style="width: 90%; /* Stops before page ends */
            height: 100%;
            outline: 1px #828282 solid;
            outline-offset: -0.5px;
            margin: 70px auto; /* Adds vertical space and centers */
            padding: 1px 0;"> <!-- Adds spacing inside the div -->
</div>

    <footer class="footer" style="margin-top: 100px;">
        <!-- Left Side: Logo & Socials -->
        <div class="footer-left">
            <img src="images/LoveThyNeighbor_logo1.jpeg" alt="Logo" class="footer-logo">
            <div class="social-icons">
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-linkedin"></i></a>
            </div>
        </div>

        <!-- Right Side: Page Links -->
        <div class="footer-right">
            <div class="footer-section">
                <div class="footer-topic">Connect</div>
                <a href="https://www.facebook.com/kglovethyneighbor/">Facebook</a>
                <a href="https://www.instagram.com/love_thy_neighbor_kg/">Instagram</a>
                <a href="https://www.kgfood.org/">Main Website</a>
            </div>
            <div class="footer-section">
                <div class="footer-topic">Contact Us</div>
                <a href="https://www.kgfood.org/contact">Email: kgc.ltn@gmail.com</a>
                <a href="https://www.kgfood.org/contact">Phone: (540) 709–1130</a>
                <!-- <a href="tel:5408981500">540-898-1500 (ext 117)</a> -->
            </div>
        </div>
    </footer>

    <!-- Font Awesome for Icons -->
    <script src="https://kit.fontawesome.com/yourkit.js" crossorigin="anonymous"></script>

</body>
</html>