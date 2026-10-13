<?php
session_start();
include("connection.php");

// Check pending leaves count for the current user
$pendingCount = 0;
if (isset($_SESSION['ID'])) {
    $logged_user_id = $_SESSION['ID'];
    $pendingSheetsStmt = $conn->prepare("SELECT COUNT(*) as pending_count FROM leaves WHERE user_id = ? AND leafsheet = 'Pending'");
    $pendingSheetsStmt->bind_param("i", $logged_user_id);
    $pendingSheetsStmt->execute();
    $pendingSheetsResult = $pendingSheetsStmt->get_result();
    $pendingSheetsData = $pendingSheetsResult->fetch_assoc();
    $pendingCount = $pendingSheetsData['pending_count'] ?? 0;
    $pendingSheetsStmt->close();
}

// Check if user is logged in — if not, redirect to login
if (!isset($_SESSION['ID'])) {
    header("Location: login.php");
    exit();
}

// Fetch user details from database
$userDetails = [];
if (isset($_SESSION['ID'])) {
    $user_id = $_SESSION['ID'];
    $logged_in_user_section_id=$_SESSION['section_id'];
    $userStmt = $conn->prepare("SELECT u.*, p.province_name, d.district_name, ds.dsname, s.section_name 
                               FROM user u 
                               LEFT JOIN provinces p ON u.province_id = p.province_id 
                               LEFT JOIN district d ON u.district_id = d.district_id 
                               LEFT JOIN dsdivision ds ON u.dsid = ds.dsid 
                               LEFT JOIN section s ON u.section_id = s.section_id 
                               WHERE u.id = ?");
    $userStmt->bind_param("i", $user_id);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $userDetails = $userResult->fetch_assoc();
    $userStmt->close();
}

// Handle the form submission when a button is clicked
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['leave_application'])) {
        header("Location: leaves.php");
        exit();
    } elseif (isset($_POST['provincial_day'])) {
        // Redirect based on role for provincial day
        if ($_SESSION['role'] == 'Admin') {
            header("Location: admin_dashboard.php");
        } elseif ($_SESSION['role'] == 'user') {
            header("Location: duties.php");
        } elseif ($_SESSION['role'] == 'Developer') {
            header("Location: duties.php");
        } elseif ($_SESSION['role'] == 'section_head') {
            header("Location: section_head_dashboard.php");
        } elseif ($_SESSION['role'] == 'new_user') {
            header("Location: new_user.php");
        } elseif ($_SESSION['role'] == 'transfered') {
            header("Location: transfered.php");
        } elseif ($_SESSION['role'] == 'District_officer') {
            header("Location: duties.php");
        }
        exit();
    } elseif (isset($_POST['allocations'])) {
        // Redirect to allocations page
        header("Location: allocation_dashboard.php");
        exit();
    } elseif (isset($_POST['ability_registry'])) {
        // Redirect based on role for ability registry
        if ($_SESSION['role'] == 'Admin' || $_SESSION['role'] == 'section_admin') {
            header("Location: abilities/admin_dashboard.php");
        } else {
            header("Location: abilities/officer_dashboard.php");
        }
        exit();
    } elseif (isset($_POST['poshana_support'])) {
    // Redirect to poshana dashboard only for poshana officers
    if ($_SESSION['role'] == 'poshana_officer') {
        header("Location: poshana/poshana_dashboard.php");
        exit();
    } else {
        // Show error message for non-poshana officers
        echo '<script>
            Swal.fire({
                icon: "error",
                title: "Access Denied",
                text: "Only Poshana Officers can access this feature.",
                confirmButtonColor: "#4361ee"
            });
        </script>';
    }
} elseif (isset($_POST['feedbacks'])) {
    // Redirect to feedback dashboard for Admin
    if ($_SESSION['role'] == 'Admin') {
        header("Location: feedback/admin_dashboard.php");
        exit();
    } else {
        // Show error message for non-admin users
        echo '<script>
            Swal.fire({
                icon: "error",
                title: "Access Denied",
                text: "Only Administrators can access the Feedback System.",
                confirmButtonColor: "#4361ee"
            });
        </script>';
    }
} elseif (isset($_POST['inventory_management'])) {
    // ------------------------------------------------------------------
    // UPDATED LOGIC FOR INVENTORY MANAGEMENT REDIRECTION
    // ------------------------------------------------------------------
    $role = $_SESSION['role'];

    // Check for Accountant (Capitalized or lowercase)
    if ($role == 'Accountant' || $role == 'accountant') {
        header("Location: accountant_dashboard.php");
        exit();
    } 
    // Check for DS Officer
    elseif ($role == 'DS_Officer' || $role == 'ds_officer') {
        header("Location: ds_officer_dashboard.php");
        exit();
    } 
    // Check for Officer
    elseif ($role == 'Officer' || $role == 'officer') {
        header("Location: officer_dashboard.php");
        exit();
    
    }
    else {
        // Default Access Denied for unauthorized roles
        echo '<script>
            Swal.fire({
                icon: "error",
                title: "Access Denied",
                text: "You do not have permission to access the Inventory Management System.",
                confirmButtonColor: "#4361ee"
            });
        </script>';
    }
    
    } elseif (isset($_POST['divisional_organizations'])) {
        // Redirect to divisional organizations database
        if ($_SESSION['role'] == 'Admin' || $_SESSION['role'] == 'developer') {
            header("Location: organizations/club_app.php");
            exit();
        } else {
            // Show access denied message
            echo '<script>
                Swal.fire({
                    icon: "error",
                    title: "Access Denied",
                    text: "Only Administrators and Developers can access the Divisional Organizations Database.",
                    confirmButtonColor: "#4361ee"
                });
            </script>';
        }
    } elseif (isset($_POST['archive_system'])) {
        // Redirect to archive system
        if ($_SESSION['role'] == 'Admin' || $_SESSION['role'] == 'Developer') {
            header("Location: archive/archive_dashboard.php");
            exit();
        } else {
            // Show access denied message
            echo '<script>
                Swal.fire({
                    icon: "error",
                    title: "Access Denied",
                    text: "Only Administrators and Developers can access the Archive System.",
                    confirmButtonColor: "#4361ee"
                });
            </script>';
        }
    }
}

// Function to check if user has access to a system
function hasAccess($system, $userRole) {
    $accessRules = [
        // Include new roles in access permission list
        'inventory' => ['acc_user', 'Developer', 'Accountant', 'accountant', 'DS_Officer', 'ds_officer', 'Officer', 'officer', 'user'],
        'organizations' => ['Admin', 'Developer'],
        'archive' => ['Admin', 'Developer'],
        'poshana' => ['poshana_officer'],
        'abilities_admin' => ['Admin', 'section_admin'],
        'provincial_admin' => ['Admin'],
    ];
    
    return isset($accessRules[$system]) && in_array($userRole, $accessRules[$system]);
}

// Get user's accessible systems for error messages
$userRole = $_SESSION['role'] ?? '';
$accessibleSystems = [];
if (hasAccess('inventory', $userRole)) $accessibleSystems[] = "Inventory Management System";
if (hasAccess('organizations', $userRole)) $accessibleSystems[] = "Divisional Organizations Database";
if (hasAccess('archive', $userRole)) $accessibleSystems[] = "Archive System";
if ($userRole == 'poshana_officer') $accessibleSystems[] = "Poshana Support System";
if (hasAccess('abilities_admin', $userRole)) $accessibleSystems[] = "Ability Registry (Admin)";
// Add other systems as needed
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Gateway</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --accent-color: #f72585;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4cc9f0;
            --inventory-color: #ff6b6b;
            --org-color: #4ecdc4;
            --archive-color: #45b7d1;
            --card-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            --hover-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', 'Inter', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.5;
            color: var(--dark-color);
            padding: 0;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 4px 20px rgba(67, 97, 238, 0.3);
            position: relative;
            overflow: hidden;
            padding: 1rem 0 0.5rem;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        
        .header-content {
            position: relative;
            z-index: 1;
        }
        
        .header h2 {
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 0.3rem;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .option-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1rem 15px;
        }
        
        .option-card {
            background: white;
            border: none;
            border-radius: 12px;
            padding: 1.2rem;
            margin-bottom: 1rem;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: var(--card-shadow);
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            min-height: 140px;
        }
        
        .option-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
        }
        
        .option-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }
        
        .option-card:active {
            transform: translateY(-2px);
        }
        
        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.8rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            font-size: 1.3rem;
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
        }
        
        .option-card h4 {
            color: var(--dark-color);
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
            line-height: 1.3;
        }
        
        .option-card p {
            color: #6c757d;
            font-size: 0.8rem;
            margin-bottom: 0;
            line-height: 1.2;
        }
        
        .option-button {
            background: none;
            border: none;
            width: 100%;
            height: 100%;
            padding: 0;
            cursor: pointer;
            outline: none;
        }
        
        .footer {
            text-align: center;
            padding: 1.5rem 0 1rem;
            color: #6c757d;
            font-size: 0.8rem;
            margin-top: 1rem;
        }

        .logout-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 16px;
            background: linear-gradient(to right, #dc3545, #c82333);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(220, 53, 69, 0.3);
            border: none;
            font-size: 0.85rem;
            cursor: pointer;
        }

        .logout-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
            color: white;
            text-decoration: none;
        }

        /* User info panel styles */
        .user-info-panel {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 0.8rem 1rem;
            margin: 0.8rem auto;
            max-width: 1200px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .user-details-single-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: nowrap;
            gap: 0.8rem;
        }

        .user-detail-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 0.6rem 0.8rem;
            border-radius: 8px;
            text-align: center;
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .user-detail-label {
            font-size: 0.7rem;
            opacity: 0.8;
            margin-bottom: 0.2rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .user-detail-value {
            font-size: 0.8rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Pending leaves alert styles */
        .blinking {
            animation: blink 1.5s infinite;
            font-weight: bold;
        }
        
        @keyframes blink {
            0% { color: #dc3545; }
            50% { color: transparent; }
            100% { color: #dc3545; }
        }
        
        .pending-alert {
            max-width: 1200px;
            margin: 0 auto 15px;
            cursor: pointer;
            border-radius: 10px;
            border: none;
            box-shadow: 0 3px 12px rgba(255, 193, 7, 0.3);
            padding: 0.8rem 1.2rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 8px;
            font-size: 0.8rem;
            opacity: 0.9;
        }

        .user-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        /* New system specific styles */
        .inventory-card .card-icon {
            background: linear-gradient(135deg, var(--inventory-color), #ee5a52);
        }

        .inventory-card:hover {
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.25);
        }

        .org-card .card-icon {
            background: linear-gradient(135deg, var(--org-color), #2E7D32);
        }

        .org-card:hover {
            box-shadow: 0 8px 25px rgba(78, 205, 196, 0.25);
        }

        .archive-card .card-icon {
            background: linear-gradient(135deg, var(--archive-color), #1565C0);
        }

        .archive-card:hover {
            box-shadow: 0 8px 25px rgba(69, 183, 209, 0.25);
        }

        .feedback-card .card-icon {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
        }

        .poshana-card .card-icon {
            background: linear-gradient(135deg, #4CAF50, #2E7D32);
        }

        .profile-photo-mini {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
        }

        .no-photo-icon {
            font-size: 1.5rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .header-top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 1rem 0.5rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .welcome-text {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        /* Grid adjustments for more cards */
        .option-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
        }

        /* Mobile-specific adjustments */
        @media (max-width: 768px) {
            .header {
                padding: 0.8rem 0 0.3rem;
                border-radius: 0 0 15px 15px;
            }
            
            .header h2 {
                font-size: 1.3rem;
            }
            
            .header p {
                font-size: 0.8rem;
            }
            
            .option-card {
                padding: 1rem;
                border-radius: 10px;
                min-height: 120px;
            }
            
            .card-icon {
                width: 45px;
                height: 45px;
                font-size: 1.2rem;
                margin-bottom: 0.6rem;
            }
            
            .option-card h4 {
                font-size: 0.9rem;
            }
            
            .option-card p {
                font-size: 0.75rem;
            }
            
            .logout-button {
                padding: 6px 12px;
                font-size: 0.8rem;
            }
            
            .user-details-single-row {
                flex-wrap: wrap;
                justify-content: center;
                gap: 0.5rem;
            }
            
            .user-detail-item {
                flex: 0 0 calc(33.333% - 0.5rem);
                min-width: 100px;
                padding: 0.5rem 0.6rem;
            }
            
            .user-info-panel {
                padding: 0.6rem 0.8rem;
                margin: 0.6rem auto;
            }

            .user-detail-label {
                font-size: 0.65rem;
            }
            
            .user-detail-value {
                font-size: 0.75rem;
            }

            .option-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 0.8rem;
            }

            .header-top-bar {
                flex-direction: column;
                gap: 0.5rem;
                text-align: center;
            }
        }

        @media (max-width: 576px) {
            .header {
                padding: 0.6rem 0 0.2rem;
            }
            
            .header h2 {
                font-size: 1.1rem;
            }
            
            .option-card {
                padding: 0.8rem;
                min-height: 110px;
            }
            
            .card-icon {
                width: 40px;
                height: 40px;
                font-size: 1.1rem;
            }
            
            .option-card h4 {
                font-size: 0.85rem;
            }
            
            .user-details-single-row {
                gap: 0.4rem;
            }
            
            .user-detail-item {
                flex: 0 0 calc(50% - 0.4rem);
                padding: 0.4rem 0.5rem;
            }
            
            .user-detail-label {
                font-size: 0.6rem;
            }
            
            .user-detail-value {
                font-size: 0.7rem;
            }

            .option-grid {
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
                gap: 0.6rem;
            }
        }

        @media (max-width: 400px) {
            .option-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="container pending-alert">
    <?php if ($pendingCount > 0): ?>
    <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center" role="alert" onclick="window.location.href='leaves.php'">
        <i class="bi bi-exclamation-circle me-2 fs-6"></i>
        <div>
            <span class="blinking">You have <strong><?= $pendingCount ?></strong> pending leave sheet(s) for approval.</span>
            <div class="small mt-1">Click to view and take action.</div>
        </div>
    </div>
    <?php endif; ?>
</div>
<div>
            <button type="button" class="logout-button" onclick="confirmLogout()">
                <i class="fas fa-sign-out-alt me-1"></i> Logout
            </button>
        </div>
<div class="header">
    <div class="header-top-bar">
        <div class="welcome-text">
            Welcome, <b><?php echo isset($_SESSION['username']) ? $_SESSION['Name'] : $_SESSION['Name']; ?></b>
        </div>
        
    </div>
    
    <div class="container user-info-panel">
        <div class="user-details-single-row">
            <div class="user-detail-item">
                <div class="user-detail-label">Province</div>
                <div class="user-detail-value">
                    <?php 
                    $provinceName = $userDetails['province_name'] ?? 'N/A';
                    $cleanProvinceName = preg_replace('/province/i', '', $provinceName);
                    echo htmlspecialchars(trim($cleanProvinceName)); 
                    ?>
                </div>
            </div>
            <div class="user-detail-item">
                <div class="user-detail-label">District</div>
                <div class="user-detail-value"><?php echo htmlspecialchars($userDetails['district_name'] ?? 'N/A'); ?></div>
            </div>
            <div class="user-detail-item">
                <div class="user-detail-label">DS Division</div>
                <div class="user-detail-value"><?php echo htmlspecialchars($userDetails['dsname'] ?? 'N/A'); ?></div>
            </div>
            <div class="user-detail-item">
                <div class="user-detail-label">Section</div>
                <div class="user-detail-value"><?php echo htmlspecialchars($userDetails['section_name'] ?? 'N/A'); ?></div>
            </div>
            <div class="user-detail-item">
                <div class="user-detail-label">Role</div>
                <div class="user-detail-value"><?php echo htmlspecialchars($_SESSION['role'] ?? 'N/A'); ?></div>
            </div>
            <div class="user-detail-item">
                <div class="user-detail-value">
                    <?php
                    $profilePhoto = $_SESSION['Photo'] ?? null;
                    if ($profilePhoto && file_exists($profilePhoto)): 
                    ?>
                        <img src="<?php echo htmlspecialchars($profilePhoto); ?>" 
                             alt="Profile Photo" 
                             class="profile-photo-mini">
                    <?php else: ?>
                        <i class="fas fa-user-circle no-photo-icon"></i>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container text-center header-content">
        <h2 class="mb-1">කරුණාකර තෝරන්න</h2>
        <p>Please select an option to continue</p>
    </div>
</div>

<div class="option-container">
    <div class="option-grid">
        <div class="option-item">
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="h-100">
                <input type="hidden" name="leave_application" value="1">
                <button type="submit" class="option-button h-100">
                    <div class="option-card">
                        <div class="card-icon">
                            <i class="fas fa-calendar-times"></i>
                        </div>
                        <h4>නිවාඩු අයදුම් කිරීම</h4>
                        <p>Leave Management system</p>
                    </div>
                </button>
            </form>
        </div>
        
        <div class="option-item">
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="h-100">
                <input type="hidden" name="provincial_day" value="1">
                <button type="submit" class="option-button h-100">
                    <div class="option-card">
                        <div class="card-icon">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <h4>පොදු පළාත් දිනය</h4>
                        <p>Performance Monitoring</p>
                    </div>
                </button>
            </form>
        </div>
        
        <div class="option-item">
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="h-100">
        <input type="hidden" name="feedbacks" value="1">
        <button type="submit" class="option-button h-100">
            <div class="option-card feedback-card">
                <div class="card-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <h4>ප්‍රජා ප්‍රතිපෝෂණ</h4>
                <p>People's Feedbacks</p>
            </div>
        </button>
    </form>
</div>
        
        <div class="option-item">
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="h-100">
                <input type="hidden" name="ents_system" value="1">
                <button type="submit" class="option-button h-100">
                    <div class="option-card">
                        <div class="card-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <h4>ව්‍යවසායක දත්ත පද්ධතිය</h4>
                        <p>Coming soon...</p>
                    </div>
                </button>
            </form>
        </div>

        <div class="option-item">
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="h-100">
                <input type="hidden" name="poshana_support" value="1">
                <button type="submit" class="option-button h-100">
                    <div class="option-card poshana-card">
                        <div class="card-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h4>පෝෂණ සහාය</h4>
                        <p>Nutritional Support</p>
                    </div>
                </button>
            </form>
        </div>

        <div class="option-item">
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="h-100">
                <input type="hidden" name="ability_registry" value="1">
                <button type="submit" class="option-button h-100">
                    <div class="option-card">
                        <div class="card-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <h4>නිලධාරීන්ගේ හැකියාවන්</h4>
                        <p>Officer Abilities</p>
                    </div>
                </button>
            </form>
        </div>

        <div class="option-item">
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="h-100">
                <input type="hidden" name="allocations" value="1">
                <button type="submit" class="option-button h-100">
                    <div class="option-card">
                        <div class="card-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <h4>Allocations</h4>
                        <p>Allocation Management</p>
                    </div>
                </button>
            </form>
        </div>

        <div class="option-item">
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="h-100">
                <input type="hidden" name="inventory_management" value="1">
                <button type="submit" class="option-button h-100">
                    <div class="option-card inventory-card">
                        <div class="card-icon">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <h4>Inventory Management</h4>
                        <p>Stock and asset management</p>
                    </div>
                </button>
            </form>
        </div>

        <div class="option-item">
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="h-100">
                <input type="hidden" name="divisional_organizations" value="1">
                <button type="submit" class="option-button h-100">
                    <div class="option-card org-card">
                        <div class="card-icon">
                            <i class="fas fa-sitemap"></i>
                        </div>
                        <h4>Organizations Database</h4>
                        <p>Organization information</p>
                    </div>
                </button>
            </form>
        </div>

        <div class="option-item">
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="h-100">
                <input type="hidden" name="archive_system" value="1">
                <button type="submit" class="option-button h-100">
                    <div class="option-card archive-card">
                        <div class="card-icon">
                            <i class="fas fa-archive"></i>
                        </div>
                        <h4>ලේඛනාගාරය</h4>
                        <p>Archive System</p>
                    </div>
                </button>
            </form>
        </div>
    </div>
</div>

<div class="footer">
    <div class="container">
        <p>© <?php echo date("Y"); ?> All Rights Reserved</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Function to handle logout with confirmation
    function confirmLogout() {
        Swal.fire({
            title: 'Are you sure?',
            text: "You will be logged out of the system.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#4361ee',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Logout!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirect to logout.php
                window.location.href = 'logout.php';
            }
        });
    }

    // Function to show access denied message with available systems
    function showAccessDenied(message, accessibleSystems) {
        let systemsList = '';
        if (accessibleSystems.length > 0) {
            systemsList = '<div class="text-start mt-3"><strong>You can access:</strong><ul class="mt-2">';
            accessibleSystems.forEach(system => {
                systemsList += `<li>${system}</li>`;
            });
            systemsList += '</ul></div>';
        }
        
        Swal.fire({
            icon: 'error',
            title: 'Access Denied',
            html: message + systemsList,
            confirmButtonColor: '#4361ee',
            confirmButtonText: 'OK'
        });
    }

    // Add some interactive elements
    document.addEventListener('DOMContentLoaded', function() {
        // Add ripple effect to cards
        const cards = document.querySelectorAll('.option-card');
        cards.forEach(card => {
            card.addEventListener('click', function(e) {
                // Create ripple element
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size/2;
                const y = e.clientY - rect.top - size/2;
                
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.classList.add('ripple-effect');
                
                this.appendChild(ripple);
                
                // Remove ripple after animation
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
    });
</script>

</body>
</html>