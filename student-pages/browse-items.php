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




$commentSql = "SELECT * FROM item_comments ORDER BY created_at ASC";
$commentResult = $conn->query($commentSql);
$allComments = [];
while ($row = $commentResult->fetch_assoc()) {
    $key = $row['item_type'] . '_' . $row['item_id'];
    $allComments[$key][] = $row;
}

$studentName = $_SESSION['student_name'] ?? 'Anonymous';
$studentID   = $_SESSION['student_id'] ?? '';

$nameParts    = explode(" ", $studentName);
$firstInitial = substr($nameParts[0], 0, 1);
$lastInitial  = substr(end($nameParts), 0, 1);
$initials     = strtoupper($firstInitial . $lastInitial);


$sql1 = "SELECT 
            id,
            staff_employee_id,
            itemName_found AS item_name,
            descriptionFoundItem AS description,
            categoryFoundItem AS category,
            locationFoundItem AS location,
            dateFoundItem AS item_date,
            foundImage AS image,
            status,
            'found' AS item_type,
            NULL AS user_id,
            user_type,
            created_at,
            expiration_date
         FROM found_items
         WHERE expiration_date IS NULL
            OR expiration_date >= CURDATE()";

$sql2 = "SELECT 
            id,
            NULL AS staff_employee_id,
            item_name,
            descriptionLostItem AS description,
            categoryLostItem AS category,
            locationLostItem AS location,
            last_seen_date AS item_date,
            lostImage AS image,
            status,
            'lost' AS item_type,
            user_id,
            user_type,
            created_at,
            expiration_date
         FROM lost_items
         WHERE expiration_date IS NULL
            OR expiration_date >= CURDATE()";

$combined = "
    SELECT * 
    FROM (
        $sql1 
        UNION ALL 
        $sql2
    ) AS all_items
    ORDER BY created_at DESC
";

$result = $conn->query($combined);

$items = [];

if ($result && $result->num_rows > 0) {
    $items = $result->fetch_all(MYSQLI_ASSOC);
}



$result = $conn->query($combined);
$items = [];
if ($result && $result->num_rows > 0) {
    $items = $result->fetch_all(MYSQLI_ASSOC);
}

$totalItems = count($items);
$pending  = count(array_filter($items, fn($i) => strtolower($i['status'] ?? '') === 'pending'));
$claimed  = count(array_filter($items, fn($i) => strtolower($i['status'] ?? '') === 'claimed'));
$returned = count(array_filter($items, fn($i) => strtolower($i['status'] ?? '') === 'returned'));




$conn->close();
?>

<div class="portal-student">
  <span class="portal-title">Browse Lost and Found Items</span>
  <span class="portal-sub">Search for your lost items among the found items database</span>
</div>

<div class="container-portal-student">
    <div class="search-student">
        <img src="admin-images/search.png">
        <input type="text" id="search-input" placeholder="Search by item name or location...">
    </div>

    <div class="filter-bar">
        <div class="filter-student">
            <select class="categories-portal" id="category-select">
                <option value="">Categories</option>
                <option value="Bags">Bags</option>
                <option value="Electronics">Electronics</option>
                <option value="School Supplies">School Supplies</option>
                <option value="Personal Items">Personal Items</option>
                <option value="Clothing">Clothing</option>
                <option value="Books">Books</option>
            </select>

            <select class="filter-building" id="building-select"> 
                <option value="">Buildings</option>
                <option value="building 1">Building 1</option>
                <option value="building 2">Building 2</option>
            </select>

            <select class="filter-room" id="room-select"> 
                <option value="">Rooms</option>
                <option value="room 101">Room 101</option>
                <option value="room 102">Room 102</option>   
                <option value="room 103">Room 103</option>
                <option value="room 104">Room 104</option>
                <option value="room 105">Room 105</option>
                <option value="room 106">Room 106</option>
                <option value="lobby">Lobby</option>
                <option value="library">Library</option>
            </select>

            <button class="clear-filter">Clear Filters</button>
        </div>
    </div>
</div>

<div class="browse-wrapper">
    <?php if (count($items) > 0): ?>
        <?php foreach ($items as $item): ?>
            <div class="added_browse"
                data-name="<?php echo strtolower(htmlspecialchars($item['item_name'])); ?>"
                data-location="<?php echo strtolower(htmlspecialchars($item['location'])); ?>"
                data-category="<?php echo strtolower(htmlspecialchars($item['category'])); ?>"
                data-description="<?php echo strtolower(htmlspecialchars($item['description'])); ?>">
                <div class="browse_added">
                    <div class="browse-image">
                        <?php
                        $imageFile = !empty($item['image']) ? trim($item['image']) : '';
                        $userType  = strtolower(trim($item['user_type'] ?? ''));

                        if ($item['item_type'] === 'found') {
                            $serverImagePath = dirname(__DIR__) . "/staff-pages/uploads/" . $imageFile;
                            $browserImagePath = "staff-pages/uploads/" . $imageFile;
                        }
                        elseif ($userType === 'staff') {
                            $serverImagePath = dirname(__DIR__) . "/staff-pages/uploads/" . $imageFile;
                            $browserImagePath = "staff-pages/uploads/" . $imageFile;
                        }
                        else {
                            $serverImagePath = dirname(__DIR__) . "/student-pages/uploads/" . $imageFile;
                            $browserImagePath = "student-pages/uploads/" . $imageFile;
                        }
                        ?>
                        <?php if (!empty($imageFile) && file_exists($serverImagePath)): ?>
                            <img
                                src="<?= htmlspecialchars($browserImagePath) ?>"
                                alt="Item Image"
                                width="120"
                            >
                        <?php else: ?>
                            <img
                                src="staff-images/staff_item.png"
                                alt="No Image"
                                width="120"
                            >
                        <?php endif; ?>
                    </div>
                    <div class="browse-details">
                        <div class="upper-browse">
                            <div class="browse-header-container">
                                <span class="browse-name">
                                    <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                                </span>

                                <?php
                                $status = $item['status'] ?? 'Approved';

                                $statusClasses = [
                                    'Approved'        => 'browse-approved',
                                    'Rejected'        => 'browse-rejected',
                                    'Ready for Claim' => 'browse-ready-for-claim',
                                    'Claimed'         => 'browse-claimed'
                                ];

                                $statusClass = $statusClasses[$status] ?? 'browse-pending';
                                ?>

                                <span class="browse-badge <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($status); ?>
                                </span>
                            </div>
                            <span class="browse-description"><?php echo htmlspecialchars($item['description']); ?></span>
                        </div>
                        <span class="browse-location">
                            <img src="staff-images/location.png"><?php echo htmlspecialchars($item['location']); ?>
                        </span>
                        <span class="browse-date">
                            <img src="staff-images/calendar.png"> <?php echo htmlspecialchars($item['item_date']); ?>
                        </span>
                        <span class="browse-category"><?php echo htmlspecialchars($item['category']); ?></span>
                    </div>

                    <?php
                        $sessionStudentID = $_SESSION['student_id'] ?? '';
    
                        $isOwner = false;
                        if ($item['item_type'] === 'lost') {
                            $isOwner = ($item['user_id'] ?? '') === $sessionStudentID;
                        } elseif ($item['item_type'] === 'found') {
                            $isOwner = ($item['staff_employee_id'] ?? '') === $sessionStudentID;
                        }
                    ?>
 
                    <div class="browse-footer">
                        <button class="submit-browse-btn"
                            <?php echo $isOwner ? 'disabled title="You cannot claim your own item"' : ''; ?>
                            onclick="<?php echo $isOwner ? '' : "openSubmitClaim({$item['id']}, '" . addslashes($item['item_name']) . "', '" . addslashes($item['location']) . "', '{$item['item_type']}')"; ?>">
                            <?php echo $isOwner ? 'Your Item' : 'Submit Claim'; ?>
                        </button>
                    </div>

                    <div class="comments-section">
                        <?php $itemComments = $allComments[$item['item_type'] . '_' . $item['id']] ?? []; ?>
                        <div class="comments-list"> 
                            <?php if (empty($itemComments)): ?>
                                <div class="empty-state">
                                    <img src="student-images/chat-box.png" alt="comment">
                                    <p>No comments yet. Know something? Leave a tip!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($itemComments as $comment): ?>
                                   <?php $isOwn = ($comment['user_id'] == ($_SESSION['student_db_id'] ?? null)); ?>
                                    <div class="comment <?php echo $isOwn ? 'own' : ''; ?>">
                                        <div class="profile-avatar"><?php echo $isOwn ? htmlspecialchars($initials) : 'A'; ?></div>
                                        <div class="comment-bubble">
                                            <span class="comment-name"><?php echo $isOwn ? 'Anonymous.' : 'Anonymous.'; ?></span>
                                            <span class="comment-text"><?php echo htmlspecialchars($comment['comment']); ?></span>
                                            <span class="comment-time"><?php echo date('M d, g:i a', strtotime($comment['created_at'])); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div> 
                        <div class="comment-input-row">
                            <div class="profile-avatar"><?php echo htmlspecialchars($initials); ?></div>
                            <input type="hidden" class="comment-item-id" value="<?php echo $item['id']; ?>">
                            <input type="hidden" class="comment-item-type" value="<?php echo $item['item_type']; ?>">
                            <input type="text" class="comment-input" placeholder="Know something? Comment here...">
                            <button type="button" onclick="submitComment(this)">Send</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div id="claim-popup" class="claim-container">
    <div class="claim-modal">
    <span class="claim-close" onclick="closeClaim()">&times;</span>

        <div class="claim-title-mod">
            <span>Submit a Claim</span>
            <span class="sub-claim">Provide details to claim this item</span>
        </div>

        <div class="claim-body">
            <div class="column-claim">
            <label>Item Name</label>
            <span class="claim-item" id="popup-item-name"></span>
            </div>

            <div class="column-claim">
            <label>Location Found</label>
            <span class="claim-location" id="popup-item-location"></span>
            </div>

            <div class="column-claim">
            <label>Description</label>
            <textarea id="claimReport" rows="3" name="claimReport"
            placeholder="Please describe why this item belongs to you and provide any identifying features..." required></textarea>
            </div>
            <div class="claim-message">
                <img src="student-images/report-warning.png">
                <span>Please be honest and provide accurate information. False claims may result in penalties.</span>
            </div>
            <button class="claim-submit" id="claim-submit-btn" onclick="submitClaim(this)">
                Submit Claim
            </button>
        </div>
    </div>
</div>
