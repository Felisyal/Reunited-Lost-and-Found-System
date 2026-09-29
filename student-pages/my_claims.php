<?php
session_start();
$conn = new mysqli("localhost", "root", "", "reunited_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$student_db_id = $_SESSION['student_db_id'] ?? null;

if (!$student_db_id) {
    die("Not logged in");
}

$sql = "
    SELECT
        c.status,
        c.claim_description,
        c.claimed_at,
        f.itemName_found AS item_name,
        f.foundImage AS item_image,
        c.item_type
    FROM claims_table c
    INNER JOIN found_items f
        ON c.item_id = f.id
        AND c.item_type = 'found'
    WHERE c.student_db_id = ?

    UNION ALL

    SELECT
        c.status,
        c.claim_description,
        c.claimed_at,
        l.item_name AS item_name,
        l.lostImage AS item_image,
        c.item_type
    FROM claims_table c
    INNER JOIN lost_items l
        ON c.item_id = l.id
        AND c.item_type = 'lost'
    WHERE c.student_db_id = ?

    ORDER BY claimed_at DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

$stmt->bind_param("ii", $student_db_id, $student_db_id);
$stmt->execute();

$result = $stmt->get_result();
?>

<div class="portal-claims">
    <div class="column-claims">
        <span class="claims-title">Submitted Claims</span>
        <span class="claims-sub">Track the status of submitted claims</span>
    </div>
</div>

<div class="report-bar">
    <div class="legend-item">
        <img src="student-images/report-warning.png" alt="warning">
        <span><strong>Pending:</strong> Under review</span>
    </div>

    <div class="legend-item">
        <img src="student-images/download.png" alt="check">
        <span><strong>Ready for Claim:</strong> Ready for pickup</span>
    </div>

    <div class="legend-item">
        <img src="student-images/checkmark.png">
        <span><strong>Claimed:</strong> Item has been claimed</span>
    </div>
</div>

<?php while ($item = $result->fetch_assoc()): ?>

<?php 
$status = strtolower(trim($item['status']));

if ($status === "pending") {
    $message = "Your claim is being reviewed by admin.";
    $icon = "student-images/report-warning.png";

} elseif ($status === "ready-for-claim") {
    $message = "Your claim has been approved. Please proceed to the office for pickup.";
    $icon = "student-images/download.png";

} elseif ($status === "claimed") {
    $message = "Your item has been claimed.";
    $icon = "student-images/checkmark.png";

} else {
    $message = "Status unavailable.";
    $icon = "student-images/report-warning.png";
}
?>

<div class="claim-card">
    <div class="claim-wrap">
        <div class="claim-top">
            <div class="claim-img">
                <?php
                    $imageFile = !empty($item['item_image'])
                        ? trim($item['item_image'])
                        : '';

                    $serverImagePath = dirname(__DIR__) . "/staff-pages/uploads/" . $imageFile;
                    $browserImagePath = "staff-pages/uploads/" . $imageFile;
                ?>

                <?php if (!empty($imageFile) && file_exists($serverImagePath)): ?>
                    <img src="<?= htmlspecialchars($browserImagePath) ?>"
                        alt="Item Image"
                        width="120">
                <?php else: ?>
                    <img src="staff-images/staff_item.png"
                        alt="No Image"
                        width="120">
                <?php endif; ?>
            </div>

            <div class="claim-info">
                <span class="info-item"><strong><?= htmlspecialchars($item['item_name']) ?></span>
                <span class="info-time">Claimed on <?= htmlspecialchars($item['claimed_at']) ?></span>
            </div>

            <div class="claim-message status-<?= htmlspecialchars($status) ?>">  
                <?php  
                if ($status === "pending") { 
                    echo "Pending"; 
                } elseif ($status === "ready-for-claim") { 
                    echo "Ready for Claim"; 
                } elseif ($status === "claimed") { 
                    echo "Claimed"; 
                } else { 
                    echo "Unknown"; 
                } 
                ?> 
            </div>
        </div>

        <div class="claim-desc"> 
            <label>Description:</label><br>
            <?= htmlspecialchars($item['claim_description']) ?>
        </div>

        <div class="claim-legend <?= $status ?>">
            <img src="<?= $icon ?>">
            <span><?= $message ?></span>
        </div>
    </div> 

</div>

<?php endwhile; ?>