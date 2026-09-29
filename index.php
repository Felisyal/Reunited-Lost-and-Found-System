<?php
date_default_timezone_set('Asia/Manila');
session_start(); 
$host = "localhost";
$dbUser = "root";
$dbPass = "";
$dbName = "reunited_db";


if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    session_unset();
    session_destroy();
    setcookie('remember_student', '', time() - 3600, '/');
    setcookie('remember_admin',   '', time() - 3600, '/');
    setcookie('remember_staff',   '', time() - 3600, '/');
    header("Location: index.php");
    exit;
}

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$conn = new mysqli($host, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


if (empty($_SESSION['student_id']) && isset($_COOKIE['remember_student'])) {
    $cookieId = $_COOKIE['remember_student'];
    $stmt = $conn->prepare("SELECT * FROM student_register WHERE student_id = ?");
    $stmt->bind_param("s", $cookieId);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    if ($student && $student['status'] === 'Active') {
        $_SESSION['student_id']    = $student['student_id'];
        $_SESSION['student_name']  = $student['student_name'];
        $_SESSION['student_db_id'] = $student['id'];
        $_SESSION['user_type']     = 'student';
        header("Location: student-portal.php");
        exit;
    }
}

if (empty($_SESSION['admin_id']) && isset($_COOKIE['remember_admin'])) {
    $cookieEmail = $_COOKIE['remember_admin'];
    $stmt = $conn->prepare("SELECT * FROM admin_register WHERE admin_email = ?");
    $stmt->bind_param("s", $cookieEmail);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    if ($admin && $admin['status'] === 'Active') {
        $_SESSION['admin_id']   = $admin['id'];
        $_SESSION['admin_name'] = $admin['admin_name'];
        $_SESSION['user_type']  = 'admin';
        header("Location: admin-portal.php");
        exit;
    }
}

if (empty($_SESSION['staff_employee_id']) && isset($_COOKIE['remember_staff'])) {
    $cookieEmail = $_COOKIE['remember_staff'];
    $stmt = $conn->prepare("SELECT * FROM staff_register WHERE staff_email = ?");
    $stmt->bind_param("s", $cookieEmail);
    $stmt->execute();
    $staff = $stmt->get_result()->fetch_assoc();
    if ($staff && $staff['status'] === 'Active') {
        $_SESSION['staff_employee_id'] = $staff['staff_employee_id'];
        $_SESSION['staff_name']        = $staff['staff_name'];
        $_SESSION['user_type']         = 'staff';
        $_SESSION['staff_email']       = $staff['staff_email'];
        header("Location: staff-faculty.php");
        exit;
    }
}

// ─── ALREADY LOGGED IN REDIRECTS ──────────────────────────────────────────────
if (!empty($_SESSION['admin_id'])) {
    header("Location: admin-portal.php");
    exit;
}
if (!empty($_SESSION['student_id'])) {
    header("Location: student-portal.php");
    exit;
}
if (!empty($_SESSION['staff_employee_id'])) {
    header("Location: staff-faculty.php");
    exit;
}

// ─── SUCCESS MESSAGE ──────────────────────────────────────────────────────────
$successMessage = '';
if (isset($_GET['register']) && $_GET['register'] === 'success') {
    $successMessage = "Registration successful! You can now log in.";
}

// ─── STUDENT LOGIN ────────────────────────────────────────────────────────────
$studentIdError = $studentPasswordError = "";
if (isset($_POST['student_submit'])) {
    $studentId       = trim($_POST['student_id'] ?? '');
    $studentPassword = trim($_POST['student_password'] ?? '');

    if (empty($studentId)) $studentIdError = "Student ID is required";
    if (empty($studentPassword)) $studentPasswordError = "Password is required";

    if (!$studentIdError && !$studentPasswordError) {
        $stmt = $conn->prepare("SELECT * FROM student_register WHERE student_id = ?");
        $stmt->bind_param("s", $studentId);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();

        if ($student && $student['lockout_until'] && strtotime($student['lockout_until']) > time()) {
            $unlockTime = date('h:i A', strtotime($student['lockout_until']));
            $studentPasswordError = "Too many failed attempts. You can try again at {$unlockTime}.";

        } elseif ($student && password_verify($studentPassword, $student['student_password'])) {

            if ($student['status'] !== 'Active') {
                $studentPasswordError = "Your account has been deactivated. Please contact the administrator.";
            } else {

                $reset = $conn->prepare("UPDATE student_register SET failed_attempts = 0, lockout_until = NULL WHERE id = ?");
                $reset->bind_param("i", $student['id']);
                $reset->execute();

                $_SESSION['student_id']    = $student['student_id'];
                $_SESSION['student_name']  = $student['student_name'];
                $_SESSION['student_db_id'] = $student['id'];
                $_SESSION['user_type']     = 'student';

                if (isset($_POST['remember_me'])) {
                    setcookie('remember_student', $student['student_id'], time() + (30 * 24 * 60 * 60), '/');
                }

                header("Location: student-portal.php");
                exit;
            }

        } else {
            if ($student) {
                $attempts = $student['failed_attempts'] + 1;

                if ($attempts >= 3) {
                    $lockUntil = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                    $update = $conn->prepare("UPDATE student_register SET failed_attempts = ?, lockout_until = ? WHERE id = ?");
                    $update->bind_param("isi", $attempts, $lockUntil, $student['id']);
                    $update->execute();

                    $unlockTime = date('h:i A', strtotime($lockUntil));
                    $studentPasswordError = "Too many failed attempts (3/3). You can try again at {$unlockTime}.";
                } else {
                    $update = $conn->prepare("UPDATE student_register SET failed_attempts = ? WHERE id = ?");
                    $update->bind_param("ii", $attempts, $student['id']);
                    $update->execute();

                    $studentPasswordError = "Invalid Student ID or Password. ({$attempts}/3 attempts used)";
                }
            } else {
                $studentPasswordError = "Invalid Student ID or Password";
            }
        }
    }
}


// ─── STAFF LOGIN ──────────────────────────────────────────────────────────────
$staffEmailError = $staffPasswordError = "";
if (isset($_POST['staff_submit'])) {
    $staffEmail    = trim($_POST['staff_email'] ?? '');
    $staffPassword = trim($_POST['staff_password'] ?? '');

    if (empty($staffEmail)) $staffEmailError = "Email is required";
    elseif (!filter_var($staffEmail, FILTER_VALIDATE_EMAIL)) $staffEmailError = "Invalid email format";
    if (empty($staffPassword)) $staffPasswordError = "Password is required";

    if (!$staffEmailError && !$staffPasswordError) {
        $stmt = $conn->prepare("SELECT * FROM staff_register WHERE staff_email = ?");
        $stmt->bind_param("s", $staffEmail);
        $stmt->execute();
        $staff = $stmt->get_result()->fetch_assoc();

        if ($staff && $staff['lockout_until'] && strtotime($staff['lockout_until']) > time()) {
            $unlockTime = date('h:i A', strtotime($staff['lockout_until']));
            $staffPasswordError = "Too many failed attempts. You can try again at {$unlockTime}.";

        } elseif ($staff && password_verify($staffPassword, $staff['staff_password'])) {

            if ($staff['status'] !== 'Active') {
                $staffPasswordError = "Your account has been deactivated. Please contact the administrator.";
            } else {

                $reset = $conn->prepare("UPDATE staff_register SET failed_attempts = 0, lockout_until = NULL WHERE id = ?");
                $reset->bind_param("i", $staff['id']);
                $reset->execute();

                $_SESSION['staff_employee_id'] = $staff['staff_employee_id'];
                $_SESSION['staff_name']        = $staff['staff_name'];
                $_SESSION['user_type']         = 'staff';
                $_SESSION['staff_email']       = $staff['staff_email'];

                if (isset($_POST['remember_me'])) {
                    setcookie('remember_staff', $staff['staff_email'], time() + (30 * 24 * 60 * 60), '/');
                }

                header("Location: staff-faculty.php");
                exit;
            }

        } else {
            if ($staff) {
                $attempts = $staff['failed_attempts'] + 1;

                if ($attempts >= 3) {
                    $lockUntil = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                    $update = $conn->prepare("UPDATE staff_register SET failed_attempts = ?, lockout_until = ? WHERE id = ?");
                    $update->bind_param("isi", $attempts, $lockUntil, $staff['id']);
                    $update->execute();

                    $unlockTime = date('h:i A', strtotime($lockUntil));
                    $staffPasswordError = "Too many failed attempts (3/3). You can try again at {$unlockTime}.";
                } else {
                    $update = $conn->prepare("UPDATE staff_register SET failed_attempts = ? WHERE id = ?");
                    $update->bind_param("ii", $attempts, $staff['id']);
                    $update->execute();

                    $staffPasswordError = "Invalid email or password. ({$attempts}/3 attempts used)";
                }
            } else {
                $staffPasswordError = "Invalid email or password";
            }
        }
    }
}
$adminEmailError = $adminPasswordError = "";
if (isset($_POST['admin_submit'])) {
    $adminEmail    = trim($_POST['email'] ?? '');
    $adminPassword = trim($_POST['password'] ?? '');

    if (empty($adminEmail)) $adminEmailError = "Email is required";
    elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) $adminEmailError = "Invalid email format";
    if (empty($adminPassword)) $adminPasswordError = "Password is required";

    if (!$adminEmailError && !$adminPasswordError) {
        $stmt = $conn->prepare("SELECT * FROM admin_register WHERE admin_email = ?");
        $stmt->bind_param("s", $adminEmail);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();

        if ($admin && $admin['lockout_until'] && strtotime($admin['lockout_until']) > time()) {
            $unlockTime = date('h:i A', strtotime($admin['lockout_until']));
            $adminPasswordError = "Too many failed attempts. You can try again at {$unlockTime}.";

        } elseif ($admin && password_verify($adminPassword, $admin['admin_password'])) {

                    if ($admin['status'] !== 'Active') {
                        $adminPasswordError = "Your account has been deactivated. Please contact the administrator.";
                    } else {

                        $reset = $conn->prepare("UPDATE admin_register SET failed_attempts = 0, lockout_until = NULL WHERE id = ?");
                        $reset->bind_param("i", $admin['id']);
                        $reset->execute();

                        $_SESSION['admin_id']   = $admin['id'];
                        $_SESSION['admin_name'] = $admin['admin_name'];
                        $_SESSION['user_type']  = 'admin';

                        if (isset($_POST['remember_me'])) {
                            setcookie('remember_admin', $admin['admin_email'], time() + (30 * 24 * 60 * 60), '/');
                        }

                        header("Location: admin-portal.php");
                        exit;
                    }

                } else {
            if ($admin) {
                $attempts = $admin['failed_attempts'] + 1;

                if ($attempts >= 3) {
                    $lockUntil = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                    $update = $conn->prepare("UPDATE admin_register SET failed_attempts = ?, lockout_until = ? WHERE id = ?");
                    $update->bind_param("isi", $attempts, $lockUntil, $admin['id']);
                    $update->execute();

                    $unlockTime = date('h:i A', strtotime($lockUntil));
                    $adminPasswordError = "Too many failed attempts (3/3). You can try again at {$unlockTime}.";
                } else {
                    $update = $conn->prepare("UPDATE admin_register SET failed_attempts = ? WHERE id = ?");
                    $update->bind_param("ii", $attempts, $admin['id']);
                    $update->execute();

                    $adminPasswordError = "Invalid email or password. ({$attempts}/3 attempts used)";
                }
            } else {
                $adminPasswordError = "Invalid email or password";
            }
        }
    }
}

// ─── STUDENT REGISTRATION ─────────────────────────────────────────────────────
$studentRegisterError = "";
if (isset($_POST['student_register_submit'])) {

    $student_name  = trim($_POST['student_name']);
    $student_id    = trim($_POST['student_number']);
    $student_email = trim($_POST['student_email']);
    $password      = $_POST['student_register_password'];
    $confirm       = $_POST['confirm_student'];

    if ($password !== $confirm) {
        $studentRegisterError = "Passwords do not match.";
    } else {

        $checkEmail = $conn->prepare("SELECT id FROM student_register WHERE student_email = ?");
        $checkEmail->bind_param("s", $student_email);
        $checkEmail->execute();
        $emailExists = $checkEmail->get_result()->fetch_assoc();

        $checkId = $conn->prepare("SELECT id FROM student_register WHERE student_id = ?");
        $checkId->bind_param("s", $student_id);
        $checkId->execute();
        $idExists = $checkId->get_result()->fetch_assoc();

        if ($emailExists) {
            $studentRegisterError = "This email is already registered. Please sign in instead.";
        } elseif ($idExists) {
            $studentRegisterError = "This Student ID is already registered.";
        } else {

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
                INSERT INTO student_register
                (student_name, student_id, student_email, student_password)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "ssss",
                $student_name,
                $student_id,
                $student_email,
                $hashedPassword
            );

            if ($stmt->execute()) {
                header("Location: index.php?register=success");
                exit;
            } else {
                $studentRegisterError = "Registration failed. Please try again.";
            }
        }
    }
}

// ─── ADMIN REGISTRATION ───────────────────────────────────────────────────────
$adminRegisterError = "";
if (isset($_POST['admin_register_submit'])) {

    $admin_name  = trim($_POST['admin_name']);
    $admin_email = trim($_POST['email_admin']);
    $password    = $_POST['admin_register_password'];
    $confirm     = $_POST['confirm_admin'];
    $reg_code    = trim($_POST['admin_reg_code']);

    $SECRET_CODE = "PUPPQ_03-24-2011!!!";

    if ($reg_code !== $SECRET_CODE) {
        $adminRegisterError = "Invalid registration code. Contact the system administrator.";
    } elseif ($password !== $confirm) {
        $adminRegisterError = "Passwords do not match.";
    } else {

        $checkEmail = $conn->prepare("SELECT id FROM admin_register WHERE admin_email = ?");
        $checkEmail->bind_param("s", $admin_email);
        $checkEmail->execute();
        $emailExists = $checkEmail->get_result()->fetch_assoc();

        if ($emailExists) {
            $adminRegisterError = "This email is already registered. Please sign in instead.";
        } else {

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
                INSERT INTO admin_register
                (admin_name, admin_email, admin_password)
                VALUES (?, ?, ?)
            ");

            $stmt->bind_param(
                "sss",
                $admin_name,
                $admin_email,
                $hashedPassword
            );

            if ($stmt->execute()) {
                header("Location: index.php?register=success");
                exit;
            } else {
                $adminRegisterError = "Registration failed. Please try again.";
            }
        }
    }
}

// ─── STAFF REGISTRATION ───────────────────────────────────────────────────────
$staffRegisterError = "";
if (isset($_POST['staff_register_submit'])) {

    $staff_name        = trim($_POST['staff_name_register']);
    $staff_email       = trim($_POST['email_staff']);
    $staff_employee_id = trim($_POST['employee_number']);
    $staff_department  = trim($_POST['Departments']);
    $password          = $_POST['staff_register_password'];
    $confirm           = $_POST['confirm_staff'];

    if ($password !== $confirm) {
        $staffRegisterError = "Passwords do not match.";
    } else {

        $checkEmail = $conn->prepare("SELECT id FROM staff_register WHERE staff_email = ?");
        $checkEmail->bind_param("s", $staff_email);
        $checkEmail->execute();
        $emailExists = $checkEmail->get_result()->fetch_assoc();

        $checkId = $conn->prepare("SELECT id FROM staff_register WHERE staff_employee_id = ?");
        $checkId->bind_param("s", $staff_employee_id);
        $checkId->execute();
        $idExists = $checkId->get_result()->fetch_assoc();

        if ($emailExists) {
            $staffRegisterError = "This email is already registered. Please sign in instead.";
        } elseif ($idExists) {
            $staffRegisterError = "This Employee ID is already registered.";
        } else {

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
                INSERT INTO staff_register
                (staff_name, staff_email, staff_employee_id, staff_department, staff_password)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "sssss",
                $staff_name,
                $staff_email,
                $staff_employee_id,
                $staff_department,
                $hashedPassword
            );

            if ($stmt->execute()) {
                header("Location: index.php?register=success");
                exit;
            } else {
                $staffRegisterError = "Registration failed. Please try again.";
            }
        }
    }
}

$registerAlertMessage = '';
$registerAlertRole = '';
$registerAlertTitle = 'Registration Failed';

if (!empty($studentRegisterError)) {
    $registerAlertMessage = $studentRegisterError;
    $registerAlertRole = 'student';
} elseif (!empty($adminRegisterError)) {
    $registerAlertMessage = $adminRegisterError;
    $registerAlertRole = 'admin';
} elseif (!empty($staffRegisterError)) {
    $registerAlertMessage = $staffRegisterError;
    $registerAlertRole = 'staff';
}

$loginAlertMessage = '';
$loginAlertRole = '';
$loginAlertIcon = '🔒';
$loginAlertTitle = (strpos($loginAlertMessage, 'deactivated') !== false)
    ? 'Account Deactivated'
    : 'Invalid email or password';

if (!empty($studentPasswordError)) {
    $loginAlertMessage = $studentPasswordError;
    $loginAlertRole = 'student';
} elseif (!empty($studentIdError)) {
    $loginAlertMessage = $studentIdError;
    $loginAlertRole = 'student';
    $loginAlertIcon = '⚠️';
} elseif (!empty($staffPasswordError)) {
    $loginAlertMessage = $staffPasswordError;
    $loginAlertRole = 'staff';
} elseif (!empty($staffEmailError)) {
    $loginAlertMessage = $staffEmailError;
    $loginAlertRole = 'staff';
    $loginAlertIcon = '⚠️';
} elseif (!empty($adminPasswordError)) {
    $loginAlertMessage = $adminPasswordError;
    $loginAlertRole = 'admin';
} elseif (!empty($adminEmailError)) {
    $loginAlertMessage = $adminEmailError;
    $loginAlertRole = 'admin';
    $loginAlertIcon = '⚠️';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reunited</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .toast-notif {
            position: fixed;
            top: 24px;
            left: 50%;
            transform: translateX(-50%) translateY(-80px);
            display: flex;
            align-items: center;
            gap: 10px;
            background: #eaf3de;
            color: #3b6d11;
            border: 0.5px solid #97c459;
            border-radius: 8px;
            padding: 12px 20px;
            font-size: 14px;
            font-weight: 500;
            z-index: 9999;
            opacity: 0;
            transition: transform 0.4s ease, opacity 0.4s ease;
            white-space: nowrap;
            pointer-events: none;
        }
        .toast-notif.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }
        .toast-notif .toast-icon {
            font-size: 18px;
            flex-shrink: 0;
        }

        /* ─── LOGIN ALERT (inline, sa loob ng form) ──────────────────────── */
        .login-alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: #fdecea;
            border: 1px solid #f5c2c0;
            border-left: 4px solid #c0392b;
            border-radius: 8px;
            padding: 12px 14px;
            margin: 10px 0;
        }
        .login-alert .alert-icon {
            font-size: 16px;
            line-height: 1.4;
            flex-shrink: 0;
        }
        .login-alert .alert-text {
            font-size: 13px;
            color: #8a2e27;
            line-height: 1.4;
        }

        /* ─── LOGIN ALERT MODAL ───────────────────────────────────────────── */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.55);
            align-items: center;
            justify-content: center;
            z-index: 10000;
        }
        .login-alert-box {
            background: #fff;
            border-radius: 12px;
            padding: 28px 26px;
            width: 90%;
            max-width: 340px;
            text-align: center;
            position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            animation: alertPop 0.2s ease-out;
        }
        @keyframes alertPop {
            from { transform: scale(0.92); opacity: 0; }
            to   { transform: scale(1); opacity: 1; }
        }
        .login-alert-box .modal-close {
            position: absolute;
            top: 10px;
            right: 16px;
            font-size: 22px;
            cursor: pointer;
            color: #999;
            line-height: 1;
        }
        .login-alert-box .modal-close:hover {
            color: #333;
        }
        .alert-icon-big {
            font-size: 36px;
            margin-bottom: 6px;
        }
        .login-alert-box h3 {
            margin: 4px 0 10px;
            color: #c0392b;
            font-size: 18px;
        }
        .login-alert-box p {
            font-size: 14px;
            color: #555;
            line-height: 1.5;
            margin-bottom: 18px;
        }
        .alert-ok-btn {
            background: #7a1f1f;
            color: #fff;
            border: none;
            padding: 10px 32px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }
        .alert-ok-btn:hover {
            background: #601818;
        }
    </style>
</head>

<body id="index-reunited">

<?php if (!empty($successMessage)): ?>
<div id="toast-notif" class="toast-notif">
    <span class="toast-icon">✅</span>
    <?php echo htmlspecialchars($successMessage); ?>
</div>
<script>
    (function() {
        var toast = document.getElementById('toast-notif');
        setTimeout(function() {
            toast.classList.add('show');
        }, 100);
        setTimeout(function() {
            toast.classList.remove('show');
        }, 4500);
    })();
</script>
<?php endif; ?>

<div id="index-section">
    <div class="index-title">
        <div class="logo-pup">
            <img src="image/PUPLogo.png" alt="PUP Logo">
        </div>
        <div class="logo-reunited">
            <img src="image/ReunitedLogo.png" alt="Reunited Logo">
        </div>
        <p class="paragraph-welcome">Lost and Found Management System</p>
    </div>
    <div class="index-container">
        <div class="admin-container">
            <div class="admin-portal">
                <div class="image-circle"><img src="image/admin.png"></div>
                <h3>Admin Portal</h3>
                <p>Manage all items, claims, users, and generate reports</p>
                <button onclick="showLogin('admin')">Access Admin Dashboard</button>
            </div>
            <div class="student-container">
                <div class="image-circle"><img src="image/student.png"></div>
                <h3>Student Portal</h3>
                <p>Search for lost items and submit claims</p>
                <button onclick="showLogin('student')">Access Student Portal</button>
            </div>
            <div class="staff-faculty">
                <div class="image-circle"><img src="image/staff.png" alt="staff"></div>
                <h3>Staff/Faculty Portal</h3>
                <p>Add found items and manage submissions</p>
                <button onclick="showLogin('staff')">Access Staff/Faculty Portal</button>
            </div>
        </div>
    </div>
</div>

<div id="login-section" style="display:none;">

    <div id="forgot-password-section" class="login-form" style="display:none;">
        <span class="back-home" onclick="goHome()">X</span>
        <h2>Forgot Password</h2>
        <p>Enter the email associated with your account and we'll help you reset your password.</p>
        <form action="forgot_password.php" method="POST">
            <label>Email Address</label>
            <input type="hidden" name="role" id="forgotRole" value="">
            <input type="email" name="email" placeholder="Your email address" required>
            <button type="submit">Send Reset Link</button>
            <button type="button" onclick="showLogin(currentRole)">Back to Login</button>
        </form>
    </div>

    <div id="admin-login" class="login-form">
        <span class="back-home" onclick="goHome()">X</span>
        <h2>Administrator Login</h2>
        <p>Sign in to access the admin panel</p>
        <form action="index.php" method="POST">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="admin@iskolarngbayan.pup.edu.ph" required>
            <label>Password</label>
            <input type="password" name="password" placeholder="Password" required>
            <div class="remember-forgot">
                <div class="remember-me">
                    <input type="checkbox" id="rememberMeAdmin" name="remember_me">
                    <label for="rememberMeAdmin">Remember me</label>
                </div>
                <button type="button" class="forgot-link" onclick="showForgotPasswordForm('admin')">Forgot Password?</button>
            </div>
            <button type="submit" name="admin_submit">Sign In</button>
            <p>Don't have an account? <button type="button" class="register-link" onclick="showRegisterForm('admin')">Register now</button></p>
        </form>
    </div>

    <div id="student-login" class="login-form">
        <span class="back-home" onclick="goHome()">X</span>
        <h2>Student Login</h2>
        <p>Sign in with your university credentials</p>
        <form action="index.php" method="POST">
            <label>Student ID</label>
            <input type="text" name="student_id" placeholder="Student ID" required>
            <label>Password</label>
            <input type="password" name="student_password" placeholder="Password" required>
            <div class="remember-forgot">
                <div class="remember-me">
                    <input type="checkbox" id="rememberMeStudent" name="remember_me">
                    <label for="rememberMeStudent">Remember me</label>
                </div>
                <button type="button" class="forgot-link" onclick="showForgotPasswordForm('student')">Forgot Password?</button>
            </div>
            <button type="submit" name="student_submit">Sign In</button>
            <p>Don't have an account? <button type="button" class="register-link" onclick="showRegisterForm('student')">Register now</button></p>
        </form>
    </div>

    <div id="staff-login" class="login-form">
        <span class="back-home" onclick="goHome()">X</span>
        <h2>Staff/Faculty Login</h2>
        <p>Sign in to access the staff/faculty portal</p>
        <form action="index.php" method="POST">
            <label>Email Address</label>
            <input type="email" name="staff_email" placeholder="Email Address" required>
            <label>Password</label>
            <input type="password" name="staff_password" placeholder="Password" required>
            <div class="remember-forgot">
                <div class="remember-me">
                    <input type="checkbox" id="rememberMeStaff" name="remember_me">
                    <label for="rememberMeStaff">Remember me</label>
                </div>
                <button type="button" class="forgot-link" onclick="showForgotPasswordForm('staff')">Forgot Password?</button>
            </div>
            <button type="submit" name="staff_submit">Sign In</button>
            <p>Don't have an account? <button type="button" class="register-link" onclick="showRegisterForm('staff')">Register now</button></p>
        </form>
    </div>
</div>


<div id="register-section" style="display:none;">

    <div class="login-form" id="admin-register">
        <span class="back-home-register" onclick="goHome()">X</span>
        <h2>Administrator Registration</h2>
        <p>Create an administrator account</p>
        <form method="POST">
            <label>Full Name</label>
            <input type="text" name="admin_name" placeholder="Administrator Name" required>
            <label>Email Address</label>
            <input type="email" name="email_admin" placeholder="admin@iskolarngbayan.pup.edu.ph" required>
            <label>Password</label>
            <input type="password" name="admin_register_password" placeholder="Password" required>
            <label>Confirm Password</label>
            <input type="password" name="confirm_admin" placeholder="Confirm Password" required>
            <label>Admin Registration Code</label>
            <input type="password" name="admin_reg_code" placeholder="Enter secret registration code" required>
            <button type="submit" name="admin_register_submit">Create Admin Account</button>
            <p>Already have an account? <button type="button" class="sign-in" onclick="showLogin(currentRole)">Sign In</button></p>
        </form>
    </div>

    <div class="login-form" id="student-register">
        <span class="back-home-register" onclick="goHome()">X</span>
        <h2>Student Registration</h2>
        <p>Create your account using your university email</p>
        <form method="POST">
            <label>Full Name</label>
            <input type="text" name="student_name" placeholder="John Doe" required>
            <label>Student ID</label>
            <input type="text" name="student_number" placeholder="2000-00000-PQ-0" pattern="[0-9]{4}-[0-9]{5}-[A-Z]{2}-[0-9]" required>
            <label>University Email</label>
            <input type="email" name="student_email" placeholder="john.doe@iskolarngbayan.pup.edu.ph" required>
            <label>Password</label>
            <input type="password" name="student_register_password" placeholder="Password" required>
            <label>Confirm Password</label>
            <input type="password" name="confirm_student" placeholder="Confirm Password" required>
            <button type="submit" name="student_register_submit">Create Student Account</button>
            <p>Already have an account? <button type="button" class="sign-in" onclick="showLogin(currentRole)">Sign In</button></p>
        </form>
    </div>

    <div class="login-form" id="staff-register">
        <span class="back-home-register" onclick="goHome()">X</span>
        <h2>Staff/Faculty Registration</h2>
        <p>Create your staff and faculty account</p>
        <form method="POST">
            <label>Full Name</label>
            <input type="text" name="staff_name_register" placeholder="John Smith" required>
            <label>Email Address</label>
            <input type="email" name="email_staff" placeholder="john.smith@university.ph" required>
            <label>Employee ID</label>
            <input type="text" name="employee_number" placeholder="EMP12345" required>
            <label for="dept-select">Department</label>
            <select name="Departments" id="dept-select">
                <option value="">Select Department</option>
                <option value="IT">Information Technology</option>
                <option value="HM">Hospitality Management</option>
                <option value="CoEp">Computer Engineering</option>
                <option value="OA">Office Administration</option>
            </select>
            <label>Password*</label>
            <input type="password" name="staff_register_password" placeholder="Password" required>
            <label>Confirm Password*</label>
            <input type="password" name="confirm_staff" placeholder="Confirm Password" required>
            <button type="submit" name="staff_register_submit">Create Staff/Faculty Account</button>
            <p>Already have an account? <button type="button" class="sign-in" onclick="showLogin(currentRole)">Sign In</button></p>
        </form>
    </div>
</div>

<?php if (!empty($registerAlertMessage)): ?>
<div id="registerAlertModal" class="modal-overlay">
    <div class="login-alert-box">
        <span class="modal-close" onclick="closeRegisterAlert()">&times;</span>
        <div class="alert-icon-big">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 1a5 5 0 0 0-5 5v3H6a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-9a2 2 0 0 0-2-2h-1V6a5 5 0 0 0-5-5zm0 2a3 3 0 0 1 3 3v3H9V6a3 3 0 0 1 3-3zm0 10a2 2 0 0 1 1 3.73V19a1 1 0 0 1-2 0v-2.27A2 2 0 0 1 12 13z"/>
            </svg>
        </div>
        <h3><?php echo htmlspecialchars($registerAlertTitle); ?></h3>
        <p><?php echo htmlspecialchars($registerAlertMessage); ?></p>
        <button class="alert-ok-btn" onclick="closeRegisterAlert()">OK</button>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($loginAlertMessage)): ?>
<div id="loginAlertModal" class="modal-overlay">
    <div class="login-alert-box">
        <span class="modal-close" onclick="closeLoginAlert()">&times;</span>
        <div class="alert-icon-big">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 1a5 5 0 0 0-5 5v3H6a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-9a2 2 0 0 0-2-2h-1V6a5 5 0 0 0-5-5zm0 2a3 3 0 0 1 3 3v3H9V6a3 3 0 0 1 3-3zm0 10a2 2 0 0 1 1 3.73V19a1 1 0 0 1-2 0v-2.27A2 2 0 0 1 12 13z"/>
            </svg>
        </div>
        <h3><?php echo htmlspecialchars($loginAlertTitle); ?></h3>
        <p><?php echo htmlspecialchars($loginAlertMessage); ?></p>
        <button class="alert-ok-btn" onclick="closeLoginAlert()">OK</button>
    </div>
</div>
<?php endif; ?>


<script src="script.js"></script>

<?php if (!empty($registerAlertMessage)): ?>
<script>
    window.addEventListener('load', function() {
        showRegisterForm('<?php echo $registerAlertRole; ?>');
        document.getElementById('registerAlertModal').style.display = 'flex';
    });

    function closeRegisterAlert() {
        var modal = document.getElementById('registerAlertModal');
        if (modal) modal.style.display = 'none';
    }

    document.getElementById('registerAlertModal').addEventListener('click', function (e) {
        if (e.target === this) closeRegisterAlert();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeRegisterAlert();
    });
</script>
<?php endif; ?>

<?php if (!empty($loginAlertMessage)): ?>
<script>
    window.addEventListener('load', function() {
        showLogin('<?php echo $loginAlertRole; ?>');
        document.getElementById('loginAlertModal').style.display = 'flex';
    });

    function closeLoginAlert() {
        var modal = document.getElementById('loginAlertModal');
        if (modal) modal.style.display = 'none';
    }

    document.getElementById('loginAlertModal').addEventListener('click', function (e) {
        if (e.target === this) closeLoginAlert();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeLoginAlert();
    });
</script>
<?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
    <script>
        window.addEventListener('load', function() {
            <?php if ($_GET['error'] === 'invalid_admin'): ?>
                showLogin('admin');
                setTimeout(() => window.alert("Invalid email or password"), 50);
            <?php elseif ($_GET['error'] === 'invalid_student'): ?>
                showLogin('student');
                setTimeout(() => window.alert("Invalid Student ID or Password"), 50);
            <?php elseif ($_GET['error'] === 'invalid_staff'): ?>
                showLogin('staff');
                setTimeout(() => window.alert("Invalid email or password"), 50);
            <?php elseif ($_GET['error'] === 'inactive_student'): ?>
                showLogin('student');
                setTimeout(() => window.alert("Your account has been deactivated. Please contact the administrator."), 50);
            <?php elseif ($_GET['error'] === 'inactive_staff'): ?>
                showLogin('staff');
                setTimeout(() => window.alert("Your account has been deactivated. Please contact the administrator."), 50);
            <?php elseif ($_GET['error'] === 'inactive_admin'): ?>
                showLogin('admin');
                setTimeout(() => window.alert("Your account has been deactivated. Please contact the administrator."), 50);
            <?php endif; ?>
        });
        </script>
    <?php endif; ?>

</body>
</html>