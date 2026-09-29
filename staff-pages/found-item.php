<?php 
session_start(); 
$conn = new mysqli("localhost", "root", "", "reunited_db"); 

if ($conn->connect_error) { 
    die("Connection failed: " . $conn->connect_error); 
} 

$staff_employee_id = $_SESSION['staff_employee_id'] ?? null; 
$user_type = $_SESSION['user_type'] ?? null; 

if (!$staff_employee_id || !$user_type) { 
    die("Not logged in properly"); 
} 

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $reportType  = $_POST['reportType'] ?? '';
    $itemName    = trim($_POST['itemName_found'] ?? '');
    $description = trim($_POST['descriptionFoundItem'] ?? '');
    $category    = $_POST['categoryFoundItem'] ?? '';
    $others      = trim($_POST['others'] ?? '');
    $location    = trim($_POST['locationFoundItem'] ?? '');
    $dateItem    = $_POST['dateFoundItem'] ?: null;

    if ($category === 'Others' && $others !== '') {
        $category = $others;
    }

    $imageFileName = null;

    if (!empty($_FILES['foundImage']['name'])) {

        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $allowedExt   = ['png', 'jpg', 'jpeg'];
        $originalName = $_FILES['foundImage']['name'];
        $ext          = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $fileSize     = $_FILES['foundImage']['size'];
        $tmpPath      = $_FILES['foundImage']['tmp_name'];
        $uploadError  = $_FILES['foundImage']['error'];

        if ($uploadError !== UPLOAD_ERR_OK) {
            $errorMessage = "Upload error (code $uploadError).";
        } elseif (!in_array($ext, $allowedExt)) {
            $errorMessage = "Only PNG and JPG images are allowed.";
        } elseif ($fileSize > 10 * 1024 * 1024) {
            $errorMessage = "Image must be 10MB or smaller.";
        } elseif (getimagesize($tmpPath) === false) {
            $errorMessage = "The uploaded file is not a valid image.";
        } else {
            $imageFileName = 'staff_' . $staff_employee_id . '_' . uniqid() . '.' . $ext;
            if (!move_uploaded_file($tmpPath, $uploadDir . $imageFileName)) {
                $errorMessage  = "Failed to save the uploaded image.";
                $imageFileName = null;
            }
        }
    }

    if ($errorMessage === '' && $itemName !== '' && $reportType !== '') {

        if ($reportType === 'Found') {
            $sql = "INSERT INTO found_items
                        (staff_employee_id, user_type, itemName_found, descriptionFoundItem,
                         categoryFoundItem, locationFoundItem, dateFoundItem, foundImage, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Approved')";
        } else {
            $sql = "INSERT INTO lost_items
                        (user_id, user_type, item_name, descriptionLostItem,
                         categoryLostItem, locationLostItem, last_seen_date, lostImage, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Approved')";
        }

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssssssss",
            $staff_employee_id,
            $user_type,
            $itemName,
            $description,
            $category,
            $location,
            $dateItem,
            $imageFileName
        );

        if ($stmt->execute()) {
            $successMessage = "Item added successfully.";
        } else {
            $errorMessage = "Database error: " . $stmt->error;
        }
        $stmt->close();

    } elseif ($errorMessage === '') {
        $errorMessage = "Please fill in all required fields.";
    }
}
?>

<div class="container-item">
    <div class="form-wrapper">
        <h1 class="found-title">Add Lost & Found Item</h1>
        <p class="found-sub">Enter details of the item you lost or found</p>

        <div class="found-modal">
            <div class="report-title">
                <span>Item Information</span>
                <span class="sub-report">Please fill in all the details about the lost or found  item.</span>
            </div>

            <form id="foundForm" method="POST" enctype="multipart/form-data">

                <div class="column-found">
                    <label>Item Name</label>
                    <input type="text" name="itemName_found" placeholder="e.g., Blue Backpack" required>
                </div>

                <div class="column-found">
                    <label for="reportType">Type of Report</label>
                    <select id="reportType" name="reportType" required>
                        <option value="" disabled selected>Select report type</option>
                        <option value="Lost">Lost</option>
                        <option value="Found">Found</option>
                    </select> 
                </div>

                <div class="column-found">
                    <label for="descriptionFoundItem">Description</label>
                    <textarea id="descriptionFoundItem" name="descriptionFoundItem" rows="3"
                        placeholder="Provide a detailed description of the item." required></textarea>
                </div>

                <div class="column-found">
                    <label for="categoryFoundItem">Category</label>
                    <select id="categoryFoundItem" name="categoryFoundItem" required>
                        <option value="" disabled selected>Select category</option>
                        <option value="Electronics">Electronics</option>
                        <option value="Bags">Bags</option>
                        <option value="Books">Books</option>
                        <option value="Clothing">Clothing</option>
                        <option value="Personal Items">Personal Items</option>
                        <option value="School Supplies">School Supplies</option>
                        <option value="Others">Other Category</option>
                    </select>
                </div>

                <div id="other-category" style="display:none;">
                    <label for="others">Other</label>
                    <input type="text" id="others" name="others" placeholder="Other Category">
                </div>

                <div class="column-found">
                    <label for="locationFoundItem">Location Found</label>
                    <input type="text" id="locationFoundItem" name="locationFoundItem"
                        list="locations" placeholder="Type a location..." required />
                </div>

                <div class="column-found">
                    <label for="dateFoundItem">Date Found</label>
                    <div class="date-found">
                        <input type="date" id="dateFoundItem" name="dateFoundItem" />
                    </div>
                </div>

                <div class="column-found">
                    <label>Upload Image</label>
                    <span class="upload-staff-descrp">
                        <strong>Note: </strong>Please upload an <strong>actual photo</strong> of the item.
                        The photo must clearly show the item being <strong>held by the person who found or lost</strong>,
                        with their <strong>face visible</strong>, for verification purposes.
                    </span>
                    <label class="upload-box" for="foundImage">
                        <div class="upload-content">
                            <div class="upload-icon">⤴</div>
                            <p class="upload-text" id="uploadText">Click to upload image</p>
                            <span class="upload-note">PNG, JPG up to 10MB</span>
                        </div>
                    </label>
                    <input type="file" id="foundImage" name="foundImage"
                        accept=".png,.jpg,.jpeg" style="display:none"
                        onchange="document.getElementById('uploadText').textContent = this.files[0]?.name || 'Click to upload image'"/>
                </div>

                <div class="button-found">
                    <button class="found-submit" type="submit">Add Item
                    </button>
                    <button class="clear-foundForm" type="button" onclick="clearformFound()">
                        Clear Form
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>