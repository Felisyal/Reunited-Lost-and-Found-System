<?php
session_start();
$conn = new mysqli("localhost", "root", "", "reunited_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['student_id'] ?? null;

if (!$user_id) {
    die("Not logged in");
}

$sql = "
    SELECT
        id,
        user_id AS reporter_id,
        user_type,
        item_name AS item_name,
        descriptionLostItem AS description,
        categoryLostItem AS category,
        locationLostItem AS location,
        last_seen_date AS item_date,
        lostImage AS image,
        status,
        created_at,
        expiration_date,
        'Lost' AS report_type
    FROM lost_items_reports
    WHERE user_id = ?

    UNION ALL

    SELECT
        id,
        staff_employee_id AS reporter_id,
        user_type,
        itemName_found AS item_name,
        descriptionFoundItem AS description,
        categoryFoundItem AS category,
        locationFoundItem AS location,
        dateFoundItem AS item_date,
        foundImage AS image,
        status,
        created_at,
        expiration_date,
        'Found' AS report_type
    FROM found_items_reports
    WHERE staff_employee_id = ?

    ORDER BY created_at DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("ss", $user_id, $user_id);
$stmt->execute();

$result = $stmt->get_result();
$items = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$conn->close();
?>

<div class="portal-report">
    <div class="column-report">
        <span class="claims-title">Report Lost & Found Items</span>
        <span class="claims-sub">Submit and track your lost and found item reports</span>
    </div>
    <button onclick="openReportLost()">+ Add Report</button>
</div>

<div class="report-bar">
    <div class="legend-item">
        <img src="student-images/report-warning.png" alt="warning">
        <span><strong>Pending:</strong> Awaiting admin review</span>
    </div>

    <div class="legend-item">
        <img src="student-images/checkmark.png" alt="check">
        <span><strong>Approved:</strong> Report verified and active</span>
    </div>

    <div class="legend-item">
        <img src="student-images/remove.png">
        <span><strong>Rejected:</strong> Report needs revision</span>
    </div>
</div>

<div id="report-popup" class="report-container">
  <div class="report-modal">
    <span class="report-close" onclick="closeReportLost()">&times;</span>

    <form id="reportForm" action="my_report.php" method="POST" enctype="multipart/form-data">
      <div class="report-title">
        <span>Report a Lost & Found Items</span>
        <span class="sub-report">
          Provide details about your lost and found item. Admin will review and approve your report.
        </span>
      </div>

      <div class="report-body">
        
        <div class="report-row-1">
          <div class="form-group">
            <label for="itemName">Item Name</label>
            <input type="text" id="itemName" name="itemName" placeholder="e.g., Black Laptop" required/>
          </div>

          <div class="form-group">
            <label for="categorySelect">Category</label>
            <select id="categorySelect" name="categoryName" required>
              <option value="" disabled selected>Select category</option>
              <option value="Electronics">Electronics</option>
              <option value="Bags">Bags</option>
              <option value="Books">Books</option>
              <option value="Clothing">Clothing</option>
              <option value="Personal Items">Personal Items</option>
              <option value="School Supplies">School Supplies</option>
              <option value="Others">Others</option>
            </select>

            <input type="text" id="others" name="others" placeholder="Enter other category." style="display: none;">
          </div>
        </div>

        <div class="report-row-2">
          <div class="form-group">
            <label for="itemLocation">Last Seen Location</label>
            <input type="text" id="itemLocation" name="itemLocation" list="locations" placeholder="Type a location..." required/>
          </div>

          <div class="form-group">
            <label for="lostDate">Last Seen Date</label>
            <div class="date-wrapper">
              <input type="date" id="lostDate" name="lostDate" required />
            </div>
          </div>
        </div>

        <div class="form-group">
          <label for="reportType">Type of Report</label>
          <select id="reportType" name="reportType" required>
            <option value="" disabled selected>Select report type</option>
            <option value="Lost">Lost</option>
            <option value="Found">Found</option>
          </select> 
        </div>

        <div class="form-group">
          <label for="descriptionReport">Description</label>
          <textarea id="descriptionReport" rows="3" name="descriptionReport"
            placeholder="Provide a detailed description." required></textarea>
        </div>

        <div class="form-group">
          <label>Upload Image</label>
            <span class="upload-student-descrp">
                <strong>Note: </strong>Please upload an <strong>actual photo</strong> of the item.
                The photo must clearly show the item being <strong>held by the person who found or lost</strong>,
                with their <strong>face visible</strong>, for verification purposes.
            </span>
          <label for="itemImage" class="upload-box">
            <div class="upload-content">
              <div class="upload-icon">⤴</div>
              <p class="upload-text">Click to upload image</p>
              <span class="upload-note">PNG, JPG up to 10MB</span>
            </div>
          </label>
          <input type="file" id="itemImage" name="itemImage" accept=".png,.jpg,.jpeg" style="display:none"/>
        </div>

        <div class="report-alert">
          <div class="report-item">
            <img src="student-images/report-warning.png" alt="info" />
            <span>
              Admin will review your report. Once approved, your lost item will
              be visible in the system for matching with found items.
            </span>
          </div>
        </div>
        <button type="submit" class="report-submit">Submit Report</button>
      </div>
    </form>
  </div>
</div>



<div class="my-reports">
  <div class="wrap-report"> 

     <?php if (!empty($items)): ?>
      <?php foreach ($items as $item): ?>

        <?php
          $status = strtolower($item['status'] ?? 'pending');

          if ($status === "pending") {
              $message = "Your report is under review by admin. You will be notified once it is approved.";
              $icon = "student-images/report-warning.png";
          } elseif ($status === "approved") {
              $message = "Your report has been approved and is now active.";
              $icon = "student-images/checkmark.png";
          } elseif ($status === "ready for claim") {
              $message = "A match was found for your item. Please wait for further instructions to claim it.";
              $icon = "student-images/download.png";
          } elseif ($status === "claimed") {
              $message = "This item has already been claimed.";
              $icon = "student-images/secured.png";
          } elseif ($status === "rejected") {
              $message = "Your report was rejected. Please revise and resubmit.";
              $icon = "student-images/remove.png";
          } elseif ($status === "expired") {
            $message = "This report has expired and is no longer active.";
            $icon = "student-images/warning.png";
          } else {
              $message = "Status unavailable.";
              $icon = "student-images/info.png";
          }
        ?>

        <div class="body-report">
          <div class="row-one">
              <div class="img-report">
                  <?php
                      $imageFile = !empty($item['image'])
                          ? trim($item['image'])
                          : '';

                      if ($item['report_type'] === 'Found') {
                          $serverImagePath = dirname(__DIR__) . "/staff-pages/uploads/" . $imageFile;
                          $browserImagePath = "staff-pages/uploads/" . $imageFile;
                      } else {
                          $serverImagePath = dirname(__DIR__) . "/student-pages/uploads/" . $imageFile;
                          $browserImagePath = "student-pages/uploads/" . $imageFile;
                      }
                  ?>

                  <?php if (!empty($imageFile) && file_exists($serverImagePath)): ?>

                      <img
                          src="<?= htmlspecialchars($browserImagePath) ?>"
                          width="120"
                          alt="<?= htmlspecialchars($item['report_type']) ?> Item"
                      >

                  <?php else: ?>

                      <img
                          src="staff-images/staff_item.png"
                          width="120"
                          alt="No Image"
                      >

                  <?php endif; ?>
              </div>
                <div class="column-one ">
                  <span class="rep-name">
                    <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                  </span>
                  <span class="created-name"> Reported On
                    <?php echo htmlspecialchars($item['created_at']); ?>
                  </span>
                </div>            
            <span class="rep-badge browse-<?php echo str_replace(' ', '-', strtolower($item['status'] ?? 'pending')); ?>">
              <?php echo htmlspecialchars(ucwords($item['status'] ?? 'pending')); ?>
            </span>
          </div>

          <div class="column-two">

            <div class="row-group">
              <div class="column-three">
                <label>Category</label><br>
                <span class="rep-category"><img src="staff-images/categories.png"><?php echo htmlspecialchars($item['category']); ?></span>
              </div>

              <div class="column-three">
                <label>Last Seen Date</label><br>
                <span class="rep-date"><img src="staff-images/calendar.png"><?php echo htmlspecialchars($item['item_date']); ?></span>
              </div>
            </div>

            <div class="row-report-group">
              <div class="column-three">
                <label>Last Seen Location</label>
                <span class="rep-location"><img src="staff-images/location.png"><?php echo htmlspecialchars($item['location']); ?></span>
              </div>

              <div class="column-three">
                <label>Expiration Date:</label>
                <span class="expire-date"><img src="staff-images/warning.png"><?php echo date("Y-m-d", strtotime($item['expiration_date'])); ?>
                </span>
              </div>
            </div>

              <div class="column-four">
                <label>Description:</label><br>
                <span class="rep-description"><?php echo htmlspecialchars($item['description']); ?></span>
              </div>
              <div class="status-message <?php echo str_replace(' ', '-', $status); ?>">
                <img src="<?php echo $icon; ?>" alt="status icon">
                <span><?php echo htmlspecialchars($message); ?></span>
              </div>
            </div>
        </div> 
      <?php endforeach; ?>
    <?php else: ?>
      <p class="no-report-wrapper">No Report Found.</p>
    <?php endif; ?>
  </div>
</div>