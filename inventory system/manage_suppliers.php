<?php
// Session Hijacking ?????????? session cookie settings
ini_set('session.cookie_httponly', 1);
session_start();

// Session ID ?? ????? ????? (Security fix)
if (isset($_SESSION['ID'])) {
    session_regenerate_id(false);
}

include('connection.php');

/* ================= SESSION & ROLE CHECK ================= */
if (!isset($_SESSION['ID']) || $_SESSION['role'] !== 'Accountant') {
    header("Location: login.php");
    exit();
}

/* ================= DSID (MANDATORY) ================= */
$dsid = $_SESSION['dsid'] ?? null;
if (!$dsid) {
    die("DS Division not assigned.");
}

$msg = '';
$msg_type = 'success';

/* ================= UPDATE SUPPLIER ================= */
if (isset($_POST['edit_supplier'])) {

    $id      = intval($_POST['id']);
    $name    = trim($_POST['name']);
    $contact = trim($_POST['contact']);
    $phone   = trim($_POST['phone']);
    $email   = trim($_POST['email']);
    $address = trim($_POST['address']);

    if (!empty($name)) {
        $stmt = $conn->prepare("
            UPDATE inventory_suppliers
            SET Supplier_Name = ?, 
                Contact_Person = ?, 
                Contact_Number = ?, 
                Email = ?, 
                Address = ?
            WHERE Supplier_ID = ? AND dsid = ?
        ");

        $stmt->bind_param(
            "sssssii",
            $name,
            $contact,
            $phone,
            $email,
            $address,
            $id,
            $dsid
        );

        if ($stmt->execute()) {
            echo "success";
        } else {
            echo "error";
        }

        $stmt->close();
    }
    exit();
}

/* ================= ADD SUPPLIER ================= */
if (isset($_POST['add_supplier'])) {

    $name           = trim($_POST['supplier_name']);
    $contact        = trim($_POST['contact_person']);
    $contactNumber  = trim($_POST['contact_number']);
    $email          = trim($_POST['email']);
    $address        = trim($_POST['address']);

    if (!empty($name)) {
        $stmt = $conn->prepare(
            "INSERT INTO inventory_suppliers
            (Supplier_Name, Contact_Person, Contact_Number, Email, Address, dsid)
            VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "sssssi",
            $name,
            $contact,
            $contactNumber,
            $email,
            $address,
            $dsid
        );
        if($stmt->execute()){
            $msg = "Supplier added successfully!";
            $msg_type = "success";
        } else {
            $msg = "Error adding supplier.";
            $msg_type = "error";
        }
        $stmt->close();
    } else {
        $msg = "Supplier Name is required.";
        $msg_type = "error";
    }
}

/* ================= DELETE SUPPLIER ================= */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    $stmt = $conn->prepare(
        "DELETE FROM inventory_suppliers WHERE Supplier_ID = ? AND dsid = ?"
    );
    $stmt->bind_param("ii", $id, $dsid);
    $stmt->execute();
    $stmt->close();

    $msg = "Supplier deleted successfully!";
    $msg_type = "success";
}

/* ================= AJAX: FETCH ITEMS BY SUPPLIER ================= */
if (isset($_GET['fetch_items'])) {
    // Header ?? ??????? ????? ?????
    header('Content-Type: application/json');
    
    $supplier_id = intval($_GET['supplier_id']);

    $stmt = $conn->prepare(
        "SELECT Item_Name, Quantity, Unit_Price
         FROM inventory_items
         WHERE Supplier_ID = ?
           AND dsid = ?"
    );
    $stmt->bind_param("ii", $supplier_id, $dsid);
    $stmt->execute();
    $res = $stmt->get_result();

    $items = [];
    while ($row = $res->fetch_assoc()) {
        // XSS ??????? ???? JSON ??? ??? ???? ???????? ?????
        $items[] = [
            'Item_Name' => htmlspecialchars($row['Item_Name']),
            'Quantity' => intval($row['Quantity']),
            'Unit_Price' => floatval($row['Unit_Price'])
        ];
    }
    echo json_encode($items);
    exit();
}

/* ================= FETCH SUPPLIERS (DS WISE) ================= */
$stmt = $conn->prepare(
    "SELECT * FROM inventory_suppliers
     WHERE dsid = ?
     ORDER BY Supplier_ID DESC"
);
$stmt->bind_param("i", $dsid);
$stmt->execute();
$suppliers = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Suppliers | Accountant Dashboard</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
:root {
    --primary: #002f6c;
    --secondary: #004b9a;
    --accent: #ffc107;
    --bg: #e8ecf5;
    --text: #333;
    --card-padding: 30px;
}
body { font-family: 'Poppins', sans-serif; background-color: var(--bg); margin:0; }
.main-content { margin-left: 260px; padding: 30px 40px; }
.flex-wrapper { display:flex; gap:30px; margin-top:20px; align-items:stretch; }
.left-panel, .right-panel { flex:1; }
.white-card { background:#fff; border-radius:14px; padding:var(--card-padding); box-shadow:0 6px 20px rgba(0,0,0,0.09); display:flex; flex-direction:column; height:100%; overflow:hidden; }
@media(max-width:900px){ .flex-wrapper{flex-direction:column} .white-card{padding:25px} }

.page-header h2 { font-weight:600; margin:0; color:var(--primary); }
.form-title { color:var(--primary); font-weight:600; font-size:1.4rem; margin-bottom:25px; border-left:5px solid var(--secondary); padding-left:12px; }
.form-group { margin-bottom:20px; }
.form-label { font-weight:500; color:var(--primary); margin-bottom:8px; display:block; }
input,textarea { width:100%; padding:12px 14px; border:1px solid #cbd5e1; border-radius:10px; background:#f9fbff; font-size:15px; box-sizing:border-box; }
input:focus,textarea:focus { border-color:var(--secondary); outline:none; box-shadow:0 0 8px rgba(0,91,187,0.25); }

.btn { border:none; padding:11px 22px; border-radius:10px; cursor:pointer; font-weight:500; transition:all 0.3s ease; display:inline-flex; align-items:center; justify-content:center; font-size:15px; }
.btn i { margin-right:8px; }
.btn-primary { background: var(--secondary); color:white; }
.btn-primary:hover { background: var(--primary); }
.btn-danger { background:#dc3545; color:white; }
.btn-danger:hover { background:#c82333; }

.table-container { flex-grow:1; overflow:auto; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.06); }
table { width:100%; min-width:800px; border-collapse:collapse; background:white; }
thead { background: var(--primary); color:white; position:sticky; top:0; z-index:10; }
.table-container thead { background: linear-gradient(90deg, #9c49f0ff, #9b3fecff); color: white; }
th, td { padding:14px 12px; text-align:left; border-bottom:1px solid #eee; }

td { white-space:normal; word-wrap:break-word; max-width:0; }
.action-btns { display:flex; gap:8px; justify-content:center; flex-wrap:wrap; }
tbody tr:hover { background:#f0f5ff; }
.alert { background:#d4edda; color:#155724; padding:14px 18px; border-radius:10px; margin-bottom:25px; border-left:6px solid #28a745; font-size:15px; }
.alert-error { background:#f8d7da; color:#721c24; border-left-color:#dc3545; }
/* ================= COMPACT TABLE VIEW ================= */

/* Table font smaller */
table {
    font-size: 13px;
}

/* Header cells */
th {
    padding: 8px 8px;
    font-size: 12px;
    letter-spacing: .3px;
}

/* Body cells */
td {
    padding: 6px 8px;
    line-height: 1.3;
}

/* Reduce row height */
tbody tr {
    height: auto;
}

/* Action buttons compact */
.action-btns {
    gap: 4px;
}

.action-btns .btn {
    padding: 4px 8px;
    font-size: 12px;
    border-radius: 6px;
}

/* Inputs inside table (if any) */
table input,
table select {
    padding: 4px 6px;
    font-size: 12px;
}

/* Make table fit more columns */
.table-container {
    overflow-x: auto;
}

/* Optional: reduce card padding slightly on large screens */
@media (min-width: 992px) {
    .white-card {
        padding: 20px;
    }
}
/* ===== REDUCE COLUMN SPACING ONLY ===== */

/* Header columns */
th {
    padding-left: 6px;
    padding-right: 6px;
    font-size: 12px;
}

/* Body columns */
td {
    padding-left: 6px;
    padding-right: 6px;
    font-size: 13px;
}



/* Action buttons column compact */
.action-btns {
    gap: 4px;
}

.action-btns .btn {
    padding: 4px 6px;
    font-size: 12px;
}
/* ===== FINAL ALIGNMENT & PROFESSIONAL FIX ===== */

/* Vertically center ALL table cells */
.table-container td {
    vertical-align: middle !important;
}

/* LEFT align text columns (professional look) */
.table-container td:nth-child(1), /* Supplier Name */
.table-container td:nth-child(2) { /* Contact Person */
    text-align: left !important;
    padding-left: 12px;
}

/* Center OTHERS + ACTION columns */
.table-container td:nth-child(3),
.table-container td:nth-child(4) {
    text-align: center !important;
}

/* Fix Action buttons vertical misalignment */
.action-btns {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    height: 100%;
    gap: 6px;
}

/* Make ALL buttons same height */
.btn {
    height: 30px;
    min-height: 30px;
    padding: 0 10px;
    font-size: 12px;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

/* Details button same size */
.table-container .btn-primary {
    height: 30px;
}

/* Prevent buttons floating up/down */
.table-container td {
    display: table-cell;
}

/* Better row consistency */
tbody tr {
    height: 44px;
}
/* ===== MANAGE ITEMS STYLE TABLE (FOR SUPPLIERS) ===== */

.table-container table {
    width: 100%;
    min-width: unset;          /* ?? key */
    border-collapse: collapse;
    font-size: 13px;
}

/* Header */
.table-container th {
    padding: 8px 10px;
    font-size: 12px;
    text-align: left;
    white-space: normal;
}

/* Body cells */
.table-container td {
    padding: 7px 10px;
    font-size: 13px;
    text-align: left;
    white-space: normal;       /* ?? allows compact flow */
}

/* Center only action + details columns */
.table-container td:nth-child(3),
.table-container td:nth-child(4),
.table-container th:nth-child(3),
.table-container th:nth-child(4) {
    text-align: center;
}

/* Action buttons inline (same row) */
.action-btns {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 6px;
    flex-wrap: nowrap;
}

/* Buttons compact like manage items */
.action-btns .btn,
.table-container .btn {
    padding: 6px 10px;
    font-size: 12px;
    border-radius: 8px;
    height: 30px;
}

/* Remove unnecessary scroll */
.table-container {
    overflow-x: auto;
}
/* ===== FORCE COMPACT TABLE (ALL COLUMNS VISIBLE) ===== */

.table-container {
    overflow-x: hidden;   /* ? no horizontal scroll */
}

.table-container table {
    width: 100%;
    table-layout: fixed;  /* ?? MOST IMPORTANT */
    border-collapse: collapse;
    font-size: 12px;
}

/* ALL columns SAME spacing */
.table-container th,
.table-container td {
    padding: 5px 6px;     /* ?? ultra small & equal */
    vertical-align: middle;
    white-space: nowrap; /* ?? keep one line */
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Header */
.table-container th {
    font-size: 11.5px;
    font-weight: 600;
}

/* ---- COLUMN WIDTH CONTROL ---- */
/* Supplier Name */
.table-container th:nth-child(1),
.table-container td:nth-child(1) {
    width: 22%;
}

/* Contact Person */
.table-container th:nth-child(2),
.table-container td:nth-child(2) {
    width: 18%;
}

/* Others (Details button) */
.table-container th:nth-child(3),
.table-container td:nth-child(3) {
    width: 15%;
    text-align: center;
}

/* Action */
.table-container th:nth-child(4),
.table-container td:nth-child(4) {
    width: 25%;
    text-align: center;
}


.btn-success {
    background-color: #f97316 !important; /* orange */
    color: #fff !important;
    border: none;
}

.btn-success:hover {
    background-color: #ea580c !important; /* darker orange */
}
.action-btns {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 4px;
    flex-wrap: nowrap;
}

.table-container .btn {
    padding: 4px 8px;
    font-size: 11.5px;
    height: 26px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
}

/* Row hover (clean look) */
tbody tr:hover {
    background: #f2f5ff;
}
/* ===== MANAGE SUPPLIER : BUTTON SIZE MATCH FIX ===== */

/* Action column width enough for all buttons */
.table-container th:last-child,
.table-container td:last-child {
    width: 28%;
    min-width: 180px;
    text-align: center;
    white-space: nowrap;
}

/* Action buttons container */
.action-btns {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    flex-wrap: nowrap;
}

/* ?? ALL buttons same size as DETAILS */
.action-btns .btn {
    height: 28px;
    min-height: 28px;
    padding: 0 10px;
    font-size: 12px;
    border-radius: 6px;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

/* Icons same size */
.action-btns .btn i {
    font-size: 12px;
    margin-right: 4px;
}

/* Reduce column spacing overall */
.table-container th,
.table-container td {
    padding: 6px 8px;
}

/* Prevent cut-off */
.table-container {
    overflow-x: auto;
}
</style>
</head>
<body>

<?php include('sidebar.php'); ?>

<div class="main-content">
    <div class="page-header">
        <h2><i class="fa-solid fa-truck-field"></i> Manage Suppliers</h2>
    </div>

    <?php if ($msg != '') { ?>
        <div class="alert <?= ($msg_type == 'error') ? 'alert-error' : '' ?>">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php } ?>

    <div class="flex-wrapper">
        <div class="left-panel white-card">
            <h4 class="form-title"><i class="fa-solid fa-user-plus"></i> Add New Supplier</h4>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Supplier Name <span style="color:red;">*</span></label>
                    <input type="text" name="supplier_name" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Contact Person</label>
                    <input type="text" name="contact_person">
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="tel" name="contact_number">
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" placeholder="Optional">
                </div>
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" rows="3" style="resize:vertical;" placeholder="Optional"></textarea>
                </div>
                <button type="submit" name="add_supplier" class="btn btn-primary">
                    <i class="fa-solid fa-circle-plus"></i> Add Supplier
                </button>
            </form>
        </div>

        <div class="right-panel white-card">
            <h4 style="color:var(--primary); font-weight:600; font-size:1.4rem; margin:0 0 20px 0; border-left:5px solid var(--secondary); padding-left:12px;">
                <i class="fa-solid fa-list-ul"></i> Registered Suppliers
            </h4>

            <div class="table-container">
                <table>
                    <thead>
    <tr>
        <th>Supplier Name</th>
        <th>Contact Person</th>
        <th>Others</th>
        <th>Action</th>
    </tr>
</thead>
                    <tbody>
                        <?php if ($suppliers->num_rows > 0):
                            while ($row = $suppliers->fetch_assoc()): ?>
                          <tr>
    <td><?= htmlspecialchars($row['Supplier_Name']) ?></td>
    <td><?= htmlspecialchars($row['Contact_Person'] ?? '-') ?></td>

    <td style="text-align:center;">
        <button class="btn btn-primary"
            onclick="showDetails(
                '<?= htmlspecialchars($row['Supplier_Name'], ENT_QUOTES) ?>',
                '<?= htmlspecialchars($row['Contact_Number'] ?? '-', ENT_QUOTES) ?>',
                '<?= htmlspecialchars($row['Email'] ?? '-', ENT_QUOTES) ?>',
                '<?= htmlspecialchars($row['Address'] ?? '-', ENT_QUOTES) ?>'
            )">
            Details
        </button>
    </td>

    <td>
        <div class="action-btns">
    <button class="btn btn-primary" onclick="showItems(<?= intval($row['Supplier_ID']) ?>)">
        Items
    </button>

    <button class="btn btn-success"
        onclick="editSupplier(
             <?= intval($row['Supplier_ID']) ?>,
            '<?= htmlspecialchars($row['Supplier_Name'], ENT_QUOTES) ?>',
            '<?= htmlspecialchars($row['Contact_Person'] ?? '', ENT_QUOTES) ?>',
            '<?= htmlspecialchars($row['Contact_Number'] ?? '', ENT_QUOTES) ?>',
            '<?= htmlspecialchars($row['Email'] ?? '', ENT_QUOTES) ?>',
            '<?= htmlspecialchars($row['Address'] ?? '', ENT_QUOTES) ?>'
            
        )">
        Edit
    </button>

    <a href="?delete=<?= intval($row['Supplier_ID']) ?>" class="btn btn-danger"
       onclick="return confirm('Are you sure?');">
        Delete
    </a>
</div>
    </td>
</tr>
                        <?php endwhile; else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center;color:#888;font-style:italic;padding:40px 0;">
                                    No suppliers found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function showItems(supplierId) {

    const params = new URLSearchParams({
        fetch_items: 1,
        supplier_id: supplierId
    });

    fetch('?' + params.toString())
        .then(res => res.json())
        .then(data => {

            if (data.length === 0) {
                Swal.fire({
                    icon: 'info',
                    title: 'No Items',
                    text: 'This supplier has no items.',
                    confirmButtonColor: '#7c3aed'
                });
                return;
            }

            let itemsHtml = `
                <div style="
                    display:flex;
                    flex-direction:column;
                    gap:14px;
                    font-size:14px;
                ">
            `;

            data.forEach(item => {
                itemsHtml += `
                    <div style="
                        border:1px solid #e5e7eb;
                        border-radius:12px;
                        padding:14px 16px;
                        background:#ffffff;
                        box-shadow:0 4px 12px rgba(0,0,0,0.05);
                    ">
                        <div style="
                            font-weight:600;
                            font-size:15px;
                            color:#1e1b4b;
                            margin-bottom:10px;
                        ">
                            ${item.Item_Name}
                        </div>

                        <div style="
                            display:grid;
                            grid-template-columns:120px 1fr;
                            row-gap:8px;
                            column-gap:10px;
                        ">
                            <div style="font-weight:500;color:#555;">Quantity</div>
                            <div>${item.Quantity}</div>

                            <div style="font-weight:500;color:#555;">Unit Price</div>
                            <div>
                                Rs. ${parseFloat(item.Unit_Price).toLocaleString(undefined,{minimumFractionDigits:2})}
                            </div>
                        </div>
                    </div>
                `;
            });

            itemsHtml += `</div>`;

            Swal.fire({
                title: 'Items Supplied',
                html: itemsHtml,
                width: 520,
                showCloseButton: true,
                confirmButtonText: 'Close',
                confirmButtonColor: '#7c3aed'
            });
        })
        .catch(() => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to fetch items.',
                confirmButtonColor: '#dc2626'
            });
        });
}
</script>
<script>
function showDetails(name, phone, email, address) {
    Swal.fire({
        title: 'Supplier Details',
        html: `
            <div style="
                display:grid;
                grid-template-columns:110px 1fr;
                row-gap:12px;
                column-gap:12px;
                text-align:left;
                font-size:14px;
            ">
                <div style="font-weight:600;color:#555;">Name</div>
                <div>${name}</div>

                <div style="font-weight:600;color:#555;">Phone</div>
                <div>${phone}</div>

                <div style="font-weight:600;color:#555;">Email</div>
                <div>${email}</div>

                <div style="font-weight:600;color:#555;">Address</div>
                <div style="white-space:pre-wrap;">${address}</div>
            </div>
        `,
        showCloseButton: true,
        confirmButtonText: 'Close',
        confirmButtonColor: '#7c3aed',
        width: 520
    });
}
</script>
<script>
function editSupplier(id, name, contact, phone, email, address) {

    Swal.fire({
        title: 'Edit Supplier',
        html: `
            <input id="s_name" class="swal2-input" placeholder="Supplier Name" value="${name}">
            <input id="s_contact" class="swal2-input" placeholder="Contact Person" value="${contact}">
            <input id="s_phone" class="swal2-input" placeholder="Phone" value="${phone}">
            <input id="s_email" class="swal2-input" placeholder="Email" value="${email}">
            <textarea id="s_address" class="swal2-textarea" placeholder="Address">${address}</textarea>
        `,
        confirmButtonText: 'Update',
        confirmButtonColor: '#28a745',
        showCancelButton: true,
        preConfirm: () => {
            return {
                id: id,
                name: document.getElementById('s_name').value,
                contact: document.getElementById('s_contact').value,
                phone: document.getElementById('s_phone').value,
                email: document.getElementById('s_email').value,
                address: document.getElementById('s_address').value
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {

            fetch('manage_suppliers.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    edit_supplier: 1,
                    id: result.value.id,
                    name: result.value.name,
                    contact: result.value.contact,
                    phone: result.value.phone,
                    email: result.value.email,
                    address: result.value.address
                })
            })
            .then(res => res.text())
            .then(() => {
                Swal.fire('Updated!', 'Supplier updated successfully.', 'success')
                    .then(() => location.reload());
            })
            .catch(() => {
                Swal.fire('Error', 'Update failed.', 'error');
            });
        }
    });
}
</script>
</body>
</html>