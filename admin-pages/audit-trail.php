<?php
require_once dirname(__DIR__) . '/config/db.php';
session_start();
if (empty($_SESSION['admin_id'])) { http_response_code(403); exit('Unauthorized'); }
date_default_timezone_set('Asia/Manila');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) { exit('Database error'); }

$rows    = $conn->query("SELECT * FROM audit_logs ORDER BY created_at DESC, id DESC LIMIT 500");
$actions = $conn->query("SELECT DISTINCT action FROM audit_logs ORDER BY action");
?>
<style>
/* Mirrors the #items_tb rules from the items module so the audit table looks identical */
#audit_tb {
    padding: 10px 30px 20px 30px;
    width: 100%;
    min-width: 700px;
    margin-top: 20px;
    box-sizing: border-box;
}
#audit_tb thead tr th {
    font-size: 14px;
    font-weight: 600;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    color: #000000;
    letter-spacing: 0.05em;
}
#audit_tb td,
#audit_tb th {
    padding: 10px 10px;
    border-bottom: 1px solid #dddddd;
    border-left: none;
    border-right: none;
    text-align: center;
    vertical-align: middle;
    box-sizing: border-box;
}
#audit_tb td {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 14px;
    color: #333;
}
#audit_tb tbody tr:hover { background-color: #f0f2f5; }

#audit_tb td.audit-details {
    text-align: left;
    max-width: 320px;
    white-space: pre-wrap;
    word-break: break-word;
}
#audit_tb .audit-time { white-space: nowrap; color: #666; }

/* Action badges (same shape as .admin-badge in the items module) */
#audit_tb .audit-action {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    white-space: nowrap;
    background: #EDEDED;
    color: #5F6368;
}
#audit_tb .audit-ok   { background: #DFF5E1; color: #1E8E3E; }
#audit_tb .audit-bad  { background: #fdecea; color: #b3261e; }
#audit_tb .audit-info { background: #e8f0fe; color: #1a56c4; }
#audit_tb .audit-warn { background: #fff4e0; color: #a56200; }
</style>

<div class="header-items">
    <div class="item-header">
        <span class="dashboard-items">Audit Trail</span>
        <span class="sub_headerItems">Record of system activity (latest 500 entries)</span>
    </div>
</div>

<div class="table-items">
    <div class="table_header_items">
        <div class="item_container">
            <div class="search-bar">
                <img src="admin-images/search.png">
                <input type="text" id="audit-search" placeholder="Search user, action, or details...">
            </div>
            <select id="audit-action" class="select-status">
                <option value="">All Actions</option>
                <?php while ($a = $actions->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($a['action']) ?>"><?= htmlspecialchars(ucwords(strtolower(str_replace('_', ' ', $a['action'])))) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
    </div>

    <div class="items_tb_scroll">
        <table id="audit_tb">
            <thead>
                <tr>
                    <th>Date &amp; Time</th>
                    <th>User</th>
                    <th>Role</th>
                    <th>Action</th>
                    <th>Target</th>
                    <th>Details</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($rows && $rows->num_rows > 0): ?>
                <?php while ($r = $rows->fetch_assoc()):
                    $act = $r['action'];
                    if (in_array($act, ['LOGIN_FAILED', 'ACCOUNT_LOCKED', 'REPORT_REJECTED', 'MATCH_REJECTED', 'CLAIM_DELETED', 'ITEM_DELETED', 'USER_DELETED'], true)) $cls = 'audit-bad';
                    elseif (in_array($act, ['LOGIN_SUCCESS', 'REPORT_APPROVED', 'MATCH_APPROVED', 'ITEM_ADDED'], true)) $cls = 'audit-ok';
                    elseif (in_array($act, ['LOGOUT', 'USER_REGISTERED', 'REPORT_SUBMITTED', 'CLAIM_SUBMITTED'], true)) $cls = 'audit-info';
                    else $cls = 'audit-warn';
                    $who = trim(($r['actor_name'] ?? '') . ' ' . ($r['actor_id'] ? '(' . $r['actor_id'] . ')' : ''));
                ?>
                    <tr data-action="<?= htmlspecialchars($act) ?>">
                        <td class="audit-time"><?= htmlspecialchars($r['created_at']) ?></td>
                        <td><?= htmlspecialchars($who !== '' ? $who : '-') ?></td>
                        <td><?= htmlspecialchars(ucfirst($r['actor_role'] ?? '-')) ?></td>
                        <td><span class="audit-action <?= $cls ?>"><?= htmlspecialchars(ucwords(strtolower(str_replace('_', ' ', $act)))) ?></span></td>
                        <td><?= htmlspecialchars(($r['target_type'] ? ucfirst($r['target_type']) : '-') . ($r['target_id'] ? ' #' . $r['target_id'] : '')) ?></td>
                        <td class="audit-details"><?= htmlspecialchars($r['details'] ?? '') ?></td>
                        <td class="audit-time"><?= htmlspecialchars($r['ip_address'] ?? '') ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7" style="text-align:center; padding:20px;">No activity recorded yet</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $conn->close(); ?>