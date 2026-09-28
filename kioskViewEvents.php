<?php
session_cache_expire(30);
session_start();

date_default_timezone_set("America/New_York");

if (!isset($_SESSION['_id']) || $_SESSION['_id'] !== 'vmskiosk') {
    header('Location: login.php');
    exit();
}

if (isset($_GET['new_user']) && $_GET['new_user'] !== '') {
    $_SESSION['kiosk_new_user'] = $_GET['new_user'];
}

$newUser = $_SESSION['kiosk_new_user'] ?? '';

if ($newUser === '') {
    header('Location: kioskindex.php');
    exit();
}

require_once('database/dbEvents.php');

$events = get_all_events_sorted_by_date_not_archived();
$today = new DateTime();
$today->setTime(0, 0, 0);

/*
$todaysEvents = array_filter($events, function($event) use ($today) {
    $eventDate = new DateTime($event->getStartDate());
    $eventDate->setTime(0, 0, 0);
    //return $eventDate == $today; //Can't add events, setting time to Brooke's Event on 3/29
});
*/
$today = new DateTime('2026-03-29'); // TEMP TEST
$today->setTime(0, 0, 0);

$todaysEvents = array_filter($events, function($event) use ($today) {
    $eventDate = new DateTime($event->getStartDate());
    $eventDate->setTime(0, 0, 0);

    return $eventDate->format('Y-m-d') === $today->format('Y-m-d');
});
?>

<!DOCTYPE html>
<html>
    <head>
        <?php require_once('universal.inc') ?>
        <link rel="stylesheet" href="css/event.css">
        <title>Kiosk View Events | Love Thy Neighbo Community Food Pantry</title>
    </head>
    <body>
        <?php require_once('header.php') ?>
        <h1>Today's Events</h1>
        <main class="general">
            <p>New user: <?= htmlspecialchars($newUser) ?></p>

            <?php if (empty($todaysEvents)): ?>
                <p>No events are happening today.</p>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="general">
                        <thread>
                            <tr>
                                <th style="width:1px">Title</th>
                                <th style="width:1px">Time</th>
                                <th style="width:1px">Role</th>
                                <th style="width:1px"></th>
                            </tr>
                        </thread>
                        <tbody>
                    <?php foreach ($todaysEvents as $event): ?>
                        <tr>
                            <td><?= htmlspecialchars($event->getName()) ?></td>
                            <td>
                                <?= htmlspecialchars($event->getStartTime()) ?>
                                -
                                <?= htmlspecialchars($event->getEndTime()) ?>
                            </td>
                            <td>
                                <form method="post" style="margin:0;">
                                    <input type="hidden" name="event_id" value="<?= $event->getID() ?>">
                                    <select name="role_id">
                                        <option value="">No specific role</option>
                                        <option value="1">Cleanup</option>
                                        <option value="2">Table</option>
                                    </select>
                            </td>
                            <td>
                                    <button type="submit" name="go">Go</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </body>
</html>
