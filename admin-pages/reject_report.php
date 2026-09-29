<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/mailer.php';

$conn = new mysqli("localhost", "root", "", "reunited_db");

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

$id   = $_POST['id'] ?? null;
$type = $_POST['type'] ?? null;

if (!$id || !$type) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

$table = "";

if ($type === "lost") {
    $table = "lost_items_reports";
} elseif ($type === "found") {
    $table = "found_items_reports";
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid type']);
    exit;
}

$stmt = $conn->prepare("UPDATE $table SET status = 'Rejected' WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    try {
        notifyReportStatus($conn, $type, $id, 'rejected');
    } catch (Throwable $e) {
        error_log("Reject email failed: " . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'message' => 'Report rejected successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>