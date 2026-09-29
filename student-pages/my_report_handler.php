<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['student_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Not logged in'
    ]);
    exit;
}

$conn = new mysqli("localhost", "root", "", "reunited_db");

if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'error' => 'DB connection failed'
    ]);
    exit;
}

$conn->set_charset("utf8mb4");

$user_id   = $_SESSION['student_id'];
$user_type = $_SESSION['user_type'] ?? 'student';

$reportType = $_POST['reportType'] ?? '';

$itemName    = trim($_POST['itemName'] ?? '');
$category    = trim($_POST['categoryName'] ?? '');
$location    = trim($_POST['itemLocation'] ?? '');
$reportDate  = $_POST['lostDate'] ?? '';
$description = trim($_POST['descriptionReport'] ?? '');

$imageName = '';
$expirationDate = date('Y-m-d', strtotime('+30 days'));


if (!in_array($reportType, ['Lost', 'Found'], true)) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid report type'
    ]);
    exit;
}


if ($category === 'Others') {
    $others = trim($_POST['others'] ?? '');

    if ($others === '') {
        echo json_encode([
            'success' => false,
            'error' => 'Please specify the category'
        ]);
        exit;
    }

    $category = $others;
}


if (
    $itemName === '' ||
    $category === '' ||
    $location === '' ||
    $reportDate === '' ||
    $description === ''
) {
    echo json_encode([
        'success' => false,
        'error' => 'Please complete all required fields'
    ]);
    exit;
}

if (!empty($_FILES['itemImage']['name'])) {

    if ($_FILES['itemImage']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode([
            'success' => false,
            'error' => 'Image upload failed'
        ]);
        exit;
    }

    if ($_FILES['itemImage']['size'] > 10 * 1024 * 1024) {
        echo json_encode([
            'success' => false,
            'error' => 'Image must not exceed 10MB'
        ]);
        exit;
    }

    $allowedTypes = [
        'image/jpeg',
        'image/png'
    ];

    $fileType = mime_content_type($_FILES['itemImage']['tmp_name']);

    if (!in_array($fileType, $allowedTypes, true)) {
        echo json_encode([
            'success' => false,
            'error' => 'Only JPG and PNG images are allowed'
        ]);
        exit;
    }

    if ($reportType === 'Lost') {
        $uploadDir = dirname(__DIR__) . '/student-pages/uploads/';
    } else {
        $uploadDir = dirname(__DIR__) . '/staff-pages/uploads/';
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $extension = strtolower(
        pathinfo($_FILES['itemImage']['name'], PATHINFO_EXTENSION)
    );

    $imageName = uniqid('item_', true) . '.' . $extension;

    if (!move_uploaded_file(
        $_FILES['itemImage']['tmp_name'],
        $uploadDir . $imageName
    )) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to save uploaded image'
        ]);
        exit;
    }
}


if ($reportType === 'Lost') {

    $stmt = $conn->prepare("
        INSERT INTO lost_items_reports
        (
            user_id,
            user_type,
            item_name,
            descriptionLostItem,
            categoryLostItem,
            locationLostItem,
            last_seen_date,
            lostImage,
            status,
            expiration_date
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?)
    ");

    $stmt->bind_param(
        "sssssssss",
        $user_id,
        $user_type,
        $itemName,
        $description,
        $category,
        $location,
        $reportDate,
        $imageName,
        $expirationDate
    );

} else {

    $stmt = $conn->prepare("
        INSERT INTO found_items_reports
        (
            staff_employee_id,
            user_type,
            itemName_found,
            descriptionFoundItem,
            categoryFoundItem,
            locationFoundItem,
            dateFoundItem,
            foundImage,
            status,
            expiration_date
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?)
    ");

    $stmt->bind_param(
        "sssssssss",
        $user_id,
        $user_type,
        $itemName,
        $description,
        $category,
        $location,
        $reportDate,
        $imageName,
        $expirationDate
    );
}

if ($stmt->execute()) {

    echo json_encode([
        'success' => true,
        'reportType' => $reportType,
        'user_id' => $user_id,
        'user_type' => $user_type,
        'message' => $reportType . ' report submitted successfully'
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