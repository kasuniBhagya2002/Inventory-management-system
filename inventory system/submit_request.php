<?php
// submit_request.php
session_start();
include('connection.php');

/* ---------------- SESSION CHECK ---------------- */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$officer_id = $_SESSION['user_id'];
$role       = $_SESSION['role'] ?? '';
$dsid       = $_SESSION['dsid'] ?? null;

/* ❗ DS ID mandatory */
if (!$dsid) {
    die("<h2 style='color:red;text-align:center;margin-top:50px;'>
            ❌ DS Office not assigned to this user!
         </h2>");
}

/* ---------------- Redirect Dashboard ---------------- */
$redirect_dashboard = "officer_dashboard.php";
if ($role === 'Accountant') {
    $redirect_dashboard = "accountant_dashboard.php";
} elseif ($role === 'DS_Officer') {
    $redirect_dashboard = "ds_officer_dashboard.php";
}

/* ---------------- No item selected ---------------- */
if (!isset($_POST['item_ids']) || count($_POST['item_ids']) == 0) {
    die("<h2 style='color:red;text-align:center;margin-top:50px;'>
            ❌ You did not select any items!
         </h2>
         <p style='text-align:center;'>
            <a href='request_item.php' style='font-size:18px;color:blue;'>← Go Back</a>
         </p>");
}

/* ---------------- INSERT REQUESTS (DS WISE) ---------------- */
foreach ($_POST['item_ids'] as $item_id) {

    $item_id = intval($item_id);
    $qty_field = "qty_" . $item_id;

    if (!isset($_POST[$qty_field])) continue;

    $qty = intval($_POST[$qty_field]);
    if ($qty <= 0) continue;

    $stmt = $conn->prepare("
        INSERT INTO inventory_item_requests
        (Officer_ID, Item_ID, Quantity, Request_Date, Status, dsid)
        VALUES (?, ?, ?, NOW(), 'Pending', ?)
    ");
    $stmt->bind_param("iiii", $officer_id, $item_id, $qty, $dsid);
    $stmt->execute();
}

/* ---------------- Redirect ---------------- */
header("Location: request_item.php?msg=Request+submitted+successfully");
exit();
?>