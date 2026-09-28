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

    include_once('database/dbinfo.php'); 
    $con = connect(); 

    if (!$con) {
        die("Database connection failed: " . mysqli_connect_error());
    }
        
    include_once('database/dbEvents.php');
    include_once('database/dbPersons.php');
    include_once('database/dbpersonhours.php');
    include_once('database/dbMessages.php');
    include_once('domain/Person.php');

    $currentDate = date('F j, Y');
    $person = false;
    $personId = '';
    $registeredEvents = 0;
    $UpcomingEvents = retrieve_event3();

    if (isset($_SESSION['_id']) && $_SESSION['_id'] !== '') {
        $person = retrieve_person($_SESSION['_id']);
    }

    if (!$person || !is_object($person)) {
        header('Location: login.php');
        die();
    }

    $personId = $person->get_id();
    $notRoot = $personId != 'vmsroot';

    $volunteers = getVolunteerCount($con);
    $admins = getAdminCount();
    $total = getTotalUsers($con);

    $stmt = $con->prepare("
        SELECT COUNT(DISTINCT eventID) AS registered_events
        FROM dbpersonhours
        WHERE personID = ?
    ");

    if ($stmt) {
        $stmt->bind_param("s", $personId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $row = $result->fetch_assoc()) {
            $registeredEvents = (int)$row['registered_events'];
        }

        $stmt->close();
    }

    $hoursValue = calcPersonHours($personId) / 60;
    $quarterHours = round($hoursValue * 4) / 4;
    $displayHours = number_format($quarterHours, 2, '.', '');
    $displayHours = rtrim(rtrim($displayHours, '0'), '.');

    function formatDashboardHours($hours) {
        $roundedHours = round($hours * 4) / 4;
        $formattedHours = number_format($roundedHours, 2, '.', '');
        return rtrim(rtrim($formattedHours, '0'), '.');
    }

    function safeHtml($value) {
        if (is_array($value)) {
            $value = implode(', ', array_map(function ($item) {
                return is_scalar($item) ? (string)$item : '';
            }, $value));
        }
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    function normalizeEvent($event) {
        $normalized = [
            'name' => '',
            'date' => '',
            'startTime' => '',
            'endTime' => '',
            'status' => ''
        ];

        if (is_array($event)) {
            $normalized['name'] = (string)($event['name'] ?? '');
            $normalized['date'] = (string)($event['date'] ?? '');
            $normalized['startTime'] = (string)($event['startTime'] ?? '');
            $normalized['endTime'] = (string)($event['endTime'] ?? '');
            $normalized['status'] = (string)($event['status'] ?? '');
        } elseif (is_object($event)) {
            $normalized['name'] = method_exists($event, 'get_name') ? (string)$event->get_name() : '';
            $normalized['date'] = method_exists($event, 'get_date') ? (string)$event->get_date() : '';
            $normalized['startTime'] = method_exists($event, 'get_startTime') ? (string)$event->get_startTime() : '';
            $normalized['endTime'] = method_exists($event, 'get_endTime') ? (string)$event->get_endTime() : '';
        } else {
            $normalized['name'] = (string)$event;
        }

        return $normalized;
    }

    function formatEventDate($dateValue) {
        if (empty($dateValue)) {
            return '';
        }

        $timestamp = strtotime($dateValue);
        if ($timestamp === false) {
            return '';
        }

        return date('F j, Y', $timestamp);
    }

    function formatEventTimeRange($startTime, $endTime) {
        $start = '';
        $end = '';

        if (!empty($startTime)) {
            $startTs = strtotime($startTime);
            if ($startTs !== false) {
                $start = date('g:i A', $startTs);
            }
        }

        if (!empty($endTime)) {
            $endTs = strtotime($endTime);
            if ($endTs !== false) {
                $end = date('g:i A', $endTs);
            }
        }

        if ($start && $end) {
            return $start . ' - ' . $end;
        }

        if ($start) {
            return $start;
        }

        if ($end) {
            return $end;
        }

        return '';
    }

    $now = new DateTime('now', new DateTimeZone('America/New_York'));
    $weekStart = (clone $now)->modify('monday this week')->setTime(0, 0, 0);
    $weekEnd = (clone $weekStart)->modify('+1 week');
    $monthStart = (clone $now)->modify('first day of this month')->setTime(0, 0, 0);
    $monthEnd = (clone $monthStart)->modify('+1 month');
    $yearStart = (clone $now)->setDate((int)$now->format('Y'), 1, 1)->setTime(0, 0, 0);
    $yearEnd = (clone $yearStart)->modify('+1 year');

    $weekToDateHours = formatDashboardHours(calcTotalHoursForRange($weekStart->format('Y-m-d H:i:s'), $weekEnd->format('Y-m-d H:i:s')));
    $monthToDateHours = formatDashboardHours(calcTotalHoursForRange($monthStart->format('Y-m-d H:i:s'), $monthEnd->format('Y-m-d H:i:s')));
    $yearToDateHours = formatDashboardHours(calcTotalHoursForRange($yearStart->format('Y-m-d H:i:s'), $yearEnd->format('Y-m-d H:i:s')));
    $scheduledEvents = fetch_user_upcoming_signups($personId, 'ASC', 3);

    if (!is_array($UpcomingEvents)) {
        $UpcomingEvents = [];
    }

    if (!is_array($scheduledEvents)) {
        $scheduledEvents = [];
    }
  
    $inactiveCount = 0;
    $countQuery = "SELECT COUNT(*) AS cnt FROM dbpersons WHERE status = 'Inactive'";
    $stmt = mysqli_prepare($con, $countQuery);
    if ($stmt) {
        mysqli_stmt_execute($stmt);
        $countResult = mysqli_stmt_get_result($stmt);
        if ($countResult) {
            $countRow = mysqli_fetch_assoc($countResult);
            $inactiveCount = intval($countRow['cnt']);
            mysqli_free_result($countResult);
        }
        mysqli_stmt_close($stmt);
    }

    // Fetch the last 3 notifications for the current user
    $notifications = get_last_3_messages($personId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="./css/dashboard.css" rel="stylesheet">
    <link rel="stylesheet" href="header.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <title>Dashboard | Love Thy Neighbor Community Food Pantry Volunteer Management</title>
 
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const extraInfo = document.querySelector(".extra-info");
            if (extraInfo) {
                extraInfo.style.maxHeight = "0px";
            }
        });

        function toggleInfo(event) {
            event.stopPropagation();
            let info = event.target.nextElementSibling;
            if (!info) return;
            let isVisible = info.style.maxHeight !== "0px";
            info.style.maxHeight = isVisible ? "0px" : "100px";
            event.target.innerText = isVisible ? "↓" : "↑";
        }
    </script>
</head>

<?php if ($_SESSION['access_level'] >= 2): ?>
<body>
<?php require 'header.php';?>
  <main class="main-content">

    <section class="welcome-banner">
      <div>
        <h1>Welcome back, <?php echo safeHtml($_SESSION['f_name'] ?? 'Volunteer'); ?>!</h1>
        <p>Here’s what’s happening in your volunteer program today.</p>
      </div>

      <div class="date-box">
        <i class="fa-solid fa-calendar-days"></i> <?php echo safeHtml($currentDate); ?>
      </div>
    </section>

    <section class="card-grid">
      <div class="card soft-red">
        <i class="fa-solid fa-calendar-week icon-red"></i>
        <h3>Week-to-Date Hours</h3>
        <p><?= safeHtml($weekToDateHours) ?></p>
      </div>

      <div class="card soft-yellow">
        <i class="fa-solid fa-calendar-days icon-yellow"></i>
        <h3>Month-to-Date Hours</h3>
        <p><?= safeHtml($monthToDateHours) ?></p>
      </div>

      <div class="card soft-blue">
        <i class="fa-solid fa-calendar-check icon-blue"></i>
        <h3>Year-to-Date Hours</h3>
        <p><?= safeHtml($yearToDateHours) ?></p>
      </div>

      <div class="card soft-green">
        <i class="fa-solid fa-user-clock icon-green"></i>
        <h3>Inactive Volunteers</h3>
        <p><?= safeHtml($inactiveCount) ?></p>
      </div>
    </section>

    <section class="wide-card-grid">
      <div class="card soft-orange">
        <i class="fa-solid fa-users icon-orange"></i>
        <h3>Volunteer Count</h3>
        <p><?= safeHtml($volunteers) ?></p>
      </div>

      <div class="card soft-maroon">
        <i class="fa-solid fa-user-shield icon-maroon"></i>
        <h3>Admin Count</h3>
        <p><?= safeHtml($admins) ?></p>
      </div>
    </section>
  
    <section class="dashboard-layout">
      <div class="dashboard-column"> 
        <div class="section-box soft-blue">
            <h2>My Scheduled Events</h2>
            <p class="muted">Events you are already signed up for.</p>
            <?php if (!empty($scheduledEvents)): ?>
              <?php foreach ($scheduledEvents as $event): ?>
                <?php
                    $eventData = normalizeEvent($event);
                    $formattedDate = formatEventDate($eventData['date']);
                    $timeRange = formatEventTimeRange($eventData['startTime'], $eventData['endTime']);
                ?>
                <div class="event-item">
                  <strong><?= safeHtml($eventData['name']) ?></strong><br>
                  <span>
                    <?= safeHtml($formattedDate) ?>
                    <?php if ($timeRange !== ''): ?>
                      • <?= safeHtml($timeRange) ?>
                    <?php endif; ?>
                  </span><br>
                  <span class="status green">Registered</span>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="event-item">
                <strong>No scheduled events</strong><br>
                <span>You are not currently signed up for any upcoming events.</span>
              </div>
            <?php endif; ?>
          </div>

        <div class="section-box soft-blue">
          <h2>Top 10 Volunteers</h2>
          <p class="muted">Ranked by total volunteer hours</p>

          <div class="leaderboard">
            <?php
            $reportData = [];
            $reportData = calcTop10();
            $total = count($reportData);
            ?>
            <div class="leaderboard-split">
              <div class="leaderboard-column">
                <?php for ($i = 0; $i < min(5, $total); $i++):
                  $rankClass = "";
                  if ($i == 0) $rankClass = "gold";
                  elseif ($i == 1) $rankClass = "silver";
                  elseif ($i == 2) $rankClass = "bronze";
                ?>
                  <div class="leader-row <?php echo $rankClass; ?>">

                    <span class="rank">
                      <?php if ($i < 3): ?>
                        <i class="fas fa-trophy trophy-icon"></i>
                      <?php endif; ?>
                      <?php echo $i + 1; ?>
                    </span>

                    <span class="name">
                      <?php
                      echo safeHtml(
                        ($reportData[$i][0] ?? '') . ' ' . ($reportData[$i][1] ?? '')
                      );
                      ?>
                    </span>

                    <span class="hours">
                      <?php echo safeHtml($reportData[$i][2] ?? '0'); ?> hrs
                    </span>
                  </div>
                <?php endfor; ?>
              </div>

              <div class="leaderboard-column">
                  <?php for ($i = 5; $i < min(10, $total); $i++): ?>
                    <div class="leader-row">
                      <span class="rank"><?php echo $i + 1; ?></span>

                      <span class="name">
                        <?php echo safeHtml(($reportData[$i][0] ?? '') . ' ' . ($reportData[$i][1] ?? '')); ?>
                      </span>

                      <span class="hours">
                        <?php echo safeHtml($reportData[$i][2] ?? '0'); ?> hrs
                      </span>
                    </div>
                  <?php endfor; ?>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="dashboard-column right-column">
          <div class="section-box soft-maroon">
            <h2>Alerts</h2>

            <?php if (!empty($notifications)): ?>
              <?php foreach ($notifications as $notification): ?>
                <div class="alert-item">
                  <strong><?= safeHtml($notification['title'] ?? '') ?></strong><br>
                  <span><?= safeHtml($notification['body'] ?? '') ?></span>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="alert-item">
                <strong>No new alerts</strong><br>
                <span>You're all caught up!</span>
              </div>
            <?php endif; ?>
        </div>

        <div class="section-box soft-yellow">
    <h2>Upcoming Events</h2>
    <p class="muted">Available events you can sign up for.</p>

    <?php if (!empty($UpcomingEvents) && is_array($UpcomingEvents)): ?>
        <?php foreach ($UpcomingEvents as $event): ?>
            <?php
                $eventName = $event['name'] ?? '';
                $eventDate = $event['date'] ?? '';
                $startTime = $event['startTime'] ?? '';
                $endTime = $event['endTime'] ?? '';

                $formattedDate = !empty($eventDate) ? date('F j, Y', strtotime($eventDate)) : '';

                $start = !empty($startTime) ? date('g:i A', strtotime($startTime)) : '';
                $end = !empty($endTime) ? date('g:i A', strtotime($endTime)) : '';

                $dateTimeLine = $formattedDate;
                if ($start && $end) {
                    $dateTimeLine .= ' • ' . $start . ' - ' . $end;
                } elseif ($start) {
                    $dateTimeLine .= ' • ' . $start;
                } elseif ($end) {
                    $dateTimeLine .= ' • ' . $end;
                }
            ?>
            <div class="event-item">
                <strong><?= htmlspecialchars($eventName) ?></strong><br>
                <span><?= htmlspecialchars($dateTimeLine) ?></span><br>
                <span class="status yellow">Open for Signup</span>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="event-item">
            <strong>No upcoming events</strong><br>
            <span>There are no upcoming events available right now.</span>
        </div>
    <?php endif; ?>
</div>
</div>
<br>
        </div>
      </div>

    </section>

  </main>
<?php include 'footer.php'; ?>
</body>
<?php endif ?>

<?php if ($_SESSION['access_level'] < 2): ?>

<body>
<?php require 'header.php'; ?>

<main class="main-content">

    <section class="welcome-banner">
        <div>
            <h1>Welcome back, <?php echo safeHtml($_SESSION['f_name'] ?? 'Volunteer'); ?>!</h1>
            <p>Here is a quick look at your hours, registered events, schedule, and upcoming opportunities.</p>
        </div>

        <div class="date-box">
          <i class="fa-solid fa-calendar-days"></i> <?php echo safeHtml($currentDate); ?>
      </div>
    </section>

    <section class="wide-card-grid volunteer-top-grid">
        <div class="card soft-blue">
            <i class="fa-solid fa-clock icon-blue"></i>
            <h3>Total Volunteer Hours</h3>
            <p><?php echo safeHtml($displayHours); ?></p>
        </div>

        <div class="card soft-green">
            <i class="fa-solid fa-calendar-check icon-green"></i>
            <h3>Registered Events</h3>
            <p><?php echo safeHtml((string)$registeredEvents); ?></p>
        </div>
    </section>

    <section class="volunteer-bottom-grid">

        <div class="section-box soft-blue">
            <h2>My Scheduled Events</h2>
            <p class="muted">Events you are already signed up for.</p>
            <?php if (!empty($scheduledEvents)): ?>
              <?php foreach ($scheduledEvents as $event): ?>
                <?php
                    $eventData = normalizeEvent($event);
                    $formattedDate = formatEventDate($eventData['date']);
                    $timeRange = formatEventTimeRange($eventData['startTime'], $eventData['endTime']);
                ?>
                <div class="event-item">
                    <strong><?= safeHtml($eventData['name']) ?></strong><br>
                    <span>
                        <?= safeHtml($formattedDate) ?>
                        <?php if ($timeRange !== ''): ?>
                            • <?= safeHtml($timeRange) ?>
                        <?php endif; ?>
                    </span><br>
                    <span class="status green">Registered</span>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="event-item">
                  <strong>No scheduled events</strong><br>
                  <span>You are not currently signed up for any upcoming events.</span>
              </div>
            <?php endif; ?>
        </div>

        <div class="section-box soft-yellow">
            <h2>Upcoming Events</h2>
            <p class="muted">Available events you can sign up for.</p>

            <?php if (!empty($UpcomingEvents)): ?>
              <?php foreach ($UpcomingEvents as $event): ?>
                <?php
                    $eventData = normalizeEvent($event);
                    $formattedDate = formatEventDate($eventData['date']);
                    $timeRange = formatEventTimeRange($eventData['startTime'], $eventData['endTime']);
                ?>
                <div class="event-item">
                    <strong><?= safeHtml($eventData['name']) ?></strong><br>
                    <span>
                        <?= safeHtml($formattedDate) ?>
                        <?php if ($timeRange !== ''): ?>
                            • <?= safeHtml($timeRange) ?>
                        <?php endif; ?>
                    </span><br>
                    <span class="status yellow">Open for Signup</span>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="event-item">
                  <strong>No upcoming events</strong><br>
                  <span>There are no upcoming events available right now.</span>
              </div>
            <?php endif; ?>
        </div>

        <div class="section-box soft-maroon">
            <h2>Alerts</h2>
            <p class="muted">Important reminders and updates.</p>

            <?php if (!empty($notifications)): ?>
              <?php foreach ($notifications as $notification): ?>
                <div class="alert-item">
                  <strong><?= safeHtml($notification['title'] ?? '') ?></strong><br>
                  <span><?= safeHtml($notification['body'] ?? '') ?></span>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="alert-item">
                <strong>No new alerts</strong><br>
                <span>You're all caught up!</span>
              </div>
            <?php endif; ?>
        </div>

    </section>

</main>

<?php include 'footer.php'; ?>
</body>

<?php endif; ?>

</html>