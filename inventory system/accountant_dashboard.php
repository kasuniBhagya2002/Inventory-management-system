<?php
ob_start();
session_start();

// Session fixation security
if (isset($_SESSION['ID'])) {
    session_regenerate_id(true);
}

include('connection.php');

/* ================= SESSION CHECK ================= */
if (!isset($_SESSION['ID'])) {
    header("Location: login.php");
    exit();
}

/* ================= ROLE CHECK ================= */
$allowed_roles = ['Accountant', 'acc_user', 'Developer'];
if (isset($_SESSION['role']) && !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: select_action.php"); 
    exit();
}

/* ================= DSID (IMPORTANT) ================= */
$dsid = $_SESSION['dsid'] ?? null;
if (!$dsid) {
    die("Error: DS Division not assigned to this user.");
}

/* ================= USER INFO ================= */
$name  = $_SESSION['Name'] ?? $_SESSION['name'] ?? 'Accountant';
$photo = $_SESSION['Photo'] ?? null;

/* ================= FETCH USER LOCATION DETAILS ================= */
$user_location = [
    'province' => 'N/A',
    'district' => 'N/A',
    'ds_division' => 'N/A',
    'section' => 'N/A'
];

$loc_stmt = $conn->prepare("
    SELECT 
        p.province_name,
        d.district_name,
        ds.dsname,
        s.section_name
    FROM user u
    LEFT JOIN provinces p ON u.Province_id = p.province_id
    LEFT JOIN district d ON u.district_id = d.district_id
    LEFT JOIN dsdivision ds ON u.dsid = ds.dsid
    LEFT JOIN section s ON u.section_id = s.section_id
    WHERE u.ID = ?
");
$loc_stmt->bind_param("i", $_SESSION['ID']);
$loc_stmt->execute();
$loc_res = $loc_stmt->get_result();
if ($loc_row = $loc_res->fetch_assoc()) {
    $user_location = [
        'province' => $loc_row['province_name'] ?? 'N/A',
        'district' => $loc_row['district_name'] ?? 'N/A',
        'ds_division' => $loc_row['dsname'] ?? 'N/A',
        'section' => $loc_row['section_name'] ?? 'N/A'
    ];
}
$loc_stmt->close();

/* ================= HELPER (SECURE SQL EXECUTION) ================= */
function db_scalar($conn, $sql, $params = []) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) return 0;

    if (!empty($params)) {
        $types = "";
        foreach($params as $param) {
            if(is_int($param)) $types .= 'i';
            elseif(is_double($param)) $types .= 'd';
            else $types .= 's';
        }
        $stmt->bind_param($types, ...$params);
    }

    if ($stmt->execute()) {
        $res = $stmt->get_result();
        $row = $res->fetch_row();
        return $row ? $row[0] : 0;
    }
    return 0;
}

/* ================= DASHBOARD DATA (DS WISE) ================= */
$total_users = (int)db_scalar($conn, "SELECT COUNT(*) FROM user WHERE dsid = ?", [$dsid]);
$total_items = (int)db_scalar($conn, "SELECT COUNT(*) FROM inventory_items WHERE dsid = ?", [$dsid]);
$pending_approvals = (int)db_scalar($conn, "SELECT COUNT(*) FROM inventory_item_requests WHERE Status = 'Pending' AND dsid = ?", [$dsid]);
$pending_requests = (int)db_scalar($conn, "SELECT COUNT(DISTINCT ir.Request_ID) FROM inventory_item_requests ir LEFT JOIN inventory_issued_items ii ON ir.Request_ID = ii.Request_ID WHERE ir.Status = 'Approved' AND ir.dsid = ? AND ii.Request_ID IS NULL", [$dsid]);
$approved_requests = (int)db_scalar($conn, "SELECT COUNT(DISTINCT ir.Request_ID) FROM inventory_item_requests ir INNER JOIN inventory_issued_items ii ON ir.Request_ID = ii.Request_ID WHERE ir.Status = 'Approved' AND ir.dsid = ?", [$dsid]);
$total_stock_value = (float)db_scalar($conn, "SELECT IFNULL(SUM(Unit_Price * Quantity),0) FROM inventory_items WHERE dsid = ?", [$dsid]);
$low_stock_count = (int)db_scalar($conn, "SELECT COUNT(*) FROM inventory_items WHERE dsid = ? AND Quantity <= Min_Quantity", [$dsid]);

/* ================= AJAX (JSON ENDPOINTS) ================= */
$action = $_GET['action'] ?? '';
if ($action) {
    header('Content-Type: application/json');
    if ($action === 'get_stock') {
        $stmt = $conn->prepare("SELECT Item_Name, Quantity, Unit_Price FROM inventory_items WHERE dsid = ? ORDER BY Quantity DESC LIMIT 15");
        $stmt->bind_param("i", $dsid);
        $stmt->execute();
        $res = $stmt->get_result();
        $labels = $values = $rows = [];
        while ($r = $res->fetch_assoc()) {
            $safe_name = htmlspecialchars($r['Item_Name'] ?? '', ENT_QUOTES, 'UTF-8');
            $labels[] = $safe_name;
            $values[] = (int)$r['Quantity'];
            $rows[]   = ['Item_Name' => $safe_name, 'Quantity' => (int)$r['Quantity'], 'Unit_Price' => (float)$r['Unit_Price']];
        }
        echo json_encode(['labels'=>$labels, 'values'=>$values, 'rows'=>$rows]);
        exit;
    }

    if ($action === 'get_issued') {
        $stmt = $conn->prepare("SELECT DATE_FORMAT(ii.Issue_Date,'%Y-%m') ym, IFNULL(SUM(ii.Quantity * i.Unit_Price),0) value FROM inventory_issued_items ii JOIN inventory_items i ON ii.Item_ID = i.Item_ID WHERE ii.dsid = ? GROUP BY ym ORDER BY ym DESC LIMIT 12");
        $stmt->bind_param("i", $dsid);
        $stmt->execute();
        $res = $stmt->get_result();
        $labels = $values = [];
        while ($r = $res->fetch_assoc()) {
            $labels[] = htmlspecialchars($r['ym'] ?? '', ENT_QUOTES, 'UTF-8');
            $values[] = (float)$r['value'];
        }
        echo json_encode(['labels' => array_reverse($labels), 'values' => array_reverse($values)]);
        exit;
    }

    if ($action === 'get_requests') {
        $stmt = $conn->prepare("SELECT Status, COUNT(*) c FROM inventory_item_requests WHERE dsid = ? GROUP BY Status");
        $stmt->bind_param("i", $dsid);
        $stmt->execute();
        $res = $stmt->get_result();
        $map = ['Pending'=>0,'Approved'=>0,'Rejected'=>0];
        while ($r = $res->fetch_assoc()) {
            if(isset($map[$r['Status']])) $map[$r['Status']] = (int)$r['c'];
        }
        echo json_encode($map);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accountant Dashboard | Inventory System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0"></script>
    <style>
        :root { --primary: #4361ee; --secondary: #3f37c9; --success: #06d6a0; --warning: #f72585; --dark: #2b2d42; --light: #f8f9fa; --card-bg: #ffffff; --bg-body: #f3f4f6; --sidebar-width: 280px; --sidebar-collapsed-width: 88px; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-body); color: var(--dark); overflow-x: hidden; min-height: 100vh; }
        
        /* Layout */
        #sidebar { position: fixed; top: 0; left: 0; width: var(--sidebar-width); height: 100vh; z-index: 1030; transition: width 0.4s ease; overflow-y: auto; overflow-x: hidden; }
        #sidebar.collapsed { width: var(--sidebar-collapsed-width); }
        .main-content { margin-left: var(--sidebar-width); transition: margin-left 0.4s ease; min-height: 100vh; }
        .main-content.sidebar-collapsed { margin-left: var(--sidebar-collapsed-width); }
        .navbar { background: linear-gradient(135deg, var(--primary), var(--secondary)); box-shadow: 0 4px 20px rgba(67, 97, 238, 0.2); padding: 12px 0; z-index: 1020; position: fixed; top: 0; left: var(--sidebar-width); right: 0; transition: left 0.4s ease; }
        .navbar.sidebar-collapsed { left: var(--sidebar-collapsed-width); }
        .navbar-brand { font-weight: 700; font-size: 1.4rem; color: white !important; letter-spacing: 0.5px; }

        /* Small Cards Styling */
        .summary-card { 
            background: var(--card-bg); 
            border-radius: 12px; 
            padding: 15px 20px; 
            border: none; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.05); 
            transition: all 0.3s ease; 
            height: 100%; 
            display: flex; 
            align-items: center; 
            text-decoration: none; 
            min-height: 80px; 
        }
        .summary-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(67, 97, 238, 0.12); }
        .card-icon { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; margin-right: 15px; flex-shrink: 0; transition: transform 0.3s ease; }
        .card-content { flex: 1; }
        .card-title { font-size: 0.75rem; color: #8d99ae; font-weight: 600; text-transform: uppercase; margin-bottom: 2px; }
        .card-value { font-size: 1.2rem; font-weight: 700; color: var(--dark); line-height: 1.1; }

        /* Alert Animations */
        @keyframes pulse-red { 0% { box-shadow: 0 0 0 0 rgba(247, 37, 133, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(247, 37, 133, 0); } 100% { box-shadow: 0 0 0 0 rgba(247, 37, 133, 0); } }
        @keyframes pulse-orange { 0% { box-shadow: 0 0 0 0 rgba(255, 159, 64, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(255, 159, 64, 0); } 100% { box-shadow: 0 0 0 0 rgba(255, 159, 64, 0); } }
        .card-alert { border-left: 4px solid var(--warning); animation: pulse-red 2s infinite; }
        .card-blink-orange { border-left: 4px solid #ff9f40; animation: pulse-orange 2s infinite; }

        /* Icon Colors */
        .icon-blue { background: rgba(67, 97, 238, 0.1); color: var(--primary); }
        .icon-green { background: rgba(6, 214, 160, 0.1); color: var(--success); }
        .icon-pink { background: rgba(247, 37, 133, 0.1); color: var(--warning); }
        .icon-purple { background: rgba(114, 9, 183, 0.1); color: #7209b7; }
        .icon-orange { background: rgba(255, 159, 64, 0.1); color: #ff9f40; }

        /* Panels and Utils */
        .user-location-panel { background: #fff; border-radius: 12px; padding: 15px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); border: 1px solid #f0f0f0; }
        .location-item { border-right: 1px solid #eee; }
        .location-item:last-child { border-right: none; }
        .location-label { font-size: 0.65rem; color: #999; text-transform: uppercase; font-weight: 600; }
        .location-value { font-size: 0.85rem; font-weight: 600; color: var(--dark); }
        .chart-card { background: var(--card-bg); border-radius: 15px; padding: 20px; box-shadow: 0 6px 15px rgba(0,0,0,0.04); height: 100%; border: 1px solid #edf2f7; }
        .chart-header { font-weight: 600; margin-bottom: 15px; color: var(--dark); font-size: 1rem; border-bottom: 1px solid #f1f1f1; padding-bottom: 10px; }
        .chart-container { position: relative; height: 220px; width: 100%; }
        .content-area { padding-top: 85px; padding-left: 1.5rem; padding-right: 1.5rem; max-width: 1400px; margin: 0 auto; }
        .nav-btn { background: rgba(255, 255, 255, 0.15); color: white !important; border-radius: 30px; padding: 6px 15px; text-decoration: none; font-size: 0.85rem; }
        .profile-img-sm { width: 32px; height: 32px; border-radius: 50%; }
    </style>
</head>
<body>

<?php include('sidebar.php'); ?>

<div class="main-content" id="mainContent">
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><i class="fa-solid fa-chart-line me-2"></i>Dashboard</a>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <div class="d-flex align-items-center gap-2">
                    <div class="d-flex align-items-center text-white me-2 d-none d-md-flex small">
                        <?php if ($photo && file_exists($photo)): ?>
                            <img src="<?= htmlspecialchars($photo) ?>" class="profile-img-sm me-2" alt="Profile">
                        <?php else: ?>
                            <div class="profile-img-sm d-flex align-items-center justify-content-center bg-white text-primary me-2"><i class="fa-solid fa-user"></i></div>
                        <?php endif; ?>
                        <span><strong><?= htmlspecialchars($name) ?></strong></span>
                    </div>
                    <a href="request_item.php" class="nav-btn"><i class="fa-solid fa-cart-plus me-1"></i> Request</a>
                    <a href="logout.php" class="nav-btn bg-danger"><i class="fa-solid fa-right-from-bracket"></i></a>
                </div>
            </div>
        </div>
    </nav>

    <div class="content-area">
        <div class="user-location-panel">
            <div class="row text-center g-0">
                <div class="col-3 location-item">
                    <div class="location-label">Province</div>
                    <div class="location-value text-truncate"><?= htmlspecialchars($user_location['province']) ?></div>
                </div>
                <div class="col-3 location-item">
                    <div class="location-label">District</div>
                    <div class="location-value text-truncate"><?= htmlspecialchars($user_location['district']) ?></div>
                </div>
                <div class="col-3 location-item">
                    <div class="location-label">Division</div>
                    <div class="location-value text-truncate"><?= htmlspecialchars($user_location['ds_division']) ?></div>
                </div>
                <div class="col-3 location-item">
                    <div class="location-label">Section</div>
                    <div class="location-value text-truncate"><?= htmlspecialchars($user_location['section']) ?></div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4 col-xl-3">
                <a href="view_request.php?status=Pending" class="summary-card <?= $pending_approvals > 0 ? 'card-blink-orange' : '' ?>">
                    <div class="card-icon icon-orange"><i class="fa-solid fa-hourglass-half"></i></div>
                    <div class="card-content">
                        <div class="card-title">Pending</div>
                        <div class="card-value"><?= $pending_approvals ?></div>
                    </div>
                </a>
            </div>
            <div class="col-md-4 col-xl-3">
                <a href="view_request.php?status=Approved" class="summary-card <?= $pending_requests > 0 ? 'card-alert' : '' ?>">
                    <div class="card-icon icon-pink"><i class="fa-solid fa-boxes-packing"></i></div>
                    <div class="card-content">
                        <div class="card-title">Approved</div>
                        <div class="card-value"><?= $pending_requests ?></div>
                    </div>
                </a>
            </div>
            <div class="col-md-4 col-xl-3">
                <a href="stock_availability.php" class="summary-card">
                    <div class="card-icon icon-green"><i class="fa-solid fa-sack-dollar"></i></div>
                    <div class="card-content">
                        <div class="card-title">Stock Value</div>
                        <div class="card-value">Rs.<?= number_format($total_stock_value, 0) ?></div>
                    </div>
                </a>
            </div>
            <div class="col-md-4 col-xl-3">
                <a href="low_stock_report.php" class="summary-card <?= $low_stock_count > 0 ? 'card-alert' : '' ?>">
                    <div class="card-icon icon-pink"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <div class="card-content">
                        <div class="card-title">Low Stock</div>
                        <div class="card-value"><?= $low_stock_count ?></div>
                    </div>
                </a>
            </div>
            <div class="col-md-4 col-xl-3">
                <a href="view_request.php?status=Issued" class="summary-card">
                    <div class="card-icon icon-blue"><i class="fa-solid fa-check-double"></i></div>
                    <div class="card-content">
                        <div class="card-title">Issued</div>
                        <div class="card-value"><?= $approved_requests ?></div>
                    </div>
                </a>
            </div>
            <div class="col-md-4 col-xl-3">
                <a href="manage_items.php" class="summary-card">
                    <div class="card-icon icon-purple"><i class="fa-solid fa-cubes"></i></div>
                    <div class="card-content">
                        <div class="card-title">Items</div>
                        <div class="card-value"><?= $total_items ?></div>
                    </div>
                </a>
            </div>
            <div class="col-md-4 col-xl-3">
                <a href="manage_users.php" class="summary-card">
                    <div class="card-icon icon-blue"><i class="fa-solid fa-users"></i></div>
                    <div class="card-content">
                        <div class="card-title">Users</div>
                        <div class="card-value"><?= $total_users ?></div>
                    </div>
                </a>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-4"><div class="chart-card"><div class="chart-header">Stock Distribution</div><div class="chart-container"><canvas id="stockChart"></canvas></div></div></div>
            <div class="col-lg-4"><div class="chart-card"><div class="chart-header">Requests</div><div class="chart-container"><canvas id="requestsChart"></canvas></div></div></div>
            <div class="col-lg-4"><div class="chart-card"><div class="chart-header">Issued History</div><div class="chart-container"><canvas id="issuedChart"></canvas></div></div></div>
        </div>

        <div class="row pb-4">
            <div class="col-12">
                <div class="chart-card">
                    <div class="chart-header d-flex justify-content-between">
                        <span>Top Stock Items</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom table-sm" id="stockTable">
                            <thead><tr><th>Item Name</th><th class="text-end">Qty</th><th class="text-end">Price</th></tr></thead>
                            <tbody class="small"><tr><td colspan="3" class="text-center">Loading...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const navbar = document.querySelector('.navbar');

    function updateLayout() {
        if (sidebar && sidebar.classList.contains('collapsed')) {
            mainContent.classList.add('sidebar-collapsed');
            navbar.classList.add('sidebar-collapsed');
        } else {
            mainContent.classList.remove('sidebar-collapsed');
            navbar.classList.remove('sidebar-collapsed');
        }
    }
    updateLayout();
    const observer = new MutationObserver(updateLayout);
    if (sidebar) observer.observe(sidebar, { attributes: true, attributeFilter: ['class'] });
});

async function loadDashboardData() {
    try {
        const stockRes = await fetch('?action=get_stock');
        const stock = await stockRes.json();
        
        if(stock.rows) {
            document.getElementById('stockTable').querySelector('tbody').innerHTML = stock.rows.slice(0, 8).map(r => `
                <tr>
                    <td>${r.Item_Name}</td>
                    <td class="text-end"><b>${r.Quantity}</b></td>
                    <td class="text-end text-primary">Rs.${Number(r.Unit_Price).toLocaleString()}</td>
                </tr>
            `).join('');
        }

        new Chart(document.getElementById('stockChart'), {
            type: 'doughnut',
            data: { labels: stock.labels.slice(0,5), datasets: [{ data: stock.values.slice(0,5), backgroundColor: ['#4361ee', '#3f37c9', '#4895ef', '#4cc9f0', '#f72585'], borderWidth: 0 }] },
            options: { maintainAspectRatio: false, cutout: '75%', plugins: { legend: { display: false } } }
        });

        const reqRes = await fetch('?action=get_requests');
        const req = await reqRes.json();
        new Chart(document.getElementById('requestsChart'), {
            type: 'doughnut',
            data: { labels: ['Pending', 'Approved', 'Rejected'], datasets: [{ data: [req.Pending||0, req.Approved||0, req.Rejected||0], backgroundColor: ['#ff9f40', '#06d6a0', '#ef476f'], borderWidth: 0 }] },
            options: { maintainAspectRatio: false, cutout: '75%', plugins: { legend: { display: false } } }
        });

        const issuedRes = await fetch('?action=get_issued');
        const issued = await issuedRes.json();
        new Chart(document.getElementById('issuedChart'), {
            type: 'bar',
            data: { labels: issued.labels, datasets: [{ label: 'Issued Value', data: issued.values, backgroundColor: '#4361ee', borderRadius: 4 }] },
            options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { ticks: { font: { size: 9 } } }, y: { ticks: { font: { size: 9 } } } } }
        });

    } catch (error) { console.error("Error:", error); }
}
document.addEventListener('DOMContentLoaded', loadDashboardData);
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>