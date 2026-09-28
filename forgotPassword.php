<?php
session_start();

ini_set("display_errors", 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/database/dbinfo.php');

// PHPMailer includes copied from createEmail.php
require_once __DIR__ . '/email/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/email/vendor/phpmailer/phpmailer/src/SMTP.php';
require_once __DIR__ . '/email/vendor/phpmailer/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function ensurePasswordResetTable(mysqli $conn): void {
    $sql = "
        CREATE TABLE IF NOT EXISTS dbpasswordreset (
            id INT AUTO_INCREMENT PRIMARY KEY,
            person_id VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            token_hash CHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_person_id (person_id),
            INDEX idx_email (email),
            INDEX idx_token_hash (token_hash)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $conn->query($sql);
}

function loadEnv(string $file): array {
    $env = [];
    if (!file_exists($file)) {
        return $env;
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $env[trim($key)] = trim($value);
    }

    return $env;
}

function sendEmails(array $emails, string $subject, string $body): array {
    $env = loadEnv(__DIR__ . '/email/.env');
    $results = [];
    $success = true;

    if (
        empty($env['SMTP_HOST']) ||
        empty($env['SMTP_USER']) ||
        empty($env['SMTP_PASS']) ||
        empty($env['SMTP_PORT']) ||
        empty($env['SMTP_FROM_NAME'])
    ) {
        return [
            'success' => false,
            'results' => [[
                'email' => 'all',
                'success' => false,
                'error' => 'SMTP settings are missing from email/.env'
            ]]
        ];
    }

    foreach ($emails as $email) {
        $email = trim((string)$email);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $success = false;
            $results[] = [
                'email' => $email,
                'success' => false,
                'error' => 'Invalid email address'
            ];
            continue;
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $env['SMTP_HOST'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $env['SMTP_USER'];
            $mail->Password   = $env['SMTP_PASS'];
            $mail->SMTPSecure = 'tls';
            $mail->Port       = (int)$env['SMTP_PORT'];

            $mail->setFrom($env['SMTP_USER'], $env['SMTP_FROM_NAME']);
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], ["\n", "\n", "\n", "\n"], $body))));

            $mail->send();
            $results[] = [
                'email' => $email,
                'success' => true
            ];
        } catch (Exception $e) {
            $success = false;
            $results[] = [
                'email' => $email,
                'success' => false,
                'error' => $mail->ErrorInfo ?: $e->getMessage()
            ];
        }
    }

    return [
        'success' => $success,
        'results' => $results
    ];
}

function buildBaseUrl(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['PHP_SELF'])), '/');
    if ($dir === '/' || $dir === '.') {
        $dir = '';
    }
    return $scheme . '://' . $host . $dir;
}

function extractEmailError($sendResult): string {
    if (!is_array($sendResult)) {
        return 'Unknown email error.';
    }

    if (isset($sendResult['results']) && is_array($sendResult['results']) && !empty($sendResult['results'])) {
        $errors = [];
        foreach ($sendResult['results'] as $result) {
            if (is_array($result) && !empty($result['error'])) {
                $errors[] = $result['email'] . ': ' . $result['error'];
            }
        }
        if (!empty($errors)) {
            return implode(' | ', $errors);
        }
    }

    if (!empty($sendResult['error'])) {
        return (string)$sendResult['error'];
    }

    return 'Email sending failed.';
}

$message = '';
$messageClass = 'bg-green-700';
$emailValue = '';

$conn = connect();
ensurePasswordResetTable($conn);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $emailValue = trim($_POST['email'] ?? '');

    if ($emailValue === '' || !filter_var($emailValue, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $messageClass = 'bg-red-700';
    } else {
        $stmt = $conn->prepare("SELECT id, email FROM dbpersons WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $emailValue);
        $stmt->execute();
        $result = $stmt->get_result();
        $person = $result->fetch_assoc();
        $stmt->close();

        if ($person) {
            $plainToken = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $plainToken);
            $expiresAt = date('Y-m-d H:i:s', time() + 3600);

            $cleanup = $conn->prepare("
                UPDATE dbpasswordreset
                SET used_at = NOW()
                WHERE person_id = ? AND used_at IS NULL
            ");
            $cleanup->bind_param("s", $person['id']);
            $cleanup->execute();
            $cleanup->close();

            $insert = $conn->prepare("
                INSERT INTO dbpasswordreset (person_id, email, token_hash, expires_at)
                VALUES (?, ?, ?, ?)
            ");
            $insert->bind_param("ssss", $person['id'], $person['email'], $tokenHash, $expiresAt);

            if (!$insert->execute()) {
                $message = 'Could not create the reset request.';
                $messageClass = 'bg-red-700';
            } else {
                $resetLink = buildBaseUrl() . "/forgotPasswordResetpassword.php?token=" . urlencode($plainToken);

                $subject = "Password Reset Request";
                $body = '
                    <p>Hello,</p>
                    <p>We received a request to reset your password.</p>
                    <p>Click the link below to choose a new password:</p>
                    <p><a href="' . htmlspecialchars($resetLink, ENT_QUOTES) . '">Reset Your Password</a></p>
                    <p>This link will expire in 1 hour.</p>
                    <p>If you did not request this reset, you can ignore this email.</p>
                    <p>Love Thy Neighbor Community Food Pantry</p>
                ';

                $sendResult = sendEmails([$person['email']], $subject, $body);

                if (is_array($sendResult) && !empty($sendResult['success'])) {
                    $message = 'If that email address exists in our system, a reset link has been sent.';
                    $messageClass = 'bg-green-700';
                } else {
                    $message = 'The reset email could not be sent. ' . extractEmailError($sendResult);
                    $messageClass = 'bg-red-700';

                    file_put_contents(
                        __DIR__ . '/forgot_password_debug.log',
                        "[" . date('Y-m-d H:i:s') . "] Failed sending reset email to {$person['email']} | " . print_r($sendResult, true) . PHP_EOL,
                        FILE_APPEND
                    );
                }
            }

            $insert->close();
        } else {
            $message = 'If that email address exists in our system, a reset link has been sent.';
            $messageClass = 'bg-green-700';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: Quicksand, sans-serif; }
    </style>
    <title>Forgot Password | Love Thy Neighbor Community Food Pantry</title>
</head>
<body>
    <div class="min-h-screen relative">
        <div class="absolute inset-0">
            <img src="images/LoveThyNeighbor_foodLightBlue.jpg"
                 alt="Background"
                 style="height: 100%;"
                 class="w-full h-full object-cover">
        </div>

        <div class="absolute inset-0 bg-black/50"></div>

        <div class="relative min-h-screen flex items-center justify-center">
            <div class="w-full max-w-xl bg-white backdrop-blur-md p-7 rounded-3xl shadow-2xl">
                <div class="w-full flex justify-center mb-6">
                    <img src="images/LoveThyNeighbor_logo1_NoBackground.png"
                         alt="Logo"
                         class="w-full max-w-xs">
                </div>

                <h1 class="text-2xl font-bold text-[rgb(0,74,173)] text-center mb-4">Forgot Password</h1>
                <p class="text-center text-gray-700 mb-6">
                    Enter your email address and we will send you a password reset link.
                </p>

                <?php if ($message !== ''): ?>
                    <div class="text-white text-center block p-3 rounded-lg mb-4 <?php echo $messageClass; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form class="w-full" method="post">
                    <div class="mb-4">
                        <label class="block text-[rgb(0,74,173)] font-medium mb-2" for="email">Email Address</label>
                        <input
                            class="w-full p-3 border border-gray-300 rounded-lg bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-400"
                            type="email"
                            name="email"
                            id="email"
                            value="<?php echo htmlspecialchars($emailValue); ?>"
                            placeholder="Enter your email"
                            required
                        >
                    </div>

                    <button class="cursor-pointer w-full bg-[rgb(0,74,173)] hover:bg-[rgb(203,37,26)] text-white font-semibold py-3 rounded-lg transition duration-300">
                        Send Reset Link
                    </button>
                </form>

                <div class="flex justify-center items-center mt-4">
                    <a href="login.php" class="text-[rgb(0,74,173)] text-sm hover:underline">Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>