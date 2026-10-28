<?php
require_once dirname(__DIR__) . '/config/db.php';
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once dirname(__DIR__) . '/config/audit.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

$matchId = (int)($_POST['match_id'] ?? 0);

if (!$matchId) {
    echo json_encode(['success' => false, 'error' => 'Missing match_id']);
    exit;
}
// Only pending matches can be rejected (it may have become Outdated after a re-run)
$stmt = $conn->prepare("UPDATE ai_matches SET status = 'Rejected' WHERE match_id = ? AND status = 'Pending'");
$stmt->bind_param("i", $matchId);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
} elseif ($stmt->affected_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'This match is no longer pending']);
} else {
    logAudit($conn, 'MATCH_REJECTED', 'match', $matchId);
    echo json_encode(['success' => true, 'message' => 'Match rejected']);
}

$conn->close();
?>