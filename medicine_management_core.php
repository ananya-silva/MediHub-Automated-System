<?php

$role = $_SESSION['role'];

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    try {

        if ($action === 'add') {

            $name = trim($_POST['medicine_name'] ?? '');
            $dosage = trim($_POST['dosage'] ?? '');
            $price = (float) ($_POST['price'] ?? 0);
            $stock = (int) ($_POST['stock_quantity'] ?? 0);
            $min = (int) ($_POST['min_stock_level'] ?? 10);
            $expiry = $_POST['expiry_date'] ?? null;
            $cat = (int) ($_POST['category_id'] ?? 0);
            $sup = (int) ($_POST['supplier_id'] ?? 0);
            $rx = isset($_POST['requires_prescription']) ? 1 : 0;

            if (
                $name === '' ||
                $price < 0 ||
                $stock < 0 ||
                $min < 0 ||
                $cat <= 0 ||
                $sup <= 0
            ) {
                throw new RuntimeException(
                    'Please fill all required fields correctly.'
                );
            }

            $img = uploadMedicineImage(
                $_FILES['medicine_image'] ?? []
            );

            $stmt = $conn->prepare(
                "INSERT INTO medicines(
                    category_id,
                    supplier_id,
                    medicine_name,
                    dosage,
                    price,
                    stock_quantity,
                    min_stock_level,
                    expiry_date,
                    requires_prescription,
                    image_path
                ) VALUES(?,?,?,?,?,?,?,?,?,?)"
            );

            $stmt->bind_param(
                'iissdiisis',
                $cat,
                $sup,
                $name,
                $dosage,
                $price,
                $stock,
                $min,
                $expiry,
                $rx,
                $img
            );

            $stmt->execute();

            $newId = $stmt->insert_id;

            $stmt->close();

            $ins = $conn->prepare(
                "INSERT INTO notifications(
                    pharmacist_id,
                    request_type,
                    medicine_id,
                    medicine_name,
                    details,
                    priority,
                    admin_status
                ) VALUES(?,?,?,?,?,'Low','Pending')"
            );

            $detail = "New medicine added by {$role}.";
            $pid = $role === 'Pharmacist'
                ? (int) $_SESSION['user_id']
                : null;

            $req = 'Add Medicine';

            $ins->bind_param(
                'isiss',
                $pid,
                $req,
                $newId,
                $name,
                $detail
            );

            $ins->execute();
            $ins->close();

            $message = 'Medicine added successfully.';

        } elseif ($action === 'update') {

            $id = (int) $_POST['medicine_id'];
            $name = trim($_POST['medicine_name'] ?? '');
            $dosage = trim($_POST['dosage'] ?? '');
            $price = (float) $_POST['price'];
            $stock = (int) $_POST['stock_quantity'];
            $min = (int) $_POST['min_stock_level'];
            $expiry = $_POST['expiry_date'] ?? null;
            $cat = (int) $_POST['category_id'];
            $sup = (int) $_POST['supplier_id'];
            $rx = isset($_POST['requires_prescription']) ? 1 : 0;

            $img = uploadMedicineImage(
                $_FILES['medicine_image'] ?? []
            );

            if ($img !== '') {

                $stmt = $conn->prepare(
                    "UPDATE medicines SET
                        category_id=?,
                        supplier_id=?,
                        medicine_name=?,
                        dosage=?,
                        price=?,
                        stock_quantity=?,
                        min_stock_level=?,
                        expiry_date=?,
                        requires_prescription=?,
                        image_path=?
                    WHERE medicine_id=?"
                );

                $stmt->bind_param(
                    'iissdiisisi',
                    $cat,
                    $sup,
                    $name,
                    $dosage,
                    $price,
                    $stock,
                    $min,
                    $expiry,
                    $rx,
                    $img,
                    $id
                );

            } else {

                $stmt = $conn->prepare(
                    "UPDATE medicines SET
                        category_id=?,
                        supplier_id=?,
                        medicine_name=?,
                        dosage=?,
                        price=?,
                        stock_quantity=?,
                        min_stock_level=?,
                        expiry_date=?,
                        requires_prescription=?
                    WHERE medicine_id=?"
                );

                $stmt->bind_param(
                    'iissdiisii',
                    $cat,
                    $sup,
                    $name,
                    $dosage,
                    $price,
                    $stock,
                    $min,
                    $expiry,
                    $rx,
                    $id
                );
            }

            $stmt->execute();
            $stmt->close();

            $message = 'Medicine updated successfully.';

        } elseif ($action === 'delete') {

            $id = (int) $_POST['medicine_id'];

            $stmt = $conn->prepare(
                "DELETE FROM medicines
                 WHERE medicine_id=?"
            );

            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();

            $message = 'Medicine deleted successfully.';
        }

    } catch (Throwable $ex) {

        $error = $ex->getMessage();
    }
}


$categories = [];

$r = $conn->query(
    "SELECT category_id, category_name
     FROM categories
     ORDER BY category_name"
);

while ($x = $r->fetch_assoc()) {
    $categories[] = $x;
}


$suppliers = [];

$r = $conn->query(
    "SELECT supplier_id, supplier_name
     FROM suppliers
     ORDER BY supplier_name"
);

while ($x = $r->fetch_assoc()) {
    $suppliers[] = $x;
}


$medicines = [];

$r = $conn->query(
    "SELECT
        m.*,
        c.category_name,
        s.supplier_name
     FROM medicines m
     LEFT JOIN categories c
        ON c.category_id = m.category_id
     LEFT JOIN suppliers s
        ON s.supplier_id = m.supplier_id
     ORDER BY m.medicine_id DESC"
);

while ($x = $r->fetch_assoc()) {
    $medicines[] = $x;
}

?>

<div class="welcome">

    <h2>
        Manage Medicines
    </h2>

    <p>
        Add, update, remove and monitor medicines.
        Images uploaded here appear on the patient medicine search page.
    </p>

</div>


<?php if ($message): ?>

    <div class="notice">
        <?= e($message) ?>
    </div>

<?php endif; ?>


<?php if ($error): ?>

    <div class="notice error">
        <?= e($error) ?>
    </div>

<?php endif; ?>


<div class="panel" id="add-medicine">

    <div class="panel-head">

        <h3>
            <i class="fa-solid fa-pills"></i>
            Add New Medicine
        </h3>

    </div>


    <form
        method="post"
        enctype="multipart/form-data"
    >

        <input
            type="hidden"
            name="action"
            value="add"
        >


        <div class="form-grid">

            <div class="field">

                <label>
                    Medicine Name *
                </label>

                <input
                    name="medicine_name"
                    required
                >

            </div>


            <div class="field">

                <label>
                    Dosage
                </label>

                <input
                    name="dosage"
                    placeholder="e.g. 500mg"
                >

            </div>


            <div class="field">

                <label>
                    Price (LKR) *
                </label>

                <input
                    name="price"
                    type="number"
                    step="0.01"
                    min="0"
                    required
                >

            </div>


            <div class="field">

                <label>
                    Stock Quantity *
                </label>

                <input
                    name="stock_quantity"
                    type="number"
                    min="0"
                    required
                >

            </div>


            <div class="field">

                <label>
                    Minimum Stock Level *
                </label>

                <input
                    name="min_stock_level"
                    type="number"
                    min="0"
                    value="10"
                    required
                >

            </div>


            <div class="field">

                <label>
                    Expiry Date *
                </label>

                <input
                    name="expiry_date"
                    type="date"
                    required
                >

            </div>


            <div class="field">

                <label>
                    Category *
                </label>

                <select
                    name="category_id"
                    required
                >

                    <option value="">
                        Select category
                    </option>

                    <?php foreach ($categories as $c): ?>

                        <option
                            value="<?= $c['category_id'] ?>"
                        >
                            <?= e($c['category_name']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="field">

                <label>
                    Supplier *
                </label>

                <select
                    name="supplier_id"
                    required
                >

                    <option value="">
                        Select supplier
                    </option>

                    <?php foreach ($suppliers as $s): ?>

                        <option
                            value="<?= $s['supplier_id'] ?>"
                        >
                            <?= e($s['supplier_name']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="field">

                <label>
                    Medicine Image
                </label>

                <input
                    name="medicine_image"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                >

                <small>
                    JPG/PNG/WEBP, max 3MB
                </small>

            </div>


            <div class="field">

                <label>
                    Prescription
                </label>

                <label style="font-weight:400">

                    <input
                        type="checkbox"
                        name="requires_prescription"
                        value="1"
                    >

                    Prescription required

                </label>

            </div>

        </div>


        <div class="actions">

            <button
                class="btn"
                type="submit"
            >

                <i class="fa-solid fa-plus"></i>

                Add Medicine

            </button>

        </div>

    </form>

</div>


<div class="panel">

    <div class="panel-head">

        <h3>
            Medicine Inventory
        </h3>

        <span>
            <?= count($medicines) ?> medicines
        </span>

    </div>


    <div class="table-wrap">

        <table class="table">

            <tr>

                <th>Image</th>
                <th>Medicine</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Expiry</th>
                <th>Prescription</th>
                <th>Actions</th>

            </tr>


            <?php foreach ($medicines as $m): ?>

                <?php
                $stock = (int) $m['stock_quantity'];
                ?>

                <tr>

                    <td>

                        <img
                            class="medicine-thumb"
                            src="<?= e(medicineImageFromRow($m)) ?>"
                            alt=""
                        >

                    </td>


                    <td>

                        <strong>
                            <?= e($m['medicine_name']) ?>
                        </strong>

                        <br>

                        <small>
                            <?= e($m['dosage']) ?>
                        </small>

                    </td>


                    <td>
                        <?= e($m['category_name'] ?? '-') ?>
                    </td>


                    <td>
                        LKR <?= number_format($m['price'], 2) ?>
                    </td>


                    <td>

                        <span
                            class="badge
                            <?= $stock === 0
                                ? 'out'
                                : (
                                    $stock <= (int) $m['min_stock_level']
                                        ? 'low'
                                        : 'ok'
                                )
                            ?>"
                        >
                            <?= $stock ?>
                        </span>

                    </td>


                    <td>
                        <?= e($m['expiry_date']) ?>
                    </td>


                    <td>
                        <?= ((int) $m['requires_prescription'])
                            ? 'Yes'
                            : 'No'
                        ?>
                    </td>


                    <td>

                        <a
                            class="btn secondary"
                            href="#edit<?= $m['medicine_id'] ?>"
                        >
                            Edit
                        </a>


                        <form
                            style="display:inline"
                            method="post"
                            onsubmit="return confirm('Delete this medicine?');"
                        >

                            <input
                                type="hidden"
                                name="action"
                                value="delete"
                            >

                            <input
                                type="hidden"
                                name="medicine_id"
                                value="<?= $m['medicine_id'] ?>"
                            >

                            <button
                                class="btn danger"
                                type="submit"
                            >
                                Delete
                            </button>

                        </form>

                    </td>

                </tr>


                <tr id="edit<?= $m['medicine_id'] ?>">

                    <td colspan="8">

                        <details>

                            <summary>
                                Edit <?= e($m['medicine_name']) ?>
                            </summary>


                            <form
                                method="post"
                                enctype="multipart/form-data"
                                style="padding-top:15px"
                            >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="update"
                                >

                                <input
                                    type="hidden"
                                    name="medicine_id"
                                    value="<?= $m['medicine_id'] ?>"
                                >


                                <div class="form-grid">

                                    <div class="field">

                                        <label>
                                            Name
                                        </label>

                                        <input
                                            name="medicine_name"
                                            value="<?= e($m['medicine_name']) ?>"
                                            required
                                        >

                                    </div>


                                    <div class="field">

                                        <label>
                                            Dosage
                                        </label>

                                        <input
                                            name="dosage"
                                            value="<?= e($m['dosage']) ?>"
                                        >

                                    </div>


                                    <div class="field">

                                        <label>
                                            Price
                                        </label>

                                        <input
                                            name="price"
                                            type="number"
                                            step="0.01"
                                            value="<?= $m['price'] ?>"
                                            required
                                        >

                                    </div>


                                    <div class="field">

                                        <label>
                                            Stock
                                        </label>

                                        <input
                                            name="stock_quantity"
                                            type="number"
                                            min="0"
                                            value="<?= $stock ?>"
                                            required
                                        >

                                    </div>


                                    <div class="field">

                                        <label>
                                            Minimum Stock
                                        </label>

                                        <input
                                            name="min_stock_level"
                                            type="number"
                                            min="0"
                                            value="<?= $m['min_stock_level'] ?>"
                                            required
                                        >

                                    </div>


                                    <div class="field">

                                        <label>
                                            Expiry
                                        </label>

                                        <input
                                            name="expiry_date"
                                            type="date"
                                            value="<?= $m['expiry_date'] ?>"
                                            required
                                        >

                                    </div>


                                    <div class="field">

                                        <label>
                                            Category
                                        </label>

                                        <select name="category_id">

                                            <?php foreach ($categories as $c): ?>

                                                <option
                                                    value="<?= $c['category_id'] ?>"
                                                    <?= $m['category_id'] == $c['category_id']
                                                        ? 'selected'
                                                        : ''
                                                    ?>
                                                >
                                                    <?= e($c['category_name']) ?>
                                                </option>

                                            <?php endforeach; ?>

                                        </select>

                                    </div>


                                    <div class="field">

                                        <label>
                                            Supplier
                                        </label>

                                        <select name="supplier_id">

                                            <?php foreach ($suppliers as $s): ?>

                                                <option
                                                    value="<?= $s['supplier_id'] ?>"
                                                    <?= $m['supplier_id'] == $s['supplier_id']
                                                        ? 'selected'
                                                        : ''
                                                    ?>
                                                >
                                                    <?= e($s['supplier_name']) ?>
                                                </option>

                                            <?php endforeach; ?>

                                        </select>

                                    </div>


                                    <div class="field">

                                        <label>
                                            Replace Image
                                        </label>

                                        <input
                                            name="medicine_image"
                                            type="file"
                                            accept="image/jpeg,image/png,image/webp"
                                        >

                                    </div>


                                    <div class="field">

                                        <label>
                                            Prescription
                                        </label>

                                        <label style="font-weight:400">

                                            <input
                                                type="checkbox"
                                                name="requires_prescription"
                                                value="1"
                                                <?= $m['requires_prescription']
                                                    ? 'checked'
                                                    : ''
                                                ?>
                                            >

                                            Required

                                        </label>

                                    </div>

                                </div>


                                <div class="actions">

                                    <button
                                        class="btn"
                                        type="submit"
                                    >
                                        Save Changes
                                    </button>

                                </div>

                            </form>

                        </details>

                    </td>

                </tr>

            <?php endforeach; ?>

        </table>

    </div>

</div>