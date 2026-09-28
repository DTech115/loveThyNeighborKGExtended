<?php
session_cache_expire(30);
session_start();

// Check if the user is logged in
if (!isset($_SESSION['_id']) || empty($_SESSION['_id'])) {
    header('Location: login.php');
    exit();
}

// Check for appropriate access level
if ($_SESSION['access_level'] < 1) {
    header('Location: index.php');
    exit();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

$loggedIn = false;
$accessLevel = 0;
$userID = null;
if (isset($_SESSION['_id'])) {
    $loggedIn = true;
    $accessLevel = $_SESSION['access_level'];
    $userID = $_SESSION['_id'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <!-- <?php require_once('universal.inc') ?> -->
    <!-- <link rel="stylesheet" href="css/messages.css"> -->
    <link rel="stylesheet" href="css/layoutInfo.css">
    <script>
        function toggleBulkActions() {
            const checkboxes = document.querySelectorAll('.messageCheckbox');
            const bulkBar = document.getElementById('bulk-actions');
            const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
            bulkBar.style.display = anyChecked ? 'flex' : 'none';
        }

        function toggleSelectAll(masterCheckbox) {
            const checkboxes = document.querySelectorAll('.messageCheckbox');
            checkboxes.forEach(cb => cb.checked = masterCheckbox.checked);
            toggleBulkActions();
        }

        function confirmAndSubmit(formId, msg) {
            if (confirm(msg)) {
                document.getElementById(formId).submit();
            }
        }
    </script>
    <style>
        #bulk-actions {
            display: none;
            justify-content: flex-end;
            align-items: center;

        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
    <title>Notifications | Love Thy Neighbor Community Food Pantry</title>
</head>
<body>
<?php require_once('header.php') ?>
<div class="page">

    <!-- Main -->
    <div class="main">
        <div class="page-header">
            <h1>Notifications</h1>
        </div>

        <?php
        require_once('database/dbMessages.php');
        require_once('database/dbPersons.php');
        require_once('include/output.php');
            
        $newMessages = get_user_unread_messages($userID);
        $oldMessages = get_user_read_messages($userID);
        $allMessages = array_merge($newMessages, $oldMessages);

        usort($allMessages, function($a, $b) {
            $timeComparison = strcmp($b['time'], $a['time']);
            if ($timeComparison !== 0) {
                return $timeComparison;
            }
            return intval($b['id']) - intval($a['id']);
        });

        // Pagination logic
        $total = count($allMessages);
        $limit = 10;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $page = max(1, $page); // Ensure page is at least 1
        $offset = ($page - 1) * $limit;
        $paginatedMessages = array_slice($allMessages, $offset, $limit);
        $total_pages = ceil($total / $limit);

        mark_all_as_read($userID);
        ?>
        <?php if (count($allMessages) > 0): ?>
            <form id="bulkDeleteForm" action="deleteNotification.php" method="POST">
                <div class="top-bar">
                <!-- <button type="submit" name="delete_all" class="button delete" style="width:10%; margin-bottom: 10px;" onclick="return confirm('Are you sure you want to delete ALL notifications?');">Delete All</button> -->
                 <button type="submit" name="delete_all" class="cancel-btn" style="width:10%; margin-bottom: 10px;" onclick="return confirm('Are you sure you want to delete ALL notifications?');">Delete All</button>
                    <div id="bulk-actions" style="display:none;">
                        <span><strong>With Selected:</strong></span>
                        <button type="submit" name="bulk_delete" class="cancel-btn" style="margin-bottom: 10px;" onclick="return confirm('Delete selected notifications?');">Delete</button>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="general">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
                                <th>From</th>
                                <th>Title</th>
                                <th>Received</th>
                                <th>Delete</th>
                            </tr>
                        </thead>
                        <tbody class="standout">
                            <?php 
                                $id_to_name_hash = [];
                                foreach ($paginatedMessages as $message):
                                    $sender = $id_to_name_hash[$message['senderID']] ?? get_name_from_id($message['senderID']);
                                    $id_to_name_hash[$message['senderID']] = $sender;

                                    $messageID = $message['id'];
                                    $title = $message['title'];
                                    $timePacked = $message['time'];
                                    [$year, $month, $day, $clock] = explode('-', $timePacked);
                                    $time = time24hto12h($clock);
                                    $class = 'message';
                                    if (!$message['wasRead']) $class .= ' unread';
                                    if ($message['prioritylevel']) $class .= ' prio' . $message['prioritylevel'];
                            ?>
                            <tr class="<?= $class ?>" data-message-id="<?= $messageID ?>">
                                <td><input type="checkbox" class="rowCheckbox" name="selected_messages[]" value="<?= $messageID ?>"></td>
                                <td><?= $sender ?></td>
                                <td><?= $title ?></td>
                                <td><?= "$month/$day/$year $time" ?></td>
                                <td>
                                    <a class="cancel-btn" 
                                    href="deleteNotification.php?id=<?= $messageID ?>" 
                                    onclick="return confirm('Are you sure you want to delete this message?');">
                                    Delete Notification
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>

            <?php if ($total_pages > 1): ?>
            <div class="pagination-container">
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="page-btn">Previous</a>
                    <?php endif; ?>

                    <?php $window = 2; ?>
                    <a href="?page=1" class="page-btn <?php echo ($page == 1) ? 'active' : ''; ?>">1</a>

                    <?php if ($page > $window + 2): ?>
                        <span class="page-btn">...</span>
                    <?php endif; ?>

                    <?php
                    $start = max(2, $page - $window);
                    $end = min($total_pages - 1, $page + $window);

                    for ($i = $start; $i <= $end; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="page-btn <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages - ($window + 1)): ?>
                        <span class="page-btn">...</span>
                    <?php endif; ?>

                    <?php if ($total_pages > 1): ?>
                        <a href="?page=<?php echo $total_pages; ?>" class="page-btn <?php echo ($page == $total_pages) ? 'active' : ''; ?>">
                        <?php echo $total_pages; ?>
                        </a>
                    <?php endif; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="page-btn">Next</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php else: ?>
                <p class="no-messages standout">You currently have no notifications.</p>
            <?php endif; ?>

            </div>
            </div>
            <?php include 'footer.php'; ?>

        <script>
            document.getElementById('selectAll').addEventListener('change', function () {
                const checkboxes = document.querySelectorAll('.rowCheckbox');
                checkboxes.forEach(cb => cb.checked = this.checked);
                toggleBulkActions();
            });

            document.querySelectorAll('.rowCheckbox').forEach(cb => {
                cb.addEventListener('change', toggleBulkActions);
            });

            function toggleBulkActions() {
                const anyChecked = [...document.querySelectorAll('.rowCheckbox')].some(cb => cb.checked);
                document.getElementById('bulk-actions').style.display = anyChecked ? 'block' : 'none';
            }
        </script>
</main>
</body>
</html>
