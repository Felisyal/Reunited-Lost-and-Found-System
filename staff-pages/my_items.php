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

$staff_employee_id = $_SESSION['staff_employee_id'] ?? null;
$user_type = $_SESSION['user_type'] ?? null;

if (!$staff_employee_id || !$user_type) {
    die("Not logged in properly");
}

$sql = "
    SELECT
        id,
        itemName_found AS item_name,
        descriptionFoundItem AS description,
        categoryFoundItem AS category,
        locationFoundItem AS location,
        dateFoundItem AS report_date,
        foundImage AS image,
        status,
        'Found' AS report_type,
        created_at,
        expiration_date
    FROM found_items
    WHERE staff_employee_id = ?
      AND (
          expiration_date IS NULL
          OR expiration_date >= CURDATE()
      )

    UNION ALL

    SELECT
        id,
        item_name AS item_name,
        descriptionLostItem AS description,
        categoryLostItem AS category,
        locationLostItem AS location,
        last_seen_date AS report_date,
        lostImage AS image,
        status,
        'Lost' AS report_type,
        created_at,
        expiration_date
    FROM lost_items
    WHERE user_id = ?
      AND user_type = ?
      AND (
          expiration_date IS NULL
          OR expiration_date >= CURDATE()
      )

    ORDER BY created_at DESC
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "sss",
    $staff_employee_id,
    $staff_employee_id,
    $user_type
);

$stmt->execute();

$result = $stmt->get_result();

$items = [];

if ($result && $result->num_rows > 0) {
    $items = $result->fetch_all(MYSQLI_ASSOC);
}

$stmt->close();

$totalItems = count($items);

$approved = count(array_filter(
    $items,
    fn($i) => strtolower(trim($i['status'] ?? '')) === 'approved'
));

$readyForClaim = count(array_filter(
    $items,
    fn($i) => strtolower(trim($i['status'] ?? '')) === 'ready for claim'
));

$claimed = count(array_filter(
    $items,
    fn($i) => strtolower(trim($i['status'] ?? '')) === 'claimed'
));

$conn->close();

?>

<div class="item-staff">
    <div class="portal-staff-item">
        <span class="portal-item">Browse Added Items</span>
        <span class="portal-item-sub">Items you've added to the system</span>
    </div>
</div>


<div class="total-items-cards">

    <!-- TOTAL -->
    <div class="items-card">
        <div class="item-container">
            <span class="total-item-label">Total Items</span>
            <span class="total-item-value">
                <?= $totalItems ?>
            </span>
        </div>
    </div>

    <div class="items-card">
        <div class="item-container">
            <span class="total-item-label">Approved</span>
            <span class="total-item-value approved">
                <?= $approved ?>
            </span>
        </div>
    </div>

    <div class="items-card">
        <div class="item-container">
            <span class="total-item-label">Ready for Claim</span>
            <span class="total-item-value ready-for-claim">
                <?= $readyForClaim ?>
            </span>
        </div>
    </div>

    <div class="items-card">
        <div class="item-container">
            <span class="total-item-label">Claimed</span>
            <span class="total-item-value claimed">
                <?= $claimed ?>
            </span>
        </div>
    </div>

</div>

<div class="item-search"> 
    <div class="search-staff"> 
        <img src="admin-images/search.png" alt="Search">
        <input 
            type="text" 
            id="staff-item-search"
            placeholder="Search by item name or location..."
        >
    </div> 
</div>

<div class="items-wrapper">
    <?php if (count($items) > 0): ?>
        <?php foreach ($items as $item): ?>
            <div class="added_container"
                data-item-name="<?= htmlspecialchars(strtolower($item['item_name'])) ?>"
                data-location="<?= htmlspecialchars(strtolower($item['location'])) ?>">
                <div class="items_added">
                    <div class="item-image">
                        <?php
                        $imageFile = !empty($item['image']) ? trim($item['image']) : '';
                        $serverImagePath = __DIR__ . "/uploads/" . $imageFile;
                        $browserImagePath = "staff-pages/uploads/" . $imageFile;
                        ?>

                        <?php if (!empty($imageFile) && file_exists($serverImagePath)): ?>
                            <img src="<?php echo htmlspecialchars($browserImagePath); ?>" alt="Item Image" width="120">
                        <?php else: ?>
                            <img src="staff-images/staff_item.png" alt="No Image" width="120">
                        <?php endif; ?>
                    </div>
                    <div class="item-details">
                        <div class="upper-details">
                            <div class="upper-header-container">

                                <span class="item-name">
                                    <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                                </span>

                                <?php
                                $status = $item['status'] ?? 'Pending';

                                $statusClasses = [
                                    'Pending'         => 'status-pending',
                                    'Approved'        => 'status-approved',
                                    'Rejected'        => 'status-rejected',
                                    'Ready for Claim' => 'status-ready-for-claim',
                                    'Claimed'         => 'status-claimed'
                                ];

                                $statusClass = $statusClasses[$status] ?? 'status-pending';
                                ?>

                                <span class="status-badge <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($status); ?>
                                </span>

                            </div>
                            <span class="item-description"><?php echo htmlspecialchars($item['description']); ?></span>
                        </div>
                        <span class="item-location"><img src="staff-images/location.png"><?php echo htmlspecialchars($item['location']); ?></span>
                        <span class="item-date"><img src="staff-images/calendar.png"> <?php echo htmlspecialchars($item['report_date']); ?></span>
                        <span class="item-category"><?php echo htmlspecialchars($item['category']); ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="no-items">
            <img src="staff-images/staff_item.png" alt="No Image">
            <div class="noitems-text">
                <span>No items added yet</span>
                <span class="noitems-sub">Start by adding your first found item</span>
            </div>
            <button onclick="loadStaffAddItem()">Add Item</button>
        </div>
    <?php endif; ?>
</div>
