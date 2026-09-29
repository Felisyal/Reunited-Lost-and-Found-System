<?php
date_default_timezone_set('Asia/Manila');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require __DIR__ . '/vendor/phpmailer/phpmailer/src/SMTP.php';
require __DIR__ . '/vendor/phpmailer/phpmailer/src/Exception.php';


$host = "localhost";
$dbUser = "root";
$dbPass = "";
$dbName = "reunited_db";

$conn = new mysqli($host, $dbUser, $dbPass, $dbName);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$email = trim($_POST['email']);
$role = trim($_POST['role']);

if ($role == "student") {
    $table = "student_register";
    $emailColumn = "student_email";
    $nameColumn = "student_name";
}
elseif ($role == "staff") {
    $table = "staff_register";
    $emailColumn = "staff_email";
    $nameColumn = "staff_name";
}
else {
    $table = "admin_register";
    $emailColumn = "admin_email";
    $nameColumn = "admin_name";
}

$stmt = $conn->prepare("SELECT * FROM $table WHERE $emailColumn = ?");
$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

$fullName = $user[$nameColumn];

if ($result->num_rows > 0) {

    $token = bin2hex(random_bytes(32));

    $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

    $update = $conn->prepare("
        UPDATE $table 
        SET reset_token = ?, reset_expires = ?
        WHERE $emailColumn = ?
    ");

    $update->bind_param("sss", $token, $expiry, $email);
    $update->execute();

    $resetLink = "http://localhost/reunited/reset_password.php?token=$token&role=$role";

    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();
        $mail->SMTPDebug = 2;
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'reunited.lostandfound@gmail.com';
        $mail->Password   = 'cjyflgpfpakujnbj';

        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('reunited.lostandfound@gmail.com', 'Reunited System');

        $mail->addAddress($email);

        $mail->isHTML(true);

        $mail->Subject = 'Password Reset Request';

        $mail->Body = "

            <div style='font-family:Arial,sans-serif;padding:20px'>
                <h2 style='color:#800000'>Reunited Password Reset</h2>

                <p>Hello $fullName,</p>

                <p>You requested to reset your password.</p>

                <p>
                    Click the button below to change your password:
                </p>

                <a href='$resetLink'
                style='
                        background:#800000;
                        color:white;
                        padding:12px 20px;
                        text-decoration:none;
                        border-radius:5px;
                        display:inline-block;
                '>
                Reset Password
                </a>

                <p style='margin-top:20px'>
                    This link will expire in <b>1 hour</b>.
                </p>

                <p>If you did not request this, you can ignore this email.</p>

                <hr>

                <small>Reunited Lost and Found System</small>
            </div>
        ";

        $mail->send();

        echo "
        <script>
            alert('Password reset link sent to your Gmail.');
            window.location.href='index.php';
        </script>
        ";

    } catch (Exception $e) {
        echo "Mailer Error: {$mail->ErrorInfo}";
    }

} else {

    echo "
    <script>
        alert('Email not found.');
        window.location.href='index.php';
    </script>
    ";
}
?>
