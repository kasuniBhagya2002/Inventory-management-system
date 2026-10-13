<?php
// accountant/issue_item.php -> (Folder ඉවත් කර ඇත)
session_start();

// ආරක්ෂක පියවර 1: Clickjacking වැළැක්වීම (X-Frame-Options)
header("X-Frame-Options: DENY");

include('connection.php');

// --- SESSION CHECK ---
// ආරක්ෂක පියවර 2: දැඩි Session පරීක්ෂාව
if (!isset($_SESSION['ID']) || $_SESSION['role'] !== 'Accountant') {
    header("Location: login.php");
    exit();
}

// ආරක්ෂක පියවර 3: ඉල්ලීම POST ද යන්න තහවුරු කිරීම
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: accountant_dashboard.php");
    exit();
}

// --- CSRF PROTECTION (ඉතා වැදගත්) ---
// ආරක්ෂක පියවර 4: CSRF Token පරීක්ෂාව
// මෙය ක්‍රියාත්මක වීමට ඔබේ Form එකේ 'csrf_token' නමින් hidden field එකක් තිබිය යුතුය.
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    // සැක සහිත ඉල්ලීමක් නම් නවත්වන්න
    error_log("CSRF attack attempt detected from User ID: " . $_SESSION['ID']);
    $_SESSION['msg_error'] = "Security Token Invalid. Please try again.";
    header("Location: accountant_dashboard.php");
    exit();
}

// Input Validation (දැනටමත් තිබූ හොඳ පුරුද්දක්, මෙය එලෙසම තබමු)
$request_id = intval($_POST['request_id'] ?? 0);
$officer_id = intval($_POST['officer_id'] ?? 0);
$item_id = intval($_POST['item_id'] ?? 0);
$issue_qty = intval($_POST['issue_qty'] ?? 0);

if ($request_id <= 0 || $officer_id <= 0 || $item_id <= 0 || $issue_qty <= 0) {
    $_SESSION['msg'] = "Invalid data submitted.";
    header("Location: accountant_dashboard.php");
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // 1) Check current stock and requested request existence
    $stmt = $conn->prepare("SELECT Quantity, Unit_Price FROM inventory_items WHERE Item_ID = ? FOR UPDATE");
    if (!$stmt) throw new Exception("Database prepare failed."); // Internal checks
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows === 0) throw new Exception("Item not found.");
    
    $item = $res->fetch_assoc();
    $stock_qty = (int)$item['Quantity'];
    $unit_price = (float)$item['Unit_Price'];

    if ($issue_qty > $stock_qty) throw new Exception("Not enough stock to issue.");

    // 2) Insert into inventory_issued_items
    $stmt2 = $conn->prepare("INSERT INTO inventory_issued_items (Request_ID, Officer_ID, Item_ID, Quantity) VALUES (?, ?, ?, ?)");
    if (!$stmt2) throw new Exception("Database prepare failed.");
    $stmt2->bind_param("iiii", $request_id, $officer_id, $item_id, $issue_qty);
    $stmt2->execute();

    // 3) Decrease stock in inventory_items table
    $new_stock = $stock_qty - $issue_qty;
    $stmt3 = $conn->prepare("UPDATE inventory_items SET Quantity = ? WHERE Item_ID = ?");
    if (!$stmt3) throw new Exception("Database prepare failed.");
    $stmt3->bind_param("ii", $new_stock, $item_id);
    $stmt3->execute();

    // 4) Update officer_limit.used_amount
    $issue_value = $unit_price * $issue_qty;

    $stmt4 = $conn->prepare("SELECT Limit_ID, Used_Amount FROM officer_limit WHERE Officer_ID = ? FOR UPDATE");
    if (!$stmt4) throw new Exception("Database prepare failed.");
    $stmt4->bind_param("i", $officer_id);
    $stmt4->execute();
    $res4 = $stmt4->get_result();

    if ($res4->num_rows > 0) {
        $ol = $res4->fetch_assoc();
        $limit_id = (int)$ol['Limit_ID'];
        $old_used = (float)$ol['Used_Amount'];
        $new_used = $old_used + $issue_value;
        $stmt5 = $conn->prepare("UPDATE officer_limit SET Used_Amount = ? WHERE Limit_ID = ?");
        if (!$stmt5) throw new Exception("Database prepare failed.");
        $stmt5->bind_param("di", $new_used, $limit_id);
        $stmt5->execute();
    } else {
        $stmt6 = $conn->prepare("INSERT INTO officer_limit (Officer_ID, Annual_Limit, Used_Amount) VALUES (?, ?, ?)");
        if (!$stmt6) throw new Exception("Database prepare failed.");
        $zero = 0.00;
        $stmt6->bind_param("idd", $officer_id, $zero, $issue_value);
        $stmt6->execute();
    }

    $conn->commit();
    $_SESSION['msg_success'] = "Issued successfully. Stock and officer usage updated.";
    
    header("Location: accountant_dashboard.php");
    exit();

} catch (Exception $ex) {
    $conn->rollback();
    
    // ආරක්ෂක පියවර 5: Error Message Hiding (Information Disclosure වැළැක්වීම)
    // සැබෑ දෝෂය User ට නොපෙන්වා Server එකේ log කරන්න.
    error_log("Issue Item Error: " . $ex->getMessage());
    
    // User ට සාමාන්‍ය පණිවිඩයක් පමණක් යවන්න. (Database structure එක එළියට යන්න දෙන්න එපා)
    if ($ex->getMessage() === "Not enough stock to issue." || $ex->getMessage() === "Item not found.") {
         $_SESSION['msg_error'] = $ex->getMessage(); // Logic සම්බන්ධ දෝෂ පෙන්වීම ගැටලුවක් නැත
    } else {
         $_SESSION['msg_error'] = "An internal error occurred while processing. Please contact support.";
    }
    
    header("Location: accountant_dashboard.php");
    exit();
}
?>