<?php
    session_cache_expire(30);
    session_start();
    header("refresh:2;url=index.php"); //The admin will be sent to the dashboard after an event was created! -Brooke
?>

    <!DOCTYPE html>
    <html>
        <head>
            <?php require_once('universal.inc') ?>
            <title>Create Event | Love Thy Neighbor Community Food Pantry</title>
        </head>
        <body>
            <?php require_once('header.php') ?>
            <h1 style="color: var(--accent-color); font-weight: bold;">Event Created!</h1> <!-- The text is now Love Thy Neighbor Blue! - Brooke -->
        </body>
    </html>