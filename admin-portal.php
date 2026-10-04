<?php
session_start();

$host = "localhost";
$dbUser = "root";
$dbPass = "";
$dbName = "reunited_db";

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$conn = new mysqli($host, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (empty($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

$adminName = $_SESSION['admin_name'];
$adminID   = $_SESSION['admin_id'];

$nameParts = explode(" ", $adminName);
$firstInitial = substr($nameParts[0], 0, 1);
$lastInitial = substr(end($nameParts), 0, 1);
$initials = strtoupper($firstInitial . $lastInitial);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reunited - Admin Panel</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body id="admin-interface">
    <nav class="sidebar" id="sidebar">
        <header>
            <div class="image-reunited">
                    <span class="logo-sidebar">
                        <img src = "image/user.png" alt="logo">
                    </span>
                <div class="text-admin">
                    <span class="brand-name">Reunited</span>
                    <span class="brand-sub">Admin Panel</span>
                </div>
            </div>
        </header>   

        <div class="menu-bar">
            <div class="menu-board">
                <ul class="menu-link">
                    <li class="logo-link" data-page="dashboard-content">
                        <a href="javascript:void(0)">
                            <img src="image/dashboard-admin.png" alt="dashboard">
                            <span class="logo-text">Dashboard</span>
                        </a>
                    </li>
                    <li class="logo-link" data-page="items">
                        <a href="javascript:void(0)">
                            <img src="image/item.png" alt="item">
                            <span class="logo-text">Items</span>
                        </a>
                    </li>
                    <li class="logo-link" data-page="claim">
                        <a href="javascript:void(0)">
                            <img src="image/claim.png" alt="claim">
                            <span class="logo-text">Claims</span>
                        </a>
                    </li>
                    <li class="logo-link" data-page="users">
                        <a href="javascript:void(0)">
                            <img src="image/users.png" alt="users">
                            <span class="logo-text">Users</span>
                        </a>
                    </li>
                    <li class="logo-link" data-page="report">
                        <a href="javascript:void(0)">
                            <img src="image/report.png" alt="report">
                            <span class="logo-text">Reports</span>
                        </a>
                    </li>
                    <li class="logo-link" data-page="assisted-matching">
                        <a href="javascript:void(0)">
                            <img src="image/generative.png" alt="assisted">
                            <span class="logo-text">AI Matching</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="sign-out">
            <a href="javascript:void(0)" class="sign-out-admin" onclick="showLogoutConfirm()">
                 <img src="image/sign-out.png" alt="signout">
                 <span class="logout-admin">Logout</span>
             </a>
        </div>
    </nav>

    

<main id="main-content">
    <header>
        <nav class="nav-content">
            <button class="toggle-btn" id="toggleBtn"><img src="image/menu.png" alt="menu"></button> 
            <div class="profile-header">
                <div class="profile-avatar"><?php echo htmlspecialchars($initials); ?></div>
                <div class="profile-info">
                    <span class="profile-name"><?php echo htmlspecialchars($adminName); ?></span>
                    <span class="profile-id">Admin ID: <?php echo htmlspecialchars($adminID); ?></span>
                </div>
            </div>
        </nav>
    </header>

    <div class="content" id="page-content">
    </div>
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