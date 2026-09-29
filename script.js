
    const menuLinks = document.querySelectorAll('.menu-link li a');
    const toggleBtn = document.getElementById('toggleBtn');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content');
    const pageContent = document.getElementById('page-content');
    const navItems    = document.querySelectorAll('.logo-link[data-page]');
    const navStudent    = document.querySelectorAll('.student_nf[data-page]');
    const studentLinks = document.querySelectorAll('.student-link li a');
    const navStaff    = document.querySelectorAll('.staff_nf[data-page]');
    const staffLinks = document.querySelectorAll('.staff-link li a');
    const matchesBorder = document.querySelectorAll('.queue-modal');

const THEMED_ICONS = {
    success: '<path d="M20 6L9 17l-5-5"/>',
    error:   '<path d="M18 6L6 18M6 6l12 12"/>',
    warning: '<path d="M12 3.5L21 20.5H3L12 3.5Z"/><path d="M12 9v5"/><path d="M12 17h.01"/>',
    confirm: '<path d="M12 3.5L21 20.5H3L12 3.5Z"/><path d="M12 9v5"/><path d="M12 17h.01"/>'
};

let _themedModalResolve = null;

function ensureThemedModal() {
    if (document.getElementById('themedModalOverlay')) return;

    const overlay = document.createElement('div');
    overlay.id = 'themedModalOverlay';
    overlay.className = 'themed-modal-overlay';
    overlay.innerHTML = `
        <div class="themed-modal-box" id="themedModalBox">
            <span class="modal-close" id="themedModalClose">&times;</span>
            <div class="alert-icon-big">
                <svg id="themedModalIconSvg" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></svg>
            </div>
            <h3 id="themedModalTitle">Title</h3>
            <p id="themedModalMessage">Message</p>
            <div class="themed-modal-actions" id="themedModalActions">
                <button class="alert-cancel-btn" id="themedModalCancelBtn" style="display:none;">Cancel</button>
                <button class="alert-ok-btn" id="themedModalOkBtn">OK</button>
            </div>
        </div>
    `;
    document.body.appendChild(overlay);

    document.getElementById('themedModalClose').addEventListener('click', () => closeThemedModal(false));
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) closeThemedModal(false);
    });
}

function closeThemedModal(result = false) {
    const overlay = document.getElementById('themedModalOverlay');
    if (overlay) overlay.classList.remove('show');
    if (_themedModalResolve) {
        const resolve = _themedModalResolve;
        _themedModalResolve = null;
        resolve(result);
    }
}

function themedAlert(message, { title = null, type = 'success' } = {}) {
    ensureThemedModal();

    const overlay   = document.getElementById('themedModalOverlay');
    const box       = document.getElementById('themedModalBox');
    const iconSvg   = document.getElementById('themedModalIconSvg');
    const titleEl   = document.getElementById('themedModalTitle');
    const msgEl     = document.getElementById('themedModalMessage');
    const cancelBtn = document.getElementById('themedModalCancelBtn');
    const okBtn     = document.getElementById('themedModalOkBtn');

    box.className = 'themed-modal-box ' + type;
    iconSvg.innerHTML = THEMED_ICONS[type] || THEMED_ICONS.success;
    titleEl.textContent = title || (type === 'error' ? 'Error' : type === 'warning' ? 'Notice' : 'Success');
    msgEl.textContent = message;

    cancelBtn.style.display = 'none';
    okBtn.textContent = 'OK';

    return new Promise((resolve) => {
        _themedModalResolve = resolve;
        okBtn.onclick = () => closeThemedModal(true);
        overlay.classList.add('show');
    });
}

function themedConfirm(message, { title = 'Please Confirm', confirmText = 'Confirm', cancelText = 'Cancel' } = {}) {
    ensureThemedModal();

    const overlay   = document.getElementById('themedModalOverlay');
    const box       = document.getElementById('themedModalBox');
    const iconSvg   = document.getElementById('themedModalIconSvg');
    const titleEl   = document.getElementById('themedModalTitle');
    const msgEl     = document.getElementById('themedModalMessage');
    const cancelBtn = document.getElementById('themedModalCancelBtn');
    const okBtn     = document.getElementById('themedModalOkBtn');

    box.className = 'themed-modal-box confirm';
    iconSvg.innerHTML = THEMED_ICONS.confirm;
    titleEl.textContent = title;
    msgEl.textContent = message;

    cancelBtn.style.display = 'inline-block';
    cancelBtn.textContent = cancelText;
    okBtn.textContent = confirmText;

    return new Promise((resolve) => {
        _themedModalResolve = resolve;
        okBtn.onclick = () => closeThemedModal(true);
        cancelBtn.onclick = () => closeThemedModal(false);
        overlay.classList.add('show');
    });
}

function showAlert(message, type = 'success') {
    themedAlert(message, { type: type === 'error' ? 'error' : 'success' });
}

function showSuccessModal(message, title = 'Success!') {
    themedAlert(message, { title, type: title && title.toLowerCase().includes('error') ? 'error' : 'success' });
}

function closeSuccessModal() {
    closeThemedModal(false);
}

    function showAlert(message, type = 'success') {
        const alertBox = document.createElement('div');
        alertBox.textContent = message;
        alertBox.className = `alert ${type}`; 
        alertBox.style.position = 'fixed';
        alertBox.style.top = '20px';
        alertBox.style.right = '20px';
        alertBox.style.zIndex = '1000';
        alertBox.style.fontWeight = '100';
        alertBox.style.fontFamily = 'Arial, Helvetica, sans-serif';
        alertBox.style.minWidth = '250px';

        document.body.appendChild(alertBox);

        setTimeout(() => {
            alertBox.remove();
        }, 3000);
    }

    matchesBorder.forEach(card => {
        matchesBorder.addEventListener('click', () => {
            matchesBorder.forEach(c => c.classList.remove('active'));
            matchesBorder.classList.add('active');
        });
    });

    navItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const page = item.getAttribute('data-page');
            setActive(page);
            loadPage(page);
            history.pushState(null, '', `#${page}`);
        });
    });

    navStudent.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const page = item.getAttribute('data-page');
            seTActive(page);
            loadPage(page);
            history.pushState(null, '', `#${page}`);
        });
    });

    navStaff.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const page = item.getAttribute('data-page');
            setStaff(page);
            loadPage(page);
            history.pushState(null, '', `#${page}`);
        });
    });

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('hidden');      
            mainContent.classList.toggle('expanded'); 
        });
    }


    window.addEventListener('popstate', () => {
        const isStudent = document.body.id === 'student-portal';
        const isStaff   = document.body.id === 'staff-faculty'; 
        const hash = window.location.hash.replace('#', '') || 
                    (isStudent ? 'browse-items' : (isStaff ? 'found-item' : 'dashboard-content'));

        if (isStudent) {
            seTActive(hash);
        } else if (isStaff) {
            setStaff(hash);   
        } else {
            setActive(hash);  
        }

        loadPage(hash);
    });

    window.addEventListener('DOMContentLoaded', () => {
        const isStudent = document.body.id === 'student-portal';
        const isStaff   = document.body.id === 'staff-portal';
        const initialPage = window.location.hash.replace('#', '') || 
                            (isStudent ? 'browse-items' : (isStaff ? 'found-item' : 'dashboard-content'));

        if (isStudent) {
            seTActive(initialPage);
        } else if (isStaff) {
            setStaff(initialPage); 
        } else {
            setActive(initialPage); 
        }

        loadPage(initialPage);
    });

    function openViewModal(data) {
        document.getElementById("view-name").textContent = data.name;
        document.getElementById("view-location").textContent = data.location;
        document.getElementById("view-description").textContent = data.description;
        document.getElementById("view-image").src = data.image;

        document.getElementById("viewModal").style.display = "flex";
    }

    function closeViewModal() {
        document.getElementById("viewModal").style.display = "none";
    }

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.view-btn');
        if (!btn) return;

        openViewModal({
            name: btn.dataset.name,
            location: btn.dataset.location,
            description: btn.dataset.description,
            image: btn.dataset.image
        });
    });

    document.addEventListener('input', function (e) {
        if (e.target.id === 'search-filter') {
            filterItems();
        }
    });

    document.addEventListener('change', function (e) {
        if (e.target.id === 'status-filter') {
            filterItems();
        }
    });

function filterItems() {
    const searchValue = document.getElementById('search-filter')?.value
        .toLowerCase()
        .trim() || '';

    const statusValue = document.getElementById('status-filter')?.value
        .toLowerCase()
        .trim() || '';

    const rows = document.querySelectorAll('#items_tb tbody tr');

    rows.forEach(row => {

        const type = (row.children[0]?.textContent || '').toLowerCase();
        const name = (row.children[2]?.textContent || '').toLowerCase();
        const category = (row.children[3]?.textContent || '').toLowerCase();
        const location = (row.children[4]?.textContent || '').toLowerCase();
        const addedBy = (row.children[6]?.textContent || '').toLowerCase();

        // GET STATUS FROM COLUMN 7
        const status = (row.children[7]?.textContent || '')
            .toLowerCase()
            .trim();

        const matchSearch =
            !searchValue ||
            type.includes(searchValue) ||
            name.includes(searchValue) ||
            category.includes(searchValue) ||
            location.includes(searchValue) ||
            addedBy.includes(searchValue);

        const matchStatus =
            !statusValue ||
            status === statusValue;

        row.style.display = (matchSearch && matchStatus)
            ? ''
            : 'none';
    });
}

function openAddItem(){
    document.getElementById("addItemModal").style.display="flex";
}

function closeAddItem(){
    document.getElementById("addItemModal").style.display="none";
}

document.addEventListener("click",e=>{
    if(e.target.closest(".add-item-btn")) openAddItem();
});

document.addEventListener("submit",e=>{
    if(e.target.id!=="addItemForm") return;

    e.preventDefault();

    fetch("admin-pages/add-item.php",{ 
        method:"POST",
        body:new FormData(e.target)
    })
    .then(r=>r.json())
    .then(d=>{
        if(d.success){
            showAlert("Item added successfully");
            e.target.reset();
            closeAddItem();
            loadPage("items");
        }else{
            showAlert(d.error||"Failed","error");
        }
    })
    .catch(()=>showAlert("Server error","error"));
});


document.addEventListener("change",e=>{
    if(e.target.id==="imageInput" && e.target.files[0]){
        let reader=new FileReader();

        reader.onload=x=>{
            document.querySelector("#addItemModal .upload-box").innerHTML=
            `<img src="${x.target.result}" style="width:100%;height:150px;object-fit:cover;border-radius:8px">`;
        };

        reader.readAsDataURL(e.target.files[0]);
    }
});

function viewItem(btn) {
    const name = btn.getAttribute("data-name");
    const description = btn.getAttribute("data-description");
    const image = btn.getAttribute("data-image");
    
    document.getElementById("view_name").textContent = name;
    document.getElementById("view_description").textContent = description || "No description available";
    document.getElementById("view_image").src = image;

    document.getElementById("itemsDisplay").style.display = "flex";

}

    function closeDisplay(id) {
        document.getElementById(id).style.display = "none";
    }

    function editItem(btn) {
        const data = btn.dataset;
        console.log('id:', data.id);
        console.log('type:', data.type);
        console.log('status:', data.status);
        
        document.getElementById("edit_id").value     = data.id;  
        document.getElementById("edit_status").value = data.status; 
        document.getElementById("edit_type").value   = data.type;


        const uploadImage = document.getElementById("editUploadContainer");

        uploadImage.innerHTML = `
            <img src="${data.image}" 
                style="width:100%; height:260px; object-fit:cover; border-radius:8px;">
        `;

        document.getElementById("editItems").style.display = "flex";
    }

async function submitEditForm() {
    const confirmed = await themedConfirm("Are you sure you want to update this item?", {
        title: 'Update Item',
        confirmText: 'Update'
    });
    if (!confirmed) return;

    const formData = new FormData(document.getElementById('editItemForm'));

    fetch('admin-pages/items.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                closeEdit('editItems');
                if (data.success) {
                    themedAlert('Item updated successfully', { type: 'success', title: 'Item Updated' });
                    loadPage('items');
                } else {
                    themedAlert('Update failed: ' + (data.error || ''), { type: 'error', title: 'Update Failed' });
                }
            })
            .catch(() => {
                closeEdit('editItems');
                showAlert('Server error', 'error');
            });
        }


    function triggerEditImageUpload() {
        document.getElementById("editImageInput").click();
    }

    document.addEventListener("change", function (e) {
        if (e.target && e.target.id === "editImageInput") {
            const file = e.target.files[0];

            if (file) {
                const reader = new FileReader();

                reader.onload = function (event) {
                    document.getElementById("editUploadContainer").innerHTML = `
                        <img src="${event.target.result}" 
                            style="width:100%; height:130px; object-fit:cover; border-radius:8px;">
                    `;
                };

                reader.readAsDataURL(file);
            }
        }
    });

    function closeEdit(id) {
        document.getElementById(id).style.display = "none";
    }

    async function deleteItem(id, type) {
    const confirmed = await themedConfirm("Are you sure you want to delete this item?", {
        title: 'Delete Item',
        confirmText: 'Delete'
    });
    if (!confirmed) return;

        fetch("admin-pages/items.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                action: "delete_item",
                id: id,
                type: type
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                themedAlert("Item deleted successfully", { type: 'success', title: 'Item Deleted' });
                loadPage("items");
            } else {
                themedAlert(data.error || "Delete failed", { type: 'error', title: 'Delete Failed' });
            }
        })
        .catch(() => {
            themedAlert("Server error", { type: 'error', title: 'Server Error' });
        });
    }

    document.addEventListener('input', function (e) {
        if (e.target.id === 'search-claim') {
            filterClaims();
        }
    });

    document.addEventListener('change', function (e) {
        if (e.target.id === 'status-claim') {
            filterClaims();
        }
    });

function filterClaims() {
    const searchValue = document.getElementById('search-claim')?.value
        .toLowerCase()
        .trim() || '';

    const statusValue = document.getElementById('status-claim')?.value
        .toLowerCase()
        .trim() || '';

    const rows = document.querySelectorAll('#claims_tb tbody tr');

    rows.forEach(row => {
        if (!row.querySelector('.status-badge')) {
            return;
        }

        const studentName = (row.children[0]?.textContent || '')
            .toLowerCase()
            .trim();

        const studentId = (row.children[1]?.textContent || '')
            .toLowerCase()
            .trim();

        const itemName = (row.children[2]?.textContent || '')
            .toLowerCase()
            .trim();

        const status = (
            row.querySelector('.status-badge')?.dataset.status || ''
        ).toLowerCase().trim();

        const matchSearch =
            !searchValue ||
            studentName.includes(searchValue) ||
            studentId.includes(searchValue) ||
            itemName.includes(searchValue);

        const matchStatus =
            !statusValue ||
            status === statusValue;

        row.style.display =
            (matchSearch && matchStatus) ? '' : 'none';
    });
}

function viewClaim(btn) {
    const data = btn.dataset;

    document.getElementById("viewStudentName").textContent = data.student;
    document.getElementById("viewStudentID").textContent = data.studentid;
    document.getElementById("viewItemName").textContent = data.name;
    document.getElementById("viewDescription").textContent = data.cdescription || "No description provided";

    const img = document.getElementById("viewClaimImage");
    if (img) {
        img.src = data.image;
    }

    document.getElementById("viewClaimModal").style.display = "flex";

    const closeViewClaim = document.querySelector(".close-view-claim");

    closeViewClaim.addEventListener("click", function () {
    document.getElementById("viewClaimModal").style.display = "none";
});
}

function editClaimAction(btn) {
    const data = btn.dataset;

    document.getElementById("clm_id").value   = data.id || "";
    document.getElementById("clm_name").value = data.name || "";
    document.getElementById("clm_status").value = data.status || "pending";
    document.getElementById("clm_desc").value  = data.cdescription || "";

    const uploadBox = document.getElementById("clmUploadContainer");

    uploadBox.innerHTML = `
        <img src="${data.image}" 
            style="width:100%; height:200px; object-fit:cover; border-radius:8px;">
    `;

    document.getElementById("editClaim").style.display = "flex";
}

async function deleteClaim(id) {
    const confirmed = await themedConfirm("Are you sure you want to delete this claim?", {
        title: 'Delete Claim',
        confirmText: 'Delete'
    });
    if (!confirmed) return;

    fetch("admin-pages/claim.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            action: "delete_claim",
            id: id
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            themedAlert("Claim deleted successfully.", { type: 'success', title: 'Deleted' });
            loadPage("claim");
        } else {
            themedAlert("Failed to delete claim.", { type: 'error' });
        }
    })
    .catch(err => {
        console.error(err);
        themedAlert("An error occurred.", { type: 'error' });
    });
}

function submitClaimEdit() {

    fetch('admin-pages/claim.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'update_claim',
            id: document.getElementById('clm_id').value,
            status: document.getElementById('clm_status').value
        })
    })
    .then(res => res.json())
    .then(async data => {
        if (data.success) {
            await themedAlert("Claim updated successfully!", { type: 'success', title: 'Updated' });
            location.reload();
        } else {
            themedAlert(data.error || "Update failed.", { type: 'error' });
            console.log(data.error);
        }

    })
    .catch(err => console.error(err));
}

function setupAddUserModal() {
    const modal = document.getElementById("addUserModal");
    const openBtn = document.querySelector(".header-users button");
    const closeBtn = document.querySelector(".close-add-user");

    if (!modal) return;

    if (openBtn) {
        openBtn.onclick = () => {
            modal.style.display = "flex";
        };
    }

    if (closeBtn) {
        closeBtn.onclick = () => {
            modal.style.display = "none";
        };
    }

    modal.onclick = (e) => {
        if (e.target === modal) {
            modal.style.display = "none";
        }
    };
}

document.addEventListener('input', function (e) {
    if (e.target.id === 'user-search') {
        filterUsers();
    }
});


document.addEventListener('change', function (e) {
    if (e.target.id === 'user-role' || e.target.id === 'user-status') {
        filterUsers();
    }

    if (e.target.id === 'role') {
        const wrapper = document.getElementById('add-user-id');
        const label   = document.getElementById('user-id-field');
        const input   = document.getElementById('user_id');

        if (e.target.value === 'student') {
            label.textContent     = 'Student ID:';
            input.placeholder     = 'Enter Student ID';
            input.required        = true;
            wrapper.style.display = 'block';
        } else if (e.target.value === 'staff') {
            label.textContent     = 'Staff ID:';
            input.placeholder     = 'Enter Staff ID';
            input.required        = true;
            wrapper.style.display = 'block';
            document.getElementById('add-staff-department').style.display = 'block';
        } else {
            wrapper.style.display = 'none';
            input.required        = false;
            input.value           = '';
            document.getElementById('add-staff-department').style.display = 'block'; 
        }
    }
});

function filterUsers() {
    const searchValue = document.getElementById('user-search')?.value.toLowerCase().trim() || '';
    const roleValue = document.getElementById('user-role')?.value.toLowerCase().trim() || '';
    const statusValue = document.getElementById('user-status')?.value.toLowerCase().trim() || '';

    const rows = document.querySelectorAll('#users_tb tbody tr');

    rows.forEach(row => {

        const name = (row.children[0]?.textContent || '').toLowerCase();
        const email = (row.children[1]?.textContent || '').toLowerCase();
        const role = (row.children[2]?.textContent || '').toLowerCase();
        const status = (row.children[3]?.textContent || '').toLowerCase();

        const matchSearch =
            !searchValue ||
            name.includes(searchValue) ||
            email.includes(searchValue);

        const matchRole =
            !roleValue ||
            role.includes(roleValue);

        const matchStatus =
            !statusValue ||
            status.includes(statusValue);

        row.style.display = (matchSearch && matchRole && matchStatus)
            ? ''
            : 'none';
    });
}

async function submitUserEdit() {
    const id     = document.getElementById("edit_id").value;
    const name   = document.getElementById("edit_name").value;
    const email  = document.getElementById("edit_email").value;
    const role   = document.getElementById("edit_role_hidden").value;
    const status = document.getElementById("edit_status").value;

    if (!status) {
        themedAlert("Please select a status.", { type: 'warning' });
        return;
    }

    fetch("admin-pages/users.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `action=edit&id=${encodeURIComponent(id)}&name=${encodeURIComponent(name)}&email=${encodeURIComponent(email)}&role=${encodeURIComponent(role)}&status=${encodeURIComponent(status)}`
    })
    .then(res => res.text())
    .then(async res => {
        if (res.trim() === 'ok') {
            await themedAlert("User updated successfully!", { type: 'success', title: 'Updated' });
            location.reload();
        } else {
            themedAlert("Update failed: " + res, { type: 'error' });
        }
    });
}

function editUser(btn) {
    document.getElementById("edit_id").value          = btn.dataset.id;
    document.getElementById("edit_name").value        = btn.dataset.name;
    document.getElementById("edit_email").value       = btn.dataset.email;
    document.getElementById("edit_role_hidden").value = btn.dataset.role;
    document.getElementById("edit_status").value      = btn.dataset.status;

    document.getElementById("editUserModal").style.display = "flex";
}   
function closeEditUser() {
    document.getElementById("editUserModal").style.display = "none";
}

async function deleteUser(id, role) {
    const confirmed = await themedConfirm("Are you sure you want to delete this user?", {
        title: 'Delete User',
        confirmText: 'Delete'
    });
    if (!confirmed) return;

    fetch("admin-pages/users.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `action=delete&id=${encodeURIComponent(id)}&role=${encodeURIComponent(role)}`
    })
    .then(res => res.text())
    .then(async res => {
        if (res.trim() === 'ok') {
            await themedAlert("User deleted successfully!", { type: 'success', title: 'Deleted' });
            location.reload();
        } else {
            themedAlert("Delete failed: " + res, { type: 'error' });
        }
    });
}

    function openSubmitClaim(itemId, itemName, itemLocation, itemType) {
        document.getElementById('popup-item-name').textContent = itemName;
        document.getElementById('popup-item-location').textContent = itemLocation;
        document.getElementById('claim-submit-btn').setAttribute('data-item-id', itemId);
        document.getElementById('claim-submit-btn').setAttribute('data-item-type', itemType);
        document.getElementById('claim-popup').style.display = 'flex';
    }

    function closeClaim() {
    const popup = document.getElementById("claim-popup");
    if (popup) popup.style.display = "none";
    }

    function openReportLost() {
    const popup = document.getElementById("report-popup");
    if (popup) popup.style.display = "flex";
    setupLostImagePreview();
    }

    function closeReportLost() {
    const popup = document.getElementById("report-popup");
    if (popup) popup.style.display = "none";
    }

    function showLogin(role) {
        document.getElementById("login-section").style.display = "flex";
        document.getElementById("admin-login").style.display = (role === "admin") ? "block" : "none";
        document.getElementById("student-login").style.display = (role === "student") ? "block" : "none";
        document.getElementById("staff-login").style.display = (role === "staff") ? "block" : "none";
        document.getElementById("forgot-password-section").style.display = 'none';
        document.getElementById("register-section").style.display = 'none';
    }


    function showForgotPasswordForm(role) {
        currentRole = role; 
        document.getElementById('admin-login').style.display = 'none';
        document.getElementById('student-login').style.display = 'none';
        document.getElementById('staff-login').style.display = 'none';
        document.getElementById('forgot-password-section').style.display = 'block';
        document.getElementById("forgotRole").value = role;
    }


    function showRegisterForm(role) {
        currentRole = role;
        document.getElementById("login-section").style.display = "none";
        document.getElementById("register-section").style.display = "flex";

        document.getElementById("admin-register").style.display = (role === "admin") ? "block" : "none";
        document.getElementById("student-register").style.display = (role === "student") ? "block" : "none";
        document.getElementById("staff-register").style.display = (role === "staff") ? "block" : "none";
    }
    function goHome() {
        document.getElementById("login-section").style.display = "none";
        document.getElementById("register-section").style.display = "none"; 
        document.getElementById("index-section").style.display = "block";
    }

    function setActive(pageName) {
        navItems.forEach(i => i.classList.remove('active'));
        const target = document.querySelector(`.logo-link[data-page="${pageName}"]`);
        if (target) target.classList.add('active');
    }

    function seTActive(pageName) {
        navStudent.forEach(i => i.classList.remove('active'));
        const target = document.querySelector(`.student_nf[data-page="${pageName}"]`);
        if (target) target.classList.add('active');
    }

    function setStaff(pageName) {
        navStaff.forEach(i => i.classList.remove('active'));
        const target = document.querySelector(`.staff_nf[data-page="${pageName}"]`);
        if (target) target.classList.add('active');
    }


    function loadPage(pageName) {
        if (!pageContent) return;
        pageContent.innerHTML = '<div class="loading">Loading...</div>';

        let folder;
        if (document.body.id === 'student-portal') {
            folder = 'student-pages';
        } else if (document.body.id === 'admin-interface') {
            folder = 'admin-pages';
        } else if (document.body.id === 'staff-portal') {
            folder = 'staff-pages';
        }

        fetch(`${folder}/${pageName}.php`)
            .then(res => {
                if (!res.ok) throw new Error('Page not found');
                return res.text();
            })
            .then(html => {
                pageContent.innerHTML = html;
                setupFoundImagePreview();
                setupFoundForm();
                setupReportForm();

                setupAddUserModal();
                
                const form = document.getElementById('addUserForm');
                    if (form) {
                        form.addEventListener('submit', function (e) {
                            e.preventDefault();
                            const formData = new FormData(this);
                            fetch('admin-pages/users.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(res => res.text())
                            .then(async res => {
                                if (res.trim() === 'ok') {
                                    document.getElementById('addUserModal').style.display = 'none';
                                    await themedAlert('User added successfully!', { type: 'success', title: 'Added' });
                                    loadPage('users');
                                } else {
                                    themedAlert('Failed to add user: ' + res, { type: 'error' });
                                }
                            })
                            .catch(err => console.error(err));
                        });
                    }
            })
            .catch(() => {
                pageContent.innerHTML = `<div class="error">Failed to load page.</div>`;
            });
    }

    function setupFoundImagePreview() {
        const foundImageInput = document.getElementById('foundImage');
        const uploadBox = document.querySelector('.upload-box');

        if (!foundImageInput || !uploadBox) return;

        const newInput = foundImageInput.cloneNode(true);
        foundImageInput.parentNode.replaceChild(newInput, foundImageInput);

        const newUploadBox = uploadBox.cloneNode(true);
        uploadBox.parentNode.replaceChild(newUploadBox, uploadBox);

        newUploadBox.addEventListener('click', (e) => {
            e.preventDefault();
            newInput.click();
        });

        newInput.addEventListener('change', () => {
            if (newInput.files && newInput.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    newUploadBox.innerHTML = `<img src="${e.target.result}" style="max-width:100%; max-height:150px; border-radius:5px;">`;
                }
                reader.readAsDataURL(newInput.files[0]);
            }
        });
    }

    function setupLostImagePreview() {
        const foundImageInput = document.getElementById('itemImage');
        const uploadBox = document.querySelector('.upload-box');

        if (!foundImageInput || !uploadBox) return;

        const newInput = foundImageInput.cloneNode(true);
        foundImageInput.parentNode.replaceChild(newInput, foundImageInput);

        const newUploadBox = uploadBox.cloneNode(true);
        uploadBox.parentNode.replaceChild(newUploadBox, uploadBox);

        newUploadBox.addEventListener('click', (e) => {
            e.preventDefault();
            newInput.click();
        });

        newInput.addEventListener('change', () => {
            if (newInput.files && newInput.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    newUploadBox.innerHTML = `<img src="${e.target.result}" style="max-width:100%; max-height:150px; border-radius:5px;">`;
                }
                reader.readAsDataURL(newInput.files[0]);
            }
        });
    }

document.addEventListener('change', function (e) {
    if (e.target.id === 'categoryFoundItem') {
        const otherWrapper = document.getElementById('other-category');
        const otherInput    = document.getElementById('others');

        if (e.target.value === 'Others') {
            otherWrapper.style.display = 'block';
            otherInput.required        = true;
        } else {
            otherWrapper.style.display = 'none';
            otherInput.required        = false;
            otherInput.value           = '';
        }
    }
});

function setupFoundForm() {
    const foundForm = document.getElementById('foundForm');
    if (!foundForm) return;

    foundForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const fileInput = document.getElementById('foundImage');
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            showSuccessModal('Please attach a photo of the found item.', 'Missing photo');
            return;
        }

        const formData = new FormData(this);

        fetch('staff-pages/found_item_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                showSuccessModal(data.message, 'Item added');
                foundForm.reset();

                const uploadBox = document.querySelector('#foundForm .upload-box');
                if (uploadBox) {
                    uploadBox.innerHTML = `
                        <div class="upload-content">
                            <div class="upload-icon">⤴</div>
                            <p class="upload-text" id="uploadText">Click to upload image</p>
                            <span class="upload-note">PNG, JPG up to 10MB</span>
                        </div>`;
                }

                const uploadText = document.getElementById('uploadText');
                if (uploadText) uploadText.textContent = 'Click to upload image';

                setupFoundImagePreview();
            } else {
                showSuccessModal(data.message, 'Error');
            }
        })
        .catch(() => {
            showSuccessModal('Something went wrong. Please try again.', 'Error');
        });
    });
}

    function clearformFound() {
        const foundForm = document.getElementById('foundForm');
        if (foundForm) {
            foundForm.reset();
            const uploadBox = document.querySelector('.upload-box');
            if (uploadBox) {
                uploadBox.innerHTML = `
                    <div class="upload-content">
                        <div class="upload-icon">⤴</div>
                        <p class="upload-text">Click to upload image</p>
                        <span class="upload-note">PNG, JPG up to 10MB</span>
                    </div>`;
            }
            setupFoundImagePreview(); 
        }
    }


function setupReportForm() {

    const form = document.getElementById("reportForm");
    if (!form) return;

    setupCategoryInput();

    form.addEventListener("submit", function(e) {

        e.preventDefault();

        if (!this.checkValidity()) {
            this.reportValidity();
            return;
        }

        const fileInput = document.getElementById('itemImage');

        if (
            !fileInput ||
            !fileInput.files ||
            fileInput.files.length === 0
        ) {
            showSuccessModal(
                'Please attach a photo of the item.',
                'Missing photo'
            );
            return;
        }

        submitReport(this);
    });
}

function setupReportForm() {

    const form = document.getElementById("reportForm");
    if (!form) return;

    const categorySelect = document.getElementById("categorySelect");
    const othersInput = document.getElementById("others");

    if (categorySelect && othersInput) {

        categorySelect.addEventListener("change", function () {

            if (this.value === "Others") {

                categorySelect.style.display = "none";
                categorySelect.disabled = true;
                categorySelect.required = false;

                othersInput.style.display = "block";
                othersInput.disabled = false;
                othersInput.required = true;

                othersInput.focus();

            } else {

                categorySelect.style.display = "block";
                categorySelect.disabled = false;
                categorySelect.required = true;

                othersInput.style.display = "none";
                othersInput.disabled = true;
                othersInput.required = false;
                othersInput.value = "";
            }
        });
    }

    form.addEventListener("submit", function(e) {

        e.preventDefault();

        if (
            categorySelect &&
            categorySelect.disabled &&
            othersInput &&
            othersInput.value.trim() === ""
        ) {
            othersInput.focus();

            showSuccessModal(
                "Please enter your category.",
                "Missing category"
            );

            return;
        }

        if (!this.checkValidity()) {
            this.reportValidity();
            return;
        }

        const fileInput = document.getElementById("itemImage");

        if (
            !fileInput ||
            !fileInput.files ||
            fileInput.files.length === 0
        ) {
            showSuccessModal(
                "Please attach a photo of the item.",
                "Missing photo"
            );

            return;
        }

        submitReport(this);
    });
}

function submitReport(form) {

    const formData = new FormData(form);

    const categorySelect = document.getElementById("categorySelect");
    const othersInput = document.getElementById("others");

    if (
        categorySelect &&
        categorySelect.value === "Others" &&
        othersInput &&
        othersInput.value.trim() !== ""
    ) {
        formData.set(
            "categoryName",
            othersInput.value.trim()
        );
    }

    fetch("student-pages/my_report_handler.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {

        if (data.success) {

            showSuccessModal(
                "Your report is now waiting for admin review.",
                "Report submitted"
            );

            form.reset();

            if (categorySelect && othersInput) {

                categorySelect.style.display = "block";
                categorySelect.disabled = false;
                categorySelect.required = true;

                othersInput.style.display = "none";
                othersInput.disabled = true;
                othersInput.required = false;
                othersInput.value = "";
            }

            closeReportLost();
            loadPage("my_report");

        } else {

            showSuccessModal(
                data.message || "Something went wrong.",
                "Error"
            );
        }

    })
    .catch(error => {

        console.error("Submit error:", error);

        showSuccessModal(
            "Something went wrong while submitting the report.",
            "Error"
        );
    });
}


    function setupCategoryInput() {

    const categorySelect = document.getElementById('categoryName');
    const othersInput = document.getElementById('others');

    if (!categorySelect || !othersInput) return;

    categorySelect.addEventListener('change', function () {

        if (this.value === 'Others') {

            categorySelect.style.display = 'none';
            categorySelect.disabled = true;
            categorySelect.required = false;

            // Show input
            othersInput.style.display = 'block';
            othersInput.disabled = false;
            othersInput.required = true;

            othersInput.focus();

        }

    });

}



    function submitComment(btn) {
    const row = btn.closest('.comment-input-row');
    const item_id   = row.querySelector('.comment-item-id').value;
    const item_type = row.querySelector('.comment-item-type').value;
    const input = row.querySelector('.comment-input');
    const comment = input.value.trim();

    if (!comment) return;

    btn.disabled = true;

    fetch('student-pages/submit_comment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ item_id, item_type, comment })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            input.value = '';
            btn.disabled = false;
            loadPage('browse-items');
        } else {
            btn.disabled = false;
            console.log('Failed:', data);
        }
    })
    .catch(err => {
        console.log('Error:', err);
        btn.disabled = false;
    });
}


async function submitClaim(btn) {
        const item_id = btn.getAttribute('data-item-id');
        const item_type = btn.getAttribute('data-item-type')
        const description = document.getElementById('claimReport').value.trim();

        if (!description) {
            themedAlert("Please enter a description", { type: 'warning' });
            return;
        }

        const confirmed = await themedConfirm('Are you sure you want to claim this item?', { title: 'Confirm Claim', confirmText: 'Claim' });
        if (!confirmed) return;

        btn.disabled = true;

        fetch('student-pages/submit_claim.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                item_id: item_id,
                item_type: item_type,
                description: description
            })
        })
        .then(res => res.json())
        .then(data => {
            console.log(data);

             if (data.success) {
                themedAlert('Claim submitted successfully!', { type: 'success', title: 'Claim Submitted' });
                closeClaim();
                loadPage('browse-items');
            } else {
                themedAlert('Error: ' + data.error, { type: 'error' });
                btn.disabled = false;
            }
        })
        .catch(err => {
            console.log(err);
            themedAlert('Something went wrong', { type: 'error' });
            btn.disabled = false;
        });
    }

function setFilter(filter, event) {
    document.querySelectorAll('.filter-tabs .tab').forEach(btn => {
        btn.classList.remove(
            'active-all',
            'active-pending',
            'active-approved',
            'active-rejected'
        );
    });
    if (event) {
        event.currentTarget.classList.add('active-' + filter);
    }
    const reports = document.querySelectorAll('.added-review');

    reports.forEach(report => {

        const status = (
            report.dataset.status || ''
        ).toLowerCase().trim();

        let show = false;

        if (filter === 'all') {

            show = true;

        } else if (filter === 'pending') {

            show = status === 'pending';

        } else if (filter === 'approved') {

            show =
                status === 'approved' ||
                status === 'ready-for-claim' ||
                status === 'claimed';

        } else if (filter === 'rejected') {

            show = status === 'rejected';
        }

        report.style.display = show ? '' : 'none';
    });
}

document.addEventListener('change', function (e) {

    if (e.target.classList.contains('sort-select')) {
        sortReports(e.target.value);
    }

});

function sortReports(sortType) {

    const container = document.querySelector('.review-report-container');

    if (!container) return;

    const reports = Array.from(
        container.querySelectorAll('.added-review')
    );

    reports.sort((a, b) => {

        if (sortType === 'az') {

            const nameA = (
                a.querySelector('.upper-title strong')?.textContent || ''
            ).trim().toLowerCase();

            const nameB = (
                b.querySelector('.upper-title strong')?.textContent || ''
            ).trim().toLowerCase();

            return nameA.localeCompare(nameB);

        }

        const dateA = getReportDate(a);
        const dateB = getReportDate(b);

        if (sortType === 'oldest') {
            return dateA - dateB;
        }
        return dateB - dateA;
    });

    reports.forEach(report => {
        container.appendChild(report);
    });
}

function getReportDate(report) {

    const dateText = report.querySelector(
        '.upper-reviewer span:nth-of-type(3)'
    )?.textContent || '';

    const dateMatch = dateText.replace('Reported:', '').trim();

    const date = new Date(dateMatch);

    return isNaN(date.getTime()) ? 0 : date.getTime();
}


    function toggleChat() {
    const win = document.getElementById('chatWindow');
    win.style.display = win.style.display === 'none' ? 'block' : 'none';
    }



    function isLostAndFoundRelated(text) {
    const keywords = [
    'lost', 'found', 'missing', 'misplaced', 'forgot', 'left', 'dropped',
    'retrieve', 'recover', 'return', 'claim', 'report', 'submit', 'assisted matching',
    'reunited', 'status', 'ai matching', 'use', 'rejected', 'expiration','declined',
    'rejection','ejected','reason', 'about', 'system','matched','match reason',
    'similarity','developer','owner','creator', 'created',

    'item', 'items', 'belonging', 'belongings', 'property', 'stuff', 'thing', 'things',

    'wallet', 'phone', 'cellphone', 'iphone', 'android', 'bag', 'backpack',
    'laptop', 'charger', 'earphones', 'headphones', 'keys', 'key',
    'id', 'school id', 'student id', 'card', 'badge', 'watch',
    'umbrella', 'book', 'notebook', 'tablet', 'glasses', 'bottle',

    'i lost',
    'i found',
    'have you seen',
    'did anyone find',
    'where is my',
    'where did i leave',
    'cant find',
    "can't find",
    'help me find',
    'picked up',
    'someone left',
    'left behind',
    'looking for',
    'looking for my',
    'anyone found',
    'someone found',
    'is there a',
    'did someone see',
    'not mine',

    'nawala',
    'napulot',
    'nakita',
    'naiwan',
    'hinahanap',
    'nawala ko',
    'may nakakita',
    'may nakapulot',
    'may nakita',
    'nawawala',
    'saan ko naiwan',
    'pakihanap',
    'hanapin',
    'napag-iwanan',
    'gamit',
    'bagay'
    ];

    return keywords.some(word =>
        text.toLowerCase().includes(word)
    );
    }

    function typeEffect(element, text, speed = 18) {
    let i = 0;
    element.textContent = '';
    const interval = setInterval(() => {
        element.textContent += text.charAt(i);
        i++;
        if (i >= text.length) clearInterval(interval);
        }, speed);
    }

    async function sendChat() {
        const input = document.getElementById('chatInput');
        const messages = document.getElementById('chatMessages');
        const text = input.value.trim();

        if (!text) return;

        if (!isLostAndFoundRelated(text)) {
        const userMsg = document.createElement('div');
        userMsg.className = 'msg user';
        userMsg.textContent = text;
        messages.appendChild(userMsg);

        const botMsg = document.createElement('div');
        botMsg.className = 'msg bot';
        botMsg.textContent = "I can only help with lost and found related questions. Please ask me about lost or found items!";
        messages.appendChild(botMsg);

        input.value = '';
        messages.scrollTop = messages.scrollHeight;
        return; 
        }

        const userMsg = document.createElement('div');
        userMsg.className = 'msg user';
        userMsg.textContent = text;
        messages.appendChild(userMsg);

        input.value = '';

        const typing = document.createElement('div');
        typing.className = 'msg bot typing';
        typing.textContent = 'Typing...';
        messages.appendChild(typing);

        messages.scrollTop = messages.scrollHeight;

        try {
            const res = await fetch('chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ message: text })
            });

            const data = await res.json();

            typing.remove();

            const botMsg = document.createElement('div');
            botMsg.className = 'msg bot';
            if (data.items && data.items.length > 0) {

                let replyText = data.reply;
                let html = "<br><br>";

                data.items.forEach(item => {
                    let imageUrl = item.image
                    ? '/' + item.image   
                    : '/Reunited/images/no-image.png';

                    html += `
                        <div style="
                            padding:10px;
                            border:1px solid #ddd;
                            margin-top:8px;
                            border-radius:8px;
                            background:#fff;
                        ">
                            <img
                                src="${imageUrl}"
                                style="
                                    width:100%;
                                    max-height:150px;
                                    object-fit:cover;
                                    border-radius:6px;
                                    margin-bottom:8px;
                                "

                            >
                            
                            <b>${item.name}</b><br>
                            Location: ${item.location}<br>
                            Status: ${item.status}
                        </div>
                    `;
                });

                botMsg.innerHTML = html; 
                const replyEl = document.createElement('span');
                botMsg.prepend(replyEl);
                typeEffect(replyEl, replyText);

            } else {
                typeEffect(botMsg, data.reply);
            }

            messages.appendChild(botMsg);
            messages.scrollTop = messages.scrollHeight;

        } catch (err) {
            typing.remove();

            const errorMsg = document.createElement('div');
            errorMsg.className = 'msg bot';
            errorMsg.textContent = 'Error connecting to server.';
            messages.appendChild(errorMsg);
        }
    }

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('clear-filter')) {

            document.getElementById('search-input').value = '';

            document.getElementById('category-select').selectedIndex = 0;
            document.getElementById('building-select').selectedIndex = 0;
            document.getElementById('room-select').selectedIndex = 0;

            filterBrowseItems();
        }
    });
    document.addEventListener('input', function (e) {
        if (e.target.id === 'search-input') {
            filterBrowseItems();
        }
    });

    document.addEventListener('change', function (e) {
        if (
            e.target.id === 'category-select' ||
            e.target.id === 'building-select' ||
            e.target.id === 'room-select'
        ) {
            filterBrowseItems();
        }
    });
    function filterBrowseItems() {

        const searchValue = document
            .getElementById('search-input')
            ?.value.toLowerCase().trim() || '';

        const categoryValue = document
            .getElementById('category-select')
            ?.value.toLowerCase().trim() || '';

        const buildingValue = document
            .getElementById('building-select')
            ?.value.toLowerCase().trim() || '';

        const roomValue = document
            .getElementById('room-select')
            ?.value.toLowerCase().trim() || '';

        

        const items = document.querySelectorAll('.added_browse');

        items.forEach(item => {

            const name = (item.dataset.name || '')
                .toLowerCase()
                .trim();

            const location = (item.dataset.location || '')
                .toLowerCase()
                .trim();

            const category = (item.dataset.category || '')
                .toLowerCase()
                .trim();

            const description = (item.dataset.description || '')
                .toLowerCase()
                .trim();

            const matchesSearch =
                !searchValue ||
                name.includes(searchValue) ||
                location.includes(searchValue) ||
                category.includes(searchValue) ||
                description.includes(searchValue);

            const matchesCategory =
                !categoryValue ||
                category.includes(categoryValue);

            const matchesBuilding =
                !buildingValue ||
                location.includes(buildingValue);

            const matchesRoom =
                !roomValue ||
                location.includes(roomValue);

            item.style.display =
                matchesSearch &&
                matchesCategory &&
                matchesBuilding &&
                matchesRoom
                    ? 'block'
                    : 'none';
        });
    }

function loadStaffAddItem() {
    setStaff('found-item');
    loadPage('found-item');
    history.pushState(null, '', '#found-item');
}


function updateReviewCounter(status, amount) {

const cards = document.querySelectorAll('.review-card');

cards.forEach(card => {

    const label = card.querySelector('.total-item-label');
    const number = card.querySelector('.total-item-review');

    if (!label || !number) return;

    const labelText = label.textContent.trim().toLowerCase();

    let targetStatus = '';

    if (labelText === 'pending review') {
        targetStatus = 'pending';
    } 
    else if (labelText === 'approved') {
        targetStatus = 'approved';
    } 
    else if (labelText === 'rejected') {
        targetStatus = 'rejected';
    }

    if (targetStatus === status) {

        const current = parseInt(number.textContent.trim()) || 0;

        number.textContent = Math.max(
            0,
            current + amount
        );
    }
});
}

document.addEventListener('click', async function(e) {

const approveBtn = e.target.closest('.approve');

if (approveBtn) {

    const id = approveBtn.dataset.id;
    const type = approveBtn.dataset.type;
    const card = approveBtn.closest('.added-review');

    if (!card) return;

    const confirmed = await themedConfirm(
        'Approve this report?',
        {
            title: 'Approve Report',
            confirmText: 'Approve'
        }
    );

    if (!confirmed) return;

    approveBtn.disabled = true;
    approveBtn.textContent = 'Approving...';

    try {

        const response = await fetch(
            'admin-pages/approval_report.php',
            {
                method: 'POST',
                headers: {
                    'Content-Type':
                        'application/x-www-form-urlencoded'
                },
                body:
                    `id=${encodeURIComponent(id)}` +
                    `&type=${encodeURIComponent(type)}`
            }
        );

        const data = await response.json();

        if (!data.success) {

            approveBtn.disabled = false;
            approveBtn.textContent = 'Approve';

            await themedAlert(
                'Error: ' +
                (data.error || 'Unknown error'),
                {
                    type: 'error',
                    title: 'Approval Failed'
                }
            );

            return;
        }

        card.dataset.status = 'approved';

        const badge = card.querySelector('.report-badge');

        if (badge) {
            badge.textContent = 'Approved';
            badge.className = 'report-badge approved';
        }

        const btnContainer =
            card.querySelector('.review-button');

        if (btnContainer) {

            btnContainer.innerHTML = `
                <div class="status-legend approved-legend">
                    <img
                        src="student-images/checkmark.png"
                        alt="approved"
                    >
                    <span>
                        This report has been approved and is active in the system.
                    </span>
                </div>
            `;
        }

        updateReviewCounter('pending', -1);
        updateReviewCounter('approved', +1);

        themedAlert(
            'Report approved successfully!',
            {
                type: 'success',
                title: 'Approved'
            }
        );

        if (data.item_id && data.type) {

            fetch(
                'admin-pages/generate-item-embedding.php',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type':
                            'application/x-www-form-urlencoded'
                    },
                    body:
                        `id=${encodeURIComponent(data.item_id)}` +
                        `&type=${encodeURIComponent(data.type)}`
                }
            )
            .then(res => res.json())
            .then(embeddingData => {

                if (!embeddingData.success) {

                    console.error(
                        'Embedding generation failed:',
                        embeddingData.error
                    );

                } else {

                    console.log(
                        'Embedding generated successfully.'
                    );
                }

            })
            .catch(error => {

                console.error(
                    'Embedding request failed:',
                    error
                );

            });
        }

    }
    catch (error) {

        console.error(error);

        approveBtn.disabled = false;
        approveBtn.textContent = 'Approve';

        themedAlert(
            'Request failed: ' + error.message,
            {
                type: 'error',
                title: 'Server Error'
            }
        );
    }

    return;
}

const rejectBtn = e.target.closest('.reject');

if (rejectBtn) {

    const id = rejectBtn.dataset.id;
    const type = rejectBtn.dataset.type;
    const card = rejectBtn.closest('.added-review');

    if (!card) return;

    const confirmed = await themedConfirm(
        'Reject this report?',
        {
            title: 'Reject Report',
            confirmText: 'Reject'
        }
    );

    if (!confirmed) return;

    rejectBtn.disabled = true;
    rejectBtn.textContent = 'Rejecting...';

    try {

        const response = await fetch(
            'admin-pages/reject_report.php',
            {
                method: 'POST',
                headers: {
                    'Content-Type':
                        'application/x-www-form-urlencoded'
                },
                body:
                    `id=${encodeURIComponent(id)}` +
                    `&type=${encodeURIComponent(type)}`
            }
        );

        const data = await response.json();

        if (!data.success) {

            rejectBtn.disabled = false;
            rejectBtn.textContent = 'Reject';

            themedAlert(
                'Error: ' +
                (data.error || 'Unknown error'),
                {
                    type: 'error',
                    title: 'Rejection Failed'
                }
            );

            return;
        }

        card.dataset.status = 'rejected';

        const badge = card.querySelector('.report-badge');

        if (badge) {
            badge.textContent = 'Rejected';
            badge.className = 'report-badge rejected';
        }

        const btnContainer =
            card.querySelector('.review-button');

        if (btnContainer) {

            btnContainer.innerHTML = `
                <div class="status-legend rejected-legend">
                    <img
                        src="student-images/remove.png"
                        alt="rejected"
                    >
                    <span>
                        This report was rejected and the student was notified.
                    </span>
                </div>
            `;
        }

        updateReviewCounter('pending', -1);
        updateReviewCounter('rejected', +1);


        themedAlert(
            'Report rejected.',
            {
                type: 'success',
                title: 'Rejected'
            }
        );

    }
    catch (error) {

        console.error(error);

        rejectBtn.disabled = false;
        rejectBtn.textContent = 'Reject';

        themedAlert(
            'Request failed: ' + error.message,
            {
                type: 'error',
                title: 'Server Error'
            }
        );
    }
    return;
}

});

document.addEventListener('click', function(e) {

    const btn = e.target.closest('#generateMatchesBtn');
    if (!btn) return;

    console.log('Generate AI Matches clicked!');

    const url = window.location.origin + '/Reunited/admin-pages/generate-matches.php';

    console.log('FINAL URL:', url);

    btn.disabled = true;
    btn.textContent = 'Generating...';

    fetch(url, {
        method: 'GET',
        cache: 'no-store'
    })
    .then(async response => {

        const text = await response.text();

        console.log('HTTP STATUS:', response.status);
        console.log('SERVER RESPONSE:', text);

        if (!response.ok) {
            throw new Error(
                `HTTP ${response.status}\n\n${text}`
            );
        }

        return text;
    })
    .then(data => {

        console.log('Generate matches success!');
        console.log(data);

        themedAlert('AI matching completed successfully!', { type: 'success', title: 'Matching Complete' });

        loadPage('assisted-matching');

    })
    .catch(error => {

        console.error('Generate matches error:', error);

        themedAlert(
            'Failed to generate AI matches.\n\n' + error.message,
            { type: 'error' }
        );

    })
    .finally(() => {

        btn.disabled = false;
        btn.textContent = 'Generate AI Matches';

    });

});

document.addEventListener('click', function (e) {
    const card = e.target.closest('.queue-modal');
    if (!card || !card.dataset.matchId) return;

    const matchId = card.getAttribute('data-match-id');

    document.querySelectorAll('.queue-modal').forEach(el => el.classList.remove('active'));
    card.classList.add('active');

    const url = window.location.origin + '/Reunited/admin-pages/assisted-matching.php?ajax=1&match_id=' + matchId;

    fetch(url, { cache: 'no-store' })
        .then(res => res.text())
        .then(html => {
            const panel = document.getElementById('matchStatusPanel');
            if (panel) {
                panel.outerHTML = html;
            } else {
                console.error('matchStatusPanel not found in DOM');
            }
        })
        .catch(err => console.error('Failed to load match details:', err));
});

document.addEventListener('click', async function (e) {

    const approveMatchBtn = e.target.closest('.approval-modal');
    if (approveMatchBtn) {
        const matchId = approveMatchBtn.dataset.id;

        const confirmed = await themedConfirm('Approve this match?', { title: 'Approve Match', confirmText: 'Approve' });
        if (!confirmed) return;

        fetch('admin-pages/approve_match.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `match_id=${matchId}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                themedAlert('Match approved successfully', { type: 'success', title: 'Match Approved' });
                loadPage('assisted-matching');
            } else {
                themedAlert(data.error || 'Failed to approve', { type: 'error', title: 'Approval Failed' });
            }
        })
        .catch(() => themedAlert('Server error', { type: 'error', title: 'Server Error' }));
    }

    const rejectMatchBtn = e.target.closest('.rejected-modal');
    if (rejectMatchBtn) {
        const matchId = rejectMatchBtn.dataset.id;

        const confirmed = await themedConfirm('Reject this match?', { title: 'Reject Match', confirmText: 'Reject' });
        if (!confirmed) return;

        fetch('admin-pages/reject_match.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `match_id=${matchId}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                themedAlert('Match rejected', { type: 'success', title: 'Match Rejected' });
                loadPage('assisted-matching');
            } else {
                themedAlert(data.error || 'Failed to reject', { type: 'error', title: 'Reject Failed' });
            }
        })
        .catch(() => themedAlert('Server error', { type: 'error', title: 'Server Error' }));
    }

});

function filterStaffItems() {
    const searchInput = document.getElementById('staff-item-search');

    if (!searchInput) return;

    const searchValue = searchInput.value.toLowerCase().trim();

    const cards = document.querySelectorAll(
        '.items-wrapper .added_container'
    );

    let visibleCount = 0;

    cards.forEach(card => {
        const itemName = card.dataset.itemName || '';
        const location = card.dataset.location || '';

        const matches =
            searchValue === '' ||
            itemName.includes(searchValue) ||
            location.includes(searchValue);

        card.style.display = matches ? '' : 'none';

        if (matches) {
            visibleCount++;
        }
    });

    updateStaffNoResults(visibleCount);
}


function updateStaffNoResults(visibleCount) {
    const wrapper = document.querySelector('.items-wrapper');

    if (!wrapper) return;

    let noResults = wrapper.querySelector('.staff-no-results');

    if (visibleCount === 0) {

        if (!noResults) {
            noResults = document.createElement('div');
            noResults.className = 'staff-no-results';

            noResults.innerHTML = `
                <img src="staff-images/staff_item.png" alt="No results">
                <div>
                    <strong>No matching items found</strong>
                    <p>Try searching another item or location.</p>
                </div>
            `;

            wrapper.appendChild(noResults);
        }

        noResults.style.display = 'flex';

    } else {

        if (noResults) {
            noResults.style.display = 'none';
        }
    }
}

document.addEventListener('input', function (e) {
    if (e.target.id === 'staff-item-search') {
        filterStaffItems();
    }
});


function filterStaffItems() {

    const searchInput = document.getElementById('staff-item-search');

    if (!searchInput) return;

    const searchValue = searchInput.value
        .toLowerCase()
        .trim();

    const items = document.querySelectorAll(
        '.items-wrapper .added_container'
    );

    items.forEach(item => {

        const itemName = (
            item.dataset.itemName || ''
        ).toLowerCase();

        const location = (
            item.dataset.location || ''
        ).toLowerCase();

        const matchesSearch =
            !searchValue ||
            itemName.includes(searchValue) ||
            location.includes(searchValue);

        item.style.display = matchesSearch ? '' : 'none';
    });
}

function showSuccessModal(message, title = 'Success!') {
    document.getElementById('successModalTitle').textContent = title;
    document.getElementById('successModalMessage').textContent = message;
    document.getElementById('successModalOverlay').style.display = 'flex';
}

function closeSuccessModal() {
    document.getElementById('successModalOverlay').style.display = 'none';
}

function showLogoutConfirm() {
    document.getElementById('logoutModalOverlay').style.display = 'flex';
}

function closeLogoutConfirm() {
    document.getElementById('logoutModalOverlay').style.display = 'none';
}


