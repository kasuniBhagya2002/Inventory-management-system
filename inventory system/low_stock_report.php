<?php
session_start();

// Security
if (isset($_SESSION['ID'])) {
    session_regenerate_id(true);
}

include('connection.php');

// Check login
if (!isset($_SESSION['ID'])) {
    header("Location: login.php");
    exit();
}

// Get DS ID
$dsid = $_SESSION['dsid'] ?? null;
if (!$dsid) {
    die("Error: DS Division not assigned.");
}

// Fetch LOW STOCK items
$stmt = $conn->prepare("
    SELECT Item_ID, Item_Name, Quantity, Min_Quantity, Unit_Price
    FROM inventory_items
    WHERE dsid = ?
    AND Quantity <= Min_Quantity
    ORDER BY Quantity ASC
");

$stmt->bind_param("i", $dsid);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Low Stock Report</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
body {
    font-family: 'Poppins', sans-serif;
    background: #f4f6f9;
    margin: 0;
}

.header {
    background: linear-gradient(135deg, #4361ee, #3f37c9);
    color: #fff;
    padding: 15px 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header h2 {
    margin: 0;
    font-weight: 500;
}

.back-btn {
    background: rgba(255,255,255,0.2);
    padding: 8px 15px;
    border-radius: 20px;
    text-decoration: none;
    color: #fff;
    transition: 0.3s;
}

.back-btn:hover {
    background: #fff;
    color: #4361ee;
}

.container {
    padding: 25px;
}

.card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th {
    background: #f1f3f6;
    padding: 12px;
    text-align: left;
}

.table td {
    padding: 12px;
    border-bottom: 1px solid #eee;
}

.table tr:hover {
    background: #f9fafc;
}

.low {
    color: red;
    font-weight: bold;
}

.badge {
    padding: 5px 10px;
    border-radius: 20px;
    background: red;
    color: white;
    font-size: 12px;
}
</style>
</head>

<body>

<div class="header">
    <h2><i class="fa-solid fa-triangle-exclamation"></i> Low Stock Items</h2>
    <a href="accountant_dashboard.php" class="back-btn">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>
</div>

<div class="container">
    <div class="card">

        <table class="table">
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Available Qty</th>
                    <th>Min Qty</th>
                    <th>Unit Price</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['Item_Name']) ?></td>
                            <td class="low"><?= $row['Quantity'] ?></td>
                            <td><?= $row['Min_Quantity'] ?></td>
                            <td>Rs. <?= number_format($row['Unit_Price'], 2) ?></td>
                            <td><span class="badge">LOW</span></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center; padding:20px;">
                            ✅ No low stock items
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>

        </table>

    </div>
</div>

</body>
</html>