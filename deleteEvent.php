<?php
    session_cache_expire(30);
    session_start();

    if ($_SESSION['access_level'] < 2) {
        header('Location: index.php');
        die();
    }
    require_once('database/dbEvents.php');
    require_once('include/input-validation.php');
    //$args = sanitize($_POST);
    //$id = $args['id'];
    if (isset($_GET['id'])) {
        $id = $_GET['id'];
    }
    if (!$id) {
        header('Location: index.php');
        die();
    }
    

    //added for reoccuring events.. will delete
    $event = fetch_event_by_id($id);

    if ($event && !empty($event['series_id'])) {
    $con = connect(); // uses the same DB connection from dbEvents.php
    $series_id = $event['series_id'];
    $stmt = mysqli_prepare($con, "DELETE FROM dbevents WHERE series_id = ?");
    mysqli_stmt_bind_param($stmt, "s", $series_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($con);

    header('Location: calendar.php?deleteSuccess');
    die();
}  
    if (delete_event($id)) {
        header('Location: calendar.php?deleteSuccess');
        die();
    }

    //
    header('Location: index.php');
?>