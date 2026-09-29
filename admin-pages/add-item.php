<?php
session_start();
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "reunited_db");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "error" => "DB connection failed"]);
    exit;
}

$item_name   = $_POST['item_name']   ?? '';
$category    = $_POST['category']    ?? '';
$description = $_POST['description'] ?? '';
$type        = $_POST['type']        ?? '';
$status      = $_POST['status']      ?? '';
$location    = $_POST['location']    ?? '';
$item_date   = $_POST['item_date']   ?? '';
$admin_id    = $_SESSION['admin_id'] ?? ''; 

$imageName = "";
if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $uploadDir = ($type === 'Found') ? "../staff-pages/uploads/" : "../student-pages/uploads/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $imageName = time() . "_" . basename($_FILES['image']['name']);
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName)) {
        echo json_encode(["success" => false, "error" => "File upload failed"]);
        exit;
    }
}

if ($type === "Found") {
    $stmt = $conn->prepare("
        INSERT INTO found_items
            (staff_employee_id, user_type, itemName_found, descriptionFoundItem,
             categoryFoundItem, locationFoundItem, dateFoundItem, foundImage, status)
        VALUES (?, 'admin', ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        echo json_encode(["success" => false, "error" => "Prepare failed: " . $conn->error]);
        exit;
    }

    $stmt->bind_param(
        "ssssssss",
        $admin_id, $item_name, $description, $category, $location, $item_date, $imageName, $status
    );

} else {
    $stmt = $conn->prepare("
        INSERT INTO lost_items
            (user_id, user_type, item_name, descriptionLostItem,
             categoryLostItem, locationLostItem, last_seen_date, lostImage, status)
        VALUES (?, 'admin', ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        echo json_encode(["success" => false, "error" => "Prepare failed: " . $conn->error]);
        exit;
    }

    $stmt->bind_param(
        "ssssssss",
        $admin_id, $item_name, $description, $category, $location, $item_date, $imageName, $status
    );
}

if (!$stmt->execute()) {
    echo json_encode(["success" => false, "error" => "Execute failed: " . $stmt->error]);
    exit;
}

echo json_encode(["success" => true]);
$stmt->close();
$conn->close();