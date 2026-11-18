<?php

require_once __DIR__ . '/dashboard_common.php';

requireDashboardRole('Pharmacist');

require_once __DIR__ . '/dashboard_nav.php';


$msg = '';


// =====================================================
// UPDATE ORDER STATUS
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = (int)($_POST['order_id'] ?? 0);

    $status = $_POST['status'] ?? 'Pending';

    $allowed = [
        'Pending',
        'Processing',
        'Approved',
        'Rejected',
        'Ready',
        'Delivered'
    ];


    if ($id > 0 && in_array($status, $allowed, true)) {

        // =================================================
        // GET CURRENT ORDER + PATIENT DETAILS
        // =================================================

        $check = $conn->prepare(
            'SELECT
                o.status,
                u.full_name,
                u.email
             FROM orders o
             INNER JOIN users u
                ON u.user_id = o.patient_id
             WHERE o.order_id = ?
             LIMIT 1'
        );


        $oldStatus = '';
        $patientName = '';
        $patientEmail = '';


        if ($check) {

            $check->bind_param('i', $id);

            $check->execute();

            $result = $check->get_result();

            $orderData = $result->fetch_assoc();

            $check->close();


            if ($orderData) {

                $oldStatus = (string)$orderData['status'];

                $patientName = (string)$orderData['full_name'];

                $patientEmail = (string)$orderData['email'];
            }
        }


        // =================================================
        // UPDATE ORDER STATUS
        // =================================================

        $st = $conn->prepare(
            'UPDATE orders
             SET status = ?
             WHERE order_id = ?'
        );


        if ($st) {

            $st->bind_param(
                'si',
                $status,
                $id
            );


            if ($st->execute()) {

                // =================================================
                // SEND EMAIL ONLY FOR APPROVED / REJECTED
                // =================================================

                if (
                    $oldStatus !== $status &&
                    in_array(
                        $status,
                        ['Approved', 'Rejected','Ready'],
                        true
                    ) &&
                    $patientEmail !== ''
                ) {

                    $emailSent = sendOrderStatusEmail(
                        $patientName,
                        $patientEmail,
                        $id,
                        $status
                    );


                    if ($emailSent) {

                        $msg =
                            'Order status updated successfully. ' .
                            'Patient email notification sent.';
                    }

                    else {

                        $msg =
                            'Order status updated, but the patient ' .
                            'email could not be sent. Please check SMTP settings.';
                    }

                }

                else {

                    $msg = 'Order status updated.';
                }
            }

            else {

                $msg = 'Failed to update order status.';
            }


            $st->close();
        }

        else {

            $msg = 'Database error while updating order.';
        }
    }
}


// =====================================================
// GET ORDERS
// =====================================================

$orders = $conn->query(
    "SELECT
        o.*,
        u.full_name,
        u.email
     FROM orders o
     INNER JOIN users u
        ON u.user_id = o.patient_id
     ORDER BY o.order_date DESC"
);


// =====================================================
// PAGE START
// =====================================================

renderPageStart(
    'Pharmacist',
    'pharmacist_orders.php',
    'Process Orders'
);

?>

<div class="welcome">

    <h2>Process Orders</h2>

    <p>
        Review patient orders and update their fulfilment status.
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

                <th>Order</th>

                <th>Patient</th>

                <th>Total</th>

                <th>Items</th>

                <th>Status</th>

                <th>Date</th>

                <th>Update</th>

            </tr>


            <?php if ($orders): ?>

                <?php while ($o = $orders->fetch_assoc()): ?>

                    <?php

                    // =================================================
                    // GET ORDER ITEMS
                    // =================================================

                    $itemStatement = $conn->prepare(
                        'SELECT
                            GROUP_CONCAT(
                                CONCAT(
                                    m.medicine_name,
                                    " x",
                                    oi.quantity
                                )
                                SEPARATOR ", "
                            ) AS items
                         FROM order_item oi
                         INNER JOIN medicines m
                            ON m.medicine_id = oi.medicine_id
                         WHERE oi.order_id = ?'
                    );


                    $items = '-';


                    if ($itemStatement) {

                        $itemStatement->bind_param(
                            'i',
                            $o['order_id']
                        );

                        $itemStatement->execute();

                        $itemResult =
                            $itemStatement->get_result();

                        $itemData =
                            $itemResult->fetch_assoc();

                        if (
                            $itemData &&
                            !empty($itemData['items'])
                        ) {
                            $items =
                                $itemData['items'];
                        }

                        $itemStatement->close();
                    }

                    ?>


                    <tr>

                        <!-- Order -->

                        <td>
                            #<?= (int)$o['order_id'] ?>
                        </td>


                        <!-- Patient -->

                        <td>

                            <?= e($o['full_name']) ?>

                            <br>

                            <small>
                                <?= e($o['email']) ?>
                            </small>

                        </td>


                        <!-- Total -->

                        <td>
                            LKR
                            <?= number_format(
                                (float)$o['total_amount'],
                                2
                            ) ?>
                        </td>


                        <!-- Items -->

                        <td>
                            <?= e($items) ?>
                        </td>


                        <!-- Status -->

                        <td>

                            <span
                                class="badge <?= e(strtolower($o['status'])) ?>"
                            >

                                <?= e(
                                    $o['status'] === 'Ready'
                                        ? 'Ready to Pickup'
                                        : $o['status']
                                ) ?>

                            </span>

                        </td>


                        <!-- Date -->

                        <td>

                            <?= date(
                                'd M Y, h:i A',
                                strtotime($o['order_date'])
                            ) ?>

                        </td>


                        <!-- Update -->

                        <td>

                            <form method="post">

                                <input
                                    type="hidden"
                                    name="order_id"
                                    value="<?= (int)$o['order_id'] ?>"
                                >


                                <select
                                    name="status"
                                    onchange="this.form.submit()"
                                >

                                    <?php foreach (
                                        [
                                            'Pending',
                                            'Processing',
                                            'Approved',
                                            'Rejected',
                                            'Ready',
                                        ] as $s
                                    ): ?>

                                        <option
                                            value="<?= e($s) ?>"
                                            <?= $s === $o['status']
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            <?= e($s) ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </form>

                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>

                    <td colspan="7">
                        No orders found.
                    </td>

                </tr>

            <?php endif; ?>

        </table>

    </div>

</div>


<?php

renderPageEnd();

?>