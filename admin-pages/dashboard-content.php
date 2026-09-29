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

$sql = "
SELECT 
    id, 
    itemName_found AS item_name, 
    descriptionFoundItem AS description,
    categoryFoundItem AS category,
    locationFoundItem AS location, 
    dateFoundItem AS item_date, 
    foundImage AS image, 
    status, 
    'Found' AS type,
    user_type,
    created_at 
FROM found_items 

UNION ALL 

SELECT 
    id, 
    item_name, 
    descriptionLostItem AS description,
    categoryLostItem AS category,
    locationLostItem AS location, 
    last_seen_date AS item_date, 
    lostImage AS image, 
    status, 
    'Lost' AS type,
    user_type,
    created_at 
FROM lost_items 

ORDER BY created_at DESC 
LIMIT 10
";

$result = $conn->query($sql);

$items = [];

if ($result && $result->num_rows > 0) {
    $items = $result->fetch_all(MYSQLI_ASSOC);
}

$totaldashBoard = 0;
$totalUsers = 0;
$totalApproved = 0;
$totalClaimed = 0;

$sqlTotalItems = "
    SELECT
        (SELECT COUNT(*) FROM lost_items) +
        (SELECT COUNT(*) FROM found_items) AS total
";

$resultTotalItems = $conn->query($sqlTotalItems);

if ($resultTotalItems) {
    $row = $resultTotalItems->fetch_assoc();
    $totaldashBoard = (int)($row['total'] ?? 0);
}

$sqlTotalUsers = "
    SELECT
        (SELECT COUNT(*) FROM student_register) +
        (SELECT COUNT(*) FROM staff_register) AS total
";

$resultTotalUsers = $conn->query($sqlTotalUsers);

if ($resultTotalUsers) {
    $row = $resultTotalUsers->fetch_assoc();
    $totalUsers = (int)($row['total'] ?? 0);
}

$sqlApproved = "
    SELECT
        (SELECT COUNT(*)
         FROM lost_items
         WHERE status IN ('Approved', 'Ready for Claim', 'Claimed'))

        +

        (SELECT COUNT(*)
         FROM found_items
         WHERE status IN ('Approved', 'Ready for Claim', 'Claimed'))

        AS total
";

$resultApproved = $conn->query($sqlApproved);

if ($resultApproved) {
    $row = $resultApproved->fetch_assoc();
    $totalApproved = (int)($row['total'] ?? 0);
}

$sqlClaimed = "
    SELECT
        (SELECT COUNT(*)
         FROM lost_items
         WHERE status = 'Claimed')

        +

        (SELECT COUNT(*)
         FROM found_items
         WHERE status = 'Claimed')

        AS total
";

$resultClaimed = $conn->query($sqlClaimed);

if ($resultClaimed) {
    $row = $resultClaimed->fetch_assoc();
    $totalClaimed = (int)($row['total'] ?? 0);
}

?>

<div class="header-dashboard">
    <span class="dashboard-title">Dashboard</span>
    <span class="name-dashboard">Welcome back, <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></span>
</div>


<div class="stats-cards">
    <div class="dashboard-card">
        <div class="stat-container">
            <div class="stat-column">
                <span class="total-item-label">Total Items</span>
                <span class="total-item-value">
                    <?php echo $totaldashBoard; ?>
                </span>
            </div>
            <div class="icon-card blue">
                <img src="admin-images/box.png" alt="">
            </div>
        </div>
    </div>

    <div class="dashboard-card">
        <div class="stat-container">
            <div class="stat-column">
                <span class="total-item-label">Total Users</span>
                <span class="total-item-value">
                    <?php echo $totalUsers; ?>
                </span>
            </div>
            <div class="icon-card green">
                <img src="admin-images/group.png" alt="">
            </div>
        </div>
    </div>

    <div class="dashboard-card">
        <div class="stat-container">
            <div class="stat-column">
                <span class="total-item-label">Total Approved</span>
                <span class="total-item-value pending">
                    <?php echo $totalApproved; ?>
                </span>
            </div>
            <div class="icon-card yellow">
                <img src="admin-images/contract.png" alt="">
            </div>
        </div>
    </div>

    <div class="dashboard-card">
        <div class="stat-container">
            <div class="stat-column">
                <span class="total-item-label">Total Claimed</span>
                <span class="total-item-value pending">
                    <?php echo $totalClaimed; ?>
                </span>
            </div>
            <div class="icon-card purple">
                <img src="admin-images/trend.png" alt="">
            </div>
        </div>
    </div>
</div>


<div class="table-lost">
        <div class="table-header">
            <div class="title-lost">
                <span class="lost-header">Recent Lost and Found Items</span>
                <span class="sub-header">Last items added to the system</span>
            </div>
        </div>
    <div class="table-scroll">  
        <table id="lost_admin">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Item Name</th>
                    <th>Location Found</th>
                    <th>Date Found</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="product_lost">
                <?php if (!empty($items)): ?>             
                <?php foreach ($items as $row): ?>
                    <?php
                    $imageFile = trim($row['image'] ?? '');
                    $userType  = strtolower(trim($row['user_type'] ?? ''));

                    if ($row['type'] === 'Found') {
                        $imagePath = 'staff-pages/uploads/' . $imageFile;
                    } elseif ($userType === 'staff') {
                        $imagePath = 'staff-pages/uploads/' . $imageFile;
                    } else {
                        $imagePath = 'student-pages/uploads/' . $imageFile;
                    }

                    $status = trim($row['status'] ?? 'Pending');

                    $statusClasses = [
                        'Pending'          => 'pending',
                        'Approved'         => 'approved',
                        'Ready for Claim'  => 'ready-for-claim',
                        'Claimed'          => 'claimed',
                        'Rejected'         => 'rejected'
                    ];

                    $statusClass = $statusClasses[$status] ?? 'pending';
                    ?>

                    <tr>
                        <td>
                            <?php if (!empty($imageFile)): ?>

                                <img 
                                    src="<?= htmlspecialchars($imagePath) ?>" 
                                    width="60" 
                                    height="60" 
                                    style="object-fit:cover; border-radius:6px;"
                                    alt="Item Image"
                                    onerror="this.onerror=null; this.src='admin-images/no-image.png';"
                                >

                            <?php else: ?>

                                <img 
                                    src="admin-images/no-image.png"
                                    width="60"
                                    height="60"
                                    style="object-fit:cover; border-radius:6px;"
                                    alt="No Image"
                                >

                            <?php endif; ?>
                        </td>
                        <td> <?= htmlspecialchars($row['item_name'] ?? '') ?></td>
                        <td> <?= htmlspecialchars($row['location'] ?? '') ?></td>
                        <td> <?= htmlspecialchars($row['item_date'] ?? '') ?></td>
                        <td>
                            <span class="dash-badge <?= htmlspecialchars($statusClass) ?>">
                                <?= htmlspecialchars($status) ?>
                            </span>
                        </td>

                        <td>
                            <button 
                                class="view-btn"
                                data-name="<?= htmlspecialchars($row['item_name'] ?? '') ?>"
                                data-location="<?= htmlspecialchars($row['location'] ?? '') ?>"
                                data-description="<?= htmlspecialchars($row['description'] ?? '') ?>"
                                data-image="<?= htmlspecialchars($imagePath) ?>"
                                data-status="<?= htmlspecialchars($status) ?>"
                            >
                                View
                            </button>
                        </td>

                    </tr>

                <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:20px;">
                            No items found
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="viewModal" class="view-modal">
  <div class="view-content">
    <div class="view-header">
      <h3 id="view-name">Item Details</h3>
      <span class="modal-close" onclick="closeViewModal()">&times;</span>
    </div>

    <img id="view-image" src="" alt="Item Image">

    <div class="view-details">

      <div class="sub-items-dets">
        <label>Location:</label>
        <span id="view-location"></span>
      </div>

      <div class="sub-items-dets">
        <label>Description:</label>
        <span id="view-description"></span>
      </div>

    </div>

  </div>
</div>

