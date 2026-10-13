<?php
// officer_dashboard.php
session_start();

// Session hijack වීම වැළැක්වීමට ID එක අලුත් කිරීම
if (isset($_SESSION['ID'])) {
    session_regenerate_id(true);
}

include('connection.php');

/* ---------------- SESSION CHECK ---------------- */
if (!isset($_SESSION['ID'])) {
    header("Location: login.php");
    exit();
}

/* ---------------- SESSION DATA ---------------- */
$user_id = (int)$_SESSION['ID'];
$name    = $_SESSION['Name'] ?? 'Officer';
$year    = date('Y');

/* ---------------- HELPER ---------------- */
function fm($v) {
    return 'Rs. ' . number_format((float)$v, 2);
}

/* ---------------- TOTAL ALLOCATED (YEAR WISE) ---------------- */
$alloc_stmt = $conn->prepare("
    SELECT COALESCE(SUM(final_amount),0)
    FROM inventory_officer_allocations
    WHERE officer_id = ?
      AND year = ?
");
$alloc_stmt->bind_param("ii", $user_id, $year);
$alloc_stmt->execute();
$total_alloc = (float)$alloc_stmt->get_result()->fetch_row()[0];

/* ---------------- TOTAL USED (FROM ISSUED ITEMS ONLY) ---------------- */
$used_stmt = $conn->prepare("
    SELECT COALESCE(SUM(ii.Quantity * it.Unit_Price),0)
    FROM inventory_issued_items ii
    JOIN inventory_items it ON ii.Item_ID = it.Item_ID
    WHERE ii.Officer_ID = ?
      AND YEAR(ii.Issue_Date) = ?
");
$used_stmt->bind_param("ii", $user_id, $year);
$used_stmt->execute();
$used_amt = (float)$used_stmt->get_result()->fetch_row()[0];

/* ---------------- REMAINING ---------------- */
$remaining = $total_alloc - $used_amt;
if ($remaining < 0) $remaining = 0;

/* ---------------- ALLOCATION HISTORY ---------------- */
$hist_stmt = $conn->prepare("
    SELECT 
        year,
        given_amount,
        carry_forward_amount,
        debt_amount,
        final_amount,
        0 AS used_amount,
        allocated_date
    FROM inventory_officer_allocations
    WHERE officer_id = ?
    ORDER BY allocated_date DESC
");
$hist_stmt->bind_param("i", $user_id);
$hist_stmt->execute();
$hist_res = $hist_stmt->get_result();

/* ---------------- REQUEST HISTORY ---------------- */
$req_stmt = $conn->prepare("
    SELECT 
        ir.Item_ID,
        i.Item_Name,
        ir.Quantity,
        i.Unit_Price,
        (ir.Quantity * i.Unit_Price) AS total_price,
        ir.Status,
        ir.Request_Date
    FROM inventory_item_requests ir
    LEFT JOIN inventory_items i ON ir.Item_ID = i.Item_ID
    WHERE ir.Officer_ID = ?
    ORDER BY ir.Request_Date DESC
");
$req_stmt->bind_param("i", $user_id);
$req_stmt->execute();
$req_res = $req_stmt->get_result();

/* ---------------- ISSUED ITEMS HISTORY ---------------- */
$item_hist_stmt = $conn->prepare("
    SELECT 
        it.Item_Name,
        ii.Quantity,
        it.Unit_Price,
        (ii.Quantity * it.Unit_Price) AS total_price,
        ii.Issue_Date
    FROM inventory_issued_items ii
    JOIN inventory_items it ON ii.Item_ID = it.Item_ID
    WHERE ii.Officer_ID = ?
    ORDER BY ii.Issue_Date DESC
");
$item_hist_stmt->bind_param("i", $user_id);
$item_hist_stmt->execute();
$item_hist_res = $item_hist_stmt->get_result();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Officer Dashboard</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root{
  --purple-primary: #8e44ad; 
  --purple-light: #9b59b6;
  --blue-primary: #3498db;
  --blue-light: #eaf2f8;
  --gradient-main: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%);
  --gradient-header: linear-gradient(90deg, #6a85b6 0%, #bac8e0 100%);
  --gradient-btn: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  --bg-color: #f4f7fc;
  --text-dark: #2c3e50;
  --text-muted: #7f8c8d;
  --card-shadow: 0 10px 20px rgba(161, 140, 209, 0.15);
}
*{box-sizing:border-box}
body{margin:0;font-family:'Poppins',sans-serif;background: var(--bg-color);color: var(--text-dark);}
header{background: var(--gradient-btn);color:#fff;padding: 15px 30px;display:flex;justify-content:space-between;align-items:center;box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);}
header h2 {font-weight: 500;font-size: 1.2rem;margin: 0;}
.logout-btn{background: rgba(255,255,255,0.2);border: 1px solid rgba(255,255,255,0.4);color: white;padding:8px 16px;border-radius:20px;font-weight:500;cursor:pointer;transition: 0.3s;}
.logout-btn:hover {background: #fff;color: var(--purple-primary);}
.container{max-width:1200px;margin:30px auto;padding:0 20px}
.action-bar {display: flex;justify-content: flex-end;margin-bottom: 25px;}
.request-btn-top {background: var(--gradient-btn);color: #fff;padding: 12px 24px;font-size: 16px;font-weight: 500;border-radius: 30px;text-decoration: none;display: inline-flex;align-items: center;gap: 10px;box-shadow: 0 4px 15px rgba(118, 75, 162, 0.4);transition: transform 0.2s ease, box-shadow 0.2s ease;}
.request-btn-top:hover {transform: translateY(-2px);box-shadow: 0 6px 20px rgba(118, 75, 162, 0.6);}
.cards{display:flex;gap:20px;flex-wrap:wrap;justify-content:center; margin-bottom: 30px;}
.card{background:#fff;border-radius:15px;padding:20px;width:280px;box-shadow: var(--card-shadow);position:relative;border: 1px solid #eef2f7;overflow: hidden;}
.card::before {content: "";position: absolute;top: 0; left: 0; right: 0; height: 5px;}
.card.blue::before { background: linear-gradient(90deg, #3498db, #85c1e9); }
.card.purple::before { background: linear-gradient(90deg, #9b59b6, #e1bee7); }
.card.green::before { background: linear-gradient(90deg, #2ecc71, #a9dfbf); }
.card h3{margin:5px 0 10px; color: var(--text-muted); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;}
.card p{margin:0;font-size:1.8rem;font-weight:600; color: var(--text-dark);}
.section{margin-bottom:30px;background:#fff;border-radius:15px;padding:25px;box-shadow: var(--card-shadow);border: 1px solid #eef2f7;}
.section h3 {margin-top: 0;color: var(--purple-primary);border-bottom: 2px solid #f0f4f8;padding-bottom: 15px;margin-bottom: 15px;}
.table{width:100%;border-collapse:separate; border-spacing: 0;}
.table th{background: #f8f9fa;color: #667eea;padding:12px 15px;text-align:left;font-weight: 600;border-bottom: 2px solid #eef2f7;}
.table td{padding:12px 15px;border-bottom:1px solid #f1f1f1;color: #555;}
.table tr:last-child td { border-bottom: none; }
.table tbody tr:hover {background-color: var(--blue-light);}
.status-badge {display: inline-flex;align-items: center;gap: 6px;padding: 6px 14px;border-radius: 20px;font-size: 0.78rem;font-weight: 600;letter-spacing: 0.4px;text-transform: uppercase;box-shadow: 0 2px 6px rgba(0,0,0,0.15);}
.status-pending {background: linear-gradient(135deg, #f6c23e, #f4b619);color: #fff;}
.status-approved {background: linear-gradient(135deg, #1cc88a, #17a673);color: #fff;}
.status-rejected {background: linear-gradient(135deg, #e74a3b, #c0392b);color: #fff;}
</style>
</head>
<body>

<header>
    <div>
        <a href="select_action.php" style="color:#fff; text-decoration:none; margin-right:15px; opacity:0.8; transition:0.2s"><i class="fa-solid fa-arrow-left"></i> Back</a>
        <h2 style="display:inline-block;">Welcome, <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?> 👋</h2>
    </div>
    <form method="POST" action="logout.php">
        <button class="logout-btn"><i class="fa-solid fa-power-off"></i> Logout</button>
    </form>
</header>

<div class="container">
    

    <div class="action-bar">
        <a class="request-btn-top" href="request_item.php">
            <i class="fa-solid fa-plus-circle"></i> Request New Item
        </a>
    </div>

    <div class="cards">
        <div class="card blue">
            <h3>Total Allocated</h3>
            <p style="color:#3498db;"><?= fm($total_alloc) ?></p>
        </div>
        <div class="card purple">
            <h3>Total Used</h3>
            <p style="color:#9b59b6;"><?= fm($used_amt) ?></p>
        </div>
        <div class="card green">
            <h3>Remaining</h3>
            <p style="color:#2ecc71;"><?= fm($remaining) ?></p>
        </div>
    </div>

    <div class="section">
        <h3><i class="fa-solid fa-clock-rotate-left"></i> Allocation History</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Year</th><th>Given</th><th>Carry</th>
                    <th>Debt</th><th>Total</th><th>Used</th><th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php while($h=$hist_res->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($h['year']) ?></td>
                    <td><?= fm($h['given_amount']) ?></td>
                    <td><?= fm($h['carry_forward_amount']) ?></td>
                    <td><?= fm($h['debt_amount']) ?></td>
                    <td><?= fm($h['final_amount']) ?></td>
                    <td><?= fm($h['used_amount']) ?></td>
                    <td><?= date('Y-m-d',strtotime($h['allocated_date'])) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3><i class="fa-solid fa-list-check"></i> Requested Items</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Total Price</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php while($r=$req_res->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($r['Item_Name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int)$r['Quantity'] ?></td>
                    <td><?= fm($r['total_price']) ?></td>
                    <td>
                        <?php
                            $status = $r['Status'];
                            $statusClass = 'status-pending';
                            if ($status == 'Approved') $statusClass = 'status-approved';
                            elseif ($status == 'Rejected') $statusClass = 'status-rejected';
                        ?>
                        <span class="status-badge <?= $statusClass ?>">
                            <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td><?= date('Y-m-d',strtotime($r['Request_Date'])) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>