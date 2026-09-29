<?php
session_start();
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "reunited_db");

if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed'
    ]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$item_id       = intval($data['item_id'] ?? 0);
$description   = trim($data['description'] ?? '');
$item_type     = strtolower(trim($data['item_type'] ?? ''));
$student_db_id = $_SESSION['student_db_id'] ?? null;

if (!$student_db_id) {
    echo json_encode([
        'success' => false,
        'error' => 'Not logged in'
    ]);
    exit;
}

if (!$item_id) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid item'
    ]);
    exit;
}

if (!in_array($item_type, ['found', 'lost'], true)) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid item type'
    ]);
    exit;
}

if ($description === '') {
    echo json_encode([
        'success' => false,
        'error' => 'Claim description is required'
    ]);
    exit;
}

$check = $conn->prepare("
    SELECT id
    FROM claims_table
    WHERE item_id = ?
      AND student_db_id = ?
      AND item_type = ?
");

if (!$check) {
    echo json_encode([
        'success' => false,
        'error' => $conn->error
    ]);
    exit;
}

$check->bind_param(
    "iis",
    $item_id,
    $student_db_id,
    $item_type
);

$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode([
        'success' => false,
        'error' => 'You already claimed this item'
    ]);

    $check->close();
    $conn->close();
    exit;
}

$check->close();

$stmt = $conn->prepare("
    INSERT INTO claims_table
    (
        item_id,
        item_type,
        student_db_id,
        claim_description,
        status,
        claimed_at
    )
    VALUES (?, ?, ?, ?, 'pending', NOW())
");

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'error' => $conn->error
    ]);
    exit;
}

$stmt->bind_param(
    "isis",
    $item_id,
    $item_type,
    $student_db_id,
    $description
);

if ($stmt->execute()) {

    echo json_encode([
        'success' => true,
        'message' => 'Claim submitted successfully',
        'item_id' => $item_id,
        'item_type' => $item_type
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