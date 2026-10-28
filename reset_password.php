<?php
require_once __DIR__ . '/config/db.php';

date_default_timezone_set('Asia/Manila');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$token = $_GET['token'] ?? '';
$role  = $_GET['role'] ?? '';

$table = "";
$passwordCol = "";

switch ($role) {

    case "admin":
        $table = "admin_register";
        $passwordCol = "admin_password";
        break;

    case "student":
        $table = "student_register";
        $passwordCol = "student_password";
        break;

    case "staff":
        $table = "staff_register";
        $passwordCol = "staff_password";
        break;

    default:
        die("Invalid role");
}

$stmt = $conn->prepare("
    SELECT *
    FROM $table
    WHERE reset_token = ?
    AND reset_expires > NOW()
");

$stmt->bind_param("s", $token);

$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();

if (!$user) {

    die("
        <h2 style='color:red;text-align:center;margin-top:50px;'>
            Invalid or Expired Token
        </h2>
    ");
}

$error = "";

if (isset($_POST['reset'])) {

    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm']);

    if (empty($password) || empty($confirm)) {

        $error = "All fields are required.";

    } elseif ($password !== $confirm) {

        $error = "Passwords do not match.";

    } elseif (strlen($password) < 8) {

        $error = "Password must be at least 8 characters.";

    } else {

        $hashedPassword = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $update = $conn->prepare("
            UPDATE $table
            SET $passwordCol = ?,
                reset_token = NULL,
                reset_expires = NULL
            WHERE reset_token = ?
        ");

        $update->bind_param(
            "ss",
            $hashedPassword,
            $token
        );

        if ($update->execute()) {

            echo <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Password Updated</title>
<style>
body{margin:0;font-family:Arial,sans-serif;background:linear-gradient(rgba(0,0,0,0.5),rgba(0,0,0,0.5)),url('image/pup1.jpg');background-size:cover;background-position:center;height:100vh;display:flex;justify-content:center;align-items:center}
.tm-box{position:relative;width:560px;max-width:92%;background:#fff;border-radius:20px;border-top:8px solid #1e8e3e;padding:40px 36px 32px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.35)}
.tm-close{position:absolute;top:14px;right:20px;font-size:26px;line-height:1;color:#999;cursor:pointer}
.tm-icon{width:96px;height:96px;margin:0 auto 18px;border-radius:50%;background:#1e8e3e;display:flex;align-items:center;justify-content:center;box-shadow:0 6px 16px rgba(30,142,62,.35)}
.tm-icon svg{width:52px;height:52px}
.tm-title{margin:0 0 12px;font-size:30px;font-weight:700;color:#1e8e3e}
.tm-message{margin:0 0 26px;font-size:17px;line-height:1.6;color:#555}
.tm-ok{min-width:130px;padding:14px 30px;border:none;border-radius:10px;background:#1e8e3e;color:#fff;font-size:18px;font-weight:700;cursor:pointer;box-shadow:0 6px 14px rgba(30,142,62,.3)}
.tm-ok:hover{background:#17772f}
</style>
</head>
<body>
<div class="tm-box">
    <span class="tm-close" onclick="goLogin()">&times;</span>
    <div class="tm-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
    </div>
    <h3 class="tm-title">Password Updated</h3>
    <p class="tm-message">Your password has been updated successfully. You can now log in with your new password.</p>
    <button type="button" class="tm-ok" onclick="goLogin()">OK</button>
</div>
<script>
function goLogin() { window.location.href = 'index.php'; }
</script>
</body>
</html>
HTML;

            exit;

        } else {

            $error = "Failed to update password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang='en'>

<head>

<meta charset='UTF-8'>

<meta name='viewport' content='width=device-width, initial-scale=1.0'>

<title>Reset Password</title>

<style>

body{
    margin:0;
    padding:0;
    font-family:Arial,sans-serif;

    background:
        linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)),
        url('image/pup1.jpg');

    background-size:cover;
    background-position:center;
    background-repeat:no-repeat;

    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}

.reset-box{
    width:400px;
    background:white;
    padding:30px;
    border-radius:10px;
    box-shadow:0 0 10px rgba(0,0,0,0.1);
}

h2{
    text-align:center;
    color:#800000;
}

input{
    width:100%;
    padding:12px;
    margin-top:10px;
    border:1px solid #ccc;
    border-radius:5px;
    box-sizing:border-box;
}

button{
    width:100%;
    padding:12px;
    margin-top:15px;
    background:#800000;
    color:white;
    border:none;
    border-radius:5px;
    cursor:pointer;
    font-size:16px;
}

button:hover{
    background:#5e0000;
}

.error{
    color:red;
    text-align:center;
    margin-top:10px;
}

</style>

</head>

<body>

<div class='reset-box'>

    <h2>Reset Password</h2>

    <form method='POST'>

        <input
            type='password'
            name='password'
            placeholder='New Password'
            required
        >

        <input
            type='password'
            name='confirm'
            placeholder='Confirm Password'
            required
        >

        <button
            type='submit'
            name='reset'
        >
            Update Password
        </button>

    </form>

    <?php
require_once __DIR__ . '/config/db.php'; if (!empty($error)): ?>

        <div class='error'>

            <?php
require_once __DIR__ . '/config/db.php'; echo $error; ?>

        </div>

    <?php
require_once __DIR__ . '/config/db.php'; endif; ?>

</div>

</body>
</html>