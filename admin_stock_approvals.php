<?php

require_once __DIR__ . '/dashboard_common.php';

requireDashboardRole('Admin');

require_once __DIR__ . '/dashboard_nav.php';

$msg = '';

/* =====================================================
   UPDATE ADMIN APPROVAL STATUS
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = (int) $_POST['notification_id'];

    $status = $_POST['admin_status'] ?? 'Pending';

    if (in_array($status, ['Pending', 'Approved', 'Rejected'], true)) {

        $stmt = $conn->prepare(
            'UPDATE notifications 
             SET admin_status = ? 
             WHERE notification_id = ?'
        );

        $stmt->bind_param('si', $status, $id);

        $stmt->execute();

        $stmt->close();

        $msg = 'Approval status updated.';
    }
}

/* =====================================================
   FETCH STOCK APPROVALS & ALERTS
===================================================== */

$rows = $conn->query(
    "SELECT 
        n.*,
        m.stock_quantity
     FROM notifications n
     LEFT JOIN medicines m 
        ON m.medicine_id = n.medicine_id
     WHERE n.request_type IN (
        'Restock Request',
        'Low Stock Alert',
        'Add Medicine',
        'Update Medicine'
     )
     ORDER BY n.submitted_date DESC"
);

/* =====================================================
   PAGE START
===================================================== */

renderPageStart(
    'Admin',
    'admin_stock_approvals.php',
    'Stock Approvals & Alerts'
);

?>

<div class="welcome">

    <h2>Stock Approvals & Alerts</h2>

    <p>
        Review pharmacist requests and low-stock notifications.
    </p>

</div>

<?php if ($msg): ?>

    <div class="notice">
        <?= e($msg) ?>
    </div>

<?php endif; ?>


<div class="panel">

    <div class="table-wrap">

        <table class="table">

            <tr>
                <th>Type</th>
                <th>Medicine</th>
                <th>Details</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Date</th>
                <th>Update</th>
            </tr>

            <?php while ($n = $rows->fetch_assoc()): ?>

                <tr>

                    <td>
                        <?= e($n['request_type']) ?>
                    </td>

                    <td>
                        <?= e($n['medicine_name']) ?>
                    </td>

                    <td>
                        <?= e($n['details']) ?>
                    </td>

                    <td>
                        <?= e($n['priority']) ?>
                    </td>

                    <td>

                        <span class="badge <?= strtolower($n['admin_status']) ?>">

                            <?= e($n['admin_status']) ?>

                        </span>

                    </td>

                    <td>
                        <?= date(
                            'd M Y, h:i A',
                            strtotime($n['submitted_date'])
                        ) ?>
                    </td>

                    <td>

                        <form method="post">

                            <input
                                type="hidden"
                                name="notification_id"
                                value="<?= $n['notification_id'] ?>"
                            >

                            <select
                                name="admin_status"
                                onchange="this.form.submit()"
                            >

                                <?php foreach (
                                    ['Pending', 'Approved', 'Rejected'] 
                                    as $s
                                ): ?>

                                    <option
                                        <?= $s === $n['admin_status']
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        <?= $s ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </form>

                    </td>

                </tr>

            <?php endwhile; ?>

        </table>

    </div>

</div>


<?php

renderPageEnd();

?>