<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_cache_expire(30);
session_start();

date_default_timezone_set("America/New_York");

if (!isset($_SESSION['access_level']) || $_SESSION['access_level'] < 1) {
    if (isset($_SESSION['change-password'])) {
        header('Location: changePassword.php');
    } else {
        header('Location: login.php');
    }
    die();
}

require_once __DIR__ . '/database/dbinfo.php';
require_once __DIR__ . '/database/dbPersons.php';

$con = connect();
if (!$con) {
    die("Database connection failed: " . mysqli_connect_error());
}

function buildArchivePageUrl($activePage, $archivedPage, $searchValue)
{
    $params = [
        'active_page' => $activePage,
        'archived_page' => $archivedPage,
    ];

    if ($searchValue !== '') {
        $params['search'] = $searchValue;
    }

    return '?' . http_build_query($params);
}

function renderArchivePagination($currentPage, $totalPages, $activePage, $archivedPage, $searchValue, $pageKey)
{
    if ($totalPages <= 1) {
        return;
    }

    $window = 2;

    echo '<div class="pagination-container"><div class="pagination">';

    if ($currentPage > 1) {
        $prevActive = $pageKey === 'active_page' ? $currentPage - 1 : $activePage;
        $prevArchived = $pageKey === 'archived_page' ? $currentPage - 1 : $archivedPage;
        echo '<a href="' . htmlspecialchars(buildArchivePageUrl($prevActive, $prevArchived, $searchValue), ENT_QUOTES, 'UTF-8') . '" class="page-btn">Previous</a>';
    }

    $firstPageUrl = buildArchivePageUrl(
        $pageKey === 'active_page' ? 1 : $activePage,
        $pageKey === 'archived_page' ? 1 : $archivedPage,
        $searchValue
    );
    echo '<a href="' . htmlspecialchars($firstPageUrl, ENT_QUOTES, 'UTF-8') . '" class="page-btn ' . ($currentPage === 1 ? 'active' : '') . '">1</a>';

    if ($currentPage > $window + 2) {
        echo '<span class="page-btn ellipsis">...</span>';
    }

    $start = max(2, $currentPage - $window);
    $end = min($totalPages - 1, $currentPage + $window);

    for ($i = $start; $i <= $end; $i++) {
        $pageUrl = buildArchivePageUrl(
            $pageKey === 'active_page' ? $i : $activePage,
            $pageKey === 'archived_page' ? $i : $archivedPage,
            $searchValue
        );
        echo '<a href="' . htmlspecialchars($pageUrl, ENT_QUOTES, 'UTF-8') . '" class="page-btn ' . ($i === $currentPage ? 'active' : '') . '">' . $i . '</a>';
    }

    if ($currentPage < $totalPages - ($window + 1)) {
        echo '<span class="page-btn ellipsis">...</span>';
    }

    if ($totalPages > 1) {
        $lastPageUrl = buildArchivePageUrl(
            $pageKey === 'active_page' ? $totalPages : $activePage,
            $pageKey === 'archived_page' ? $totalPages : $archivedPage,
            $searchValue
        );
        echo '<a href="' . htmlspecialchars($lastPageUrl, ENT_QUOTES, 'UTF-8') . '" class="page-btn ' . ($currentPage === $totalPages ? 'active' : '') . '">' . $totalPages . '</a>';
    }

    if ($currentPage < $totalPages) {
        $nextActive = $pageKey === 'active_page' ? $currentPage + 1 : $activePage;
        $nextArchived = $pageKey === 'archived_page' ? $currentPage + 1 : $archivedPage;
        echo '<a href="' . htmlspecialchars(buildArchivePageUrl($nextActive, $nextArchived, $searchValue), ENT_QUOTES, 'UTF-8') . '" class="page-btn">Next</a>';
    }

    echo '</div></div>';
}

$currentDate = date('F j, Y');
$message = '';
$searchValue = isset($_REQUEST['search']) ? trim($_REQUEST['search']) : '';
$searchTerm = $searchValue !== '' ? "%$searchValue%" : '';

$perPage = 10;
$activePage = isset($_GET['active_page']) ? max(1, intval($_GET['active_page'])) : 1;
$archivedPage = isset($_GET['archived_page']) ? max(1, intval($_GET['archived_page'])) : 1;
$activeOffset = ($activePage - 1) * $perPage;
$archivedOffset = ($archivedPage - 1) * $perPage;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $archiveAction = isset($_POST['archive_action']) ? trim($_POST['archive_action']) : '';

    if ($archiveAction === 'archive_all') {
        $oneYearAgo = date('Y-m-d H:i:s', strtotime('-1 year'));

        $idsToArchive = [];
        $archiveListQuery = "
            SELECT p.id
            FROM dbpersons AS p
            WHERE (p.status = 'Active' OR p.status IS NULL)
            " . ($searchTerm !== '' ? "AND (p.first_name LIKE ? OR p.last_name LIKE ? OR p.email LIKE ? OR p.id LIKE ?)" : "") . "
            AND NOT EXISTS (
                SELECT 1
                FROM dbpersonhours AS h
                WHERE h.personID COLLATE utf8mb4_unicode_ci = p.id COLLATE utf8mb4_unicode_ci
                AND COALESCE(h.end_time, h.start_time) >= ?
            )
        ";

        $stmt = mysqli_prepare($con, $archiveListQuery);
        if ($stmt) {
            if ($searchTerm !== '') {
                mysqli_stmt_bind_param($stmt, 'sssss', $searchTerm, $searchTerm, $searchTerm, $searchTerm, $oneYearAgo);
            } else {
                mysqli_stmt_bind_param($stmt, 's', $oneYearAgo);
            }
            mysqli_stmt_execute($stmt);
            $archiveListResult = mysqli_stmt_get_result($stmt);
        } else {
            $archiveListResult = false;
        }

        if ($archiveListResult) {
            while ($row = mysqli_fetch_assoc($archiveListResult)) {
                $idsToArchive[] = $row['id'];
            }
            mysqli_free_result($archiveListResult);
            if (isset($stmt)) {
                mysqli_stmt_close($stmt);
            }

            $archivedCount = 0;
            foreach ($idsToArchive as $personId) {
                if (archive_volunteer($personId, 'Inactive')) {
                    $archivedCount++;
                }
            }

            $message = $archivedCount > 0
                ? "Archived $archivedCount user(s) with no activity in the past year."
                : 'No active users matched the criteria.';
        } else {
            $message = 'Unable to archive users. Please try again.';
        }
    } elseif ($archiveAction === 'unarchive_all') {
        $idsToRestore = [];
        $restoreListQuery = "SELECT id FROM dbpersons WHERE status = 'Inactive'" . ($searchTerm !== '' ? " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR id LIKE ?)" : "");
        $stmt = mysqli_prepare($con, $restoreListQuery);
        if ($stmt) {
            if ($searchTerm !== '') {
                mysqli_stmt_bind_param($stmt, 'ssss', $searchTerm, $searchTerm, $searchTerm, $searchTerm);
            }
            mysqli_stmt_execute($stmt);
            $restoreListResult = mysqli_stmt_get_result($stmt);
        } else {
            $restoreListResult = false;
        }

        if ($restoreListResult) {
            while ($row = mysqli_fetch_assoc($restoreListResult)) {
                $idsToRestore[] = $row['id'];
            }
            mysqli_free_result($restoreListResult);
            if (isset($stmt)) {
                mysqli_stmt_close($stmt);
            }

            $restoredCount = 0;
            foreach ($idsToRestore as $personId) {
                if (archive_volunteer($personId, 'Active')) {
                    $restoredCount++;
                }
            }

            $message = $restoredCount > 0 ? "Unarchived $restoredCount user(s)." : 'No archived users matched the request.';
        } else {
            $message = 'Unable to unarchive users. Please try again.';
        }
    } elseif (isset($_POST['archive_id'])) {
        $archiveId = trim($_POST['archive_id']);
        $archiveAction = isset($_POST['archive_action']) ? trim($_POST['archive_action']) : '';

        if ($archiveId !== '') {
            if ($archiveAction === 'check_year') {
                $stmt = $con->prepare("SELECT MAX(COALESCE(end_time, start_time)) AS last_time FROM dbpersonhours WHERE personID = ?");
                if ($stmt) {
                    $stmt->bind_param('s', $archiveId);
                    $stmt->execute();
                    $stmt->bind_result($lastTime);
                    $stmt->fetch();
                    $stmt->close();

                    $oneYearAgo = date('Y-m-d H:i:s', strtotime('-1 year'));
                    if (empty($lastTime) || $lastTime < $oneYearAgo) {
                        if (archive_volunteer($archiveId, 'Inactive')) {
                            $message = 'User had no volunteer activity in the past year and was archived.';
                        } else {
                            $message = 'Unable to archive the user after checking volunteer history.';
                        }
                    } else {
                        $message = 'User has volunteered within the past year and was not archived.';
                    }
                } else {
                    $message = 'Unable to verify volunteer history. Please try again.';
                }
            } else {
                if (archive_volunteer($archiveId)) {
                    $message = 'User status switched successfully.';
                } else {
                    $message = 'Unable to update the user status. Please try again.';
                }
            }
        }
    }
}

$activeCount = 0;
$countQuery = "SELECT COUNT(*) AS cnt FROM dbpersons WHERE (status = 'Active' OR status IS NULL)" . ($searchTerm !== '' ? " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR id LIKE ?)" : "");
$stmt = mysqli_prepare($con, $countQuery);
if ($stmt) {
    if ($searchTerm !== '') {
        mysqli_stmt_bind_param($stmt, 'ssss', $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    }
    mysqli_stmt_execute($stmt);
    $countResult = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
} else {
    $countResult = false;
}
if ($countResult) {
    $countRow = mysqli_fetch_assoc($countResult);
    $activeCount = intval($countRow['cnt']);
    mysqli_free_result($countResult);
}

$activePageCount = max(1, ceil($activeCount / $perPage));
if ($activePage > $activePageCount) {
    $activePage = $activePageCount;
    $activeOffset = ($activePage - 1) * $perPage;
}

$archivePeople = [];
$query = "SELECT id, first_name, last_name, email, phone_number, `type`
          FROM dbpersons
          WHERE (status = 'Active' OR status IS NULL)" . ($searchTerm !== '' ? " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR id LIKE ?)" : "") . "
          ORDER BY last_name ASC, first_name ASC
          LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($con, $query);
if ($stmt) {
    if ($searchTerm !== '') {
        mysqli_stmt_bind_param($stmt, 'ssssii', $searchTerm, $searchTerm, $searchTerm, $searchTerm, $perPage, $activeOffset);
    } else {
        mysqli_stmt_bind_param($stmt, 'ii', $perPage, $activeOffset);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
} else {
    $result = false;
}
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $archivePeople[] = $row;
    }
    mysqli_free_result($result);
}

$inactiveCount = 0;
$countQuery = "SELECT COUNT(*) AS cnt FROM dbpersons WHERE status = 'Inactive'" . ($searchTerm !== '' ? " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR id LIKE ?)" : "");
$stmt = mysqli_prepare($con, $countQuery);
if ($stmt) {
    if ($searchTerm !== '') {
        mysqli_stmt_bind_param($stmt, 'ssss', $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    }
    mysqli_stmt_execute($stmt);
    $countResult = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
} else {
    $countResult = false;
}
if ($countResult) {
    $countRow = mysqli_fetch_assoc($countResult);
    $inactiveCount = intval($countRow['cnt']);
    mysqli_free_result($countResult);
}

$archivedPageCount = max(1, ceil($inactiveCount / $perPage));
if ($archivedPage > $archivedPageCount) {
    $archivedPage = $archivedPageCount;
    $archivedOffset = ($archivedPage - 1) * $perPage;
}

$archivedPeople = [];
$query = "SELECT id, first_name, last_name, email, phone_number, `type`
          FROM dbpersons
          WHERE status = 'Inactive'" . ($searchTerm !== '' ? " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR id LIKE ?)" : "") . "
          ORDER BY last_name ASC, first_name ASC
          LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($con, $query);
if ($stmt) {
    if ($searchTerm !== '') {
        mysqli_stmt_bind_param($stmt, 'ssssii', $searchTerm, $searchTerm, $searchTerm, $searchTerm, $perPage, $archivedOffset);
    } else {
        mysqli_stmt_bind_param($stmt, 'ii', $perPage, $archivedOffset);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
} else {
    $result = false;
}
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $archivedPeople[] = $row;
    }
    mysqli_free_result($result);
}

$totalCount = $activeCount + $inactiveCount;

mysqli_close($con);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive Portal | Love Thy Neighbor Community Food Pantry Volunteer Management</title>

    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="header.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/layoutInfo.css">
</head>

<body class="archive-page">
<?php require_once 'header.php'; ?>

<main class="main-content">

    <section class="welcome-banner">
        <div>
            <h1>Archive People Portal</h1>
            <p>Manage active and inactive users from the admin archive dashboard.</p>
        </div>

        <div class="date-box">
            <i class="fa-solid fa-calendar-days"></i> <?php echo htmlspecialchars($currentDate); ?>
        </div>
    </section>

    <section class="card-grid">
        <div class="card soft-blue">
            <i class="fa-solid fa-users icon-blue"></i>
            <h3>Total Users</h3>
            <p><?php echo htmlspecialchars((string)$totalCount); ?></p>
        </div>

        <div class="card soft-green">
            <i class="fa-solid fa-user-check icon-green"></i>
            <h3>Active Users</h3>
            <p><?php echo htmlspecialchars((string)$activeCount); ?></p>
        </div>

        <div class="card soft-yellow">
            <i class="fa-solid fa-user-clock icon-yellow"></i>
            <h3>Inactive Users</h3>
            <p><?php echo htmlspecialchars((string)$inactiveCount); ?></p>
        </div>
    </section>

    <?php if ($message): ?>
        <div class="message-box"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <section class="search-section">
        <form method="get" class="search-form">
            <label for="search" class="sr-only">Search users</label>
            <input id="search" type="search" name="search" value="<?php echo htmlspecialchars($searchValue, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search by name, email, or username">
            <button type="submit">Search</button>
        </form>
    </section>

    <section class="archive-section">
        <div class="table-card">
            <div class="archive-page-header">
                <div>
                    <h2>Active Users</h2>
                    <p class="muted">Use this table to archive currently active volunteers and admins.</p>
                </div>
                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="archive-page-action-form">
                    <input type="hidden" name="archive_action" value="archive_all">
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchValue, ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="action-btn archive-btn" onclick="return confirm('Archive all active users that have not volunteered in the past year?');">Archive All Users Who Haven't Volunteered in Over a Year</button>
                </form>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($archivePeople)): ?>
                            <?php foreach ($archivePeople as $person): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($person['first_name'] . ' ' . $person['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($person['id']); ?></td>
                                    <td><?php echo htmlspecialchars($person['email']); ?></td>
                                    <td><?php echo htmlspecialchars($person['phone_number']); ?></td>
                                    <td><?php echo htmlspecialchars($person['type']); ?></td>
                                    <td><span class="badge active">Active</span></td>
                                    <td>
                                        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="archive-page-action-form">
                                            <input type="hidden" name="archive_id" value="<?php echo htmlspecialchars($person['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="archive_action" value="check_year">
                                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchValue, ENT_QUOTES, 'UTF-8'); ?>">
                                            <button
                                                type="submit"
                                                class="action-btn archive-btn"
                                                onclick="return confirm('Check volunteer activity for the past year and archive if none is found?');">
                                                Archive
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td class="no-data" colspan="7">No active users found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php renderArchivePagination($activePage, $activePageCount, $activePage, $archivedPage, $searchValue, 'active_page'); ?>
        </div>

        <div class="table-card">
            <div class="archive-page-header">
                <div>
                    <h2>Archived Users</h2>
                    <p class="muted">Use this table to restore users who are currently inactive.</p>
                </div>
                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="archive-page-action-form">
                    <input type="hidden" name="archive_action" value="unarchive_all">
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchValue, ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="action-btn unarchive-btn" onclick="return confirm('Unarchive all archived users?');">Unarchive All</button>
                </form>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($archivedPeople)): ?>
                            <?php foreach ($archivedPeople as $person): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($person['first_name'] . ' ' . $person['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($person['id']); ?></td>
                                    <td><?php echo htmlspecialchars($person['email']); ?></td>
                                    <td><?php echo htmlspecialchars($person['phone_number']); ?></td>
                                    <td><?php echo htmlspecialchars($person['type']); ?></td>
                                    <td><span class="badge archived">Inactive</span></td>
                                    <td>
                                        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="archive-page-action-form">
                                            <input type="hidden" name="archive_id" value="<?php echo htmlspecialchars($person['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="archive_action" value="toggle">
                                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchValue, ENT_QUOTES, 'UTF-8'); ?>">
                                            <button
                                                type="submit"
                                                class="action-btn unarchive-btn"
                                                onclick="return confirm('Unarchive this user?');">
                                                Unarchive
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td class="no-data" colspan="7">No archived users found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php renderArchivePagination($archivedPage, $archivedPageCount, $activePage, $archivedPage, $searchValue, 'archived_page'); ?>
        </div>
    </section>

</main>

<?php include 'footer.php'; ?>
</body>
</html>
