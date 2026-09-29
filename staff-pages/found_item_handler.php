
<?php
session_start();

header('Content-Type: application/json');

$staff_employee_id = $_SESSION['staff_employee_id'] ?? null;
$user_type = $_SESSION['user_type'] ?? null;

if (!$staff_employee_id || !$user_type) {
    echo json_encode([
        "status" => "error",
        "message" => "User not logged in properly."
    ]);
    exit();
}


$host = "localhost";
$dbUser = "root";
$dbPass = "";
$dbName = "reunited_db";

$conn = new mysqli($host, $dbUser, $dbPass, $dbName);

if ($conn->connect_error) {
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed."
    ]);
    exit();
}

$conn->set_charset("utf8mb4");

$reportType = trim($_POST['reportType'] ?? '');

$itemName = trim($_POST['itemName_found'] ?? '');
$description = trim($_POST['descriptionFoundItem'] ?? '');
$category = trim($_POST['categoryFoundItem'] ?? '');
$location = trim($_POST['locationFoundItem'] ?? '');
$date = trim($_POST['dateFoundItem'] ?? '');

$otherCategory = trim($_POST['others'] ?? '');

if ($category === "Others" && !empty($otherCategory)) {
    $category = $otherCategory;
}

if (!in_array($reportType, ['Lost', 'Found'], true)) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid report type."
    ]);
    exit();
}

if (
    empty($itemName) ||
    empty($description) ||
    empty($category) ||
    empty($location) ||
    empty($date)
) {
    echo json_encode([
        "status" => "error",
        "message" => "Please complete all required fields."
    ]);
    exit();
}

$dateObject = DateTime::createFromFormat('Y-m-d', $date);

if (!$dateObject || $dateObject->format('Y-m-d') !== $date) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid date."
    ]);
    exit();
}

$fileName = null;

if (isset($_FILES['foundImage']) && $_FILES['foundImage']['error'] !== UPLOAD_ERR_NO_FILE) {

    if ($_FILES['foundImage']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode([
            "status" => "error",
            "message" => "There was an error uploading the image."
        ]);
        exit();
    }

    // 10MB limit
    if ($_FILES['foundImage']['size'] > 10 * 1024 * 1024) {
        echo json_encode([
            "status" => "error",
            "message" => "Image must not exceed 10MB."
        ]);
        exit();
    }

    $uploadDir = dirname(__DIR__) . '/staff-pages/uploads/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png'
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $_FILES['foundImage']['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowedMimeTypes[$mimeType])) {
        echo json_encode([
            "status" => "error",
            "message" => "Only JPG, JPEG, and PNG images are allowed."
        ]);
        exit();
    }

    $extension = $allowedMimeTypes[$mimeType];

    $fileName = uniqid('item_', true) . '.' . $extension;

    $targetFile = $uploadDir . $fileName;

    if (!move_uploaded_file($_FILES['foundImage']['tmp_name'], $targetFile)) {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to save uploaded image."
        ]);
        exit();
    }
}

$expirationDate = date('Y-m-d', strtotime($date . ' +30 days'));

if ($reportType === 'Lost') {

    $sql = "
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
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to prepare lost item query: " . $conn->error
        ]);
        exit();
    }

    $stmt->bind_param(
        "sssssssss",
        $staff_employee_id,
        $user_type,
        $itemName,
        $description,
        $category,
        $location,
        $date,
        $fileName,
        $expirationDate
    );

    if ($stmt->execute()) {

        echo json_encode([
            "status" => "success",
            "message" => "Lost item report added successfully!",
            "report_type" => "Lost",
            "report_id" => $stmt->insert_id
        ]);

    } else {

        echo json_encode([
            "status" => "error",
            "message" => "Failed to add lost item: " . $stmt->error
        ]);
    }

    $stmt->close();
}


elseif ($reportType === 'Found') {

    $sql = "
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
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to prepare found item query: " . $conn->error
        ]);
        exit();
    }

    $stmt->bind_param(
        "sssssssss",
        $staff_employee_id,
        $user_type,
        $itemName,
        $description,
        $category,
        $location,
        $date,
        $fileName,
        $expirationDate
    );

    if ($stmt->execute()) {

        echo json_encode([
            "status" => "success",
            "message" => "Found item report added successfully!",
            "report_type" => "Found",
            "report_id" => $stmt->insert_id
        ]);

    } else {

        echo json_encode([
            "status" => "error",
            "message" => "Failed to add found item: " . $stmt->error
        ]);
    }

    $stmt->close();
}

$conn->close();
?>
