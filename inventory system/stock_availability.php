<?php
// Session ආරක්ෂාව සඳහා cookie settings (session hijacking වැළැක්වීමට)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

// Session ID එක අලුත් කිරීම (Security regeneration)
if (isset($_SESSION['ID'])) {
    session_regenerate_id(false);
}

include('connection.php');

// --- SESSION CHECK ---
if (!isset($_SESSION['ID']) || $_SESSION['role'] !== 'Accountant') {
    header("Location: login.php");
    exit();
}

// --- USER NAME ---
$name = $_SESSION['Name'] ?? $_SESSION['name'] ?? 'Accountant';

// ✅ CORRECT DSID - casting to integer for safety
$dsid = (int)$_SESSION['dsid'];

// ===============================
// ✅ DSID FILTERED INVENTORY QUERY (SECURE PREPARED STATEMENT)
// ===============================
$sql = "
    SELECT 
        i.Item_ID,
        i.Item_Name,
        i.Unit_Price,
        COALESCE(i.Min_Quantity, 5) AS Min_Quantity,
        COALESCE(i.Quantity, 0) AS Static_Stock,
        (
            SELECT COUNT(*) 
            FROM information_schema.tables 
            WHERE table_schema = DATABASE() 
              AND table_name = 'stock_details'
        ) AS has_stock_details
    FROM inventory_items i
    WHERE i.dsid = ?
    ORDER BY i.Item_Name ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $dsid);
$stmt->execute();
$res = $stmt->get_result();

$items = [];

while ($row = $res->fetch_assoc()) {

    $has_details_table = (int)$row['has_stock_details'];

    if ($has_details_table) {
        $item_id = (int)$row['Item_ID'];

        $stock_sql = "
            SELECT COALESCE(SUM(
                CASE 
                    WHEN transaction_type IN ('Purchase/In', 'Return') THEN quantity
                    ELSE -quantity 
                END
            ), 0) AS real_stock
            FROM stock_details
            WHERE item_id = ?
              AND dsid = ?
        ";

        $stmt2 = $conn->prepare($stock_sql);
        $stmt2->bind_param("ii", $item_id, $dsid);
        $stmt2->execute();
        $stock_res = $stmt2->get_result();
        $stock_row = $stock_res->fetch_assoc();

        $current_stock = (int)$stock_row['real_stock'];
    } else {
        $current_stock = (int)$row['Static_Stock'];
    }

    $min_qty = (int)$row['Min_Quantity'];
    $unit_price = (float)$row['Unit_Price'];

    $row['Stock'] = $current_stock;
    $row['Needed'] = max(0, $min_qty - $current_stock);
    $row['Restock_Cost'] = $row['Needed'] * $unit_price;

    $items[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Stock Availability | Accountant</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root{
    --primary:#002f6c;
    --secondary:#004b9a;
    --accent:#ffc107;
    --bg:#f0f4f8;
    --card:#fff;
    --green:#28a745;
    --orange:#fd7e14;
    --red:#dc3545;
}

body{
    margin:0;
    background:var(--bg);
    font-family:'Poppins',sans-serif;
    color:#333;
}

.main-content{
    margin-left:260px;
    padding:26px 32px; /* slightly reduced */
}

h2{
    color:var(--primary);
    font-weight:600;
    margin-bottom:18px;
    font-size:1.6rem; /* smaller */
}

/* ================= CARD ================= */
.card{
    background:var(--card);
    border-radius:14px;
    box-shadow:0 6px 20px rgba(0,0,0,0.08);
    padding:22px;
    margin-bottom:22px;
}

/* ================= TABLE ================= */
.table-container{
    overflow-x:auto;
    border-radius:12px;
    box-shadow:0 4px 15px rgba(0,0,0,0.06);
}

table{
    width:100%;
    min-width:720px;
    border-collapse:collapse;
    background:#fff;
}

/* Header */
.table-container thead{
    background:linear-gradient(90deg,#7c3aed,#a78bfa);
    color:#fff;
}

th{
    padding:9px 8px;        /* 🔻 column gap reduced */
    text-align:center;
    font-weight:600;
    font-size:12px;         /* 🔻 font size */
    white-space:nowrap;
}

/* Body cells */
td{
    padding:8px 8px;        /* 🔻 row + column gap reduced */
    text-align:center;
    border-bottom:1px solid #eee;
    font-size:12.5px;       /* 🔻 font size */
    line-height:1.25;
    white-space:nowrap;     /* prevents cut */
}

/* Row hover & zebra */
tbody tr:hover{background:#f8fbff;}
tbody tr:nth-child(even){background:#f9fbfd;}

/* ================= BADGES ================= */
.badge{
    padding:4px 8px;        /* compact */
    border-radius:40px;
    font-weight:600;
    font-size:11px;
    display:inline-block;
}

.badge-green{background:#d4edda;color:#155724;}
.badge-orange{background:#fff3cd;color:#856404;}
.badge-red{background:#f8d7da;color:#721c24;}

/* ================= BUTTON ================= */
.btn-low{
    background:#fd7e14;
    color:white;
    border:none;
    padding:5px 10px;       /* compact */
    border-radius:7px;
    cursor:pointer;
    font-size:11.5px;
    font-weight:500;
    transition:all 0.3s;
    white-space:nowrap;
}

.btn-low:hover{
    background:#e96b02;
    transform:translateY(-1px);
}

/* ================= POPUP ================= */
.details-popup{
    position:fixed;
    top:0;left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.6);
    display:none;
    align-items:center;
    justify-content:center;
    z-index:10000;
}

.popup-content{
    background:#fff;
    padding:26px;
    border-radius:16px;
    width:90%;
    max-width:480px;
    box-shadow:0 10px 40px rgba(0,0,0,0.2);
    text-align:center;
}

.popup-content h3{
    color:var(--primary);
    margin-bottom:18px;
    font-size:1.3rem;
}

.needed-box,
.cost-box{
    padding:12px;
    border-radius:10px;
    margin:12px 0;
    font-size:1.1rem;
    font-weight:600;
}

.needed-box{
    background:#fff4e5;
    color:#b15a00;
}

.cost-box{
    background:#e8f5e9;
    color:#1d7a32;
}

.close-btn{
    background:var(--red);
    color:white;
    border:none;
    padding:8px 18px;
    border-radius:8px;
    cursor:pointer;
    margin-top:8px;
    font-size:12px;
}

/* ================= MOBILE ================= */
@media(max-width:992px){
    th,td{
        font-size:11.5px;
        padding:7px 6px;
    }
}
</style>
</head>
<body>

<?php include('sidebar.php'); ?>

<div class="main-content">
    <h2><i class="fa-solid fa-warehouse"></i> Stock Availability</h2>

    <div class="card">
        <h3 style="color:var(--secondary);margin-bottom:20px;font-size:1.5rem;">
            <i class="fa-solid fa-clipboard-list"></i> Item-wise Stock Details
        </h3>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Current Stock</th>
                        <th>Minimum Limit</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($items as $it): 
                        $status = $it['Stock'] <= 0 ? 'red' : ($it['Stock'] <= $it['Min_Quantity'] ? 'orange' : 'green');
                        $statusText = $it['Stock'] <= 0 ? 'Out of Stock' : ($it['Stock'] <= $it['Min_Quantity'] ? 'Low Stock' : 'In Stock');
                    ?>
                    <tr>
                        <td style="text-align:left;font-weight:500;"><?= htmlspecialchars($it['Item_Name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><strong><?= (int)$it['Stock'] ?></strong></td>
                        <td><?= (int)$it['Min_Quantity'] ?></td>
                        <td><span class="badge badge-<?= $status ?>"><?= $statusText ?></span></td>
                        <td>
                            <?php if ($it['Needed'] > 0): ?>
                                <button class="btn-low" onclick="showDetails('<?= addslashes(htmlspecialchars($it['Item_Name'])) ?>', <?= (int)$it['Needed'] ?>, <?= (float)$it['Restock_Cost'] ?>)">
                                    <i class="fa-solid fa-exclamation-triangle"></i> Low Stock
                                </button>
                            <?php else: ?>
                                <span style="color:#28a745;">✓ Sufficient</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($items)): ?>
                    <tr><td colspan="5" style="padding:30px;color:#888;font-style:italic;">No items found in stock.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php $lowItems = array_filter($items, fn($i) => $i['Stock'] < $i['Min_Quantity']); ?>
    <?php if (!empty($lowItems)): ?>
    <div class="card">
        <h3 style="color:var(--red); font-size:1.4rem;">
            <i class="fa-solid fa-triangle-exclamation"></i> Low Stock Items Summary
        </h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Item</th><th>Stock</th><th>Minimum</th><th>Needed</th><th>Restock Cost (Rs.)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($lowItems as $low): ?>
                    <tr>
                        <td><?= htmlspecialchars($low['Item_Name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int)$low['Stock'] ?></td>
                        <td><?= (int)$low['Min_Quantity'] ?></td>
                        <td><?= (int)$low['Needed'] ?></td>
                        <td><?= number_format((float)$low['Restock_Cost'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="details-popup" id="detailsPopup">
    <div class="popup-content">
        <h3 id="popupItemName">Item Name</h3>
        <p>To reach minimum stock level:</p>
        <div class="needed-box"><i class="fa-solid fa-plus-circle"></i> <span id="neededQty">0</span> units needed</div>
        <div class="cost-box"><i class="fa-solid fa-money-bill-wave"></i> Estimated Cost: Rs. <span id="restockCost">0.00</span></div>
        <button class="close-btn" onclick="document.getElementById('detailsPopup').style.display='none'">Close</button>
    </div>
</div>

<script>
function showDetails(itemName, needed, cost) {
    document.getElementById('popupItemName').textContent = itemName;
    document.getElementById('neededQty').textContent = needed;
    document.getElementById('restockCost').textContent = Number(cost).toLocaleString('en-LK', {minimumFractionDigits: 2});
    document.getElementById('detailsPopup').style.display = 'flex';
}
document.getElementById('detailsPopup').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>

</body>
</html>