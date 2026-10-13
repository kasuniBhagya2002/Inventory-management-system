<?php
// Session ආරක්ෂාව සඳහා cookie settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

if (isset($_SESSION['ID'])) {
    session_regenerate_id(false);
}

include('connection.php');

/* ---------------- SESSION CHECK ---------------- */
if (!isset($_SESSION['ID']) || $_SESSION['role'] !== 'Accountant') {
    header("Location: login.php");
    exit();
}

$dsid = isset($_SESSION['dsid']) ? (int)$_SESSION['dsid'] : null;
if (!$dsid) {
    die("Error: DS office not assigned to this user.");
}

/* ---------------- HELPER ---------------- */
function to_float($v){
    $v = str_replace(',', '', trim($v));
    return ($v === '' || !is_numeric($v)) ? 0.0 : (float)$v;
}

/* ---------------- HANDLE ISSUE POST ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_request'])) {
    $request_id = (int)($_POST['request_id'] ?? 0);
    $issue_qty  = (int)($_POST['issue_qty'] ?? 0);
    $now = date('Y-m-d H:i:s');

    if ($request_id <= 0 || $issue_qty <= 0) {
        $_SESSION['msg_err'] = "Invalid quantity entered.";
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    }

    $conn->begin_transaction();
    try {
        $q = $conn->prepare("
            SELECT ir.Item_ID, ir.Quantity AS Req_Qty, ir.Officer_ID,
                   i.Unit_Price, i.Quantity AS Stock_Qty, ir.Request_Date, ir.Status
            FROM inventory_item_requests ir
            JOIN inventory_items i ON ir.Item_ID = i.Item_ID
            WHERE ir.Request_ID = ? AND ir.dsid = ? FOR UPDATE
        ");
        $q->bind_param("ii", $request_id, $dsid);
        $q->execute();
        $res = $q->get_result();

        if ($res->num_rows === 0) throw new Exception("Request record not found.");
        $row = $res->fetch_assoc();
        
        // DS Approve කර නොමැති වුවත් නිකුත් කිරීමට දැන් අවසර දී ඇත (Access Granted)

        $item_id = (int)$row['Item_ID'];
        $stock_qty = (int)$row['Stock_Qty'];

        $upd = $conn->prepare("UPDATE inventory_items SET Quantity = Quantity - ? WHERE Item_ID = ? AND Quantity >= ? AND dsid = ?");
        $upd->bind_param("iiii", $issue_qty, $item_id, $issue_qty, $dsid);
        $upd->execute();

        if ($upd->affected_rows > 0) {
            $ins = $conn->prepare("INSERT INTO inventory_issued_items (Request_ID, Officer_ID, Item_ID, Quantity, Issue_Date, dsid) VALUES (?, ?, ?, ?, ?, ?)");
            $ins->bind_param("iiiisi", $request_id, $row['Officer_ID'], $item_id, $issue_qty, $now, $dsid);
            $ins->execute();
            $conn->commit();
            $_SESSION['msg_success'] = "Inventory item issued successfully.";
        } else {
            throw new Exception("Stock depletion failed. Check warehouse quantity.");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['msg_err'] = $e->getMessage();
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

/* ---------------- FETCH DATA ---------------- */
$sql = "
SELECT ir.Request_ID, ir.Officer_ID, ir.Item_ID,
       ir.Quantity AS Req_Qty, ir.Request_Date, ir.Status,
       off.Name AS OfficerName,
       ds.Name AS DS_Name,
       i.Item_Name, i.Unit_Price, i.Quantity AS Stock_Qty,
       COALESCE(SUM(iss.Quantity),0) AS Issued_Qty,
       (SELECT COALESCE(SUM(final_amount),0) FROM inventory_officer_allocations WHERE officer_id = ir.Officer_ID AND YEAR(allocated_date) = YEAR(ir.Request_Date) AND MONTH(allocated_date) = MONTH(ir.Request_Date)) AS Allocation
FROM inventory_item_requests ir
JOIN user off ON ir.Officer_ID = off.ID
LEFT JOIN inventory_approvals ap ON ap.Request_ID = ir.Request_ID
LEFT JOIN user ds ON ap.DS_Officer_ID = ds.ID
JOIN inventory_items i ON ir.Item_ID = i.Item_ID
LEFT JOIN inventory_issued_items iss ON iss.Request_ID = ir.Request_ID
WHERE ir.dsid = ? AND (ir.Status = 'Approved' OR ir.Status = 'Pending')
GROUP BY ir.Request_ID
ORDER BY FIELD(ir.Status, 'Approved', 'Pending'), ir.Request_Date ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $dsid);
$stmt->execute();
$result = $stmt->get_result();
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Accountant | Inventory Management</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root {
        --primary: #6366f1;
        --primary-dark: #4f46e5;
        --bg-main: #f8fafc;
        --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    body { font-family: 'Inter', sans-serif; background: var(--bg-main); color: #334155; margin: 0; }
    .main { margin-left: 260px; padding: 30px; transition: 0.3s; }
    
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
    .page-title h2 { margin: 0; font-size: 1.5rem; font-weight: 700; color: #1e293b; }

    .inventory-card { background: #ffffff; border-radius: 12px; box-shadow: var(--card-shadow); border: 1px solid #e2e8f0; overflow: hidden; }
    .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .table { width: 100%; border-collapse: collapse; min-width: 1000px; }
    
    .table thead th { 
        background: #f1f5f9; color: #475569; font-size: 0.75rem; 
        text-transform: uppercase; padding: 15px 20px; font-weight: 700;
        border-bottom: 2px solid #e2e8f0; text-align: left;
    }
    
    .table tbody td { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; font-size: 0.875rem; vertical-align: middle; }
    .table tbody tr:hover { background: #f8fafc; }

    /* Badges & Colors */
    .badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
    .badge-success { background: #dcfce7; color: #166534; } /* Issued / Complete */
    .badge-warning { background: #fef9c3; color: #854d0e; } /* Partial / Pending */
    .badge-danger { background: #fee2e2; color: #991b1b; } /* Not Issued */

    .balance-text { color: #ef4444; font-weight: 700; } /* Balance Red Color */

    .btn-group { display: flex; gap: 8px; }
    .btn { 
        border: none; padding: 8px 14px; border-radius: 6px; cursor: pointer; 
        font-weight: 600; font-size: 0.8rem; display: flex; align-items: center; gap: 5px; transition: 0.2s;
    }
    .btn-issue { background: var(--primary); color: white; }
    .btn-details { background: #f1f5f9; color: #475569; }

    .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; display: flex; align-items: center; gap: 10px; }
    .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #34d399; }
    .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #f87171; }

    .modal { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); align-items: center; justify-content: center; z-index: 9999; }
    .modal-content { background: white; padding: 30px; border-radius: 16px; width: 500px; max-width: 90%; position: relative; }
    .close-modal { position: absolute; top: 20px; right: 20px; border: none; background: none; font-size: 1.5rem; cursor: pointer; color: #94a3b8; }

    @media (max-width: 1024px) { .main { margin-left: 0; padding: 20px; } }
    /* ===== VIEW REQUEST TABLE – COMPACT & MODERN ===== */

/* Table width & layout */
.table {
    min-width: unset;
    font-size: 13px;
}

/* Header cells */
.table thead th {
    padding: 8px 12px;          /* 🔥 column gap reduce */
    font-size: 11.5px;
    letter-spacing: 0.4px;
    text-align: left;
    white-space: nowrap;
}

/* Body cells */
.table tbody td {
    padding: 7px 12px;          /* 🔥 row + column gap reduce */
    font-size: 13px;
    line-height: 1.35;
    vertical-align: middle;
    white-space: nowrap;
}

/* Reduce row height */
.table tbody tr {
    height: 42px;
}

/* Hover effect – subtle modern */
.table tbody tr:hover {
    background: #f1f5ff;
}

/* Status badge – smaller & cleaner */
.badge {
    padding: 4px 10px;
    font-size: 11.5px;
    border-radius: 14px;
    font-weight: 600;
}

/* Balance text emphasis */
.balance-text {
    font-size: 13px;
    font-weight: 700;
}

/* Action buttons alignment */
.table td:last-child {
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Button group compact */
.btn-group {
    gap: 6px;
}

/* Buttons – uniform size */
.btn {
    padding: 6px 10px;
    font-size: 12px;
    height: 28px;
    border-radius: 6px;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

/* Issue button */
.btn-issue {
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    box-shadow: 0 4px 10px rgba(99, 102, 241, 0.25);
}

/* Details button */
.btn-details {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}

/* Prevent horizontal scroll unless needed */
.table-responsive {
    overflow-x: auto;
}

.table thead th {
    background: linear-gradient(135deg, #8c51f0ff, #9865ebff); /* Purple gradient */
    color: #ffffff;
    font-weight: 700;
    border-bottom: none;
}

/* Header row bottom separator */
.table thead {
    box-shadow: inset 0 -1px 0 rgba(255, 255, 255, 0.15);
}

/* Optional: rounded corners for header */
.table thead th:first-child {
    border-top-left-radius: 12px;
}
.table thead th:last-child {
    border-top-right-radius: 12px;
}
</style>
</head>
<body>

<?php include('sidebar.php'); ?>

<div class="main">
    <div class="page-header"><div class="page-title"><h2>Inventory Control Dashboard</h2></div></div>

    <?php if (!empty($_SESSION['msg_success'])): ?>
        <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($_SESSION['msg_success']); unset($_SESSION['msg_success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['msg_err'])): ?>
        <div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($_SESSION['msg_err']); unset($_SESSION['msg_err']); ?></div>
    <?php endif; ?>

    <div class="inventory-card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Req. ID</th>
                        <th>Officer Name</th>
                        <th>Item Description</th>
                        <th>Requested</th>
                        <th>Issued</th>
                        <th>Balance (To Issue)</th>
                        <th>Warehouse Stock</th>
                        <th>DS Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($r = $result->fetch_assoc()): 
                            $reqId = (int)$r['Request_ID'];
                            $status = $r['Status'];
                            $requested = (int)$r['Req_Qty'];
                            $issued = (int)$r['Issued_Qty']; 
                            $stock = (int)$r['Stock_Qty'];
                            $balance = max(0, $requested - $issued);
                        ?>
                        <tr>
                            <td>#<?= $reqId ?></td>
                            <td><strong><?= htmlspecialchars($r['OfficerName']) ?></strong></td>
                            <td><?= htmlspecialchars($r['Item_Name']) ?></td>
                            <td style="text-align: center;"><?= $requested ?></td>
                            <td style="text-align: center; color: #10b981; font-weight: 600;"><?= $issued ?></td>
                            <td style="text-align: center;"><span class="balance-text"><?= $balance ?></span></td>
                            <td style="text-align: center;"><?= $stock ?></td>
                            <td>
                                <span class="badge <?= ($status === 'Approved') ? 'badge-success' : 'badge-warning' ?>">
                                    <?= ($status === 'Approved') ? 'Approved' : 'Pending' ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-details" onclick='showDetails(<?= json_encode([
                                        "officer" => $r["OfficerName"],
                                        "item" => $r["Item_Name"],
                                        "requested" => $requested,
                                        "issued" => $issued,
                                        "balance" => $balance,
                                        "allocation" => number_format($r["Allocation"], 2),
                                        "ds" => $r["DS_Name"] ?? "Not Approved Yet"
                                    ]) ?>)'><i class="fa-solid fa-eye"></i> View</button>

                                    <?php if ($balance > 0 && $stock > 0): ?>
                                        <button class="btn btn-issue" onclick="openIssueModal(<?= $reqId ?>, <?= $balance ?>, <?= $stock ?>)"><i class="fa-solid fa-plus"></i> Issue</button>
                                    <?php else: ?>
                                        <button class="btn btn-details" disabled style="opacity: 0.5;">Issued</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal" id="detailsModal">
    <div class="modal-content">
        <button class="close-modal" onclick="closeModal('detailsModal')">&times;</button>
        <div style="font-size: 1.2rem; font-weight: 700; margin-bottom: 15px; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">Request Information</div>
        <div id="detailsContent" style="line-height: 2.2;"></div>
    </div>
</div>

<div class="modal" id="issueModal">
    <div class="modal-content">
        <button class="close-modal" onclick="closeModal('issueModal')">&times;</button>
        <div style="font-size: 1.2rem; font-weight: 700; margin-bottom: 15px;">Finalize Issuance</div>
        <form method="POST">
            <input type="hidden" name="request_id" id="f_request_id">
            <p id="issue_hint" style="background: #f1f5f9; padding: 10px; border-radius: 8px; font-size: 0.85rem;"></p>
            <div style="margin-top: 15px;">
                <label style="font-weight: 600;">Qty to Issue:</label>
                <input type="number" name="issue_qty" id="f_issue_qty" required min="1" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px;">
            </div>
            <button type="submit" name="issue_request" class="btn btn-issue" style="width: 100%; margin-top: 20px; padding: 14px; justify-content: center;">Confirm Disburse</button>
        </form>
    </div>
</div>

<script>
function showDetails(d) {
    const content = `
        <p><strong>Officer Name:</strong> ${d.officer}</p>
        <p><strong>Item Description:</strong> ${d.item}</p>
        <p><strong>Officer Monthly Allocation:</strong> <span style="color: #4f46e5; font-weight: 700;">Rs. ${d.allocation}</span></p>
        <p><strong>DS Approval Status:</strong> ${d.ds}</p>
        <hr style="border: 0.5px solid #eee;">
        <p><strong>Requested:</strong> ${d.requested} | <strong>Already Issued:</strong> ${d.issued}</p>
        <p><strong>Remaining to Issue:</strong> <span style="color: #ef4444; font-weight: 800;">${d.balance}</span></p>
    `;
    document.getElementById('detailsContent').innerHTML = content;
    document.getElementById('detailsModal').style.display = 'flex';
}

function openIssueModal(id, balance, stock) {
    document.getElementById('f_request_id').value = id;
    const maxQty = Math.min(balance, stock);
    const input = document.getElementById('f_issue_qty');
    input.max = maxQty; input.value = maxQty;
    document.getElementById('issue_hint').innerHTML = `Outstanding: <strong>${balance}</strong> | Warehouse Stock: <strong>${stock}</strong>`;
    document.getElementById('issueModal').style.display = 'flex';
}

function closeModal(id) { document.getElementById(id).style.display = 'none'; }
window.onclick = function(e) { if(e.target.className === 'modal') e.target.style.display = 'none'; }
</script>

</body>
</html>