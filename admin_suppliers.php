<?php

require_once __DIR__ . '/dashboard_common.php';

requireDashboardRole('Admin');

require_once __DIR__ . '/dashboard_nav.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['supplier_name'] ?? '');
    $person = trim($_POST['contact_person'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name !== '') {

        $s = $conn->prepare(
            'INSERT INTO suppliers(
                supplier_name,
                contact_person,
                phone,
                email,
                address
            )
            VALUES (?, ?, ?, ?, ?)'
        );

        $s->bind_param(
            'sssss',
            $name,
            $person,
            $phone,
            $email,
            $address
        );

        $s->execute();
        $s->close();

        $msg = 'Supplier added.';
    }
}

$rows = $conn->query(
    'SELECT *
     FROM suppliers
     ORDER BY supplier_id DESC'
);

renderPageStart(
    'Admin',
    'admin_suppliers.php',
    'Manage Suppliers'
);

?>

<div class="welcome">

    <h2>Manage Suppliers</h2>

    <p>
        Maintain supplier information used by medicine records.
    </p>

</div>

<?php if ($msg): ?>

    <div class="notice">
        <?= e($msg) ?>
    </div>

<?php endif; ?>


<div class="panel">

    <div class="panel-head">
        <h3>Add Supplier</h3>
    </div>

    <form method="post">

        <div class="form-grid">

            <div class="field">
                <label>Supplier Name *</label>
                <input
                    name="supplier_name"
                    required
                >
            </div>

            <div class="field">
                <label>Contact Person</label>
                <input name="contact_person">
            </div>

            <div class="field">
                <label>Phone</label>
                <input name="phone">
            </div>

            <div class="field">
                <label>Email</label>
                <input
                    name="email"
                    type="email"
                >
            </div>

            <div class="field full">

                <label>Address</label>

                <textarea name="address"></textarea>

            </div>

        </div>

        <div class="actions">

            <button class="btn">
                Add Supplier
            </button>

        </div>

    </form>

</div>


<div class="panel">

    <div class="table-wrap">

        <table class="table">

            <tr>
                <th>Supplier</th>
                <th>Contact</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Address</th>
            </tr>

            <?php while ($s = $rows->fetch_assoc()): ?>

                <tr>

                    <td>
                        <?= e($s['supplier_name']) ?>
                    </td>

                    <td>
                        <?= e($s['contact_person']) ?>
                    </td>

                    <td>
                        <?= e($s['phone']) ?>
                    </td>

                    <td>
                        <?= e($s['email']) ?>
                    </td>

                    <td>
                        <?= e($s['address']) ?>
                    </td>

                </tr>

            <?php endwhile; ?>

        </table>

    </div>

</div>


<?php

renderPageEnd();

?>