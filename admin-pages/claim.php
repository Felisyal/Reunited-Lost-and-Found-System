
<?php
session_start();
$conn = new mysqli("localhost", "root", "", "reunited_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "
SELECT 
    c.id,
    c.status,
    c.claimed_at,
    c.claim_description,
    c.item_type,

    COALESCE(f.itemName_found, l.item_name) AS item_name,
    COALESCE(f.foundImage, l.lostImage) AS item_image,

    s.student_id,
    s.student_name

FROM claims_table c

LEFT JOIN found_items f 
    ON c.item_id = f.id
    AND c.item_type = 'found'

LEFT JOIN lost_items l 
    ON c.item_id = l.id
    AND c.item_type = 'lost'

INNER JOIN student_register s 
    ON c.student_db_id = s.id

ORDER BY c.claimed_at DESC
";

$result = $conn->query($sql);

if (!$result) {
    die("Query Error: " . $conn->error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['action'])) {

        if ($data['action'] === 'update_claim') {

            $id = (int)$data['id']; 
            $status = strtolower(trim($data['status']));

            $stmt = $conn->prepare(
                "UPDATE claims_table SET status=? WHERE id=?"
            );

            $stmt->bind_param("si", $status, $id);

            if ($stmt->execute()) {
                echo json_encode(["success" => true]);
            } else {
                echo json_encode([
                    "success" => false,
                    "error" => $stmt->error
                ]);
            }

            exit;
        }

        if ($data['action'] === 'delete_claim') {

            $id = (int)$data['id'];

            $stmt = $conn->prepare(
                "DELETE FROM claims_table WHERE id=?"
            );

            $stmt->bind_param("i", $id);

            echo json_encode([
                "success" => $stmt->execute()
            ]);

            exit;
        }
    }
}

$result = $conn->query($sql);

if (!$result) {
    die("Query Error: " . $conn->error); 
}


?>

<div class="header-claim">
    <span class="dashboard_claim">Claims Management</span>
    <span class="sub_claim">Review and manage item claims</span>
</div>

<div class="table-claim">
        <div class="table_header_claim">
            <div class="claim_container">
                <div class="search-claim">
                    <img src="admin-images/search.png">
                    <input type="text" id="search-claim" placeholder="Search by item name or location...">
                </div>
                <select id="status-claim" class="select-status"> 
                    <option value="">All Status</option> 
                    <option value="pending">Pending</option> 
                    <option value="ready-for-claim">Ready for Claim</option> 
                    <option value="claimed">Claimed</option> 
                </select>
            </div>
        </div>
    <div class="claims_tb_scroll">
        <table id="claims_tb">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Student ID</th>
                    <th>Item Name</th>
                    <th>Date Submitted</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="claim_users">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php $status = strtolower(trim($row['status']));?>
                    <tr>
                        <td><?= htmlspecialchars($row['student_name']) ?> </td>
                        <td><?= htmlspecialchars($row['student_id']) ?></td>
                        <td><?= htmlspecialchars($row['item_name']) ?></td>
                        <td><?= htmlspecialchars($row['claimed_at']) ?> </td>

                        <td>
                            <span 
                                class="status-badge status-<?= htmlspecialchars($status) ?>"
                                data-status="<?= htmlspecialchars($status) ?>"
                            >
                                <?php
                                switch ($status) {

                                    case 'pending':
                                        echo 'Pending';
                                        break;

                                    case 'ready-for-claim':
                                        echo 'Ready for Claim';
                                        break;

                                    case 'claimed':
                                        echo 'Claimed';
                                        break;

                                    default:
                                        echo 'Unknown';
                                        break;
                                }
                                ?>
                            </span>
                        </td>

                        <td class="claim-action-btn">

                            <!-- VIEW -->
                            <button 
                                onclick="viewClaim(this)"
                                data-name="<?= htmlspecialchars($row['item_name']) ?>"
                                data-student="<?= htmlspecialchars($row['student_name']) ?>"
                                data-studentid="<?= htmlspecialchars($row['student_id']) ?>"
                                data-cdescription="<?= htmlspecialchars($row['claim_description'] ?? '') ?>"
                                data-image="staff-pages/uploads/<?= htmlspecialchars($row['item_image'] ?? '') ?>"
                            >
                                <img src="admin-images/view.png">
                            </button>


                            <!-- EDIT -->
                            <button 
                                class="edit-claim-btn"
                                onclick="editClaimAction(this)"
                                data-id="<?= $row['id'] ?>"
                                data-name="<?= htmlspecialchars($row['item_name']) ?>"
                                data-cdescription="<?= htmlspecialchars($row['claim_description'] ?? '') ?>"
                                data-status="<?= htmlspecialchars($status) ?>"
                                data-image="staff-pages/uploads/<?= htmlspecialchars($row['item_image'] ?? '') ?>"
                            >
                                <img src="admin-images/pencil.png">
                            </button>

                            <button onclick="deleteClaim(<?= $row['id'] ?>)">
                                <img src="admin-images/delete.png">
                            </button>

                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="9" style="text-align:center; padding:20px;">
                        No Claims Found
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>
        </table>
    </div>
</div>



<div id="editClaim" class="edit-claim" style="display:none;">
    <div class="edit-claim-content">
        <div class="edit-claim-header">
            <div class="edit-claim-title">
                <h2>Edit Claim</h2>
                <span class="edit-claim-sub">Update claim status and review details</span>
            </div>

            <span class="close-edit" onclick="closeEdit('editClaim')">&times;</span>
        </div>

        <form id="editClaimForm" method="POST">
            <input type="hidden" id="clm_id" name="id">

            <div class="edit-claim-image">
                <div id="clmUploadContainer"></div>
            </div>

            <div class="edit-claim-field">
                <label>Item Name</label>
                <input type="text" id="clm_name" disabled>
            </div>

            <div class="edit-claim-field">
                <label>Claim Description</label>
                <textarea id="clm_desc" name="claim_description" rows="3" disabled></textarea>
            </div>

            <div class="edit-claim-field">
                <label>Status</label>
                <select id="clm_status" name="status"> 
                    <option value="pending">Pending</option> 
                    <option value="ready-for-claim">Ready for Claim</option> 
                    <option value="claimed">Claimed</option> 
                </select>
            </div>

            <div class="edit-claim-actions">
                <button type="button" onclick="submitClaimEdit()">Update</button>
                <button type="button" onclick="document.getElementById('editClaim').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="viewClaimModal" class="view-claim">
    <div class="view-claim-content">
        <div class="view-claim-header">
            <div class="view-claim-title">
                <h2>Claim Details</h2>
                <span class="view-claim-sub">View full information about this item</span>
            </div>

            <span class="close-view-claim">&times;</span>
        </div>

        <div class="claim-details">
            <div class="column-details-claim">
                <div class="claim-image-container">
                    <img id="viewClaimImage" src="" alt="Claim Item Image"
                        style="width:100%; max-height:150px; object-fit:cover;border-radius:8px; margin-bottom:10px;">
                 </div>

                <div class="sub-claim">
                    <label>Student Name:</label>
                    <span id="viewStudentName"></span>
                </div>

                <div class="sub-claim">
                    <label>Student ID:</label>
                    <span id="viewStudentID"></span>
                </div>

                <div class="sub-claim">
                    <label>Item Name:</label>
                    <span id="viewItemName"></span>
                </div>

                <div class="sub-claim">
                    <label>Description:</label>
                    <span class="description_view" id="viewDescription"></span>
                </div>
            </div>
        </div>
    </div>
</div>   

