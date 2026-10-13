<?php
// request_item.php
include('connection.php'); // Path updated
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Path updated
    exit();
}

$officer_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? '';


$allowed_roles = ['Officer', 'Accountant', 'DS_Officer'];
if (!in_array($role, $allowed_roles)) {
    header("Location: login.php");
    exit();
}

$dsid = $_SESSION['dsid'] ?? null;
if (!$dsid) {
    die("DS office not assigned to this user.");
}

// Role එක අනුව ආපසු යන dashboard ලින්ක් එක තීරණය කිරීම
// Updated to ensure both Accountant and DS_Officer are handled correctly
$dashboard_link = "officer_dashboard.php"; 
if ($role == 'Accountant') {
    $dashboard_link = "accountant_dashboard.php";
} elseif ($role == 'DS_Officer') {
    $dashboard_link = "ds_officer_dashboard.php";
}

/* -----------------------------------------------------
    Fetch Categories
----------------------------------------------------- */
$categories = $conn->query("SELECT Category_ID, Category_Name FROM inventory_categories ORDER BY Category_Name");

/* -----------------------------------------------------
    Fetch Items with Category
----------------------------------------------------- */
$stmtItems = $conn->prepare("
    SELECT i.*, c.Category_Name 
    FROM inventory_items i
    LEFT JOIN inventory_categories c ON i.Category_ID = c.Category_ID
    WHERE i.dsid = ?
");
$stmtItems->bind_param("i", $dsid);
$stmtItems->execute();
$items = $stmtItems->get_result();

/* -----------------------------------------------------
    AVAILABLE BALANCE
----------------------------------------------------- */
$sql1 = "SELECT COALESCE(SUM(final_amount), 0) AS total_alloc
         FROM inventory_officer_allocations WHERE officer_id = ?";
$stmt1 = $conn->prepare($sql1);
$stmt1->bind_param("i", $officer_id);
$stmt1->execute();
$total_alloc = (float)$stmt1->get_result()->fetch_assoc()['total_alloc'];

$sql2 = "
    SELECT COALESCE(SUM(ii.Quantity * it.Unit_Price), 0) AS used_total
    FROM inventory_issued_items ii
    JOIN inventory_items it ON ii.Item_ID = it.Item_ID
    WHERE ii.Officer_ID = ?
      AND it.dsid = ?
";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("ii", $officer_id, $dsid);
$stmt2->execute();
$used_total = (float)$stmt2->get_result()->fetch_assoc()['used_total'];

$remaining = max(0, $total_alloc - $used_total);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Select Item to Request</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
body { background:#eef3ff; font-family:Inter,sans-serif; }
.balance-box {
    background:#fff;padding:12px;border-radius:12px;
    box-shadow:0 3px 10px rgba(0,0,0,0.12);
    width:240px;float:left;margin-bottom:20px;
}
.balance-value { font-size:20px;font-weight:700;color:#0048b5; }
.card { border:none;border-radius:16px;box-shadow:0 4px 12px rgba(0,0,0,0.08); }
.card img { height:140px;object-fit:contain;background:#dde7ff;border-radius:16px 16px 0 0; }
.price { color:#0062ff;font-weight:bold; }
.btn-submit {
    background:linear-gradient(90deg,#0062ff,#003b9a);
    color:white;font-size:18px;padding:10px 28px;border-radius:10px;border:none;
}
/* Label click area */
.select-label {
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

/* Checkbox size */
.select-label input[type="checkbox"] {
    transform: scale(1.3);
    cursor: pointer;
}

/* Hover effect */
.select-label:hover input[type="checkbox"] {
    transform: scale(1.4);
}
</style>
</head>

<body>
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="fw-bold text-primary">🛒 Select Items (<?= htmlspecialchars(str_replace('_', ' ', $role)) ?>)</h3>
        <a href="<?= $dashboard_link ?>" class="btn btn-primary">← Back</a>
    </div>

    <div class="balance-box">
        <div>Available Balance</div>
        <div class="balance-value">Rs. <?= number_format($remaining,2) ?></div>
    </div>

    <div class="d-flex justify-content-end mb-3">
        <button type="submit" form="reqForm" class="btn-submit">Submit Request</button>
    </div>

    <div style="clear:both"></div>

    <div class="row mb-4">
        <div class="col-md-4">
            <select id="categoryFilter" class="form-select">
                <option value="">📂 All Categories</option>
                <?php while($c = $categories->fetch_assoc()): ?>
                    <option value="<?= $c['Category_ID'] ?>">
                        <?= htmlspecialchars($c['Category_Name'] ?? '') ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="col-md-8">
            <input type="text" id="searchInput" class="form-control" placeholder="🔍 Search items...">
        </div>
    </div>

    <form id="reqForm" action="submit_request.php" method="POST">
        <div class="row g-4" id="productContainer">

        <?php while($row = $items->fetch_assoc()):
            $img = ($row['Image'] && file_exists($row['Image']))
                    ? $row['Image'] : "uploads/no_image.png";
            
            $itemName = $row['Item_Name'] ?? '';
            $categoryName = $row['Category_Name'] ?? '';
            $dataName = strtolower($itemName);
        ?>
            <div class="col-6 col-md-3 product-card"
                 data-name="<?= $dataName ?>"
                 data-category="<?= $row['Category_ID'] ?>">

                <div class="card h-100">
                    <img src="<?= $img ?>">
                    <div class="card-body text-center">
                        <h6><?= htmlspecialchars($itemName) ?></h6>
                        <small class="text-muted"><?= htmlspecialchars($categoryName) ?></small>
                        <p class="price mt-1">Rs. <?= number_format($row['Unit_Price'],2) ?></p>

                        <label class="select-label">
    <input type="checkbox" name="item_ids[]" value="<?= $row['Item_ID'] ?>">
    Select
</label>
                        <input type="number" min="1" name="qty_<?= $row['Item_ID'] ?>"
                               class="form-control mt-2" placeholder="Qty">
                    </div>
                </div>
            </div>
        <?php endwhile; ?>

        </div>
    </form>
</div>

<script>
function filterItems(){
    let text = document.getElementById("searchInput").value.toLowerCase();
    let cat  = document.getElementById("categoryFilter").value;

    document.querySelectorAll(".product-card").forEach(card=>{
        let matchText = card.dataset.name.includes(text);
        let matchCat  = !cat || card.dataset.category === cat;
        card.style.display = (matchText && matchCat) ? "block" : "none";
    });
}

document.getElementById("searchInput").addEventListener("keyup", filterItems);
document.getElementById("categoryFilter").addEventListener("change", filterItems);
</script>
<script>
const params = new URLSearchParams(window.location.search);
const msg = params.get('msg');

if (msg) {
    Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: msg.replace(/\+/g, ' '),
        confirmButtonColor: '#0062ff'
    });

    // URL clean කරනවා
    window.history.replaceState({}, document.title, window.location.pathname);
}
</script>

</body>
</html>