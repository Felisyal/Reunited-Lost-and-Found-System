<?php
session_start();
$host = "localhost";
$dbUser = "root";
$dbPass = "";
$dbName = "reunited_db";

$conn = new mysqli($host, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sqlLost = "SELECT l.id, l.user_id, l.user_type, l.item_name, l.descriptionLostItem, 
               l.categoryLostItem, l.locationLostItem, l.last_seen_date, l.lostImage, l.status, l.created_at, l.expiration_date,
               s.student_name, s.student_id AS student_number
        FROM lost_items_reports l
        LEFT JOIN student_register s ON l.user_id = s.student_id
        ORDER BY l.created_at DESC";

$resultLost = $conn->query($sqlLost);
$lostItems = [];
if ($resultLost && $resultLost->num_rows > 0) {
    $lostItems = $resultLost->fetch_all(MYSQLI_ASSOC);
}

$sqlFound = "SELECT f.id, f.staff_employee_id, f.itemName_found, f.descriptionFoundItem, 
               f.categoryFoundItem, f.locationFoundItem, f.dateFoundItem, f.foundImage, f.status, f.created_at, f.expiration_date,
               s.student_name, s.student_id AS student_number
        FROM found_items_reports f
        LEFT JOIN student_register s ON f.staff_employee_id = s.student_id
        ORDER BY f.created_at DESC";

$resultFound = $conn->query($sqlFound);
$foundItems = [];
if ($resultFound && $resultFound->num_rows > 0) {
    $foundItems = $resultFound->fetch_all(MYSQLI_ASSOC);
}

$allItems = array_merge($lostItems, $foundItems);
usort($allItems, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

$totalPending  = count(array_filter($allItems, fn($i) => strtolower($i['status']) === 'pending'));
$totalApproved = count(array_filter($allItems, fn($i) => in_array(strtolower($i['status']), ['approved', 'ready for claim', 'claimed'])));
$totalRejected = count(array_filter($allItems, fn($i) => strtolower($i['status']) === 'rejected'));
?>

<div class="review-title">
    <div class="review-contain">
        <span class="review-text">Review Reports</span>
        <span class="review-item-sub">Review and manage lost and found reports submitted by students and staff</span>
    </div>
</div>

<div class="review-items-cards">
    <div class="review-card">
       <div class="review-container">
           <span class="total-item-label">Pending Review</span>
           <span class="total-item-review"><?= $totalPending ?></span>
       </div>
    </div>
    <div class="review-card">
       <div class="review-container">
           <span class="total-item-label">Approved</span>
           <span class="total-item-review"><?= $totalApproved ?></span>
       </div>
    </div>
    <div class="review-card">
       <div class="review-container">
           <span class="total-item-label">Rejected</span>
           <span class="total-item-review"><?= $totalRejected ?></span>
       </div>
    </div>
</div>

<div class="filter-section">
    <div class="filter-tabs">
        <button class="tab active-all" onclick="setFilter('all', event)">All</button>
        <button class="tab" onclick="setFilter('pending', event)">Pending</button>
        <button class="tab" onclick="setFilter('approved', event)">Approved</button>
        <button class="tab" onclick="setFilter('rejected', event)">Rejected</button>
    </div>
    <select class="sort-select" name="sort">
        <option value="newest">Newest first</option>
        <option value="oldest">Oldest first</option>
        <option value="az">A – Z</option>
    </select>
</div>

<div class="review-report-container">
    <?php if (empty($allItems)): ?>
        <div class="no-report-wrapper">
            No Report Found
        </div>
    <?php endif; ?>

    <?php foreach ($allItems as $item): ?>
        <?php
            $isLost    = isset($item['item_name']) && !isset($item['itemName_found']);
            $itemName  = $isLost ? $item['item_name']           : $item['itemName_found'];
            $desc      = $isLost ? $item['descriptionLostItem'] : $item['descriptionFoundItem'];
            $category  = $isLost ? $item['categoryLostItem']    : $item['categoryFoundItem'];
            $location  = $isLost ? $item['locationLostItem']    : $item['locationFoundItem'];
            $date      = $isLost ? $item['last_seen_date']      : $item['dateFoundItem'];
            $image     = $isLost ? ($item['lostImage'] ?? '')   : ($item['foundImage'] ?? '');
            $addedBy   = $isLost ? $item['user_id']             : $item['staff_employee_id'];
            $badgeType = $isLost ? 'Lost'                       : 'Found';
            $badgeClass= $isLost ? 'lost-badge'                 : 'found-badge';
            $dataType  = $isLost ? 'lost'                       : 'found';
            $dateLabel = $isLost ? 'Last Seen'                  : 'Date Found';
            $locLabel  = $isLost ? 'Last Seen Location'         : 'Location Found';

            $statusRaw   = strtolower($item['status']);
            $statusClass = str_replace(' ', '-', $statusRaw);

            if ($isLost) {

                $imageFile = !empty($image) ? trim($image) : '';
                $userType = strtolower(trim($item['user_type'] ?? ''));

                if ($userType === 'staff') {
                    $serverImagePath = dirname(__DIR__) . "/staff-pages/uploads/" . $imageFile;
                    $browserImagePath = "staff-pages/uploads/" . $imageFile;
                } else {
                    $serverImagePath = dirname(__DIR__) . "/student-pages/uploads/" . $imageFile;
                    $browserImagePath = "student-pages/uploads/" . $imageFile;
                }

            } else {

    $imageFile = !empty($image) ? trim($image) : '';

    $serverImagePath = dirname(__DIR__) . "/staff-pages/uploads/" . $imageFile;
    $browserImagePath = "staff-pages/uploads/" . $imageFile;
}
        ?>
        <div class="added-review" data-status="<?= $statusClass ?>">
            <div class="review-content">
                <div class="upper-reviewer">
                    <div class="upper-between">
                        <span class="upper-title"><strong><?= htmlspecialchars($itemName) ?></strong></span>
                        <div style="display:flex; gap:6px; align-items:center;">
                            <span class="type-badge <?= $badgeClass ?>"><?= $badgeType ?></span>
                            <span class="report-badge <?= $statusClass ?>"><?= htmlspecialchars($item['status']) ?></span>
                        </div>
                    </div>
                    <span><img src="staff-images/review-profile.png"> <?= htmlspecialchars($addedBy) ?></span>
                    <span><img src="staff-images/calendar.png"> Reported: <?= date('M j, Y', strtotime($item['created_at'])) ?></span>
                </div>

                <div class="review-image">
                    <?php if (!empty($imageFile) && file_exists($serverImagePath)): ?>
                        <img src="<?= htmlspecialchars($browserImagePath) ?>" alt="Item Image" width="120">
                    <?php else: ?>
                        <img src="staff-images/staff_item.png" alt="No Image" width="120">
                    <?php endif; ?>
                </div>

                <div class="review-describe">
                    <div class="container-catdate">
                        <div class="review-row">
                            <label>Category</label>
                            <span><?= htmlspecialchars($category) ?></span>
                        </div>
                        <div class="review-row">
                            <label><?= $dateLabel ?></label>
                            <span><?= date('M j, Y', strtotime($date)) ?></span>
                        </div>
                    </div>

                    <div class="col-review">
                        <label><?= $locLabel ?></label>
                        <span><?= htmlspecialchars($location) ?></span>
                    </div>

                    <div class="col-review-descript">
                        <label>Description</label>
                        <span><?= htmlspecialchars($desc) ?></span>
                    </div>

                    <?php if ($statusRaw === 'pending'): ?>
                        <div class="review-button">
                            <button class="approve" data-id="<?= $item['id'] ?>" data-type="<?= $dataType ?>">Approve</button>
                            <button class="reject"  data-id="<?= $item['id'] ?>" data-type="<?= $dataType ?>">Reject</button>
                        </div>
                    <?php elseif ($statusRaw === 'approved'): ?>
                        <div class="status-legend approved-legend">
                            <img src="student-images/checkmark.png" alt="approved">
                            <span>This report has been approved and is active in the system.</span>
                        </div>
                    <?php elseif ($statusRaw === 'ready for claim'): ?>
                        <div class="status-legend approved-legend">
                            <img src="student-images/checkmark.png" alt="ready for claim">
                            <span>A match was found for this item. Waiting for the owner to claim it.</span>
                        </div>
                    <?php elseif ($statusRaw === 'claimed'): ?>
                        <div class="status-legend approved-legend">
                            <img src="student-images/checkmark.png" alt="claimed">
                            <span>This item has been successfully claimed.</span>
                        </div>
                    <?php elseif ($statusRaw === 'rejected'): ?>
                        <div class="status-legend rejected-legend">
                            <img src="student-images/remove.png" alt="rejected">
                            <span>This report was rejected and the student was notified.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>