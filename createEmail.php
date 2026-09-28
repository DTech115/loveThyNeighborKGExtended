<?php
session_cache_expire(30);
session_start();
ini_set("display_errors",1);
error_reporting(E_ALL);

// Admin check
if(!isset($_SESSION['_id'])) {
    header('Location: login.php');
    exit;
}

require_once(__DIR__ . '/database/dbinfo.php');
require_once(__DIR__ . '/database/dbPersons.php');
require_once(__DIR__ . '/database/dbEvents.php');
require_once(__DIR__ . '/database/dbpersonhours.php');

// Manual PHPMailer include
require_once __DIR__ . '/email/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/email/vendor/phpmailer/phpmailer/src/SMTP.php';
require_once __DIR__ . '/email/vendor/phpmailer/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ------------------------
// Get all members for dropdown
// ------------------------
function getUsersAndEmails() {
    $conn = connect();
    $members = [];
    $res = $conn->query("SELECT id, CONCAT(first_name,' ',last_name,' (',email,')') as label FROM dbpersons ORDER BY first_name");
    while ($row = $res->fetch_assoc()) {
        $members[] = ['label' => $row['label'], 'value' => $row['id']];
    }
    return $members;
}

$allMembers = getUsersAndEmails();
$memberLookup = [];
foreach ($allMembers as $member) {
    $memberLookup[$member['value']] = $member['label'];
}

// ------------------
// get events for drop down
// ------------------------

$allEvents = array_values(array_filter(
    get_all_events_sorted_by_date_not_archived(),
    static function ($event) {
        return !is_archived($event->getID());
    }
));



function loadEnv(string $file): array {
    $env = [];
    if (!file_exists($file)) return $env;
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        [$key, $value] = explode('=', $line, 2);
        $env[trim($key)] = trim($value);
    }
    return $env;
}

// Load .env file
$env = loadEnv(__DIR__ . '/email/.env');


// ------------------------
// Send emails via PHPMailer
// ------------------------
function sendEmails(array $emails, string $subject, string $body): array {
    global $env; // use loaded .env variables
    $results = [];
    $success = true;

    foreach ($emails as $email) {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $env['SMTP_HOST'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $env['SMTP_USER'];
            $mail->Password   = $env['SMTP_PASS'];
            $mail->SMTPSecure = 'tls';
            $mail->Port       = $env['SMTP_PORT'];

            $mail->setFrom($env['SMTP_USER'], $env['SMTP_FROM_NAME']);
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
            $results[] = ["email" => $email, "success" => true];
        } catch (Exception $e) {
            $success = false;
            $results[] = ["email" => $email, "success" => false, "error" => $mail->ErrorInfo];
        }
    }

    return ['success' => $success, 'results' => $results];
}


// ------------------------
// Retrieve emails from db
// ------------------------
function retrieveAllEmails(array $ids = []): array {
    $conn = connect();
    $emails = [];

    if (empty($ids)) {
        $res = $conn->query("SELECT id, email FROM dbpersons WHERE email IS NOT NULL AND email != ''");
        while ($row = $res->fetch_assoc()) {
            $emails[$row['id']] = $row['email'];
        }
        return $emails;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('s', count($ids));

    $sql = "SELECT id, email FROM dbpersons WHERE id IN ($placeholders) AND email IS NOT NULL AND email != ''";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) return [];

    $params = [&$types];
    foreach ($ids as $k => $v) $params[] = &$ids[$k];
    call_user_func_array([$stmt, 'bind_param'], $params);

    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $emails[$row['id']] = $row['email'];
    $stmt->close();

    return $emails;
}

// ------------------------
// Submit or schedule email
// ------------------------
function submitEmail(array $recipientIDs, string $subject, string $body, bool $sendNow, string $sendDate, string $recipientsType): array {
    $errors = [];

    // Determine recipients
    if ($recipientsType === 'specific' && !empty($recipientIDs)) {
        $emails = retrieveAllEmails($recipientIDs);
    } else if ($recipientsType === 'events' )
    {
        $emails = retrieveAllEmails($recipientIDs);
    }
    else{
        $emails = retrieveAllEmails();
        $recipientIDs = array_keys($emails);
    }

    if (empty($emails)) {
        return ['success' => false, 'errors' => ["No emails found for selected recipients."]];
    }

    // Send Now
    if ($sendNow) {
        $results = sendEmails(array_values($emails), $subject, $body); // deleted the thing about WhiskeyValorAdmin before emails, purpose unclear
        if (!$results['success']) {
            foreach ($results['results'] as $f) $errors[] = "Failed to send to {$f['email']}: {$f['error']}";
            return ['success' => false, 'errors' => $errors ?: ["Unknown error sending emails"]];
        }
        return ['success' => true, 'errors' => []];
    }

    // Schedule email
    if (empty($sendDate)) return ['success' => false, 'errors' => ["Send date is required for scheduled emails."]];

    $conn = connect();
    foreach ($recipientIDs as $recipientID) {
        $stmt = $conn->prepare("
            INSERT INTO dbscheduledemails
            (userID, recipientID, subject, body, scheduledSend, sent)
            VALUES (?, ?, ?, ?, ?, 0)
        ");
        if (!$stmt) {
            $errors[] = "DB prepare failed: " . $conn->error;
            continue;
        }
        $uid = (string)$_SESSION['_id'];
        $rid = (string)$recipientID;
        $stmt->bind_param("sssss", $uid, $rid, $subject, $body, $sendDate);
        if (!$stmt->execute()) $errors[] = "Failed to schedule email for {$recipientID}: " . $stmt->error;
        $stmt->close();
    }

    return ['success' => empty($errors), 'errors' => $errors];
}

// ------------------------
// Form handling
// ------------------------
$isAdmin = $_SESSION['access_level'] >= 2;
$submissionMessage = '';
$preselectedRecipientIDs = array_values(array_filter(array_map('trim', (array)($_POST['selected_users'] ?? []))));
$preselectedMembers = [];

foreach ($preselectedRecipientIDs as $recipientID) {
    if (isset($memberLookup[$recipientID])) {
        $preselectedMembers[] = [
            'value' => $recipientID,
            'label' => $memberLookup[$recipientID]
        ];
    }
}

if ($isAdmin && $_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {

    $action = $_POST['action'] ?? '';
    $subject = trim($_POST['subject'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $sendNowStr = $_POST['scheduled'] ?? 'true';
    $sendDate = $_POST['sendTime'] ?? '';
    $recipientsType = $_POST['recipients'] ?? 'all';
    $recipientIDs_raw = $_POST['recipientIDs'] ?? [];
    $eventID = $_POST['eventID'] ?? '';
    $sendNow = ($sendNowStr === 'true');

    // Collect recipient IDs
    $recipientIDs = [];
    if ($recipientsType === 'specific' && !empty($recipientIDs_raw)) {
        $recipientIDs = array_filter(array_map('trim', (array)$recipientIDs_raw));
    }

    if ($recipientsType === 'events' && !empty($eventID))
        {
            if (is_archived((int)$eventID)) {
                $submissionMessage = "<div class='error-toast'>Archived events cannot be used for participant emails.</div>";
            } else {
                $recipientIDs = getEventParticipants((int)$eventID);
            }
        }

    // ------------------------------------------------------
    // ACTION: SAVE DRAFT
    // ------------------------------------------------------
    if ($action === 'draft') {

        $conn = connect();
        $stmt = $conn->prepare("
            INSERT INTO dbdrafts (userID, subject, body, recipientID)
            VALUES (?, ?, ?, ?)
        ");

        $uid = (string)$_SESSION['_id'];

        // Store selected users as a comma-separated list for drafts, or "all" when none are selected.
        $rid = !empty($recipientIDs) ? implode(',', $recipientIDs) : "all";


        $stmt->bind_param("ssss", 
            $uid, 
            $subject, 
            $content, 
            $rid
        );

        if (!$stmt->execute()) {
            $submissionMessage = "<div class='error-toast'>Failed to save draft: {$stmt->error}</div>";
        } else {
            $submissionMessage = "<div class='success-toast'>Draft saved!</div>";
        }

        $stmt->close();
    }


    // ------------------------------------------------------
    // ACTION: SEND (NOW / SCHEDULE)
    // ------------------------------------------------------
    else if ($action === 'send') {

        if (!empty($submissionMessage)) {
            // Keep the archived-event validation message set above.
        } else if (empty($subject)) {
            $submissionMessage = "<div class='error-toast'>Email Subject is required.</div>";
        } else {

            $result = submitEmail($recipientIDs, $subject, $content, $sendNow, $sendDate, $recipientsType);

            if ($result['success']) {
                $submissionMessage = "<div class='success-toast'>Email successfully sent/scheduled!</div>";
            } else {
                $submissionMessage = "<div class='error-toast'>Errors:<br>" . implode("<br>", $result['errors']) . "</div>";
            }
        }
    }
}


?>



<!DOCTYPE html>
<html>
<head>
    <title>Send Email | Love Thy Neighbor Community Food Pantry</title>
    <link rel="stylesheet" href="css/layoutInfo.css">
</head>
<body>
<?php require_once('header.php'); ?>

<?php if (!$isAdmin): ?>
    <div class='error-toast'>You do not have permission to view this page.</div>
<?php else: ?>

<?= $submissionMessage ?>

<form method="POST" class="info-form">
    <div class="page-wrapper">
        <div class="info-card">
            <div class="info-header">
                <h1>Email</h1>
            </div>
            <div class="form-group">
                <label for="subject">* Email Subject</label>
                <input type="text" id="subject" name="subject" required>
            </div>

            <div class="form-group">
                <label for="content">Email Body</label>
                <textarea id="content" name="content" rows="10"></textarea>
            </div>
            <div class ="form-row">
                <div class ="form-group">
                    <label for="scheduled">Send Now?</label>
                    <select name="scheduled" id="scheduled">
                        <option value="true">Yes</option>
                        <option value="false">No (Schedule)</option>
                    </select>
                </div>   
                <div class="form-group" id="selectorTime" style="display: none;">
                    <label for="sendTime">Send Date</label>
                    <input type="date" id="sendTime" name="sendTime">
                </div>
            </div>    
            
            <div class="form-group">
                <label for="recipients">Recipients</label>
                <select name="recipients" id="recipients">
                    <option value="all">All Love Thy Neighbor KG Members</option>
                    <option value="specific">Specific Users</option>
                    <option value="events">Event Participants</option>
                </select>
            </div>

        <!--This only appears when specific users is selected  -->
            <div class="form-group" id="selectorRecipients" style="display:none;">
                <label for="recipientSearch">Select Members</label>
                <div class="search-select" id="recipientSearchSelect">
                    <input
                        type="text"
                        id="recipientSearch"
                        class="search-select-input"
                        placeholder="Search by name or email"
                        autocomplete="off"
                        aria-expanded="false"
                        aria-controls="recipientResults"
                    >
                    <div class="search-select-results" id="recipientResults" role="listbox"></div>
                </div>

                <!-- Selected members appear here as chips -->
                <div id="selectedMembersContainer" style="display:flex; flex-wrap:wrap; gap:6px; margin-top:8px;"></div>

                <!-- Hidden inputs injected by JS -->
                <div id="recipientHiddenInputs"></div>

                <!-- Keep the original select for JS data source only — hide it visually -->
                <select id="recipientID" name="_recipientID_unused" class="search-select-native" style="display:none;">
                    <option value="">-- Select a Member --</option>
                    <?php foreach ($allMembers as $m): ?>
                        <option value="<?= htmlspecialchars($m['value']) ?>"><?= htmlspecialchars($m['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
                    <!--I will put the event functionallity here  -->
            <div class="form-group" id="selectorEvents" style="display:none;">
                <label for="eventID">Select Event</label>
                <select id="eventID" name="eventID">
                    <option value="">-- Select an Event --</option>
                    <?php foreach ($allEvents as $m): ?>
                        <option value="<?= htmlspecialchars($m->getID()) ?>"><?= htmlspecialchars($m->getName())?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="email-actions">
                <button type="submit" name="action" value="send" class="submit-btn">Create Email</button>
                <!--<button type="submit" name="action" value="draft" class="draft-btn">Save Draft</button> -->
            </div>
        </div>
    </div>

</form>
<?php include 'footer.php'; ?>


<script>
const scheduledSelect = document.getElementById('scheduled');
const timeDiv = document.getElementById('selectorTime');
const sendTimeInput = document.getElementById('sendTime');
const recipientsSelect = document.getElementById('recipients');
const recipientsDiv = document.getElementById('selectorRecipients');
const recipientSelect = document.getElementById('recipientID');
const recipientSearch = document.getElementById('recipientSearch');
const recipientResults = document.getElementById('recipientResults');
const recipientSearchSelect = document.getElementById('recipientSearchSelect');
const eventsDiv = document.getElementById('selectorEvents');
const eventSelect = document.getElementById('eventID');
const selectedMembersContainer = document.getElementById('selectedMembersContainer');
const recipientHiddenInputs = document.getElementById('recipientHiddenInputs');
const preselectedMembers = <?= json_encode($preselectedMembers, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

let selectedMembers = [...preselectedMembers];

function renderSelectedChips() {
    selectedMembersContainer.innerHTML = '';
    recipientHiddenInputs.innerHTML = '';
    selectedMembers.forEach((member) => {
        const chip = document.createElement('span');
        chip.style.cssText = 'display:inline-flex;align-items:center;gap:4px;background:#e2e8f0;padding:4px 10px;border-radius:999px;font-size:0.85rem;';
        chip.textContent = member.label;
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.textContent = '×';
        removeBtn.style.cssText = 'background:none;border:none;cursor:pointer;font-size:1rem;line-height:1;padding:0;margin-left:2px;';
        removeBtn.addEventListener('click', () => {
            selectedMembers = selectedMembers.filter(m => m.value !== member.value);
            renderSelectedChips();
        });
        chip.appendChild(removeBtn);
        selectedMembersContainer.appendChild(chip);
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'recipientIDs[]';
        hidden.value = member.value;
        recipientHiddenInputs.appendChild(hidden);
    });
}

const memberOptions = Array.from(recipientSelect.options)
    .filter((option) => option.value !== '')
    .map((option) => ({
        value: option.value,
        label: option.textContent.trim(),
        searchText: option.textContent.trim().toLowerCase()
    }));

function closeRecipientResults() {
    recipientResults.classList.remove('is-open');
    recipientSearch.setAttribute('aria-expanded', 'false');
}

function openRecipientResults() {
    recipientResults.classList.add('is-open');
    recipientSearch.setAttribute('aria-expanded', 'true');
}

function renderRecipientResults(filterText = '') {
    const normalizedFilter = filterText.trim().toLowerCase();
    const matchingMembers = memberOptions.filter((member) => member.searchText.includes(normalizedFilter));

    recipientResults.innerHTML = '';

    if (matchingMembers.length === 0) {
        const emptyState = document.createElement('div');
        emptyState.className = 'search-select-empty';
        emptyState.textContent = 'No matching members found.';
        recipientResults.appendChild(emptyState);
        openRecipientResults();
        return;
    }

    matchingMembers.slice(0, 8).forEach((member) => {
        const optionButton = document.createElement('button');
        optionButton.type = 'button';
        optionButton.className = 'search-select-option';
        optionButton.textContent = member.label;
        optionButton.setAttribute('role', 'option');
        optionButton.dataset.value = member.value;
        optionButton.addEventListener('click', () => {
            const alreadySelected = selectedMembers.some(m => m.value === member.value);
            if (!alreadySelected) {
                selectedMembers.push({ value: member.value, label: member.label });
                renderSelectedChips();
            }
            recipientSearch.value = '';
            closeRecipientResults();
        });
        recipientResults.appendChild(optionButton);
    });

    openRecipientResults();
}

function toggleTime() {
    const sendNow = scheduledSelect.value === 'true';
    timeDiv.style.display = sendNow ? 'none' : 'block';
    sendTimeInput.required = !sendNow;
}

function toggleRecipients() {
    const showSpecificMembers = recipientsSelect.value === 'specific';
    const showEvents = recipientsSelect.value === 'events';

    recipientsDiv.style.display = showSpecificMembers ? 'block' : 'none';
    eventsDiv.style.display = showEvents ? 'block' : 'none';

    recipientSearch.required = false;
    eventSelect.required = showEvents;

    if (!showSpecificMembers) {
        selectedMembers = [];
        renderSelectedChips();
        recipientSearch.value = '';
        closeRecipientResults();
    }

    if (!showEvents) {
        eventSelect.value = '';
    }
}

recipientSearch.addEventListener('focus', () => {
    renderRecipientResults(recipientSearch.value);
});

recipientSearch.addEventListener('input', () => {
    recipientSelect.value = '';
    renderRecipientResults(recipientSearch.value);
});

recipientSearch.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeRecipientResults();
    }
});

document.addEventListener('click', (event) => {
    if (!recipientSearchSelect.contains(event.target)) {
        closeRecipientResults();
    }
});

scheduledSelect.addEventListener('change', toggleTime);
recipientsSelect.addEventListener('change', toggleRecipients);

document.querySelector('form').addEventListener('submit', (e) => {
    if (recipientsSelect.value === 'specific' && selectedMembers.length === 0) {
        e.preventDefault();
        alert('Please select at least one recipient.');
    }
});

document.addEventListener('DOMContentLoaded', () => {
    if (selectedMembers.length > 0) {
        recipientsSelect.value = 'specific';
        renderSelectedChips();
    }

    toggleTime();
    toggleRecipients();
});
</script>

<?php endif; ?>
</body>
</html>
