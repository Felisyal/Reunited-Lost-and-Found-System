<?php
session_start();
$conn = new mysqli("localhost", "root", "", "reunited_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id     = intval($_POST['id']);
    $name   = $_POST['name'];
    $email  = $_POST['email'];
    $role   = $_POST['role'];
    $status = $_POST['status'];

    switch ($role) {
        case 'admin':
            $table    = 'admin_register';
            $nameCol  = 'admin_name';
            $emailCol = 'admin_email';
            break;
        case 'student':
            $table    = 'student_register';
            $nameCol  = 'student_name';
            $emailCol = 'student_email';
            break;
        case 'staff':
            $table    = 'staff_register';
            $nameCol  = 'staff_name';
            $emailCol = 'staff_email';
            break;
        default:
            die("Invalid role.");
    }

    $stmt = $conn->prepare("UPDATE $table SET $nameCol = ?, $emailCol = ?, status = ? WHERE id = ?");
    $stmt->bind_param("sssi", $name, $email, $status, $id);
    $stmt->execute();
    $stmt->close();

    echo "ok";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['email'], $_POST['role'], $_POST['status'])) {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $role     = trim($_POST['role']);
    $status   = trim($_POST['status']);
    $user_id  = isset($_POST['user_id']) ? trim($_POST['user_id']) : null;

    switch ($role) {
        case 'admin':
            $table    = 'admin_register';
            $nameCol  = 'admin_name';
            $emailCol = 'admin_email';
            $idCol    = null;
            break;
        case 'student':
            $table    = 'student_register';
            $nameCol  = 'student_name';
            $emailCol = 'student_email';
            $idCol    = 'student_id';
            break;
        case 'staff':
            $table    = 'staff_register';
            $nameCol  = 'staff_name';
            $emailCol = 'staff_email';
            $idCol    = 'staff_employee_id'; 
            break;
        default:
            echo "error: Invalid role.";
            exit;
    }

    if ($role === 'staff' && $idCol && $user_id) {
        $department = trim($_POST['department'] ?? '');
        $stmt = $conn->prepare("INSERT INTO $table ($nameCol, $emailCol, $idCol, staff_department, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $email, $user_id, $department, $status);
    } elseif ($idCol && $user_id) {
        $stmt = $conn->prepare("INSERT INTO $table ($nameCol, $emailCol, $idCol, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $email, $user_id, $status);
    } else {
        $stmt = $conn->prepare("INSERT INTO $table ($nameCol, $emailCol, status) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $status);
    }

    if ($stmt->execute()) {
        echo "ok";
    } else {
        echo "error: " . $stmt->error;
    }

    $stmt->close();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id   = intval($_POST['id']);
    $role = $_POST['role'];

    switch ($role) {
        case 'admin':
            $table = 'admin_register';
            break;
        case 'student':
            $table = 'student_register';
            break;
        case 'staff':
            $table = 'staff_register';
            break;
        default:
            echo "Invalid role.";
            exit;
    }

    $stmt = $conn->prepare("DELETE FROM $table WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    echo "ok";
    exit;
}

$sql = "
SELECT id, admin_name AS name, admin_email AS email, 'Admin' AS role, status
FROM admin_register

UNION ALL

SELECT id, student_name AS name, student_email AS email, 'Student' AS role, status
FROM student_register

UNION ALL

SELECT id, staff_name AS name, staff_email AS email, 'Staff' AS role, status
FROM staff_register
";
$result = $conn->query($sql);
$conn->close();
?>

<div class="header-users">
    <div class="users-fixed">
        <span class="dashboard-users">User Management</span>
        <span class="sub-users-header">Manage system users and permission</span>
    </div>
    <button>+ Add Users</button>
</div>  

<div class="table-users">
        <div class="table_header_users">
            <div class="users_container">
                <div class="search-users">
                    <img src="admin-images/search.png">
                    <input type="text" id="user-search" placeholder="Search by item name or location...">
                </div>
                    <select id="user-role" class="select-role">
                        <option value="">All Roles</option>
                        <option value="admin">Admin</option>
                        <option value="student">Student</option>
                        <option value="staff">Staff</option>
                    </select>
                    <select id="user-status" class="select-status">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>   
            </div>
        </div>
    <div class="users_tb_scroll">
        <table id="users_tb">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td>
                            <span class="role-badge role-<?= strtolower($row['role']) ?>">
                                <?= htmlspecialchars($row['role']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge status-<?= strtolower($row['status']) ?>">
                                <?= htmlspecialchars($row['status']) ?>
                            </span>
                        </td>
                        <td class="users-action-btn">
                            <button
                                onclick="editUser(this)"
                                data-id="<?= $row['id'] ?>"
                                data-name="<?= htmlspecialchars($row['name']) ?>"
                                data-email="<?= htmlspecialchars($row['email']) ?>"
                                data-role="<?= strtolower($row['role']) ?>"
                                data-status="<?= htmlspecialchars($row['status']) ?>"
                            >
                                <img src="admin-images/pencil.png">
                            </button>

                            <button
                                onclick="deleteUser(
                                    <?= $row['id'] ?>,
                                    '<?= strtolower($row['role']) ?>'
                                )"
                            >
                                <img src="admin-images/delete.png">
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align:center; padding:20px;">
                            No Users Found
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="addUserModal" class="add-user">
    <div class="add-user-content">
        <div class="add-user-header">
            <div class="add-user-title">
                <h2>Add New User</h2>
                <span class="add-user-sub">Create an account for admin, staff, or student</span>
            </div>

            <span class="close-add-user">&times;</span>
        </div>

        <form id="addUserForm">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" placeholder="Enter Username" required>
            
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" placeholder="Enter Email Address" required>
            
            <label for="role">Role:</label>
            <select id="role" name="role" required>
                <option value="" disabled selected>Select Role</option>
                <option value="admin">Admin</option>
                <option value="student">Student</option>
                <option value="staff">Staff</option>
            </select>

            <div id="add-user-id" style="display:none;">
                <label for="user_id" id="user-id-field">ID:</label>
                <input type="text" id="user_id" name="user_id" placeholder="Enter ID">
            </div>

            <div id="add-staff-department" style="display:none;">
                <label for="department">Department:</label>
                <input type="text" id="department" name="department" placeholder="Enter Department">
            </div>
            
            <label for="status">Status:</label>
            <select id="userStatus" name="status" required>
                <option value="" disabled selected>Select Status</option>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
            </select>
            
            <div class="add-user-actions">
                <button type="submit">Add User</button>
            </div>
        </form>
    </div>
</div>

<div id="editUserModal" class="user-edit-modal">
    <div class="user-edit-content">
        <div class="user-edit-header">
            <div class="edit-user-title">
                <h2>Edit User</h2>
                <span class="edit-user-sub">Update user information and account status</span>
            </div>

            <span class="close-user-edit" onclick="closeEditUser()">&times;</span>
        </div>

        <form id="editUserForm">
            <input type="hidden" id="edit_id">
            <input type="hidden" id="edit_role_hidden">

            <label for="edit_name">Username</label>
            <input type="text" id="edit_name">

            <label for="edit_email">Email</label>
            <input type="email" id="edit_email">

            <label for="edit_status">Status</label>
            <select id="edit_status">
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
            </select>

            <div class="user-edit-actions">
                <button type="button" onclick="submitUserEdit()">
                    Update User
                </button>
            </div>
        </form>

    </div>
</div>