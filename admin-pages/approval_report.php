<?php
session_start();

header('Content-Type: application/json');

require_once __DIR__ . '/../config/api_config.php';
require_once __DIR__ . '/sync_functions.php';
require_once __DIR__ . '/mailer.php';

$conn = new mysqli("localhost", "root", "", "reunited_db");

if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'error' => 'DB connection failed'
    ]);
    exit;
}

$id   = $_POST['id'] ?? null;
$type = $_POST['type'] ?? null;

if (!$id || !$type) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing parameters'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| LOST REPORT
|--------------------------------------------------------------------------
*/

if ($type === "lost") {

    // Check if already approved/moved
    $check = $conn->prepare("
        SELECT status, linked_item_id
        FROM lost_items_reports
        WHERE id = ?
        LIMIT 1
    ");
    $check->bind_param("i", $id);
    $check->execute();

    $report = $check->get_result()->fetch_assoc();

    if (!$report) {
        echo json_encode([
            'success' => false,
            'error' => 'Lost report not found'
        ]);
        exit;
    }

    // Prevent duplicate insertion
    if (!empty($report['linked_item_id'])) {
        echo json_encode([
            'success' => false,
            'error' => 'This report has already been approved.'
        ]);
        exit;
    }

    $conn->begin_transaction();

    try {

        // Update report status
        $update = $conn->prepare("
            UPDATE lost_items_reports
            SET status = 'Approved'
            WHERE id = ?
        ");

        $update->bind_param("i", $id);

        if (!$update->execute()) {
            throw new Exception($update->error);
        }

        // Move report into lost_items
        $move = $conn->prepare("
            INSERT INTO lost_items
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
                expiration_date,
                report_id
            )
            SELECT
                user_id,
                user_type,
                item_name,
                descriptionLostItem,
                categoryLostItem,
                locationLostItem,
                last_seen_date,
                lostImage,
                'Approved',
                expiration_date,
                id
            FROM lost_items_reports
            WHERE id = ?
        ");

        $move->bind_param("i", $id);

        if (!$move->execute()) {
            throw new Exception($move->error);
        }

        $newId = $conn->insert_id;

        // Link report -> item
        $linkStmt = $conn->prepare("
            UPDATE lost_items_reports
            SET linked_item_id = ?
            WHERE id = ?
        ");

        $linkStmt->bind_param("ii", $newId, $id);

        if (!$linkStmt->execute()) {
            throw new Exception($linkStmt->error);
        }

        $conn->commit();

        try {
            notifyReportStatus($conn, 'lost', $id, 'approved');
        } catch (Throwable $e) {
            error_log("Lost approval email failed: " . $e->getMessage());
        }

        echo json_encode([
            'success' => true,
            'message' => 'Lost report approved successfully',
            'item_id' => $newId,
            'type' => 'lost'
        ]);

        exit;

    } catch (Exception $e) {

        $conn->rollback();

        echo json_encode([
            'success' => false,
            'error' => 'Lost approval failed: ' . $e->getMessage()
        ]);

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| FOUND REPORT
|--------------------------------------------------------------------------
*/

elseif ($type === "found") {

    // Check if already approved/moved
    $check = $conn->prepare("
        SELECT status, linked_item_id
        FROM found_items_reports
        WHERE id = ?
        LIMIT 1
    ");

    $check->bind_param("i", $id);
    $check->execute();

    $report = $check->get_result()->fetch_assoc();

    if (!$report) {
        echo json_encode([
            'success' => false,
            'error' => 'Found report not found'
        ]);
        exit;
    }

    // Prevent duplicate insertion
    if (!empty($report['linked_item_id'])) {
        echo json_encode([
            'success' => false,
            'error' => 'This report has already been approved.'
        ]);
        exit;
    }

    $conn->begin_transaction();

    try {

        $update = $conn->prepare("
            UPDATE found_items_reports
            SET status = 'Approved'
            WHERE id = ?
        ");

        $update->bind_param("i", $id);

        if (!$update->execute()) {
            throw new Exception($update->error);
        }

        // Move report into found_items
        $move = $conn->prepare("
            INSERT INTO found_items
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
                expiration_date,
                report_id
            )
            SELECT
                staff_employee_id,
                user_type,
                itemName_found,
                descriptionFoundItem,
                categoryFoundItem,
                locationFoundItem,
                dateFoundItem,
                foundImage,
                'Approved',
                expiration_date,
                id
            FROM found_items_reports
            WHERE id = ?
        ");

        $move->bind_param("i", $id);

        if (!$move->execute()) {
            throw new Exception($move->error);
        }

        $newId = $conn->insert_id;

        // Link report -> item
        $linkStmt = $conn->prepare("
            UPDATE found_items_reports
            SET linked_item_id = ?
            WHERE id = ?
        ");

        $linkStmt->bind_param("ii", $newId, $id);

        if (!$linkStmt->execute()) {
            throw new Exception($linkStmt->error);
        }

        $conn->commit();
        
        try {
            notifyReportStatus($conn, 'found', $id, 'approved');
        } catch (Throwable $e) {
            error_log("Found approval email failed: " . $e->getMessage());
        }

        echo json_encode([
            'success' => true,
            'message' => 'Found report approved successfully',
            'item_id' => $newId,
            'type' => 'found'
        ]);

        exit;

    } catch (Exception $e) {

        $conn->rollback();

        echo json_encode([
            'success' => false,
            'error' => 'Found approval failed: ' . $e->getMessage()
        ]);

        exit;
    }
}

else {

    echo json_encode([
        'success' => false,
        'error' => 'Invalid type'
    ]);

    exit;
}

$conn->close();
?>
