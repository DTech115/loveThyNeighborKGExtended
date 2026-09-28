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
            <style>

                h1 {
                    font-family: Quicksand, sans-serif;
                    color: #white;
                    font-weight: normal;
                    font-size: 30px;
                }
            </style>
        </head>
        <body>
            <?php require_once('header.php') ?>
            <h1>Sign-Up Approved!</h1>
        </body>
    </html>