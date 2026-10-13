<?php
// Session ආරක්ෂාව සඳහා cookie settings (session hijack වැළැක්වීමට)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

// Session ID එක අලුත් කිරීම මගින් session hijacking වළක්වයි
if (isset($_SESSION['ID'])) {
    session_regenerate_id(false);
}

include('connection.php');

// --- SESSION CHECK ---
if (!isset($_SESSION['ID']) || $_SESSION['role'] !== 'Accountant') {
    header("Location: login.php");
    exit();
}

// Logged-in accountant DS (Casting to integer for safety)
$dsid = isset($_SESSION['dsid']) ? intval($_SESSION['dsid']) : 0;

// Selected officer (Casting to integer for safety)
$selected_officer = isset($_GET['officer_id']) ? intval($_GET['officer_id']) : null;

// 🔹 Load ONLY officers from SAME DS
$users_stmt = $conn->prepare("
    SELECT ID, Name 
    FROM user 
    WHERE dsid = ?
    ORDER BY Name
");
$users_stmt->bind_param("i", $dsid);
$users_stmt->execute();
$users = $users_stmt->get_result();

// Defaults
$allocated = 0;
$used = 0;
$remaining = 0;
$issued_items = null; // Changed to null to check for results properly

if ($selected_officer) {

    // --- SECURITY CHECK ---
    // Ensure selected officer belongs to same DS
    $check_stmt = $conn->prepare("
        SELECT ID 
        FROM user 
        WHERE ID = ? AND dsid = ?
    ");
    $check_stmt->bind_param("ii", $selected_officer, $dsid);
    $check_stmt->execute();

    if ($check_stmt->get_result()->num_rows === 0) {
        $selected_officer = null;
    }
}

if ($selected_officer) {

    /* ================== ALLOCATED ================== */
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(final_amount),0)
        FROM inventory_officer_allocations
        WHERE officer_id = ?
          AND dsid = ?
    ");
    $stmt->bind_param("ii", $selected_officer, $dsid);
    $stmt->execute();
    $allocated = (float)$stmt->get_result()->fetch_row()[0];

    /* ================== USED (FIXED) ================== */
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(ii.Quantity * i.Unit_Price),0)
        FROM inventory_issued_items ii
        JOIN inventory_items i ON ii.Item_ID = i.Item_ID
        WHERE ii.Officer_ID = ?
    ");
    $stmt->bind_param("i", $selected_officer);
    $stmt->execute();
    $used = (float)$stmt->get_result()->fetch_row()[0];

    $remaining = max(0, $allocated - $used);

    /* ================== HISTORY (FIXED) ================== */
    $stmt = $conn->prepare("
        SELECT 
            ii.Issue_Date,
            i.Item_Name,
            ii.Quantity,
            i.Unit_Price,
            (ii.Quantity * i.Unit_Price) AS total
        FROM inventory_issued_items ii
        JOIN inventory_items i ON ii.Item_ID = i.Item_ID
        WHERE ii.Officer_ID = ?
        ORDER BY ii.Issue_Date DESC
    ");
    $stmt->bind_param("i", $selected_officer);
    $stmt->execute();
    $issued_items = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Officer History | Inventory System</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
body { background:#eef2ff; font-family: "Segoe UI", sans-serif; }
.main-wrapper { margin-left:260px; padding:24px; }
@media (max-width: 992px) { .main-wrapper { margin-left:0; } }

.summary-row {
    display:grid;
    grid-template-columns: repeat(auto-fit,minmax(300px,1fr));
    gap:18px;
    margin:20px 0;
}
.sum-card {
    background:#fff;
    border-radius:14px;
    padding:12px 18px 16px;
    box-shadow:0 6px 16px rgba(0,0,0,.12);
    position:relative;
    min-height:90px;
}
.sum-card::before {
    content:'';
    position:absolute;
    top:0; left:0;
    height:6px;
    width:100%;
    border-radius:14px 14px 0 0;
}
.sum-card h6 { margin:0; font-size:13px; color:#6c757d; font-weight:600; }
.sum-card h3 { margin-top:6px; font-size:22px; font-weight:700; }

.blue::before { background:#0d6efd; }
.orange::before { background:#fd7e14; }
.green::before { background:#198754; }

.table-wrapper { background:#fff; border-radius:16px; padding:18px; box-shadow:0 6px 18px rgba(0,0,0,.12); }
.table thead { background:#343a40; color:#fff; }
.table td, .table th { vertical-align:middle; white-space:nowrap; }
</style>
</head>

<body>

<?php include('sidebar.php'); ?>

<div class="main-wrapper">

    <h4 class="mb-3 fw-bold">📑 Officer Issue & Allocation History</h4>

    

    <form method="GET" class="row g-2 align-items-center mb-3">
        <div class="col-md-6">
            <select name="officer_id" class="form-select" required>
                <option value="">-- Select Officer --</option>
                <?php while($u=$users->fetch_assoc()): ?>
                    <option value="<?= (int)$u['ID'] ?>" <?= (isset($selected_officer) && $u['ID']==$selected_officer)?'selected':'' ?>>
                        <?= htmlspecialchars($u['Name'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">View History</button>
        </div>
    </form>

    <?php if ($selected_officer): ?>

    <div class="summary-row">
        <div class="sum-card blue">
            <h6>Allocated Amount</h6>
            <h3>Rs. <?= number_format($allocated,2) ?></h3>
        </div>
        <div class="sum-card orange">
            <h6>Used Amount</h6>
            <h3>Rs. <?= number_format($used,2) ?></h3>
        </div>
        <div class="sum-card green">
            <h6>Remaining Balance</h6>
            <h3>Rs. <?= number_format($remaining,2) ?></h3>
        </div>
    </div>

    <div class="table-wrapper mt-4">
        <h5 class="mb-3 fw-semibold">Issued Items History</h5>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($issued_items && $issued_items->num_rows > 0): ?>
                    <?php while($r=$issued_items->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['Issue_Date'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($r['Item_Name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= intval($r['Quantity']) ?></td>
                            <td>Rs. <?= number_format($r['Unit_Price'],2) ?></td>
                            <td>Rs. <?= number_format($r['total'],2) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">No issued items found</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php endif; ?>

</div>
</body>
</html>