<?php
session_cache_expire(30);
session_start();

if (!isset($_SESSION['access_level']) || $_SESSION['access_level'] < 1) {
    header('Location: login.php');
    die();
}

require_once('include/input-validation.php');
require_once('database/dbEvents.php');
require_once('database/dbPersons.php');

ini_set('display_errors', 1);
error_reporting(E_ALL);

$args = sanitize($_GET);
$id = $args['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['bulk_action'])) {
    $event_id = $_POST['event_id'] ?? null;
    $user_id = $_POST['user_id'] ?? null;

    if (!$event_id) {
        echo 'Event ID is missing.';
        die();
    }

    if (!$user_id) {
        echo 'User ID is missing.';
        die();
    }
    if ($_POST['action'] === 'remove'){
        if (remove_user_from_event($event_id, $user_id)) {
            $remove_success = "User $user_id was successfully removed.";
        } else {
            $remove_error = "Failed to remove user $user_id.";
        }
    }
}

$event_info = fetch_event_by_id($id);
if (!$event_info) {
    echo 'Invalid event ID.';
    die();
}

$signups = fetch_event_signups($id);
$access_level = $_SESSION['access_level'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    

    <title>View Event Details | <?php echo htmlspecialchars($event_info['name']); ?></title>
    <link rel="stylesheet" href="css/layoutInfo.css" />
</head>
<body>
<?php require_once('header.php'); ?>

<div class="page">
    <div class="main">
        <div class="page-header">
            <div>
                <h1><?php echo htmlspecialchars($event_info['name']); ?> Sign Ups</h1>
                <p class="signup-subtitle">Manage volunteers registered for this event</p>
            </div>
            <a href="viewOverallEventsKG.php" class="add-btn back-btn">← Back to Events</a>
        </div>

        <div class="event-summary">
            <h2><?php echo htmlspecialchars($event_info['name']); ?></h2>
            <div class="signup-count">
                <?php echo count($signups); ?> volunteer<?php echo count($signups) === 1 ? '' : 's'; ?> signed up
            </div>

            <?php if (!empty($remove_success)): ?>
                <div class="success"><?php echo htmlspecialchars($remove_success); ?></div>
            <?php endif; ?>

            <?php if (!empty($remove_error)): ?>
                <div class="error"><?php echo htmlspecialchars($remove_error); ?></div>
            <?php endif; ?>
        </div>

        <?php if (count($signups) > 0): ?>
            <div class="table-wrapper">
                <table class="table-card">
                    <thead>
                        <tr>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>User ID</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Photos?</th>
                            <?php if ($access_level >= 2): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($signups as $signup):
                            $user_info = retrieve_person($signup['userID']);
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user_info->get_first_name()); ?></td>
                                <td><?php echo htmlspecialchars($user_info->get_last_name()); ?></td>
                                <td>
                                    <a class="user-link" href="viewProfile.php?id=<?php echo urlencode($signup['userID']); ?>">
                                        <?php echo htmlspecialchars($signup['userID']); ?>
                                    </a>
                                </td>
                                    <td><?php echo htmlspecialchars($user_info->get_email()); ?></td>
                                    <td><?php echo htmlspecialchars($user_info->get_phone1()); ?></td>
                                    <td><?php echo htmlspecialchars($user_info->get_photo_release() == 1 ? 'Yes' : 'No'); ?></td>


                                

                                <?php if ($access_level >= 2): ?>
                                    <td>
                                        <div class="action-buttons">
                                            <form method="POST" action="./adjustEventHours.php">
                                                <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($id); ?>">
                                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($signup['userID']); ?>">
                                                <input type="hidden" name="nav" value="viewEventSignUps">
                                                <button type="submit" class="action-btn adjust-btn">Adjust Hours</button>
                                            </form>

                                            <form method="POST">
                                                <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($id); ?>">
                                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($signup['userID']); ?>">
                                                <input type="hidden" name="action" value="remove">
                                                <button type="submit" class="action-btn remove-btn" onclick="return confirm('Are you sure you want to remove this user?');">
                                                    Remove
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>No one has signed up for this event yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>