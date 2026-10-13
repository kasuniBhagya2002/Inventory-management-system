<?php
// Session ආරක්ෂාව සඳහා (Security Hardening)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('connection.php');

// වැරදි ඇතුළත් කිරීම් වැළැක්වීමට ID එක පරීක්ෂා කිරීම
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<div class='alert alert-danger'>Invalid officer ID.</div>";
    exit();
}

// SQL Injection වැළැක්වීමට Integer casting කිරීම
$officer_id = intval($_GET['id']);

/* -----------------------------------------
   1. Fetch Officer Basic Info
   Tables Updated: user, section
--------------------------------------------*/
$sql = "
    SELECT u.Name, s.section_name 
    FROM user u
    LEFT JOIN section s ON u.section_id = s.section_id
    WHERE u.ID = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $officer_id);
$stmt->execute();
$info = $stmt->get_result()->fetch_assoc();

if (!$info) {
    echo "<div class='alert alert-warning'>Officer not found.</div>";
    exit();
}

// XSS ආරක්ෂාව සඳහා දත්ත Escaping කිරීම
$name    = htmlspecialchars($info['Name'], ENT_QUOTES, 'UTF-8');
$section = htmlspecialchars($info['section_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8');

/* -----------------------------------------
   2. TOTAL ALLOCATED AMOUNT
--------------------------------------------*/
$alloc_sql = "SELECT SUM(final_amount) AS total_alloc 
              FROM inventory_officer_allocations 
              WHERE officer_id = ?";

$stm_alloc = $conn->prepare($alloc_sql);
$stm_alloc->bind_param("i", $officer_id);
$stm_alloc->execute();
$total_alloc_res = $stm_alloc->get_result()->fetch_assoc();
$total_alloc = floatval($total_alloc_res['total_alloc'] ?? 0);

/* -----------------------------------------
   3. TOTAL USED AMOUNT (Approved Requests)
--------------------------------------------*/
$used_sql = "
    SELECT SUM(i.Unit_Price * ir.Quantity) AS used_total
    FROM inventory_item_requests ir
    JOIN inventory_items i ON ir.Item_ID = i.Item_ID
    WHERE ir.Officer_ID = ? AND ir.Status = 'Approved'
";

$stm_used = $conn->prepare($used_sql);
$stm_used->bind_param("i", $officer_id);
$stm_used->execute();
$total_used_res = $stm_used->get_result()->fetch_assoc();
$total_used = floatval($total_used_res['used_total'] ?? 0);

/* -----------------------------------------
   4. BALANCE
--------------------------------------------*/
$balance = $total_alloc - $total_used;

?>
<style>
.info-card {
    background: #f8faff;
    padding: 18px;
    border-radius: 12px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.07);
    max-width: 650px;
    margin: 0 auto;
    font-family: 'Inter', sans-serif;
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #e3e6f0;
}
.info-row:last-child {
    border-bottom: none;
}

.info-title {
    font-weight: 600;
    color: #003f7f;
}

.amount-box {
    display: flex;
    gap: 10px;
    margin-top: 18px;
}
.amount-box div {
    text-align: center;
    padding: 14px;
    border-radius: 10px;
    width: 33%;
    font-weight: 600;
    font-size: 15px;
}

.limit   { background: #d4edda; color: #155724; }
.used    { background: #fff3cd; color: #856404; }
.balance { background: #cce5ff; color: #004085; }

@media(max-width: 600px){
    .amount-box {
        flex-direction: column;
    }
    .amount-box div {
        width: 100%;
    }
}
</style>

<div class="info-card">
    <div class="info-row">
        <div class="info-title">👤 Officer Name:</div>
        <div><?= $name ?></div>
    </div>

    <div class="info-row">
        <div class="info-title">🏢 Section:</div>
        <div><?= $section ?></div>
    </div>

    <div class="amount-box">
        <div class="limit">
            Total Allocated <br>
            Rs. <?= number_format($total_alloc, 2) ?>
        </div>

        <div class="used">
            Total Used <br>
            Rs. <?= number_format($total_used, 2) ?>
        </div>

        <div class="balance">
            Balance <br>
            Rs. <?= number_format($balance, 2) ?>
        </div>
    </div>
</div>