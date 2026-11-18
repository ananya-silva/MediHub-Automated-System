<?php

require_once __DIR__ . '/dashboard_common.php';

requireDashboardRole('Pharmacist');

require_once __DIR__ . '/dashboard_nav.php';


$rows = $conn->query(
    "SELECT
        m.*,
        c.category_name,
        s.supplier_name
     FROM medicines m
     LEFT JOIN categories c
        ON c.category_id = m.category_id
     LEFT JOIN suppliers s
        ON s.supplier_id = m.supplier_id
     ORDER BY m.stock_quantity ASC"
);


renderPageStart(
    'Pharmacist',
    'pharmacist_inventory.php',
    'Inventory'
);

?>


<div class="welcome">

    <h2>
        Inventory
    </h2>

    <p>
        Live stock levels, minimum stock alerts and expiry monitoring.
    </p>

</div>


<div class="panel">

    <div class="table-wrap">

        <table class="table">

            <tr>

                <th>
                    Medicine
                </th>

                <th>
                    Category
                </th>

                <th>
                    Stock
                </th>

                <th>
                    Minimum
                </th>

                <th>
                    Expiry
                </th>

                <th>
                    Supplier
                </th>

                <th>
                    Status
                </th>

            </tr>


            <?php while ($m = $rows->fetch_assoc()): ?>

                <?php

                $stock = (int) $m['stock_quantity'];

                $status = $stock === 0
                    ? 'Out of Stock'
                    : (
                        $stock <= (int) $m['min_stock_level']
                            ? 'Low Stock'
                            : 'In Stock'
                    );

                ?>


                <tr>

                    <td>

                        <img
                            class="medicine-thumb"
                            src="<?= e(medicineImageFromRow($m)) ?>"
                        >

                        <strong>
                            <?= e($m['medicine_name']) ?>
                        </strong>

                    </td>


                    <td>
                        <?= e($m['category_name']) ?>
                    </td>


                    <td>
                        <?= number_format($stock) ?>
                    </td>


                    <td>
                        <?= number_format($m['min_stock_level']) ?>
                    </td>


                    <td>
                        <?= e($m['expiry_date']) ?>
                    </td>


                    <td>
                        <?= e($m['supplier_name']) ?>
                    </td>


                    <td>

                        <span
                            class="badge <?= $stock === 0
                                ? 'out'
                                : (
                                    $stock <= (int) $m['min_stock_level']
                                        ? 'low'
                                        : 'ok'
                                ) ?>"
                        >
                            <?= $status ?>
                        </span>

                    </td>

                </tr>


            <?php endwhile; ?>

        </table>

    </div>

</div>


<?php

renderPageEnd();

?>