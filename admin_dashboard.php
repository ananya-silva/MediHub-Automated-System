<?php

require_once __DIR__ . '/dashboard_common.php';
require_once __DIR__ . '/dashboard_nav.php';

requireDashboardRole('Admin');

$totalUsers = (int) $conn->query("
    SELECT COUNT(*) c
    FROM users
    WHERE role = 'Patient'
")->fetch_assoc()['c'];

$pharm = (int) $conn->query("
    SELECT COUNT(*) c
    FROM users
    WHERE role = 'Pharmacist'
")->fetch_assoc()['c'];

$sup = (int) $conn->query("
    SELECT COUNT(*) c
    FROM suppliers
")->fetch_assoc()['c'];

$med = (int) $conn->query("
    SELECT COUNT(*) c
    FROM medicines
")->fetch_assoc()['c'];

$orders = (int) $conn->query("
    SELECT COUNT(*) c
    FROM orders
    WHERE DATE(order_date) = CURDATE()
")->fetch_assoc()['c'];

$pending = (int) $conn->query("
    SELECT COUNT(*) c
    FROM notifications
    WHERE admin_status = 'Pending'
")->fetch_assoc()['c'];

$rev = (float) $conn->query("
    SELECT COALESCE(SUM(total_amount), 0) total
    FROM orders
    WHERE status IN ('Approved', 'Ready', 'Delivered')
      AND DATE(order_date) = CURDATE()
")->fetch_assoc()['total'];

renderPageStart(
    'Admin',
    'admin_dashboard.php',
    'Admin Dashboard'
);

?>

<div class="welcome">

    <h2>Admin Dashboard</h2>

    <p>
        Central overview of users, pharmacy operations,
        stock and revenue.
    </p>

</div>

<div class="stats">

    <?php
    foreach (
        [
            ['Patients', $totalUsers, 'users'],
            ['Pharmacists', $pharm, 'user-doctor'],
            ['Suppliers', $sup, 'truck-medical'],
            ['Medicines', $med, 'pills'],
            ["Today's Orders", $orders, 'cart-shopping'],
            ['Pending Approvals', $pending, 'clipboard-check'],
            ['Today Revenue', 'LKR ' . number_format($rev, 2), 'money-bill-wave']
        ] as $s
    ):
    ?>

        <div class="stat">

            <div class="icon">
                <i class="fa-solid fa-<?= $s[2] ?>"></i>
            </div>

            <small>
                <?= $s[0] ?>
            </small>

            <strong>
                <?= is_numeric($s[1]) ? number_format($s[1]) : e($s[1]) ?>
            </strong>

        </div>

    <?php endforeach; ?>

</div>

<div class="cards2">

    <div class="panel">

        <div class="panel-head">
            <h3>Management</h3>
        </div>

        <p>
            <a class="btn" href="admin_medicines.php">
                <i class="fa-solid fa-pills"></i>
                Manage Medicines
            </a>
        </p>

        <p>
            <a class="btn secondary" href="admin_users.php">
                <i class="fa-solid fa-users"></i>
                Manage Users
            </a>
        </p>

        <p>
            <a class="btn secondary" href="admin_suppliers.php">
                <i class="fa-solid fa-truck-medical"></i>
                Manage Suppliers
            </a>
        </p>

    </div>

    <div class="panel">

        <div class="panel-head">
            <h3>Analytics</h3>
        </div>

        <p>
            Revenue and order trends are calculated directly
            from completed/approved orders.
        </p>

        <a class="btn" href="admin_analytics.php">
            Open Analytics
            <i class="fa-solid fa-chart-line"></i>
        </a>

    </div>

</div>

<?php

renderPageEnd();

?>