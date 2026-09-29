<?php

date_default_timezone_set('Asia/Manila');

$conn = new mysqli("localhost", "root", "", "reunited_db");

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

            echo "
            <script>

                alert('Password updated successfully!');

                window.location.href='index.php';

            </script>
            ";

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

    <?php if (!empty($error)): ?>

        <div class='error'>

            <?php echo $error; ?>

        </div>

    <?php endif; ?>

</div>

</body>
</html>