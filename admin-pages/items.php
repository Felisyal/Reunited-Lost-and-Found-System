<?php
$host = "localhost";
$dbUser = "root";
$dbPass = "";
$dbName = "reunited_db";

$conn = new mysqli($host, $dbUser, $dbPass, $dbName);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

require_once 'sync_functions.php';
require_once 'mailer.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
}
function getAddedBy($conn, $id, $type) {
    $stmt = $conn->prepare("SELECT staff_name FROM staff_register WHERE staff_employee_id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) return $row['staff_name'];

    $stmt = $conn->prepare("SELECT student_name FROM student_register WHERE student_id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) return $row['student_name'];

    $stmt = $conn->prepare("SELECT admin_name FROM admin_register WHERE id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) return $row['admin_name'];

    return 'Unknown';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_item') {

    $id     = (int)$_POST['id'];
    $status = $_POST['status'];
    $type   = $_POST['type'];

    $oldStatus = null;
    $checkTable = ($type === 'Found') ? 'found_items' : 'lost_items';
    $checkStmt = $conn->prepare("SELECT status FROM $checkTable WHERE id = ?");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $checkRow = $checkStmt->get_result()->fetch_assoc();
    if ($checkRow) $oldStatus = $checkRow['status'];

    $imageName = null;

    if (!empty($_FILES['image']['name'])) {
        $imageName = time() . "_" . basename($_FILES['image']['name']);
        $uploadDir = ($type === 'Found') 
            ? "../staff-pages/uploads/" 
            : "../student-pages/uploads/";

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName)) {
            echo json_encode(["success" => false, "error" => "Image upload failed"]);
            exit;
        }
    }

    if ($type === 'Found') {
        $sql = "UPDATE found_items SET status=?"
             . ($imageName ? ", foundImage=?" : "")
             . " WHERE id=?";
    } else {
        $sql = "UPDATE lost_items SET status=?"
             . ($imageName ? ", lostImage=?" : "")
             . " WHERE id=?";
    }

    $stmt = $conn->prepare($sql);

    if ($imageName) {
        $stmt->bind_param("ssi", $status, $imageName, $id);
    } else {
        $stmt->bind_param("si", $status, $id);
    }

    if ($stmt->execute()) {
        $itemType = ($type === 'Found') ? 'found' : 'lost';
        syncReportStatus($conn, $id, $itemType, $status);

        $emailSent = null;

        if ($status === 'Ready for Claim' && $oldStatus !== 'Ready for Claim') {

            if ($type === 'Found') {
                $q = $conn->prepare("SELECT itemName_found AS name, staff_employee_id AS owner_id FROM found_items WHERE id = ?");
            } else {
                $q = $conn->prepare("SELECT item_name AS name, user_id AS owner_id FROM lost_items WHERE id = ?");
            }
            $q->bind_param("i", $id);
            $q->execute();
            $item = $q->get_result()->fetch_assoc();
            $q->close();

            if ($item) {
                $contact = getReporterContact($conn, $item['owner_id']);
                if ($contact) {
                    $emailSent = ($type === 'Found')
                        ? sendMatchedToFinderEmail($contact['email'], $contact['name'], $item['name'])
                        : sendReadyForClaimEmail($contact['email'], $contact['name'], $item['name']);
                } else {
                    error_log("Ready-for-claim email: no contact for owner " . $item['owner_id']);
                }
            }
        }

        echo json_encode(["success" => true, "email_sent" => $emailSent]);
    }
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' && isset($data['action']) && $data['action'] === 'delete_item'
) {

    $id = (int)$data['id'];
    $type = $data['type'];

    try {

        $conn->begin_transaction();

        if ($type === 'Found') {

            $stmt = $conn->prepare(
                "DELETE FROM ai_matches WHERE found_item_id = ?"
            );
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare(
                "DELETE FROM found_items WHERE id = ?"
            );
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

        } elseif ($type === 'Lost') {

            $stmt = $conn->prepare(
                "DELETE FROM ai_matches WHERE lost_item_id = ?"
            );
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare(
                "DELETE FROM lost_items WHERE id = ?"
            );
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

        } else {

            throw new Exception("Invalid item type.");

        }

        $conn->commit();

        echo json_encode([
            'success' => true
        ]);

    } catch (Exception $e) {

        $conn->rollback();

        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }

    exit;
}

$sql = "
SELECT 
    f.id, f.staff_employee_id, f.itemName_found AS item_name, f.categoryFoundItem AS category, f.descriptionFoundItem AS description,
    f.locationFoundItem AS location, f.dateFoundItem AS item_date,
    f.status, f.foundImage AS image, 'Found' AS type, f.user_type, f.staff_employee_id AS added_by,
    f.created_at AS created_at
FROM found_items f
UNION ALL
SELECT 
    l.id, l.user_id, l.item_name AS item_name, l.categoryLostItem AS category, l.descriptionLostItem AS description,
    l.locationLostItem AS location, l.last_seen_date AS item_date,
    l.status, l.lostImage AS image, 'Lost' AS type, l.user_type, l.user_id AS added_by,
    l.created_at AS created_at
FROM lost_items l
ORDER BY created_at DESC
";

$result = $conn->query($sql);   
if (!$result) die("SQL Error: " . $conn->error);
?>

<div class="header-items">
    <div class="item-header">
        <span class="dashboard-items">Items Management</span>
        <span class="sub_headerItems">Manage all lost and found items</span>
    </div>
    <button class="add-item-btn">+ Add Item</button>
</div>

<div class="table-items">
    <div class="table_header_items">
        <div class="item_container">
            <div class="search-bar">
                <img src="admin-images/search.png">
                <input type="text" id="search-filter" placeholder="Search by item name or location...">
            </div>

            <select class="select-status" id="status-filter" name="status">
                <option value="">All Status</option>
                <option value="Approved">Approved</option>
                <option value="Ready for Claim">Ready for Claim</option>
                <option value="Claimed">Claimed</option>
            </select>
        </div>
    </div>
    <div class="items_tb_scroll">
        <table id="items_tb">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Image</th>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th>Location Found</th>
                    <th>Date Found</th>
                    <th>Added By</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <span class="<?= strtolower($row['type']) ?>">
                                    <?= htmlspecialchars($row['type']) ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $imageFile = trim($row['image'] ?? '');
                                $userType = strtolower(trim($row['user_type'] ?? ''));

                                if ($row['type'] === 'Found') {
                                    $imagePath = 'staff-pages/uploads/' . $imageFile;
                                } elseif ($userType === 'staff') {
                                    $imagePath = 'staff-pages/uploads/' . $imageFile;
                                } else {
                                    $imagePath = 'student-pages/uploads/' . $imageFile;
                                }
                                ?>

                                <?php if (!empty($imageFile)): ?>
                                    <img 
                                        src="<?= htmlspecialchars($imagePath) ?>"
                                        width="60"
                                        height="60"
                                        style="object-fit:cover; border-radius:6px;"
                                        onerror="this.src='staff-images/staff_item.png';"
                                    >
                                <?php else: ?>
                                    <img 
                                        src="staff-images/staff_item.png"
                                        width="60"
                                        height="60"
                                        style="object-fit:cover; border-radius:6px;"
                                        alt="No Image"
                                    >
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['item_name']) ?></td>
                            <td><?= htmlspecialchars($row['category']) ?></td>
                            <td><?= htmlspecialchars($row['location']) ?></td>
                            <td><?= htmlspecialchars($row['item_date']) ?></td>

                            <td>
                                <?= htmlspecialchars(getAddedBy($conn, $row['added_by'], $row['user_type'])) ?>
                            </td>

                            <td>
                                <span
                                    class="admin-badge <?= str_replace(' ', '-', strtolower($row['status'])) ?>"
                                    data-status="<?= htmlspecialchars(strtolower(trim($row['status']))) ?>"
                                >
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <button
                                onclick="viewItem(this)"
                                data-name="<?= htmlspecialchars($row['item_name']) ?>"
                                data-description="<?= htmlspecialchars($row['description'] ?? '') ?>"
                                data-image="<?= ($row['type'] === 'Found')
                                    ? 'staff-pages/uploads/' . htmlspecialchars($row['image'])
                                    : 'student-pages/uploads/' . htmlspecialchars($row['image']) ?>"
                                >
                                    <img src="admin-images/view.png">
                                </button>

                                <button 
                                    onclick="editItem(this)" 
                                    data-id="<?= $row['id'] ?>" 
                                    data-status="<?= htmlspecialchars($row['status']) ?>" 
                                    data-type="<?= htmlspecialchars($row['type']) ?>" 
                                    data-image="<?=
                                        ($row['type'] === 'Found')
                                            ? 'staff-pages/uploads/' . htmlspecialchars($row['image'])
                                            : (
                                                strtolower(trim($row['user_type'] ?? '')) === 'staff'
                                                    ? 'staff-pages/uploads/' . htmlspecialchars($row['image'])
                                                    : 'student-pages/uploads/' . htmlspecialchars($row['image'])
                                            )
                                    ?>"
                                >
                                    <img src="admin-images/pencil.png">
                                </button>

                                <button onclick="deleteItem(<?= $row['id'] ?>, '<?= $row['type'] ?>')">
                                    <img src="admin-images/delete.png">
                                </button>
                            </td>

                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align:center; padding:20px;">
                            No Items Found
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="addItemModal" class="add-item-modal">
  <div class="add-item-content">

    <form id="addItemForm" enctype="multipart/form-data">
      <div class="add-item-title">
          <span>Add New Item</span>
          <span class="add-item-sub">
              Provide details about the item.
          </span>
          <span class="close-form" onclick="closeAddItem()">&times;</span>
      </div>
      <div class="add-item-body">

          <div class="add-row">
              <div class="form-field">
                  <label for="item_name">Item Name</label>
                  <input type="text" id="item_name" name="item_name"
                      placeholder="e.g., Black Laptop" required>
              </div>

              <div class="form-field">
                  <label for="category">Category</label>
                  <input type="text" id="category" name="category"
                      placeholder="Select category" required>
              </div>
          </div>

          <div class="add-row">
              <div class="form-field">
                  <label for="location">Location</label>
                  <input type="text" id="location" name="location"
                      placeholder="Type a location..." required>
              </div>

              <div class="form-field">
                  <label for="item_date">Date</label>
                  <input type="date" id="item_date" name="item_date" required>
              </div>
          </div>

          <div class="add-row">
              <div class="form-field">
                  <label for="type">Type</label>
                  <select id="type" name="type" required>
                      <option value="" disabled selected>Select type</option>
                      <option value="Found">Found</option>
                      <option value="Lost">Lost</option>
                  </select>
              </div>

              <div class="form-field">
                  <label for="status">Status</label>
                  <select id="status" name="status" required>
                      <option value="" disabled selected>Select status</option>
                      <option value="Approved">Approved</option>
                      <option value="Ready for Claim">Ready for Claim</option>
                      <option value="Claimed">Claimed</option>
                  </select>
              </div>
          </div>

          <div class="form-field">
              <label for="description">Description</label>
              <textarea id="description"
                  name="description"
                  placeholder="Provide a detailed description."
                  required></textarea>
          </div>

            <div class="form-field">
                <label>Upload Image</label>
                <span class="upload-admin-descrp">
                    <strong>Note: </strong>Please upload an <strong>actual photo</strong> of the item.
                    The photo must clearly show the item being <strong>held by the person who found or lost</strong>,
                    with their <strong>face visible</strong>, for verification purposes.
                </span>
                <label for="imageInput" class="upload-box">
                    <div class="upload-content">
                        <div class="upload-icon">⤴</div>
                        <p class="upload-text">Click to upload image</p>
                        <span class="upload-note">PNG, JPG up to 10MB</span>
                    </div>
                </label>

                <input type="file"
                    id="imageInput"
                    name="image"
                    accept=".png,.jpg,.jpeg"
                    required>
            </div>

          <button type="submit" class="submit-item">Add Item</button>

      </div>
    </form>
  </div>
</div>

<div id="itemsDisplay" class="items-display">
  <div class="display-content">
    <div class="display-header">
        <div class="display-title">
            <h2>Item Details</h2>
            <span class="display-sub">View full information about the selected item</span>
        </div>

        <span class="close-display" onclick="closeDisplay('itemsDisplay')">&times;</span>
    </div>

    <div class="item-details">
      
      <img id="view_image" src="" style="width:100%; max-height:250px; 
      object-fit:cover; border-radius:8px; margin-bottom:10px;">

      <div class="sub-items-dets">
        <label>Item Name:</label>
        <span id="view_name"></span>
      </div>

      <div class="sub-items-dets">
        <label>Description:</label>
        <span class="description-list" id="view_description"></span>
      </div>

    </div>
  </div>
</div>

<div id="editItems" class="edit-items">
  <div class="edit-content">
    <div class="edit-header">
        <div class="edit-title">
            <h2>Edit Item</h2>
            <span class="edit-sub">Update item status and image details</span>
        </div>

        <span class="close-edit" onclick="closeEdit('editItems')">&times;</span>
    </div>

    <form id="editItemForm" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="update_item">
        <input type="hidden" name="id"     id="edit_id">
        <input type="hidden" name="type"   id="edit_type">

        <div class="edit-field">
            <label>Image</label>
            <div class="upload-image" onclick="triggerEditImageUpload()">
                <input type="file" name="image" id="editImageInput" accept="image/*" hidden>
                <div class="edit-container" id="editUploadContainer">
                    <div class="icon-upload">⤴</div>
                    <p class="upload-title">Click to change image</p>
                    <span class="image-type">PNG, JPG up to 10MB</span>
                </div>
            </div>
        </div>

        <div class="edit-field">
            <label for="edit_status">Status</label>
            <select id="edit_status" name="status">
                <option value="Approved">Approved</option>
                <option value="Ready for Claim">Ready for Claim</option>
                <option value="Claimed">Claimed</option>
            </select>
        </div>

        <div class="edit-actions">
            <button type="button" class="submit-update" onclick="submitEditForm()">Update</button>
            <button type="button" class="cancel-update" onclick="closeEdit('editItems')">Cancel</button>
        </div>
    </form>
  </div>
</div>

<?php $conn->close(); ?>