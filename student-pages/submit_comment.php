<?php
session_start();
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "reunited_db");

$data      = json_decode(file_get_contents('php://input'), true);
$item_id   = intval($data['item_id'] ?? 0);
$comment   = trim($data['comment'] ?? '');
$item_type = $data['item_type'] ?? 'found';
$user_id   = $_SESSION['student_db_id'] ?? null;
$fullName  = $_SESSION['student_name'] ?? 'Anonymous';
$user_name = explode(' ', trim($fullName))[0];

if ($item_id && !empty($comment) && $user_id) {
    $stmt = $conn->prepare("INSERT INTO item_comments (item_id, item_type, user_id, user_name, comment) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $item_id, $item_type, $user_id, $user_name, $comment);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Missing data', 'item_id' => $item_id, 'user_id' => $user_id, 'comment' => $comment]);
}
$conn->close();
?>