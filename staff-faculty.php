<?php
session_start();

$host = "localhost";
$dbUser = "root";
$dbPass = "";
$dbName = "reunited_db";


header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no- cache");
header("Expires: 0");

$conn = new mysqli($host, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['staff_email']) && isset($_POST['staff_password'])) {
    $staffEmail    = trim($_POST['staff_email']);
    $staffPassword = trim($_POST['staff_password']);

    $stmt = $conn->prepare("SELECT * FROM staff_register WHERE staff_email = ?");
    $stmt->bind_param("s", $staffEmail); 
    $stmt->execute();
    $staff = $stmt->get_result()->fetch_assoc();

    if ($staff && password_verify($staffPassword, $staff['staff_password'])) {
        $_SESSION['staff_employee_id'] = $staff['staff_employee_id'];         
        $_SESSION['user_type'] = 'staff';             
        $_SESSION['staff_email'] = $staff['staff_email'];

        header("Location: staff-faculty.php");
        exit;
    } else {
        header("Location: index.php?error=invalid_staff");
        exit;
    }
}

if (empty($_SESSION['staff_employee_id']) || empty($_SESSION['user_type'])) {
    header("Location: index.php");
    exit;
}

$staffEmail = $_SESSION['staff_email'];

$stmt = $conn->prepare("SELECT staff_name, staff_email, staff_department, staff_employee_id FROM staff_register WHERE staff_email = ?");
$stmt->bind_param("s", $staffEmail);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    session_unset();
    session_destroy();
    header("Location: index.php?error=invalid_staff");
    exit;
}

$staffName  = $user['staff_name'];
$staffEmpID = $user['staff_employee_id'];
$staffDept  = $user['staff_department'];

$nameParts = explode(" ", trim($staffName));
$firstInitial = substr($nameParts[0], 0, 1);
$lastInitial = substr(end($nameParts), 0, 1);
$initials = strtoupper($firstInitial . $lastInitial);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reunited - Staff/Faculty Portal</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body id="staff-portal">
    <nav class="staff-nav">
        <header>
            <div class="header-staff">
                <img src = "image/user.png" alt="logo">
                <div class="text-staff-port">
                    <span class="staff-title">Reunited</span>
                    <span class="staff-sub">Staff/Faculty Portal</span>
                </div>
            </div>

            <div class="nav-center-staff">
                <div class="feed-staff-header">
                    <ul class="staff-link">
                        <li class="staff_nf" data-page="found-item">
                            <a href="javascript:void(0)">
                                <img src="staff-images/plus.png" alt="add">
                                <span class="text-staff">Report Item</span>
                            </a>
                        </li>   
                        <li class="staff_nf" data-page="my_items">
                            <a href="javascript:void(0)">
                                <img src="staff-images/staff_item.png" alt="item">
                                <span class="text-staff">My Items</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="profile-header">
                <div class="profile-avatar"><?php echo htmlspecialchars($initials); ?></div>
                <div class="profile-info">
                    <span class="profile-name"><?php echo htmlspecialchars($staffName); ?></span>
                    <span class="profile-id">Employee ID: <?php echo htmlspecialchars($staffEmpID); ?></span>
                </div>
                <img src="image/sign-out.png" alt="logout" 
                onclick="showLogoutConfirm()">
            </div>

        </header>
    </nav>



<main id="main-student">
    <div class="content" id="page-content"></div>
</main>

<div class="modal-overlay" id="successModalOverlay">
  <div class="login-alert-box success-alert-box">
    <span class="modal-close" onclick="closeSuccessModal()">&times;</span>
    <div class="alert-icon-big">
      <svg viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg>
    </div>
    <h3 id="successModalTitle">Success!</h3>
    <p id="successModalMessage">Your report was submitted successfully.</p>
    <button class="alert-ok-btn" onclick="closeSuccessModal()">OK</button>
  </div>
</div>

<div class="modal-overlay" id="logoutModalOverlay">
  <div class="login-alert-box success-alert-box">
    <span class="modal-close" onclick="closeLogoutConfirm()">&times;</span>
    <div class="alert-icon-big">
      <svg viewBox="0 0 24 24"><path d="M16 17v-3H9v-4h7V7l5 5-5 5M14 2a2 2 0 0 1 2 2v2h-2V4H5v16h9v-2h2v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9z"/></svg>
    </div>
    <h3>Sign out</h3>
    <p>Are you sure you want to sign out?</p>
    <div style="display:flex; gap:10px; justify-content:center;">
      <button class="alert-ok-btn" style="background:#fff; color:#7a1f1f; border:1px solid #7a1f1f;" onclick="closeLogoutConfirm()">Cancel</button>
      <button class="alert-ok-btn" onclick="window.location.href='index.php?logout=1'">Sign out</button>
    </div>
  </div>
</div>


<div class="ai-chat-widget">
  <div class="chat-window" id="chatWindow" style="display:none">
    <div class="chat-head">
      <div class="chat-head-left">
        <div class="chat-head-avatar">R</div>
        <div>
          <div class="chat-head-name">Reunited Assistant</div>
          <div class="chat-head-status">AI Powered</div>
        </div>
      </div>
      <button onclick="toggleChat()">✕</button>
    </div>
    <div class="chat-messages" id="chatMessages">
      <div class="msg bot">Hi! I'm the Reunited Assistant. How can I help you today?</div>
    </div>
    <div class="chat-input-row">
      <input type="text" id="chatInput" placeholder="Type a message..."
             onkeydown="if(event.key==='Enter') sendChat()">
      <button onclick="sendChat()">Send</button>
    </div>
  </div>
  <button class="chat-fab" onclick="toggleChat()">💬</button>
</div>



<script src="script.js"></script>
</body>
</html>