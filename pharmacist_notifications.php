<?php

require_once __DIR__ . '/dashboard_common.php';

requireDashboardRole('Pharmacist');

require_once __DIR__ . '/dashboard_nav.php';


$rows = $conn->query(
    "SELECT *
     FROM notifications
     WHERE request_type IN (
         'Low Stock Alert',
         'Add Medicine',
         'Update Medicine',
         'Restock Request'
     )
     ORDER BY submitted_date DESC
     LIMIT 100"
);


renderPageStart(
    'Pharmacist',
    'pharmacist_notifications.php',
    'Notifications'
);

?>


<div class="welcome">

    <h2>
        System Notifications
    </h2>

    <p>
        Low-stock and medicine-related alerts.
    </p>

</div>


<div class="panel">

    <div class="table-wrap">

        <table class="table">

            <tr>

                <th>
                    Type
                </th>

                <th>
                    Medicine
                </th>

                <th>
                    Details
                </th>

                <th>
                    Priority
                </th>

                <th>
                    Date
                </th>

            </tr>


            <?php while ($n = $rows->fetch_assoc()): ?>

                <tr>

                    <td>
                        <?= e($n['request_type']) ?>
                    </td>


                    <td>
                        <?= e($n['medicine_name'] ?? '-') ?>
                    </td>


                    <td>
                        <?= e($n['details']) ?>
                    </td>


                    <td>

                        <span
                            class="badge <?= in_array(
                                $n['priority'],
                                ['High', 'Critical']
                            ) ? 'out' : 'pending' ?>"
                        >
                            <?= e($n['priority']) ?>
                        </span>

                    </td>


                    <td>
                        <?= date(
                            'd M Y, h:i A',
                            strtotime($n['submitted_date'])
                        ) ?>
                    </td>

                </tr>

            <?php endwhile; ?>

        </table>

    </div>

</div>


<?php

renderPageEnd();

?>