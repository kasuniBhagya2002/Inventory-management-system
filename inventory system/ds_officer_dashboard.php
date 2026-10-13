<?php
// Secure session settings to prevent session hijacking
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

// Regenerate session ID to prevent session fixation
if (isset($_SESSION['ID'])) {
    session_regenerate_id(false);
}

include('connection.php');

/* ================= SESSION CHECK ================= */
if (!isset($_SESSION['ID']) || $_SESSION['role'] !== 'DS_Officer') {
    header("Location: login.php");
    exit();
}

// Ensure dsid is an integer to prevent injection
$dsid = intval($_SESSION['dsid']);
$name = $_SESSION['Name'];

/* ================= FETCH PENDING REQUESTS ================= */
$pending_sql = "
    SELECT ir.Request_ID, ir.Officer_ID, u.Name AS Officer_Name, 
            s.section_name AS Section, i.Item_Name, ir.Quantity, ir.Request_Date
    FROM inventory_item_requests ir
    JOIN user u ON ir.Officer_ID = u.ID
    LEFT JOIN section s ON u.section_id = s.section_id
    JOIN inventory_items i ON ir.Item_ID = i.Item_ID
    WHERE ir.Status = 'Pending'
      AND ir.dsid = ?
    ORDER BY ir.Request_Date DESC
";

$stmt = $conn->prepare($pending_sql);
$stmt->bind_param("i", $dsid);
$stmt->execute();
$pending = $stmt->get_result();

/* ================= FETCH HISTORY ================= */
$history_sql = "
    SELECT ir.Request_ID, u.Name AS Officer_Name, 
            i.Item_Name, ir.Quantity, ir.Status, ir.Request_Date
    FROM inventory_item_requests ir
    JOIN user u ON ir.Officer_ID = u.ID
    JOIN inventory_items i ON ir.Item_ID = i.Item_ID
    WHERE ir.Status IN ('Approved','Rejected')
      AND ir.dsid = ?
    ORDER BY ir.Request_Date DESC
";

$stmt2 = $conn->prepare($history_sql);
$stmt2->bind_param("i", $dsid);
$stmt2->execute();
$history = $stmt2->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DS Officer Dashboard | Inventory System</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
        --accent-gradient: linear-gradient(135deg, #7209b7 0%, #b5179e 100%);
        --body-bg: #f3f4f6;
        --text-dark: #1f2937;
        --text-light: #6b7280;
    }

    body {
        background-color: var(--body-bg);
        font-family: 'Poppins', sans-serif;
        color: var(--text-dark);
        padding-bottom: 40px;
    }

    .navbar {
        background: var(--primary-gradient);
        box-shadow: 0 4px 20px rgba(67, 97, 238, 0.2);
        padding: 15px 0;
    }
    
    .navbar-brand { font-weight: 700; font-size: 1.4rem; color: white !important; letter-spacing: 0.5px; }

    .user-badge {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(5px);
        padding: 8px 15px;
        border-radius: 30px;
        color: white;
        font-weight: 500;
        display: flex; align-items: center; gap: 10px;
    }

    .btn-logout {
        background: #fee2e2; color: #ef476f; border: none; border-radius: 30px;
        padding: 8px 20px; font-weight: 600; transition: 0.3s;
    }
    .btn-logout:hover { background: #ef476f; color: white; }

    .card {
        border: none; border-radius: 20px; background: white;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        overflow: hidden; transition: transform 0.3s ease;
    }

    /* --- BLINKING ANIMATION FOR PENDING CARD --- */
    @keyframes card-blink {
        0% { box-shadow: 0 10px 30px rgba(67, 97, 238, 0.1); border: 2px solid transparent; }
        50% { box-shadow: 0 0 20px rgba(67, 97, 238, 0.6); border: 2px solid #4361ee; }
        100% { box-shadow: 0 10px 30px rgba(67, 97, 238, 0.1); border: 2px solid transparent; }
    }

    .blink-card {
        animation: card-blink 2s infinite;
    }
    /* ------------------------------------------- */

    .card-header {
        background: white; border-bottom: 1px solid #f0f0f0; padding: 20px 25px;
        font-weight: 700; font-size: 1.1rem; display: flex; align-items: center; gap: 10px;
    }

    .card-header.pending-header { color: #4361ee; }
    .card-header.history-header { color: #7209b7; }

    .table thead th {
        background: #f8f9fa; color: var(--text-light); font-weight: 600;
        font-size: 0.85rem; text-transform: uppercase; padding: 15px 20px; border: none;
    }

    .table tbody td {
        padding: 15px 20px; vertical-align: middle;
        font-size: 0.95rem; border-bottom: 1px solid #f3f4f6;
    }

    .avatar-circle {
        width: 35px; height: 35px; background: #e0f2fe; color: #0284c7;
        border-radius: 50%; display: flex; align-items: center; justify-content: center;
        font-weight: bold; font-size: 0.9rem;
    }

    .status-badge { padding: 6px 12px; border-radius: 50px; font-size: 0.8rem; font-weight: 600; }
    .status-Pending { background: #fff7ed; color: #ea580c; border: 1px solid #ffedd5; }
    .status-Approved { background: #ecfdf5; color: #059669; border: 1px solid #d1fae5; }
    .status-Rejected { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; }

    .btn-action {
        width: 35px; height: 35px; border-radius: 10px; border: none;
        display: inline-flex; align-items: center; justify-content: center;
        transition: 0.2s; margin-right: 5px; cursor: pointer;
    }
    .btn-action:hover { transform: translateY(-2px); }
    
    .btn-view { background: #e0f2fe; color: #0284c7; }
    .btn-view:hover { background: #0284c7; color: white; }
    
    .btn-approve { background: #dcfce7; color: #16a34a; }
    .btn-approve:hover { background: #16a34a; color: white; }
    
    .btn-reject { background: #fee2e2; color: #dc2626; }
    .btn-reject:hover { background: #dc2626; color: white; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark mb-5">
  <div class="container">
    <a class="navbar-brand" href="#"><i class="fa-solid fa-file-signature me-2"></i>DS Portal</a>
    <div class="d-flex align-items-center gap-3">
  <div class="user-badge">
    <i class="fa-solid fa-user-tie"></i>
    <span><?php echo htmlspecialchars($name); ?></span>
  </div>

  <!-- ✅ NEW : REQUEST BUTTON -->
  <a href="request_item.php" class="btn btn-light rounded-pill fw-semibold">
    <i class="fa-solid fa-cart-plus me-1 text-primary"></i> Request
  </a>

  <form action="logout.php" method="POST" class="m-0">
    <button class="btn-logout">
      <i class="fa-solid fa-power-off me-1"></i> Logout
    </button>
  </form>
</div>
  </div>
</nav>

<div class="container">
  <div class="card mb-5 <?php if ($pending->num_rows > 0) echo 'blink-card'; ?>">
    <div class="card-header pending-header">
      <i class="fa-solid fa-hourglass-half fs-5"></i> Pending Approvals
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Officer Details</th>
              <th>Section</th>
              <th>Item Requested</th>
              <th class="text-center">Qty</th>
              <th class="text-end">Requested Date</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($pending->num_rows > 0) { 
              while ($row = $pending->fetch_assoc()) { ?>
              <tr>
                <td>
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-circle">
                            <?= substr(htmlspecialchars($row['Officer_Name']), 0, 1) ?>
                        </div>
                        <span class="fw-semibold"><?= htmlspecialchars($row['Officer_Name']) ?></span>
                    </div>
                </td>
                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['Section'] ?? 'General') ?></span></td>
                <td class="text-primary fw-medium"><?= htmlspecialchars($row['Item_Name']) ?></td>
                <td class="text-center fw-bold"><?= intval($row['Quantity']) ?></td>
                <td class="text-end text-muted"><?= date('M d, Y', strtotime($row['Request_Date'])) ?></td>
                <td class="text-center">
                  <button class="btn-action btn-view" onclick="showOfficerInfo(<?= intval($row['Officer_ID']) ?>)" title="View Details">
                    <i class="fa-solid fa-eye"></i>
                  </button>
                  
                  <form action="approve_request.php" method="POST" class="d-inline approval-form" id="form-<?= intval($row['Request_ID']) ?>">
                    <input type="hidden" name="request_id" value="<?= intval($row['Request_ID']) ?>">
                    <input type="hidden" name="action" id="action-<?= intval($row['Request_ID']) ?>" value="">
                    
                    <button type="button" class="btn-action btn-approve" onclick="confirmAction(<?= intval($row['Request_ID']) ?>, 'Approved')" title="Approve">
                      <i class="fa-solid fa-check"></i>
                    </button>
                    
                    <button type="button" class="btn-action btn-reject" onclick="confirmAction(<?= intval($row['Request_ID']) ?>, 'Rejected')" title="Reject">
                      <i class="fa-solid fa-xmark"></i>
                    </button>
                  </form>
                </td>
              </tr>
            <?php }} else { ?>
              <tr><td colspan="6" class="text-center py-5 text-muted"><i class="fa-solid fa-folder-open fa-2x mb-3 d-block"></i>No pending requests found.</td></tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header history-header">
      <i class="fa-solid fa-clock-rotate-left fs-5"></i> Approval History
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Officer Name</th>
              <th>Item Requested</th>
              <th class="text-center">Qty</th>
              <th class="text-center">Status</th>
              <th class="text-end">Processed Date</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($history->num_rows > 0) {
              while ($row = $history->fetch_assoc()) { ?>
              <tr>
                <td class="fw-medium"><?= htmlspecialchars($row['Officer_Name']) ?></td>
                <td class="text-dark"><?= htmlspecialchars($row['Item_Name']) ?></td>
                <td class="text-center fw-bold"><?= intval($row['Quantity']) ?></td>
                <td class="text-center">
                    <span class="status-badge status-<?= htmlspecialchars($row['Status']) ?>"><?= htmlspecialchars($row['Status']) ?></span>
                </td>
                <td class="text-end text-muted"><?= date('M d, Y', strtotime($row['Request_Date'])) ?></td>
              </tr>
            <?php }} else { ?>
              <tr><td colspan="5" class="text-center py-4 text-muted">No history available.</td></tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="infoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
      <div class="modal-header bg-light border-bottom-0">
        <h5 class="modal-title fw-bold text-primary"><i class="fa-solid fa-id-card me-2"></i>Officer Profile</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4" id="officerDetails">
        <div class="d-flex justify-content-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showOfficerInfo(id) {
  fetch('officer_info.php?id=' + encodeURIComponent(id))
    .then(res => res.text())
    .then(data => {
      document.getElementById('officerDetails').innerHTML = data;
      new bootstrap.Modal(document.getElementById('infoModal')).show();
    });
}

function confirmAction(requestId, actionType) {
    let titleText = actionType === 'Approved' ? 'Approve Request?' : 'Reject Request?';
    let confirmBtnColor = actionType === 'Approved' ? '#16a34a' : '#dc2626';
    let iconType = actionType === 'Approved' ? 'question' : 'warning';

    Swal.fire({
        title: titleText,
        text: "You are about to " + actionType.toLowerCase() + " this request.",
        icon: iconType,
        showCancelButton: true,
        confirmButtonColor: confirmBtnColor,
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, ' + actionType + ' it!',
        cancelButtonText: 'Cancel (Undo)'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('action-' + requestId).value = actionType;
            document.getElementById('form-' + requestId).submit();
        }
    });
}
</script>

</body>
</html>