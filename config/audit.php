
<?php
date_default_timezone_set('Asia/Manila');

function logAudit($conn, $action, $targetType = null, $targetId = null, $details = null, $actorId = null, $actorRole = null, $actorName = null) {
    try {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $actorRole = $actorRole ?? ($_SESSION['user_type'] ?? null);
        $actorId   = $actorId   ?? ($_SESSION['student_id'] ?? $_SESSION['staff_employee_id'] ?? $_SESSION['admin_id'] ?? null);
        $actorName = $actorName ?? ($_SESSION['student_name'] ?? $_SESSION['staff_name'] ?? $_SESSION['admin_name'] ?? null);

        $actorId  = $actorId  !== null ? (string)$actorId  : null;
        $targetId = $targetId !== null ? (string)$targetId : null;
        $ip  = $_SERVER['REMOTE_ADDR'] ?? null;
        $now = date('Y-m-d H:i:s');

        $stmt = $conn->prepare("INSERT INTO audit_logs (actor_id, actor_name, actor_role, action, target_type, target_id, details, ip_address, created_at) VALUES (?,?,?,?,?,?,?,?,?)");
        if (!$stmt) return false;
        $stmt->bind_param("sssssssss", $actorId, $actorName, $actorRole, $action, $targetType, $targetId, $details, $ip, $now);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    } catch (Throwable $e) {
        error_log("Audit error: " . $e->getMessage());
        return false;
    }
}