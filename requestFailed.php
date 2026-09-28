<?php
    session_cache_expire(30);
    session_start();
    header("refresh:2; url=viewMyUpcomingEvents.php");
?>
    <!DOCTYPE html>
    <html>
        <head>
            <?php require_once('universal.inc') ?>
            <title>Sign-Up for Event | Love Thy Neighbor Community Food Pantry</title>
        </head>
        <body>
            <?php require_once('header.php') ?>
            <h1>Oops! You are already on the sign-up waitlist for this event.</h1>
        </body>
    </html>

    <?php
    exit();
    ?>