<?php
session_start();
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "reunited_db");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

$matchId = (int)($_POST['match_id'] ?? 0);

if (!$matchId) {
    echo json_encode(['success' => false, 'error' => 'Missing match_id']);
    exit;
}
$stmt = $conn->prepare("UPDATE ai_matches SET status = 'Rejected' WHERE match_id = ?");
$stmt->bind_param("i", $matchId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Match rejected']);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$conn->close();
?>