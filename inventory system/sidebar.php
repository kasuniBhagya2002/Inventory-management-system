<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accountant Panel</title>

   
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-bg: linear-gradient(135deg, #f3e8ff 0%, #e0f2fe 100%);
            --sidebar-bg: #ffffff;
            --accent: #7c3aed;
            --accent-hover: #6d28d9;
            --accent-light: #ede9fe;
            --blue-light: #e0f2fe;
            --gold: #a855f7;
            --text: #1e1b4b;
            --text-muted: #6366f1;
            --border: #e2e8f0;
            --shadow: 0 8px 32px rgba(124, 58, 237, 0.12);
            --highlight-bg: rgba(124, 58, 237, 0.08);
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: var(--text);
            min-height: 100vh;
            background: var(--primary-bg);
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 280px;
            background: var(--sidebar-bg);
            color: var(--text);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow);
            z-index: 1000;
            border-right: 1px solid var(--border);
            backdrop-filter: blur(12px);
            background: rgba(255, 255, 255, 0.85);
        }

        .sidebar::after {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, #a78bfa, #7c3aed);
            opacity: 0.9;
            border-radius: 0 4px 4px 0;
        }

        .sidebar-header {
            padding: 36px 24px;
            text-align: center;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(to bottom, #f3e8ff, transparent);
        }
        .sidebar-header i {
            font-size: 48px;
            color: var(--gold);
            margin-bottom: 14px;
            filter: drop-shadow(0 4px 12px rgba(167, 139, 250, 0.3));
            background: linear-gradient(135deg, #a78bfa, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .sidebar-header h2 {
            margin: 0;
            font-size: 23px;
            font-weight: 700;
            color: var(--text);
            letter-spacing: -0.5px;
        }

        .sidebar-menu {
            padding: 20px 0;
            flex: 1;
            overflow-y: auto;
        }
        .sidebar-menu li {
            margin: 6px 14px;
        }
        .sidebar-menu a {
            display: flex;
            align-items: center;
            color: var(--text-muted);
            text-decoration: none;
            padding: 16px 20px;
            border-radius: 14px;
            font-weight: 500;
            font-size: 15.5px;
            transition: all 0.35s ease;
            position: relative;
            overflow: hidden;
        }
        .sidebar-menu a i {
            width: 28px;
            font-size: 20px;
            margin-right: 18px;
            text-align: center;
            transition: all 0.35s ease;
            background: linear-gradient(135deg, #a78bfa, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            filter: drop-shadow(0 2px 4px rgba(124, 58, 237, 0.2));
        }
        .sidebar-menu a span {
            flex: 1;
            white-space: nowrap;
        }

        .sidebar-menu a:hover {
            background: var(--accent-light);
            color: var(--accent);
            transform: translateX(8px);
            box-shadow: 0 8px 25px rgba(124, 58, 237, 0.18);
        }
        .sidebar-menu a:hover i {
            transform: scale(1.2) rotate(8deg);
            filter: drop-shadow(0 4px 8px rgba(124, 58, 237, 0.4));
        }

        .sidebar-menu a.active {
            background: linear-gradient(90deg, #ede9fe, #dbeafe);
            color: var(--accent);
            font-weight: 600;
            border-left: 5px solid var(--accent);
            box-shadow: 0 8px 25px rgba(124, 58, 237, 0.22);
        }
        .sidebar-menu a.active i {
            transform: scale(1.15);
        }

        .sidebar-footer {
            padding: 24px;
            border-top: 1px solid var(--border);
            background: linear-gradient(to top, #f3e8ff, transparent);
        }
        .sidebar-footer a {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            color: var(--accent);
            font-weight: 600;
            text-decoration: none;
            padding: 16px;
            border-radius: 14px;
            transition: all 0.35s ease;
            background: var(--accent-light);
        }
        .sidebar-footer a:hover {
            background: #d8b4fe;
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(124, 58, 237, 0.25);
        }
        .sidebar-footer a i {
            background: linear-gradient(135deg, #a78bfa, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .toggle-btn {
            position: absolute;
            top: 24px;
            right: -22px;
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #a78bfa, #7c3aed);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: var(--shadow);
            transition: all 0.35s ease;
            z-index: 1001;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .toggle-btn:hover {
            transform: scale(1.12) rotate(90deg);
            box-shadow: 0 12px 35px rgba(124, 58, 237, 0.3);
        }
        .toggle-btn i {
            font-size: 20px;
        }

        .sidebar.collapsed {
            width: 88px;
        }
        .sidebar.collapsed .sidebar-header h2,
        .sidebar.collapsed .sidebar-menu a span,
        .sidebar.collapsed .sidebar-footer a span {
            opacity: 0;
            visibility: hidden;
            position: absolute;
        }
        .sidebar.collapsed .sidebar-header {
            padding: 36px 0;
        }
        .sidebar.collapsed .sidebar-menu a {
            justify-content: center;
            padding: 18px;
        }
        .sidebar.collapsed .sidebar-menu a i {
            margin: 0;
            font-size: 22px;
        }
        .sidebar.collapsed .sidebar-footer a {
            padding: 18px;
        }
        .sidebar.collapsed .toggle-btn i {
            transform: rotate(180deg);
        }

        @media (max-width: 1024px) {
            .sidebar {
                width: 88px;
            }
            .sidebar:not(.collapsed) {
                width: 280px;
            }
            .sidebar-header h2,
            .sidebar-menu a span,
            .sidebar-footer a span {
                opacity: 1;
                visibility: visible;
                position: static;
            }
        }

        @media (max-width: 768px) {
            .sidebar:not(.collapsed)::before {
                content: "";
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.35);
                z-index: -1;
                pointer-events: none;
            }
        }

        .highlight, strong, .important, .label, .title, h1, h2, h3, h4, h5, h6,
        table th, .card-title, .modal-title, .swal-title {
            color: #1e1b4b !important;
            font-weight: 600 !important;
        }

        table, table th, table td,
        .swal-text, .swal-button,
        .popup *, .modal-content *, .modal-body * {
            color: #1e1b4b !important;
        }

        label, .form-label {
            color: #1e1b4b !important;
            font-weight: 500;
        }
    </style>
</head>
<body>

    <div class="sidebar" id="sidebar">
        <button class="toggle-btn" id="toggle-btn">
            <i class="fa-solid fa-bars-staggered"></i>
        </button>

        <div>
            <div class="sidebar-header">
                <i class="fa-solid fa-building-columns"></i>
                <h2>Accountant Panel</h2>
            </div>

            <ul class="sidebar-menu">
                
                <li><a href="accountant_dashboard.php" class="<?= ($current_page == 'accountant_dashboard.php') ? 'active' : '' ?>">
                    <i class="fa-solid fa-house"></i><span>Dashboard</span></a></li>

                 <li><a href="select_action.php" class="<?= ($current_page == '../select_action.php') ? 'active' : '' ?>">
                    <i class="fa-solid fa-arrow-right"></i><span>Back To Home</span></a></li>
                
                <li><a href="manage_items.php" class="<?= ($current_page == 'manage_items.php') ? 'active' : '' ?>">
                    <i class="fa-solid fa-boxes-stacked"></i><span>Manage Items</span></a></li>
                
                <li><a href="manage_suppliers.php" class="<?= ($current_page == 'manage_suppliers.php') ? 'active' : '' ?>">
                    <i class="fa-solid fa-truck"></i><span>Manage Suppliers</span></a></li>
                
                <li><a href="officer_allocation.php" class="<?= ($current_page == 'officer_allocation.php') ? 'active' : '' ?>">
                    <i class="fa-solid fa-scale-balanced"></i><span>Officer Allocation</span></a></li>
                
                <li><a href="view_request.php" class="<?= ($current_page == 'view_request.php') ? 'active' : '' ?>">
                    <i class="fa-solid fa-clipboard-check"></i><span>View Requests</span></a></li>
                
                <li><a href="stock_availability.php" class="<?= ($current_page == 'stock_availability.php') ? 'active' : '' ?>">
                    <i class="fa-solid fa-warehouse"></i><span>Stock Availability</span></a></li>

                <li><a href="officer_history.php" class="<?= ($current_page == 'officer_history.php') ? 'active' : '' ?>">
                    <i class="fa-solid fa-history"></i><span>Officer History</span></a></li>
            </ul>
        </div>

        <div class="sidebar-footer">
            <a href="../logout.php">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const toggleBtn = document.getElementById("toggle-btn");
            const sidebar = document.getElementById("sidebar");

            if (localStorage.getItem("sidebarCollapsed") === "true") {
                sidebar.classList.add("collapsed");
                toggleBtn.querySelector("i").classList.replace("fa-bars-staggered", "fa-bars");
            }

            toggleBtn.addEventListener("click", function () {
                sidebar.classList.toggle("collapsed");
                
                if (sidebar.classList.contains("collapsed")) {
                    toggleBtn.querySelector("i").classList.replace("fa-bars-staggered", "fa-bars");
                    localStorage.setItem("sidebarCollapsed", "true");
                } else {
                    toggleBtn.querySelector("i").classList.replace("fa-bars", "fa-bars-staggered");
                    localStorage.setItem("sidebarCollapsed", "false");
                }
            });

            function checkScreenSize() {
                if (window.innerWidth <= 1024) {
                    sidebar.classList.add("collapsed");
                    localStorage.setItem("sidebarCollapsed", "true");
                    toggleBtn.querySelector("i").classList.replace("fa-bars-staggered", "fa-bars");
                }
            }

            checkScreenSize();
            window.addEventListener("resize", checkScreenSize);
        });
    </script>

</body>
</html>