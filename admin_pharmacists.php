<?php

require_once __DIR__ . '/dashboard_common.php';
require_once __DIR__ . '/dashboard_nav.php';

requireDashboardRole('Admin');

$message = '';
$error = '';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'add_pharmacist'
) {
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (
        $name === '' ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        $username === '' ||
        strlen($password) < 8
    ) {
        $error = 'Please provide valid details. Password must contain at least 8 characters.';
    } else {

        $check = $conn->prepare(
            'SELECT user_id
             FROM users
             WHERE email = ? OR username = ?
             LIMIT 1'
        );

        $check->bind_param('ss', $email, $username);
        $check->execute();

        $exists = $check->get_result()->num_rows > 0;

        $check->close();

        if ($exists) {
            $error = 'Email or username already exists.';
        } else {

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $role = 'Pharmacist';
            $status = 'Active';

            $stmt = $conn->prepare(
                'INSERT INTO users(
                    full_name,
                    email,
                    username,
                    password_hash,
                    phone,
                    address,
                    role,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );

            $stmt->bind_param(
                'ssssssss',
                $name,
                $email,
                $username,
                $hash,
                $phone,
                $address,
                $role,
                $status
            );

            if ($stmt->execute()) {
                $message = 'Pharmacist account created successfully.';
            } else {
                $error = 'Unable to create pharmacist account.';
            }

            $stmt->close();
        }
    }
}

$rows = $conn->query("
    SELECT
        user_id,
        full_name,
        email,
        username,
        phone,
        status,
        created_at
    FROM users
    WHERE role = 'Pharmacist'
    ORDER BY user_id DESC
");

renderPageStart(
    'Admin',
    'admin_pharmacists.php',
    'Manage Pharmacists'
);

?>

<div class="welcome">

    <h2>Manage Pharmacists</h2>

    <p>
        Add pharmacy staff and manage active pharmacist accounts.
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


<div class="panel" id="add-pharmacist">

    <div class="panel-head">

        <h3>
            <i class="fa-solid fa-user-doctor"></i>
            Add New Pharmacist
        </h3>

    </div>

    <form method="post">

        <input
            type="hidden"
            name="action"
            value="add_pharmacist"
        >

        <div class="form-grid">

            <div class="field">
                <label>Full Name *</label>
                <input
                    name="full_name"
                    required
                >
            </div>

            <div class="field">
                <label>Email *</label>
                <input
                    type="email"
                    name="email"
                    required
                >
            </div>

            <div class="field">
                <label>Username *</label>
                <input
                    name="username"
                    required
                >
            </div>

            <div class="field">
                <label>Password *</label>
                <input
                    type="password"
                    name="password"
                    minlength="8"
                    required
                >
            </div>

            <div class="field">
                <label>Phone</label>
                <input name="phone">
            </div>

            <div class="field full">
                <label>Address</label>
                <textarea
                    name="address"
                    rows="2"
                ></textarea>
            </div>

        </div>

        <div class="actions">

            <button
                class="btn"
                type="submit"
            >
                <i class="fa-solid fa-user-plus"></i>
                Create Pharmacist
            </button>

        </div>

    </form>

</div>


<div class="panel">

    <div class="panel-head">

        <h3>Pharmacist Accounts</h3>

        <span>
            <?= ($rows ? $rows->num_rows : 0) ?>
            pharmacists
        </span>

    </div>

    <div class="table-wrap">

        <table class="table">

            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Username</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Created</th>
            </tr>

            <?php while ($u = $rows->fetch_assoc()): ?>

                <tr>

                    <td>
                        #<?= e($u['user_id']) ?>
                    </td>

                    <td>
                        <?= e($u['full_name']) ?>
                    </td>

                    <td>
                        <?= e($u['email']) ?>
                    </td>

                    <td>
                        <?= e($u['username']) ?>
                    </td>

                    <td>
                        <?= e($u['phone'] ?? '') ?>
                    </td>

                    <td>

                        <span
                            class="badge <?= $u['status'] === 'Active' ? 'ok' : 'out' ?>"
                        >
                            <?= e($u['status']) ?>
                        </span>

                    </td>

                    <td>
                        <?= e(date('d M Y', strtotime($u['created_at']))) ?>
                    </td>

                </tr>

            <?php endwhile; ?>

        </table>

    </div>

</div>


<?php

renderPageEnd();

?>