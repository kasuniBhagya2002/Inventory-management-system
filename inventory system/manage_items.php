<?php
ini_set('session.cookie_httponly', 1);
session_start();
include('connection.php');

/* ================= SESSION & ROLE CHECK ================= */

$allowed_roles = ['Accountant', 'Developer'];
if (!isset($_SESSION['ID']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: login.php");
    exit();
}

/* ================= DSID (MANDATORY) ================= */
$dsid = $_SESSION['dsid'] ?? null;
if (!$dsid) {
    die("DS Division not assigned.");
}

/* ================= FETCH BASE DATA ================= */
$categories = $conn->query("SELECT Category_ID, Category_Name FROM inventory_categories ORDER BY Category_Name ASC");
$suppliers  = $conn->query("SELECT Supplier_ID, Supplier_Name FROM inventory_suppliers ORDER BY Supplier_Name ASC");

$msg = '';
$msg_type = 'success';

/* ================= ADD CATEGORY ================= */
if (isset($_POST['add_category'])) {
    $cat_name = trim($_POST['category_name']);
    if ($cat_name !== '') {
        $stmt = $conn->prepare("SELECT 1 FROM inventory_categories WHERE Category_Name = ?");
        $stmt->bind_param("s", $cat_name);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 0) {
            $ins = $conn->prepare("INSERT INTO inventory_categories (Category_Name) VALUES (?)");
            $ins->bind_param("s", $cat_name);
            $ins->execute();
            $msg = "New category added successfully!";
        } else {
            $msg = "Category already exists!";
            $msg_type = "error";
        }
        $stmt->close();
    }
}

/* ================= HELPER: SECURE FILE UPLOAD ================= */
function uploadItemImage($fileField) {
    if (empty($_FILES[$fileField]['name'])) return "";
    
    $file = $_FILES[$fileField];
    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (in_array($ext, $allowed_exts) && in_array($mime, $allowed_mimes) && $file['size'] <= 5 * 1024 * 1024) {
        $dir = "uploads/items/";
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $fname = time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
        if (move_uploaded_file($file['tmp_name'], $dir . $fname)) {
            return $dir . $fname;
        }
    }
    return false; 
}

/* ================= ADD ITEM ================= */
if (isset($_POST['add_item'])) {
    $name = trim($_POST['item_name']);
    $price = floatval($_POST['unit_price']);
    $qty = intval($_POST['quantity']);
    $min_qty = intval($_POST['min_quantity']);
    $supplier_id = intval($_POST['supplier_id']);
    $category_id = intval($_POST['category_id']);
    
    $image = uploadItemImage('image');
    if ($image === false) { $image = ""; $msg = "Invalid image file."; $msg_type = "error"; }

    if ($msg_type !== "error") {
        $stmt = $conn->prepare("INSERT INTO inventory_items (Item_Name, Image, Unit_Price, Quantity, Min_Quantity, Supplier_ID, Category_ID, dsid) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdiiiii", $name, $image, $price, $qty, $min_qty, $supplier_id, $category_id, $dsid);
        $stmt->execute();
        $stmt->close();
        $msg = "Item added successfully!";
    }
}

/* ================= UPDATE ITEM ================= */
if (isset($_POST['update_item'])) {
    $id = intval($_POST['edit_item_id']);
    $name = trim($_POST['edit_item_name']);
    $price = floatval($_POST['edit_unit_price']);
    $qty = intval($_POST['edit_quantity']);
    $min_qty = intval($_POST['edit_min_quantity']);
    $supplier = intval($_POST['edit_supplier_id']);
    $category = intval($_POST['edit_category_id']);


    $imgStmt = $conn->prepare("SELECT Image FROM inventory_items WHERE Item_ID = ? AND dsid = ?");
    $imgStmt->bind_param("ii", $id, $dsid);
    $imgStmt->execute();
    $imgRow = $imgStmt->get_result()->fetch_assoc();
    $oldImagePath = $imgRow['Image'] ?? '';

    $uploadedPath = uploadItemImage('edit_image');

    if ($uploadedPath !== "" && $uploadedPath !== false) {
        
        if ($oldImagePath && file_exists($oldImagePath)) @unlink($oldImagePath);
        $stmt = $conn->prepare("UPDATE inventory_items SET Item_Name=?, Image=?, Unit_Price=?, Quantity=?, Min_Quantity=?, Supplier_ID=?, Category_ID=? WHERE Item_ID=? AND dsid=?");
        $stmt->bind_param("ssdiiiii i", $name, $uploadedPath, $price, $qty, $min_qty, $supplier, $category, $id, $dsid);
    } else {
        $stmt = $conn->prepare("UPDATE inventory_items SET Item_Name=?, Unit_Price=?, Quantity=?, Min_Quantity=?, Supplier_ID=?, Category_ID=? WHERE Item_ID=? AND dsid=?");
        $stmt->bind_param("sdiiiii i", $name, $price, $qty, $min_qty, $supplier, $category, $id, $dsid);
    }
    $stmt->execute();
    $stmt->close();
    $msg = "Item updated successfully!";
}

/* ================= DELETE ITEM ================= */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("SELECT Image FROM inventory_items WHERE Item_ID = ? AND dsid = ?");
    $stmt->bind_param("ii", $id, $dsid);
    $stmt->execute();
    $res = $stmt->get_result();
    if($row = $res->fetch_assoc()){
        if ($row['Image'] && file_exists($row['Image'])) @unlink($row['Image']);
        $del = $conn->prepare("DELETE FROM inventory_items WHERE Item_ID = ? AND dsid = ?");
        $del->bind_param("ii", $id, $dsid);
        $del->execute();
        $msg = "Item deleted successfully!";
    }
}

/* ================= RESTOCK ================= */
if (isset($_POST['restock'])) {
    $itemId = intval($_POST['restock_item_id']);
    $qty = intval($_POST['restock_qty']);
    if ($qty > 0) {
        $stmt = $conn->prepare("UPDATE inventory_items SET Quantity = Quantity + ? WHERE Item_ID = ? AND dsid = ?");
        $stmt->bind_param("iii", $qty, $itemId, $dsid);
        $stmt->execute();
        $msg = "Item restocked successfully!";
    }
}

$stmt = $conn->prepare("SELECT i.*, s.Supplier_Name, c.Category_Name FROM inventory_items i LEFT JOIN inventory_suppliers s ON i.Supplier_ID = s.Supplier_ID LEFT JOIN inventory_categories c ON i.Category_ID = c.Category_ID WHERE i.dsid = ? ORDER BY i.Item_ID DESC");
$stmt->bind_param("i", $dsid);
$stmt->execute();
$items = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Items</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        
        :root { --primary-bg: linear-gradient(135deg, #f3e8ff 0%, #e0f2fe 100%); --accent: #7c3aed; --accent-light: #ede9fe; --text: #1e1b4b; --border: #e2e8f0; --shadow: 0 8px 32px rgba(124, 58, 237, 0.12); }
        body { font-family: 'Inter', sans-serif; background: var(--primary-bg); margin: 0; color: var(--text); min-height: 100vh; }
        .main-content { margin-left: 280px; padding: 30px 40px; transition: margin-left 0.4s; }
        .card { background: rgba(255,255,255,0.9); backdrop-filter: blur(12px); border-radius: 16px; box-shadow: var(--shadow); overflow: hidden; }
        .table-title { padding: 24px 30px 0; margin: 0 0 20px; color: var(--accent); font-size: 1.7rem; font-weight: 700; }
        .flex-wrapper { display: flex; gap: 30px; align-items: flex-start; }
        .left-col { width: 45%; } .right-col { width: 55%; }
        .table-container { overflow-x: auto; }
        .table-container table { width: 100%; min-width: 1200px; border-collapse: collapse; }
        .table-container thead { background: linear-gradient(90deg, #7c3aed, #a78bfa); color: white; }
        .table-container th, .table-container td { padding: 16px 14px; text-align: center; vertical-align:middle; white-space:nowrap;}
        .low-qty { background-color: #fee2e2 !important; color: #991b1b; font-weight: bold; }
        .btn { padding: 10px 16px; border: none; border-radius: 10px; cursor: pointer; font-size: 14px; font-weight: 600; transition: 0.3s; display: inline-flex;align-items:center;justify-content:center;line-height:1;}
        .btn-primary { background: var(--accent); color: white; }
        .btn-edit { background: #06b6d4; color: white; }
        .btn-restock { background: #f59e0b; color: white; }
        .btn-danger { background: #dc2626; color: white; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px,1fr)); gap: 20px; padding: 0 30px 30px; }
        .form-group { display: flex; flex-direction: column; }
        input, select { padding: 14px; border-radius: 12px; border: 1px solid #ddd; background: #f8f9ff; font-size: 15px; }
        .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); display: none; align-items: center; justify-content: center; z-index: 9999; }
        .modal-content { background: white; padding: 35px; border-radius: 20px; width: 90%; max-width: 650px; max-height: 90vh; overflow-y: auto; }
        .add-cat-btn { background: transparent; border: none; color: var(--accent); font-size: 18px; cursor: pointer; }
        
.table-container table {
    min-width: unset;        
    font-size: 13px;         
}

.table-container th,
.table-container td {
    padding: 8px 6px;        
    white-space: nowrap;     
}

.table-container th {
    font-size: 13px;
}

.table-container td img {
    width: 45px;
    height: 45px;
}


.btn {
    padding: 6px 10px;
    font-size: 12px;
    border-radius: 8px;
}

.table-container td:nth-child(2),  
.table-container td:nth-child(3),  
.table-container td:nth-child(4) { 
    text-align: left;
    padding-left: 12px;
}

.table-container td:last-child {
    display: flex;
    align-items: center;      
    justify-content: center;
    gap: 6px;
}

.btn {
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-primary,
.btn-edit,
.btn-restock,
.btn-danger {
    min-height: 30px;
}

.table-container td:last-child {
    vertical-align: middle;
    text-align: center;
}


.action-btns {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    flex-wrap: nowrap;
}


.action-btns .btn,
.table-container td .btn {
    height: 30px;
    min-height: 30px;
    padding: 0 12px;
    font-size: 12px;
    border-radius: 8px;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}


.table-container td {
    vertical-align: middle;
}

.table-container td {
    vertical-align: middle;
}

.table-container td:last-child {
    text-align: center;
}

.action-btns {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    flex-wrap: nowrap;
}

.action-btns .btn {
    height: 32px;
    min-height: 32px;
    padding: 0 14px;
    font-size: 12px;
    border-radius: 8px;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
}

.table-container td:last-child {
    display: table-cell !important;
}

    </style>
</head>
<body>

<?php include('sidebar.php'); ?>

<div class="main-content">
    <h2 style="color:var(--accent); margin-bottom:30px;"><i class="fa-solid fa-boxes-stacked"></i> Manage Items</h2>
    <div class="flex-wrapper">
        <div class="left-col">
            <div class="card">
                <h3 style="padding:30px 30px 0; color:var(--accent);">Add New Item</h3>
                <form method="POST" enctype="multipart/form-data" class="form-grid">
                    <div class="form-group"><label>Item Name</label><input type="text" name="item_name" required></div>
                    <div class="form-group"><label>Unit Price</label><input type="number" step="0.01" name="unit_price" required></div>
                    <div class="form-group"><label>Initial Quantity</label><input type="number" name="quantity" required></div>
                    <div class="form-group"><label>Min Qty</label><input type="number" name="min_quantity" value="5" required></div>
                    <div class="form-group">
                        <label>Supplier</label>
                        <select name="supplier_id" required>
                            <option value="">-- Select --</option>
                            <?php while($s = $suppliers->fetch_assoc()): ?>
                                <option value="<?= $s['Supplier_ID'] ?>"><?= htmlspecialchars($s['Supplier_Name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Category <button type="button" class="add-cat-btn" onclick="openAddCategory()"><i class="fa-solid fa-plus"></i></button></label>
                        <select name="category_id" required>
                            <option value="">-- Select --</option>
                            <?php $categories->data_seek(0); while($cat = $categories->fetch_assoc()): ?>
                                <option value="<?= $cat['Category_ID'] ?>"><?= htmlspecialchars($cat['Category_Name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Image</label><input type="file" name="image" id="imageInput" accept="image/*"><img id="previewImage" style="display:none;"></div>
                    <button type="submit" name="add_item" class="btn btn-primary" style="grid-column:1/-1;">Add Item</button>
                </form>
            </div>
        </div>

        <div class="right-col">
            <div class="card">
                <h3 class="table-title">Available Items</h3>
                <div class="table-container">
                    <table>
                       <thead>
    <tr>
        <th>Image</th>
        <th>Item Name</th>
        <th>Supplier</th>
        <th>Other</th>
        <th>Action</th>
    </tr>
</thead>
                        <tbody>
                            <?php while($row = $items->fetch_assoc()): 
                                $low = $row['Quantity'] < ($row['Min_Quantity'] ?? 5); ?>
                            <tr class="<?= $low ? 'low-qty' : '' ?>">
    <td>
        <?php if($row['Image'] && file_exists($row['Image'])): ?>
            <img src="<?= htmlspecialchars($row['Image']) ?>" width="70" height="70" style="border-radius:12px; object-fit:cover;">
        <?php else: ?>
            <div style="width:70px;height:70px;background:#eee;border-radius:12px;"></div>
        <?php endif; ?>
    </td>

    <td><?= htmlspecialchars($row['Item_Name']) ?></td>

    <td><?= htmlspecialchars($row['Supplier_Name'] ?? '-') ?></td>

    <td>
        <button class="btn btn-primary" onclick='openDetails(<?= json_encode($row, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
            Details
        </button>
    </td>

    <td>
        <button class="btn btn-edit" onclick='openEdit(<?= json_encode($row, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Edit</button>
        <button class="btn btn-restock" onclick="openRestock(<?= $row['Item_ID'] ?>)">Restock</button>
        <button class="btn btn-danger" onclick="confirmDelete(<?= $row['Item_ID'] ?>)">Delete</button>
    </td>
</tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="addCategoryModal" class="modal">
    <div class="modal-content">
        <h3>Add Category</h3>
        <form method="POST">
            <input type="text" name="category_name" required style="width:100%;">
            <button type="submit" name="add_category" class="btn btn-primary">Add</button>
            <button type="button" onclick="this.closest('.modal').style.display='none'" class="btn btn-danger">Cancel</button>
        </form>
    </div>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <h3>Edit Item</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="edit_item_id" id="edit_id">
            <input type="text" name="edit_item_name" id="edit_name" required>
            <input type="number" step="0.01" name="edit_unit_price" id="edit_price" required>
            <input type="number" name="edit_quantity" id="edit_qty" required>
            <input type="number" name="edit_min_quantity" id="edit_min_qty" required>
            <select name="edit_category_id" id="edit_category" required>
                <?php $categories->data_seek(0); while($cat = $categories->fetch_assoc()): ?>
                    <option value="<?= $cat['Category_ID'] ?>"><?= htmlspecialchars($cat['Category_Name']) ?></option>
                <?php endwhile; ?>
            </select>
            <select name="edit_supplier_id" id="edit_supplier" required>
                <?php $suppliers->data_seek(0); while($s = $suppliers->fetch_assoc()): ?>
                    <option value="<?= $s['Supplier_ID'] ?>"><?= htmlspecialchars($s['Supplier_Name']) ?></option>
                <?php endwhile; ?>
            </select>
            <div id="currentImageDiv"><img id="currentImgTag" src=""></div>
            <input type="file" name="edit_image" accept="image/*">
            <button type="submit" name="update_item" class="btn btn-edit">Save</button>
            <button type="button" onclick="this.closest('.modal').style.display='none'" class="btn btn-danger">Cancel</button>
        </form>
    </div>
</div>

<div id="restockModal" class="modal">
    <div class="modal-content">
        <h3>Restock</h3>
        <form method="POST">
            <input type="hidden" name="restock_item_id" id="restock_id">
            <input type="number" name="restock_qty" min="1" required>
            <button type="submit" name="restock" class="btn btn-restock">Add</button>
            <button type="button" onclick="this.closest('.modal').style.display='none'" class="btn btn-danger">Cancel</button>
        </form>
    </div>
</div>

<script>

// SweetAlert Success/Error Messages
<?php if($msg): ?>
Swal.fire({ icon: '<?= $msg_type ?>', title: '<?= $msg_type === "success" ? "Success" : "Error" ?>', text: '<?= addslashes($msg) ?>', timer: 3000, showConfirmButton: false });
<?php endif; ?>

// Image Preview for Add
document.getElementById("imageInput").addEventListener("change", function(e) {
    if(this.files[0]){
        let preview = document.getElementById("previewImage");
        preview.src = URL.createObjectURL(this.files[0]);
        preview.style.display = "block";
        preview.style.width = "100px";
    }
});

function openEdit(row) {
    document.getElementById("edit_id").value = row.Item_ID;
    document.getElementById("edit_name").value = row.Item_Name;
    document.getElementById("edit_price").value = row.Unit_Price;
    document.getElementById("edit_qty").value = row.Quantity;
    document.getElementById("edit_min_qty").value = row.Min_Quantity;
    document.getElementById("edit_supplier").value = row.Supplier_ID;
    document.getElementById("edit_category").value = row.Category_ID;

    let imgTag = document.getElementById("currentImgTag");
    if(row.Image){ imgTag.src = row.Image; imgTag.style.display="block"; imgTag.style.width="100px"; }
    else { imgTag.style.display="none"; }
    document.getElementById("editModal").style.display = "flex";
}

function openRestock(id) {
    document.getElementById("restock_id").value = id;
    document.getElementById("restockModal").style.display = "flex";
}

function confirmDelete(id) {
    Swal.fire({ title: 'Delete Item?', text: "You won't be able to revert this!", icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', confirmButtonText: 'Yes, delete it!' }).then((result) => {
        if (result.isConfirmed) { window.location.href = "?delete=" + id; }
    });
}

function openAddCategory() { document.getElementById('addCategoryModal').style.display = 'flex'; }

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) { event.target.style.display = "none"; }
}
</script>
<script>
function openDetails(row) {
    document.getElementById('d_item').innerText = row.Item_Name ?? '-';
    document.getElementById('d_category').innerText = row.Category_Name ?? '-';
    document.getElementById('d_price').innerText = parseFloat(row.Unit_Price).toFixed(2);
    document.getElementById('d_qty').innerText = row.Quantity;
    document.getElementById('d_min').innerText = row.Min_Quantity;
    document.getElementById('d_value').innerText = (row.Unit_Price * row.Quantity).toFixed(2);
    document.getElementById('d_date').innerText = row.Date_Added;

    document.getElementById('detailsModal').style.display = 'flex';
}

function closeDetails() {
    document.getElementById('detailsModal').style.display = 'none';
}
</script>
<div id="detailsModal" class="modal">
    <div class="modal-content">
        <h3>Item Details</h3>
        <p><b>Item:</b> <span id="d_item"></span></p>
        <p><b>Category:</b> <span id="d_category"></span></p>
        <p><b>Unit Price:</b> Rs. <span id="d_price"></span></p>
        <p><b>Quantity:</b> <span id="d_qty"></span></p>
        <p><b>Minimum Qty:</b> <span id="d_min"></span></p>
        <p><b>Total Value:</b> Rs. <span id="d_value"></span></p>
        <p><b>Date Added:</b> <span id="d_date"></span></p>

        <button class="btn btn-danger" onclick="closeDetails()">Close</button>
    </div>
</div>
</body>
</html>