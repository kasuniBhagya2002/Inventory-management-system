<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

if (isset($_SESSION['ID'])) {
    session_regenerate_id(false);
}

include('connection.php');

/* ================= SESSION + ROLE CHECK ================= */
if (!isset($_SESSION['ID']) || $_SESSION['role'] !== 'Accountant') {
    header("Location: login.php");
    exit();
}

/* ================= DSID ================= */
$dsid = isset($_SESSION['dsid']) ? intval($_SESSION['dsid']) : 0;
if ($dsid <= 0) {
    die("DS office not assigned.");
}

$action = $_REQUEST['action'] ?? '';

/* =====================================================
   FETCH DATA (FIXED)
   ===================================================== */
if ($action === 'fetch_allocations') {
    header('Content-Type: application/json');

    $filter_oid = isset($_GET['officer_id']) ? intval($_GET['officer_id']) : 0;

    $sql = "
        SELECT 
            oa.officer_id,
            u.Position,
            s.section_name,
            oa.allocated_date,
            oa.final_amount AS total_allocated,
            oa.used_amount,
            (oa.final_amount - oa.used_amount) AS remaining,
            ar.amount AS received_amount
        FROM alloc_receive ar
        LEFT JOIN inventory_officer_allocations oa
            ON ar.inventory_allocation_id = oa.id
        LEFT JOIN user u
            ON oa.officer_id = u.ID
        LEFT JOIN section s
            ON u.section_id = s.section_id
        WHERE ar.matter_id = 21
          AND (oa.dsid = ? OR oa.dsid IS NULL)
    ";

    if ($filter_oid > 0) {
        $sql .= " AND (oa.officer_id = ? OR oa.officer_id IS NULL)";
    }

    $sql .= " ORDER BY ar.rid DESC";

    $st = $conn->prepare($sql);

    if ($filter_oid > 0) {
        $st->bind_param("ii", $dsid, $filter_oid);
    } else {
        $st->bind_param("i", $dsid);
    }

    $st->execute();
    $res = $st->get_result();

    $out = [];
    while ($r = $res->fetch_assoc()) {
        $r['Position'] = htmlspecialchars($r['Position'] ?? '');
        $r['section_name'] = htmlspecialchars($r['section_name'] ?? '');
        $out[] = $r;
    }

    echo json_encode($out);
    exit;
}

/* ================= PAGE LOAD ================= */
$ofs = $conn->prepare("
    SELECT ID, Name 
    FROM user 
    WHERE Role IN ('Officer','DS_Officer') 
      AND dsid = ?
    ORDER BY Name ASC
");
$ofs->bind_param("i", $dsid);
$ofs->execute();
$officers_res = $ofs->get_result();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Matter ID 21 Details</title>
<meta name="viewport" content="width=device-width,initial-scale=1">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
body { font-family: Poppins, sans-serif; background:#eef3ff; margin:0; }
.main-content { margin-left:260px; padding:30px; }

.white-card {
    background:#fff; border-radius:14px; padding:25px;
    box-shadow:0 6px 20px rgba(0,0,0,0.08);
}

.table-container { overflow:auto; margin-top:15px; }

table {
    width:100%;
    border-collapse:collapse;
}

thead {
    background:linear-gradient(90deg,#0062ff,#003b9a);
    color:white;
}

th,td {
    padding:12px;
    border-bottom:1px solid #eee;
}

tbody tr:hover { background:#f0f5ff; }

.not-alloc {
    color:red;
    font-weight:600;
}

.toolbar {
    margin-bottom:10px;
}

select {
    padding:10px;
    border-radius:8px;
    border:1px solid #ccc;
    cursor:pointer;
}
</style>
</head>

<body>

<?php include('sidebar.php'); ?>

<div class="main-content">
    <div class="white-card">

        <h2>📊 Matter ID 21 Details</h2>

        <div class="toolbar">
            <select id="filterOfficer">
                <option value="">-- All Officers --</option>
                <?php while ($r = $officers_res->fetch_assoc()): ?>
                    <option value="<?= (int)$r['ID'] ?>">
                        <?= htmlspecialchars($r['Name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="table-container">
            <table id="allocTable">
                <thead>
                    <tr>
                        <th>Position</th>
                        <th>Section</th>
                        <th>Received</th>
                        <th>Allocated</th>
                        <th>Used</th>
                        <th>Remaining</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

    </div>
</div>

<script>
async function loadAllocations(){
    const filter = document.getElementById('filterOfficer').value;
    const res = await fetch('?action=fetch_allocations&officer_id=' + filter);
    const rows = await res.json();

    const tbody = document.querySelector('#allocTable tbody');

    tbody.innerHTML = rows.length ? rows.map(r => `
        <tr>
            <td>${r.Position ? r.Position : '<span class="not-alloc">Not Allocated</span>'}</td>
            <td>${r.section_name ?? '-'}</td>
            <td>Rs. ${Number(r.received_amount ?? 0).toLocaleString(undefined,{minimumFractionDigits:2})}</td>
            <td>Rs. ${Number(r.total_allocated ?? 0).toLocaleString(undefined,{minimumFractionDigits:2})}</td>
            <td>Rs. ${Number(r.used_amount ?? 0).toLocaleString(undefined,{minimumFractionDigits:2})}</td>
            <td>Rs. ${Number(r.remaining ?? 0).toLocaleString(undefined,{minimumFractionDigits:2})}</td>
        </tr>
    `).join('')
    :
    '<tr><td colspan="6" style="text-align:center">No records</td></tr>';
}

document.getElementById('filterOfficer').addEventListener('change', loadAllocations);

loadAllocations();
</script>

</body>
</html>