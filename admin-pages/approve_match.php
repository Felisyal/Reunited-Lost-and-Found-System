<?php
session_start();
header('Content-Type: application/json');
require_once 'sync_functions.php';

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

$stmt = $conn->prepare("SELECT lost_item_id, found_item_id FROM ai_matches WHERE match_id = ?");
$stmt->bind_param("i", $matchId);
$stmt->execute();
$match = $stmt->get_result()->fetch_assoc();

if (!$match) {
    echo json_encode(['success' => false, 'error' => 'Match not found']);
    exit;
}

$lostId  = $match['lost_item_id'];
$foundId = $match['found_item_id'];

error_log("DEBUG approve_match: lostId=$lostId foundId=$foundId"); 
  
$conn->query("UPDATE ai_matches SET status = 'Approved' WHERE match_id = $matchId");
$conn->query("UPDATE lost_items SET status = 'Ready for Claim' WHERE id = $lostId");
$conn->query("UPDATE found_items SET status = 'Ready for Claim' WHERE id = $foundId");

syncReportStatus($conn, $lostId, 'lost', 'Ready for Claim');
syncReportStatus($conn, $foundId, 'found', 'Ready for Claim');

$lostItemStmt = $conn->prepare("SELECT user_id, item_name FROM lost_items WHERE id = ?");
$lostItemStmt->bind_param("i", $lostId);
$lostItemStmt->execute();
$lostItem = $lostItemStmt->get_result()->fetch_assoc();

error_log("DEBUG approve_match: lostItem=" . json_encode($lostItem)); 

if ($lostItem) {
    $contact = getReporterContact($conn, $lostItem['user_id']);
    error_log("DEBUG approve_match: lost contact=" . json_encode($contact));  
    if ($contact) {
        $sent = sendReadyForClaimEmail($contact['email'], $contact['name'], $lostItem['item_name']);
        error_log("DEBUG approve_match: sendReadyForClaimEmail result=" . var_export($sent, true));  
    }
}

$foundItemStmt = $conn->prepare("SELECT staff_employee_id, itemName_found FROM found_items WHERE id = ?");
$foundItemStmt->bind_param("i", $foundId);
$foundItemStmt->execute();
$foundItem = $foundItemStmt->get_result()->fetch_assoc();

error_log("DEBUG approve_match: foundItem=" . json_encode($foundItem));  

if ($foundItem) {
    $finderContact = getReporterContact($conn, $foundItem['staff_employee_id']);
    error_log("DEBUG approve_match: finder contact=" . json_encode($finderContact));   
    if ($finderContact) {
        $sent = sendMatchedToFinderEmail($finderContact['email'], $finderContact['name'], $foundItem['itemName_found']);
        error_log("DEBUG approve_match: sendMatchedToFinderEmail result=" . var_export($sent, true));   
    }
}

echo json_encode(['success' => true, 'message' => 'Match approved successfully']);

$conn->close();
?>