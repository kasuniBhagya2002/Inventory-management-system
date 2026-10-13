<?php
// Session ආරක්ෂාව සඳහා cookie settings (පළමුවෙන්ම තිබිය යුතුය)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

// Session hijacking වැළැක්වීමට session ID එක අලුත් කිරීම
if (isset($_SESSION['ID'])) {
    session_regenerate_id(false);
}

include('connection.php');

/* ================= SESSION CHECK ================= */
// DS_Officer කෙනෙක් බව සහ session එක පවතින බව තහවුරු කිරීම
if (!isset($_SESSION['ID']) || $_SESSION['role'] !== 'DS_Officer') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Input දත්ත ලබා ගැනීම සහ මූලික පිරිසිදු කිරීම
    $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
    $action     = isset($_POST['action']) ? $_POST['action'] : ''; 
    
    // Whitelisting: Action එක Approved හෝ Rejected පමණක් බව පරීක්ෂා කිරීම (Security check)
    $allowed_actions = ["Approved", "Rejected"];
    if (!in_array($action, $allowed_actions)) {
        header("Location: ds_officer_dashboard.php?msg=Invalid+Action");
        exit();
    }

    // Session variables (පිටතින් වෙනස් කළ නොහැකි ලෙස session එකෙන් කෙලින්ම ලබා ගැනීම)
    $ds_id      = $_SESSION['ID'];
    $ds_name    = $_SESSION['Name']; // Database එකට යන බැවින් පසුව bind_param හරහා ආරක්ෂා කෙරේ

    /* ---------------------------------------------------------
        STEP 1: Fetch Request Info (Officer + Qty + Item Price)
    ----------------------------------------------------------*/
    $sql = "
        SELECT ir.Officer_ID, i.Unit_Price, ir.Quantity
        FROM inventory_item_requests ir
        JOIN inventory_items i ON ir.Item_ID = i.Item_ID
        WHERE ir.Request_ID = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();

    if (!$req) {
        header("Location: ds_officer_dashboard.php?msg=Invalid+Request");
        exit();
    }

    $officer_id = $req['Officer_ID'];
    $cost = floatval($req['Unit_Price']) * intval($req['Quantity']); 

    /* ---------------------------------------------------------
        STEP 2: Update inventory_item_requests Table
    ----------------------------------------------------------*/
    $update = $conn->prepare("
        UPDATE inventory_item_requests
        SET Status = ?, Approved_By = ?, Approved_Date = NOW()
        WHERE Request_ID = ?
    ");
    $update->bind_param("ssi", $action, $ds_name, $request_id);
    $update->execute();

    /* ---------------------------------------------------------
        STEP 3: Insert approval log
    ----------------------------------------------------------*/
    $insert = $conn->prepare("
        INSERT INTO inventory_approvals 
            (Request_ID, DS_Officer_ID, Approval_Status, Approval_Date)
        VALUES (?, ?, ?, NOW())
    ");
    $insert->bind_param("iis", $request_id, $ds_id, $action);
    $insert->execute();

    /* ---------------------------------------------------------
        STEP 4: Only run balance logic if Approved
    ----------------------------------------------------------*/
    if ($action === "Approved") {

        /* -------------------------------
            Get Officer Total Allocation
        -------------------------------*/
        $alloc_sql = "
            SELECT SUM(final_amount) AS total_alloc
            FROM inventory_officer_allocations
            WHERE officer_id = ?
        ";
        $stmt2 = $conn->prepare($alloc_sql);
        $stmt2->bind_param("i", $officer_id);
        $stmt2->execute();
        $row = $stmt2->get_result()->fetch_assoc();

        $total_alloc = floatval($row['total_alloc'] ?? 0);

        /* -------------------------------
            Get Officer Total Used
        -------------------------------*/
        $used_sql = "
            SELECT SUM(i.Unit_Price * ir.Quantity) AS used_total
            FROM inventory_item_requests ir
            JOIN inventory_items i ON ir.Item_ID = i.Item_ID
            WHERE ir.Officer_ID = ? AND ir.Status = 'Approved'
        ";
        $stmt3 = $conn->prepare($used_sql);
        $stmt3->bind_param("i", $officer_id);
        $stmt3->execute();
        $row2 = $stmt3->get_result()->fetch_assoc();

        $used_total = floatval($row2['used_total'] ?? 0);

        /* -------------------------------
            Balance Calculation
        -------------------------------*/
        $balance = $total_alloc - $used_total;
        
        // අවශ්‍ය නම් මෙහිදී balance එක 0 ට අඩු දැයි පරීක්ෂා කර warning එකක් ලබා දිය හැක.
    }

    /* ---------------------------------------------------------
        DONE → Redirect to DS Officer Dashboard
    ----------------------------------------------------------*/
    $safe_msg = urlencode("Request " . $action . " Successfully");
    header("Location: ds_officer_dashboard.php?msg=" . $safe_msg);
    exit();
}
?>