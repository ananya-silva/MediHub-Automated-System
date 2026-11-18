<?php

require_once __DIR__ . '/dashboard_common.php';
require_once __DIR__ . '/dashboard_nav.php';

requireDashboardRole('Pharmacist');


$pending = (int) $conn->query(
    "SELECT COUNT(*) c
     FROM orders
     WHERE status='Pending'"
)->fetch_assoc()['c'];


$low = (int) $conn->query(
    "SELECT COUNT(*) c
     FROM medicines
     WHERE stock_quantity<=min_stock_level"
)->fetch_assoc()['c'];


$today = (int) $conn->query(
    "SELECT COUNT(*) c
     FROM orders
     WHERE DATE(order_date)=CURDATE()"
)->fetch_assoc()['c'];


$meds = (int) $conn->query(
    "SELECT COUNT(*) c
     FROM medicines"
)->fetch_assoc()['c'];


$revenue = (float) $conn->query(
    "SELECT COALESCE(SUM(total_amount),0) total
     FROM orders
     WHERE status IN ('Approved','Ready','Delivered')
     AND DATE(order_date)=CURDATE()"
)->fetch_assoc()['total'];


$recent = $conn->query(
    "SELECT
        o.order_id,
        u.full_name,
        o.total_amount,
        o.status,
        o.order_date
     FROM orders o
     JOIN users u
        ON u.user_id=o.patient_id
     ORDER BY o.order_date DESC
     LIMIT 8"
);


renderPageStart(
    'Pharmacist',
    'pharmacist_dashboard.php',
    'Pharmacist Dashboard'
);

?>


<div class="welcome">

    <h2>
        Pharmacist Dashboard
    </h2>

    <p>
        Manage medicines, orders, inventory and daily pharmacy operations.
    </p>

</div>


<div class="stats">

    <?php
    foreach (
        [
            [
                'Pending Orders',
                $pending,
                'clock'
            ],
            [
                'Low Stock',
                $low,
                'triangle-exclamation'
            ],
            [
                "Today's Orders",
                $today,
                'cart-shopping'
            ],
            [
                'Today Revenue',
                'LKR ' . number_format($revenue, 2),
                'money-bill-wave'
            ],
            [
                'Medicines',
                $meds,
                'pills'
            ]
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
                <?= is_numeric($s[1])
                    ? number_format($s[1])
                    : e($s[1])
                ?>
            </strong>

        </div>

    <?php endforeach; ?>

</div>


<div class="panel">

    <div class="panel-head">

        <h3>
            Recent Orders
        </h3>

        <a
            class="btn"
            href="pharmacist_orders.php"
        >
            View All Orders

            <i class="fa-solid fa-arrow-right"></i>
        </a>

    </div>


    <div class="table-wrap">

        <table class="table">

            <tr>

                <th>
                    Order
                </th>

                <th>
                    Patient
                </th>

                <th>
                    Total
                </th>

                <th>
                    Status
                </th>

                <th>
                    Date
                </th>

            </tr>


            <?php while ($r = $recent->fetch_assoc()): ?>

                <tr>

                    <td>
                        #<?= e($r['order_id']) ?>
                    </td>

                    <td>
                        <?= e($r['full_name']) ?>
                    </td>

                    <td>
                        LKR <?= number_format(
                            $r['total_amount'],
                            2
                        ) ?>
                    </td>

                    <td>

                        <span
                            class="badge <?= strtolower($r['status']) ?>"
                        >
                            <?= e($r['status']) ?>
                        </span>

                    </td>

                    <td>
                        <?= date(
                            'd M Y, h:i A',
                            strtotime($r['order_date'])
                        ) ?>
                    </td>

                </tr>

            <?php endwhile; ?>

        </table>

    </div>

</div>


<div class="cards2">

    <div class="panel">

        <div class="panel-head">

            <h3>
                Quick Actions
            </h3>

        </div>


        <p>

            <a
                class="btn"
                href="pharmacist_medicines.php#add-medicine"
            >
                <i class="fa-solid fa-plus"></i>
                Add Medicine
            </a>


            <a
                class="btn secondary"
                href="pharmacist_inventory.php"
            >
                <i class="fa-solid fa-boxes-stacked"></i>
                Check Inventory
            </a>

        </p>

    </div>


    <div class="panel">

        <div class="panel-head">

            <h3>
                Stock Alert
            </h3>

        </div>


        <p>

            <?= $low > 0
                ? '<span class="badge low">' .
                    $low .
                    ' medicine(s) need attention</span>'
                : '<span class="badge ok">' .
                    'All medicines are above minimum stock.' .
                    '</span>'
            ?>

        </p>


        <a href="pharmacist_notifications.php">
            Open notifications →
        </a>

    </div>

</div>


<?php

renderPageEnd();

?>