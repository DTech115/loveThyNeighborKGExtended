<?php
session_start();
ini_set("display_errors", 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/database/dbinfo.php');
require_once(__DIR__ . '/include/input-validation.php');

function ensurePasswordResetTable(mysqli $conn): void {
    $sql = "
        CREATE TABLE IF NOT EXISTS dbpasswordreset (
            id INT AUTO_INCREMENT PRIMARY KEY,
            person_id VARCHAR(255) NULL,
            email VARCHAR(255) NOT NULL,
            token_hash CHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_token_hash (token_hash)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $conn->query($sql);
}

$conn = connect();
ensurePasswordResetTable($conn);

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$tokenHash = $token !== '' ? hash('sha256', $token) : '';

$validRequest = false;
$message = '';
$messageClass = 'bg-red-700';
$resetRow = null;

if ($tokenHash !== '') {
    $stmt = $conn->prepare("
        SELECT id, person_id, email, expires_at, used_at
        FROM dbpasswordreset
        WHERE token_hash = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $tokenHash);
    $stmt->execute();
    $result = $stmt->get_result();
    $resetRow = $result->fetch_assoc();
    $stmt->close();

    if ($resetRow && empty($resetRow['used_at']) && strtotime($resetRow['expires_at']) > time()) {
        $validRequest = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $SecurePasswordResult = isSecurePassword($password);
    if (!$validRequest) {
        $message = 'This reset link is invalid or has expired.';
    } elseif ($SecurePasswordResult === false) {
        $message = 'Password must be at least 8 characters long, have at least one uppercase letter, one lowercase letter, and one number.';
    } elseif ($password !== $confirmPassword) {
        $message = 'Passwords do not match.';
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $updateUser = $conn->prepare("UPDATE dbpersons SET password = ? WHERE email = ? LIMIT 1");
        $updateUser->bind_param("ss", $passwordHash, $resetRow['email']);
        $updateUser->execute();
        $updateUser->close();

        $markUsed = $conn->prepare("UPDATE dbpasswordreset SET used_at = NOW() WHERE id = ?");
        $markUsed->bind_param("i", $resetRow['id']);
        $markUsed->execute();
        $markUsed->close();

        header("Location: login.php?reset=success");
        die();
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
    <title>Reset Password | Love Thy Neighbor Community Food Pantry</title>
</head>
<body>
    <div class="min-h-screen relative">
        <div class="absolute inset-0">
            <img src="images/LoveThyNeighbor_foodLightBlue.jpg"
                 alt="Background"
                 class="w-full h-full object-cover">
        </div>

        <div class="absolute inset-0 bg-black/50"></div>

        <div class="relative min-h-screen flex items-center justify-center px-4">
            <div class="w-full max-w-xl bg-white p-7 rounded-3xl shadow-2xl">
                <div class="w-full flex justify-center mb-6">
                    <img src="images/LoveThyNeighbor_logo1_NoBackground.png"
                         alt="Logo"
                         class="w-full max-w-xs">
                </div>

                <h1 class="text-2xl font-bold text-[rgb(0,74,173)] mb-3 text-center">Reset Password</h1>

                <?php if ($message !== ''): ?>
                    <div class="text-white text-center <?php echo $messageClass; ?> block p-3 rounded-lg mb-4">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($validRequest): ?>
                    <form method="post" class="w-full">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                        <div class="mb-4">
                            <label class="block text-[rgb(0,74,173)] font-medium mb-2" for="password">New Password</label>
                            <input class="w-full p-3 border border-gray-300 rounded-lg bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-400"
                                   type="password"
                                   name="password"
                                   id="password"
                                   placeholder="Enter a new password"
                                   required>
                        </div>

                        <div class="mb-4">
                            <label class="block text-[rgb(0,74,173)] font-medium mb-2" for="confirm_password">Confirm New Password</label>
                            <input class="w-full p-3 border border-gray-300 rounded-lg bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-400"
                                   type="password"
                                   name="confirm_password"
                                   id="confirm_password"
                                   placeholder="Confirm your new password"
                                   required>
                        </div>

                        <button class="cursor-pointer w-full bg-[rgb(0,74,173)] hover:bg-[rgb(203,37,26)] text-white font-semibold py-3 rounded-lg transition duration-300">
                            Reset Password
                        </button>
                    </form>
                <?php else: ?>
                    <div class="text-center text-gray-700">
                        This reset link is invalid or has expired.
                    </div>
                    <div class="text-center mt-5">
                        <a href="forgotPassword.php" class="text-[rgb(0,74,173)] hover:underline">Request a new reset link</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>