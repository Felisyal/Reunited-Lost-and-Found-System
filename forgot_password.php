<?php

date_default_timezone_set('Asia/Manila');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$host = "localhost";
$dbUser = "root";
$dbPass = "";
$dbName = "reunited_db";

$conn = new mysqli($host, $dbUser, $dbPass, $dbName);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$email = trim($_POST['email'] ?? '');
$role = trim($_POST['role'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "
    <script>
        alert('Please enter a valid email address.');
        window.location.href='index.php';
    </script>
    ";
    exit;
}

if ($role === "student") {
    $table = "student_register";
    $emailColumn = "student_email";
    $nameColumn = "student_name";
} elseif ($role === "staff") {
    $table = "staff_register";
    $emailColumn = "staff_email";
    $nameColumn = "staff_name";
} elseif ($role === "admin") {
    $table = "admin_register";
    $emailColumn = "admin_email";
    $nameColumn = "admin_name";
} else {
    echo "
    <script>
        alert('Invalid account role.');
        window.location.href='index.php';
    </script>
    ";
    exit;
}

$stmt = $conn->prepare("
    SELECT $nameColumn
    FROM $table
    WHERE $emailColumn = ?
    LIMIT 1
");

$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "
    <script>
        alert('Email not found.');
        window.location.href='index.php';
    </script>
    ";

    $stmt->close();
    $conn->close();
    exit;
}

$user = $result->fetch_assoc();
$fullName = $user[$nameColumn];

$stmt->close();

$token = bin2hex(random_bytes(32));
$expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

$update = $conn->prepare("
    UPDATE $table
    SET reset_token = ?, reset_expires = ?
    WHERE $emailColumn = ?
");

$update->bind_param("sss", $token, $expiry, $email);

if (!$update->execute()) {
    echo "
    <script>
        alert('Unable to process your password reset request.');
        window.location.href='index.php';
    </script>
    ";

    $update->close();
    $conn->close();
    exit;
}

$update->close();

$resetLink = "http://localhost/reunited/reset_password.php?token=" . urlencode($token) . "&role=" . urlencode($role);

$safeName = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
$safeResetLink = htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8');

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = $_ENV['SMTP_USERNAME'];
    $mail->Password = $_ENV['SMTP_PASSWORD'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->SMTPDebug = 0;

    $mail->setFrom(
        'reunited.lostandfound@gmail.com',
        'Reunited System'
    );

    $mail->addAddress($email, $fullName);

    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = 'Reset your Reunited password';

    $mail->Body = "
        <table role='presentation' width='100%' cellpadding='0' cellspacing='0'
            style='background-color:#f4f4f7; padding:32px 0;'>
            <tr>
                <td align='center'>
                    <table role='presentation' width='600' cellpadding='0' cellspacing='0'
                        style='background-color:#ffffff; border-radius:10px; overflow:hidden;
                        font-family:Segoe UI, Arial, sans-serif;
                        box-shadow:0 2px 8px rgba(0,0,0,0.06);'>

                        <tr>
                            <td style='background-color:#ffffff; padding:32px 40px 24px 40px; text-align:center;'>
                                <div style='color:#800000; font-size:26px; font-weight:700; letter-spacing:0.5px;'>
                                    REUNITED
                                </div>

                                <div style='color:#800000; font-size:16px; margin-top:4px;'>
                                    Lost &amp; Found System — PUP Parañaque
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td style='padding:28px 40px 0 40px;'>
                                <h2 style='margin:0; font-size:22px; color:#800000;'>
                                    Reset your password
                                </h2>
                            </td>
                        </tr>

                        <tr>
                            <td style='padding:20px 40px 32px 40px; font-size:16px; line-height:1.6; color:#444444;'>

                                <p style='margin:0 0 20px 0;'>
                                    Hi {$safeName},
                                </p>

                                <p style='margin:0 0 20px 0;'>
                                    We received a request to reset the password for your
                                    <strong>Reunited</strong> account.
                                </p>

                                <p style='margin:0 0 20px 0;'>
                                    Click the button below to reset your password:
                                </p>

                                <p style='text-align:center; margin:28px 0;'>
                                    <a href='{$safeResetLink}'
                                        style='
                                            display:inline-block;
                                            background-color:#800000;
                                            color:#ffffff;
                                            font-weight:600;
                                            font-size:13px;
                                            padding:10px 20px;
                                            border-radius:20px;
                                            text-decoration:none;
                                        '>
                                        RESET PASSWORD
                                    </a>
                                </p>

                                <p style='margin:0 0 20px 0;'>
                                    This password reset link will expire in
                                    <strong>1 hour</strong>.
                                </p>

                                <p style='margin:0 0 20px 0;'>
                                    If you did not request a password reset,
                                    you can safely ignore this email.
                                </p>

                                <p style='margin-top:28px;'>
                                    — Reunited Admin Team
                                </p>

                            </td>
                        </tr>

                        <tr>
                            <td style='background-color:#f4f4f7; padding:16px 32px;
                                text-align:center; font-size:11px; color:#999999;'>
                                This is an automated message from the Reunited system.
                                Please do not reply to this email.
                            </td>
                        </tr>

                    </table>
                </td>
            </tr>
        </table>
    ";

    $mail->AltBody =
        "Hi {$fullName},\n\n" .
        "We received a request to reset the password for your Reunited account.\n\n" .
        "Reset your password using this link:\n" .
        "{$resetLink}\n\n" .
        "This password reset link will expire in 1 hour.\n\n" .
        "If you did not request a password reset, you can safely ignore this email.\n\n" .
        "— Reunited Admin Team";

    $mail->send();

    echo "
    <script>
        alert('Password reset link sent to your Gmail.');
        window.location.href='index.php';
    </script>
    ";

} catch (Exception $e) {
    error_log("Password Reset Mailer Error: " . $mail->ErrorInfo);

    echo "
    <script>
        alert('Unable to send the password reset email. Please try again.');
        window.location.href='index.php';
    </script>
    ";
}

$conn->close();

?>