<?php
function syncReportStatus($conn, $itemId, $itemType, $newStatus) {
    if ($itemType === 'lost') {
        $stmt = $conn->prepare("SELECT report_id FROM lost_items WHERE id = ?");
        $stmt->bind_param("i", $itemId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if ($row && $row['report_id']) {
            $update = $conn->prepare("UPDATE lost_items_reports SET status = ? WHERE id = ?");
            $update->bind_param("si", $newStatus, $row['report_id']);
            $update->execute();
        }
    } elseif ($itemType === 'found') {
        $stmt = $conn->prepare("SELECT report_id FROM found_items WHERE id = ?");
        $stmt->bind_param("i", $itemId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if ($row && $row['report_id']) {
            $update = $conn->prepare("UPDATE found_items_reports SET status = ? WHERE id = ?");
            $update->bind_param("si", $newStatus, $row['report_id']);
            $update->execute();
        }
    }
}
?>