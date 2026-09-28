<?php
session_start();
ini_set("display_errors", 1);
error_reporting(E_ALL);

$loggedIn = false;
$accessLevel = 0;
$username = null;

if (isset($_SESSION['_id'])) {
    $loggedIn = true;
    $accessLevel = $_SESSION['access_level'];
    $username = $_SESSION['_id'];
}

// Require admin privileges
if ($accessLevel < 1) {
    header('Location: login.php');
    die();
}

include_once('database/dbinfo.php');
$con = connect();

if (!$con) {
    die("Database connection failed: " . mysqli_connect_error());
}

require_once('database/dbPersons.php');

// Search / filter values
$search = $_GET['search'] ?? '';
$search_by = $_GET['search_by'] ?? 'all';
$status = $_GET['status'] ?? 'all';
$per_page = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

// Shared date range values
$sdate = $_GET['sdate'] ?? '';
$edate = $_GET['edate'] ?? '';

$event_date = $_GET['event_date'] ?? '';
$event_id   = $_GET['event_id'] ?? '';

$total_users = getUserCount($search, $search_by, $status, $event_id);
$total_pages = max(1, ceil($total_users / $per_page));

$users = getUsersForViewPage($search, $per_page, $offset, $search_by, $status, $event_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require_once('database/dbPersons.php'); ?>
<title>Generate Service Letters | Love Thy Neighbor Community Food Pantry</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="css/layoutInfo.css">
<style>
    .date-card {
        margin-bottom: 1.5rem;
    }

    .date-form {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        align-items: end;
    }

    .date-form-group {
        display: flex;
        flex-direction: column;
        min-width: 180px;
    }

    .date-form-group label {
        font-weight: 600;
        margin-bottom: 0.3rem;
    }

    .date-form-group input[type="date"] {
        padding: 8px;
    }

    .service-letter-action-form {
        margin: 0;
    }

    .service-letter-action-form .report-btn {
        display: inline-block;
        text-align: center;
    }
</style>
</head>

<body>
<?php include('header.php'); ?>
<div class="page">
    <div class="main">

   

        <div class="page-header">
            <h1>Users</h1>
        </div>



        <div class="filter-card">
            <form class="filter-form" method="GET" action="serviceLetterReport.php">
                <input type="hidden" name="page" value="1">

                <select name="search_by">
                    <option value="all" <?php echo ($search_by === 'all' ? 'selected' : ''); ?>>All</option>
                    <option value="name" <?php echo ($search_by === 'name' ? 'selected' : ''); ?>>Name</option>
                    <option value="username" <?php echo ($search_by === 'username' ? 'selected' : ''); ?>>Username</option>
                    <option value="email" <?php echo ($search_by === 'email' ? 'selected' : ''); ?>>Email</option>
                    <option value="phone" <?php echo ($search_by === 'phone' ? 'selected' : ''); ?>>Phone</option>
                </select>

                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search users..." 
                    value="<?php echo htmlspecialchars($search); ?>"
                >

                <select name="status">
                    <option value="all" <?php echo ($status === 'all' ? 'selected' : ''); ?>>All</option>
                    <option value="active" <?php echo ($status === 'active' ? 'selected' : ''); ?>>Active</option>
                    <option value="archived" <?php echo ($status === 'archived' ? 'selected' : ''); ?>>Archived</option>
                </select>

                <button type="submit">Filter</button>

                <input 
                    type="date" 
                    id="sdate" 
                    name="sdate" 
                    value="<?php echo htmlspecialchars($sdate); ?>" 
                    min="2026-04-01"
                    required
                    onchange="this.form.submit()"
                >

                <input 
                    type="date" 
                    id="edate" 
                    name="edate" 
                    value="<?php echo htmlspecialchars($edate); ?>" 
                    required
                    onchange="this.form.submit()"
                >
            </form>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                        <?php if ($user['id'] !== 'vmsroot' && $user['id'] !== 'vmsroot2'): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['phone_number']); ?></td>
                            <td><?php echo htmlspecialchars($user['type']); ?></td>
                            <td>
                                <?php if (!empty($user['archived']) && $user['archived'] == 1): ?>
                                    <span class="badge archived">Archived</span>
                                <?php else: ?>
                                    <span class="badge active">Active</span>
                                <?php endif; ?>
                            </td>
                            <td class="action">
                                <form method="GET" action="processServiceLetterReport.php" class="service-letter-action-form" onsubmit="return validateServiceLetterForm(this)">
                                    <input type="hidden" name="target_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                    <input type="hidden" name="sdate" value="<?php echo htmlspecialchars($sdate); ?>">
                                    <input type="hidden" name="edate" value="<?php echo htmlspecialchars($edate); ?>">
                                    <button type="submit" class="report-btn">Generate Report</button>
                                </form>
                            </td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">No users found.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination-container">
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&search_by=<?php echo $search_by; ?>&status=<?php echo $status; ?>&sdate=<?php echo urlencode($sdate); ?>&edate=<?php echo urlencode($edate); ?>&event_date=<?php echo urlencode($event_date); ?>&event_id=<?php echo urlencode($event_id); ?>" class="page-btn">Previous</a>
                <?php endif; ?>

                <?php $window = 2; ?>
                <a href="?page=1&search=<?php echo urlencode($search); ?>&search_by=<?php echo $search_by; ?>&status=<?php echo $status; ?>&sdate=<?php echo urlencode($sdate); ?>&edate=<?php echo urlencode($edate); ?>&event_date=<?php echo urlencode($event_date); ?>&event_id=<?php echo urlencode($event_id); ?>"
                   class="page-btn <?php echo ($page == 1) ? 'active' : ''; ?>">1</a>

                <?php if ($page > $window + 2): ?>
                    <span class="page-btn">...</span>
                <?php endif; ?>

                <?php
                $start = max(2, $page - $window);
                $end = min($total_pages - 1, $page + $window);

                for ($i = $start; $i <= $end; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&search_by=<?php echo $search_by; ?>&status=<?php echo $status; ?>&sdate=<?php echo urlencode($sdate); ?>&edate=<?php echo urlencode($edate); ?>&event_date=<?php echo urlencode($event_date); ?>&event_id=<?php echo urlencode($event_id); ?>"
                       class="page-btn <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages - ($window + 1)): ?>
                    <span class="page-btn">...</span>
                <?php endif; ?>

                <?php if ($total_pages > 1): ?>
                    <a href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>&search_by=<?php echo $search_by; ?>&status=<?php echo $status; ?>&sdate=<?php echo urlencode($sdate); ?>&edate=<?php echo urlencode($edate); ?>&event_date=<?php echo urlencode($event_date); ?>&event_id=<?php echo urlencode($event_id); ?>"
                       class="page-btn <?php echo ($page == $total_pages) ? 'active' : ''; ?>">
                        <?php echo $total_pages; ?>
                    </a>
                <?php endif; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&search_by=<?php echo $search_by; ?>&status=<?php echo $status; ?>&sdate=<?php echo urlencode($sdate); ?>&edate=<?php echo urlencode($edate); ?>&event_date=<?php echo urlencode($event_date); ?>&event_id=<?php echo urlencode($event_id); ?>" class="page-btn">Next</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>

<script>
function validateServiceLetterForm(form) {
    const sdate = form.sdate.value;
    const edate = form.edate.value;

    if (!sdate || !edate) {
        alert('Please select both start and end dates in the filter form before generating a report.');
        return false;
    }

    if (sdate < '2026-04-01') {
        alert('Start date cannot be before April 1, 2026.');
        return false;
    }

    return true;
}
</script>

</body>
</html>