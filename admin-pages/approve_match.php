<?php
require_once dirname(__DIR__) . '/config/db.php';
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/sync_functions.php';
require_once __DIR__ . '/mailer.php';
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

$stmt = $conn->prepare("SELECT lost_item_id, found_item_id, status FROM ai_matches WHERE match_id = ?");
$stmt->bind_param("i", $matchId);
$stmt->execute();
$match = $stmt->get_result()->fetch_assoc();

if (!$match) {
    echo json_encode(['success' => false, 'error' => 'Match not found']);
    exit;
}

// Only pending matches can be approved (it may have become Outdated after a re-run)
if ($match['status'] !== 'Pending') {
    echo json_encode(['success' => false, 'error' => 'This match is no longer pending']);
    exit;
}

$lostId  = (int)$match['lost_item_id'];
$foundId = (int)$match['found_item_id'];

$conn->query("UPDATE ai_matches SET status = 'Approved' WHERE match_id = $matchId");
$conn->query("UPDATE lost_items SET status = 'Ready for Claim' WHERE id = $lostId");
$conn->query("UPDATE found_items SET status = 'Ready for Claim' WHERE id = $foundId");

syncReportStatus($conn, $lostId, 'lost', 'Ready for Claim');
syncReportStatus($conn, $foundId, 'found', 'Ready for Claim');

$lostItemStmt = $conn->prepare("SELECT user_id, item_name FROM lost_items WHERE id = ?");
$lostItemStmt->bind_param("i", $lostId);
$lostItemStmt->execute();
$lostItem = $lostItemStmt->get_result()->fetch_assoc();

if ($lostItem) {
    $contact = getReporterContact($conn, $lostItem['user_id']);
    if ($contact) {
        $sent = sendReadyForClaimEmail($contact['email'], $contact['name'], $lostItem['item_name']);
        if (!$sent) error_log("approve_match: ready-for-claim email failed for lost item #$lostId");
    }
}

$foundItemStmt = $conn->prepare("SELECT staff_employee_id, itemName_found FROM found_items WHERE id = ?");
$foundItemStmt->bind_param("i", $foundId);
$foundItemStmt->execute();
$foundItem = $foundItemStmt->get_result()->fetch_assoc();

if ($foundItem) {
    $finderContact = getReporterContact($conn, $foundItem['staff_employee_id']);
    if ($finderContact) {
        $sent = sendMatchedToFinderEmail($finderContact['email'], $finderContact['name'], $foundItem['itemName_found']);
        if (!$sent) error_log("approve_match: matched-to-finder email failed for found item #$foundId");
    }
}

logAudit($conn, 'MATCH_APPROVED', 'match', $matchId, "lost_item=$lostId, found_item=$foundId");

echo json_encode(['success' => true, 'message' => 'Match approved successfully']);

$conn->close();
?>