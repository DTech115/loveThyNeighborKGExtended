<?php
session_start();
// Added
ini_set("display_errors", 1);
error_reporting(E_ALL);
include_once("./database/dbinfo.php");

$loggedIn = false;
$accessLevel = 0;
$username = null;

if (isset($_SESSION['_id'])) {
    $loggedIn = true;
    $accessLevel = $_SESSION['access_level'];
    $username = $_SESSION['_id']; // Username is stored here
}

// Require admin privileges
// if ($accessLevel < 1) {
//     header('Location: login.php');
//     die();
// }

//End of added

// Create database connection HERE (so everything in this file can use it)
$con = connect();

if (!$con) {
    die("Database connection failed: " . mysqli_connect_error());
}
require_once('database/dbEvents.php');



if(isset($_GET['archive'])){
    //using <a> kinda requires this funky lil redirect thing so if you reload the page a million times it doesnt spam the db
    $id = (int) $_GET['archive'];
    if(is_archived($id)){
        unarchive_event($id);
    }
    else{
        archive_event($id);
    }
    header("Location: ./viewOverallEventsKG.php");
    exit();
}
require_once('database/dbEvents.php');
// Search and filter values
$search = trim($_GET['search'] ?? '');
$search_by = $_GET['search_by'] ?? 'all';
$status = $_GET['status'] ?? 'active';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$archive_past_events=auto_Archive();

// Whitelist search_by
$allowed_search_by = ['all', 'name', 'date', 'location'];
if (!in_array($search_by, $allowed_search_by, true)) {
    $search_by = 'all';
}

// Whitelist status
$allowed_statuses = ['all', 'active', 'archived'];
if (!in_array($status, $allowed_statuses, true)) {
    $status = 'all';
}

// Validate dates
if ($date_from !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
    $date_from = '';
}

if ($date_to !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
    $date_to = '';
}

// Pagination
$per_page = 10;
$page = max(1, (int)($_GET['page'] ?? 1));

// Count first
$total_events = getEventCount($search, $search_by, $status, $date_from, $date_to);
$total_pages = max(1, ceil($total_events / $per_page));

if ($page > $total_pages) {
    $page = 1;
}

$offset = ($page - 1) * $per_page;

// Then fetch paginated results
$theEvents = getEventsForViewPage($search, $search_by, $status, $date_from, $date_to, $per_page, $offset);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once('database/dbEvents.php'); ?>
    <title>View Events | Love Thy Neighbor Community Food Pantry</title>
    <link rel="stylesheet" href="css/layoutInfo.css">

</head>
<?php if ($_SESSION['access_level'] >= 2): ?>
<body>
<?php include('header.php'); ?>
<div class="page">

    <!-- Main -->
    <div class="main">

        <div class="page-header">
            <h1>View Events</h1>
            <a href="addEvent.php" class="add-btn">+ Create Event</a>
            <?php if(isset($_SESSION['toggleArchive'])){ echo '<p> hi!!! </p>';} ?>
            <a href="calendar.php" class="add-btn">View Calendar</a>
        </div>

        <div class="filter-card">
            <form class="filter-form" method="GET" action="viewOverallEventsKG.php">
                <!-- SEARCH TYPE -->
                <select name="search_by">
                    <option value="active" <?php echo ($status === 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="name" <?php echo ($search_by === 'name') ? 'selected' : ''; ?>>Name</option>
                    <!--<option value="date" <?php echo ($search_by === 'date') ? 'selected' : ''; ?>>Date</option>-->
                    <option value="location" <?php echo ($search_by === 'location') ? 'selected' : ''; ?>>Location</option>
                </select>
                <!-- SEARCH BAR -->
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search events..."
                    value="<?php echo htmlspecialchars($search); ?>"
                    class="search-input"
                >

                <!-- STATUS -->
                <select name="status">
                    <option value="all" <?php echo ($status === 'all') ? 'selected' : ''; ?>>All</option>
                    <option value="active" <?php echo ($status === 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="archived" <?php echo ($status === 'archived') ? 'selected' : ''; ?>>Archived</option>
                    <!--<option value="canceled" <?php echo ($status === 'canceled') ? 'selected' : ''; ?>>Canceled</option>-->
                </select>

                <!-- BUTTON -->
                <button type="submit">Search</button>

                <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from);?> title="Start Date">
                <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to);?>title="End Date">


                <button type="submit">Apply Filters</button>

       
                <?php if ($search !== '' || $status !== 'active' || $date_from !== '' || $date_to !== '' || $search_by !== 'all'): ?>
                    <a href="viewOverallEventsKG.php" class="clear-btn">Reset Filters</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Table -->
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Date</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Location</th>
                        <th>Capacity</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($theEvents)): ?>
                    <?php foreach ($theEvents as $theEvent): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($theEvent->getName()); ?></td>
                            <td><?php echo htmlspecialchars($theEvent->getStartDate()); ?></td>
                            <td><?php echo htmlspecialchars($theEvent->getStartTime()); ?></td>
                            <td><?php echo htmlspecialchars($theEvent->getEndTime()); ?></td>
                            <td><?php echo htmlspecialchars($theEvent->getLocation() ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($theEvent->getCapacity()); ?></td>
                            <td>
                                <?php if ($theEvent->getArchived() == 1): ?>
                                    <span class="badge archived">Archived</span>
                                <?php else: ?>
                                    <span class="badge active">Active</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <a href="event.php?id=<?php echo urlencode($theEvent->getID()); ?>" class="view-btn">View</a>
                                <a href="viewEventSignUps.php?id=<?php echo urlencode($theEvent->getID()); ?>" class="edit-btn">Attendance</a>
                                <a href="editEvent.php?id=<?php echo urlencode($theEvent->getID()); ?>" class="edit-btn">Edit</a>
                                <?php if (is_archived($theEvent->getID())): ?>
                                    <a href="./viewOverallEventsKG.php?archive=<?php echo $theEvent->getID(); ?>" class="archive-btn">Unarchive</a>
                                <?php else: ?>
                                    <a href="./viewOverallEventsKG.php?archive=<?php echo $theEvent->getID(); ?>" class="archive-btn">Archive</a>
                                <?php endif; ?> 
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">No events found.</td>
                    </tr>
                <?php endif; ?>
                </tbody>    
            </table>
        </div>

        <div class="pagination-container">
            <div class="pagination">
                <?php $window = 2; ?>

                <?php
                $query_base = '&search=' . urlencode($search)
                    . '&search_by=' . urlencode($search_by)
                    . '&status=' . urlencode($status)
                    . '&date_from=' . urlencode($date_from)
                    . '&date_to=' . urlencode($date_to);
                ?>
                <!-- previous button -->
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1 . $query_base; ?>" class="page-btn">Previous</a>
                <?php endif; ?>
                

                <!-- ALWAYS show first page -->
                <a href="?page=1<?php echo $query_base; ?>"
                    class="page-btn <?php echo ($page == 1) ? 'active' : ''; ?>">
                    1
                </a>

                <!-- LEFT ELLIPSIS RAH -->
                <?php if ($page > $window + 2): ?>
                    <span class="page-btn">...</span>
                <?php endif; ?>

                <!-- middle pages -->
                <?php 
                $start = max(2, $page - $window);
                $end = min($total_pages - 1, $page + $window);

                for ($i = $start; $i <= $end; $i++): ?>
                    <a href="?page=<?php echo $i . $query_base; ?>"
                        class="page-btn <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <!-- RIGHT ELLIPSIS RAH -->
                <?php if ($page < $total_pages - ($window + 1)): ?>
                    <span class="page-btn">...</span>
                <?php endif; ?>

                <!-- ALWAYS show last page [ assuming if more than 1 page] -->
                <?php if ($total_pages > 1): ?>
                    <a href="?page=<?php echo $total_pages . $query_base; ?>"
                        class="page-btn <?php echo ($page == $total_pages) ? 'active' : ''; ?>">
                        <?php echo $total_pages; ?>
                    </a>
                <?php endif; ?>

                <!-- next button -->
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1 . $query_base; ?>" class="page-btn">Next</a>
                <?php endif; ?> 
            </div>
        </div>

    </div>

</div>
<?php include 'footer.php'; ?>
</body>

<?php elseif ($_SESSION['access_level'] == 1):?>

<body>
<?php include('header.php'); ?>
<div class="page">

    <!-- Main -->
    <div class="main">

        <div class="page-header">
            <h1>View Events</h1>
            <?php if(isset($_SESSION['toggleArchive'])){ echo '<p> hi!!! </p>';} ?>
            <a href="calendar.php" class="add-btn">View Calendar</a>
        </div>

        <div class="filter-card">
            <form class="filter-form" method="GET" action="viewOverallEventsKG.php">
                <!-- SEARCH TYPE -->
                <select name="search_by">
                    <option value="all" <?php echo ($search_by === 'all') ? 'selected' : ''; ?>>All</option>
                    <option value="name" <?php echo ($search_by === 'name') ? 'selected' : ''; ?>>Name</option>
                    <!--<option value="date" <?php echo ($search_by === 'date') ? 'selected' : ''; ?>>Date</option>-->
                    <option value="location" <?php echo ($search_by === 'location') ? 'selected' : ''; ?>>Location</option>
                </select>
                <!-- SEARCH BAR -->
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search events..."
                    value="<?php echo htmlspecialchars($search); ?>"
                    class="search-input"
                >

                <!-- STATUS -->
                <select name="status">
                    <option value="all" <?php echo ($status === 'all') ? 'selected' : ''; ?>>All</option>
                    <option value="active" <?php echo ($status === 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="archived" <?php echo ($status === 'archived') ? 'selected' : ''; ?>>Archived</option>
                    <!--<option value="canceled" <?php echo ($status === 'canceled') ? 'selected' : ''; ?>>Canceled</option>-->
                </select>

                <!-- BUTTON -->
                <button type="submit">Search</button>

                <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from);?> title="Start Date">
                <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to);?>title="End Date">


                <button type="submit">Apply Filters</button>

       
                <?php if ($search !== '' || $status !== 'active' || $date_from !== '' || $date_to !== '' || $search_by !== 'all'): ?>
                    <a href="viewOverallEventsKG.php" class="clear-btn">Reset Filters</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Table -->
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Date</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Location</th>
                        <th>Capacity</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($theEvents)): ?>
                    <?php foreach ($theEvents as $theEvent): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($theEvent->getName()); ?></td>
                            <td><?php echo htmlspecialchars($theEvent->getStartDate()); ?></td>
                            <td><?php echo htmlspecialchars($theEvent->getStartTime()); ?></td>
                            <td><?php echo htmlspecialchars($theEvent->getEndTime()); ?></td>
                            <td><?php echo htmlspecialchars($theEvent->getLocation() ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($theEvent->getCapacity()); ?></td>
                            <td>
                                <?php if ($theEvent->getArchived() == 1): ?>
                                    <span class="badge archived">Archived</span>
                                <?php else: ?>
                                    <span class="badge active">Active</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <a href="event.php?id=<?php echo urlencode($theEvent->getID()); ?>" class="view-btn">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">No events found.</td>
                    </tr>
                <?php endif; ?>
                </tbody>    
            </table>
        </div>

        <div class="pagination-container">
            <div class="pagination">
                <?php $window = 2; ?>

                <?php
                $query_base = '&search=' . urlencode($search)
                    . '&search_by=' . urlencode($search_by)
                    . '&status=' . urlencode($status)
                    . '&date_from=' . urlencode($date_from)
                    . '&date_to=' . urlencode($date_to);
                ?>
                <!-- previous button -->
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1 . $query_base; ?>" class="page-btn">Previous</a>
                <?php endif; ?>
                

                <!-- ALWAYS show first page -->
                <a href="?page=1<?php echo $query_base; ?>"
                    class="page-btn <?php echo ($page == 1) ? 'active' : ''; ?>">
                    1
                </a>

                <!-- LEFT ELLIPSIS RAH -->
                <?php if ($page > $window + 2): ?>
                    <span class="page-btn">...</span>
                <?php endif; ?>

                <!-- middle pages -->
                <?php 
                $start = max(2, $page - $window);
                $end = min($total_pages - 1, $page + $window);

                for ($i = $start; $i <= $end; $i++): ?>
                    <a href="?page=<?php echo $i . $query_base; ?>"
                        class="page-btn <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <!-- RIGHT ELLIPSIS RAH -->
                <?php if ($page < $total_pages - ($window + 1)): ?>
                    <span class="page-btn">...</span>
                <?php endif; ?>

                <!-- ALWAYS show last page [ assuming if more than 1 page] -->
                <?php if ($total_pages > 1): ?>
                    <a href="?page=<?php echo $total_pages . $query_base; ?>"
                        class="page-btn <?php echo ($page == $total_pages) ? 'active' : ''; ?>">
                        <?php echo $total_pages; ?>
                    </a>
                <?php endif; ?>

                <!-- next button -->
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1 . $query_base; ?>" class="page-btn">Next</a>
                <?php endif; ?> 
            </div>
        </div>

    </div>

</div>
<?php include 'footer.php'; ?>
</body>
<?php endif; ?>
</html>