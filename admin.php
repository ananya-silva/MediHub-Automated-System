<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';


/* =====================================================
   HELPER FUNCTIONS
===================================================== */

function response($success, $message = '', $data = [])
{
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}


function input($name, $default = '')
{
    return trim($_POST[$name] ?? $_GET[$name] ?? $default);
}


function rows($result)
{
    $data = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }

    return $data;
}


/* =====================================================
   ADMIN LOGIN CHECK
===================================================== */

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'Admin'
) {
    http_response_code(403);

    response(
        false,
        'Access denied. Please login as Admin.'
    );
}


$action = input('action');


try {

    switch ($action) {


        /* =================================================
           SESSION
        ================================================= */

        case 'session':

            response(
                true,
                'Session loaded.',
                [
                    'user_id' => $_SESSION['user_id'],
                    'name' => $_SESSION['name'] ?? 'Admin',
                    'role' => $_SESSION['role']
                ]
            );


        /* =================================================
           DASHBOARD
        ================================================= */

        case 'dashboard':

            $totalUsers = $conn->query(
                "SELECT COUNT(*) AS c
                 FROM users
                 WHERE role='Patient'"
            )->fetch_assoc()['c'] ?? 0;


            $pharmacists = $conn->query(
                "SELECT COUNT(*) AS c
                 FROM users
                 WHERE role='Pharmacist'"
            )->fetch_assoc()['c'] ?? 0;


            $suppliers = $conn->query(
                "SELECT COUNT(*) AS c
                 FROM suppliers"
            )->fetch_assoc()['c'] ?? 0;


            $medicines = $conn->query(
                "SELECT COUNT(*) AS c
                 FROM medicines"
            )->fetch_assoc()['c'] ?? 0;


            $todayOrders = $conn->query(
                "SELECT COUNT(*) AS c
                 FROM orders
                 WHERE DATE(order_date)=CURDATE()"
            )->fetch_assoc()['c'] ?? 0;


            $pendingApprovals = $conn->query(
                "SELECT COUNT(*) AS c
                 FROM notifications
                 WHERE admin_status='Pending'"
            )->fetch_assoc()['c'] ?? 0;


            $monthlyOrders = $conn->query(
                "SELECT COUNT(*) AS c
                 FROM orders
                 WHERE MONTH(order_date)=MONTH(CURDATE())
                 AND YEAR(order_date)=YEAR(CURDATE())"
            )->fetch_assoc()['c'] ?? 0;


            $revenue = $conn->query(
                "SELECT COALESCE(SUM(total_amount),0) AS total
                 FROM orders
                 WHERE status IN
                 ('Approved','Ready','Delivered')"
            )->fetch_assoc()['total'] ?? 0;


            response(
                true,
                'Dashboard loaded.',
                [
                    'total_users' => (int)$totalUsers,
                    'pharmacists' => (int)$pharmacists,
                    'suppliers' => (int)$suppliers,
                    'medicines' => (int)$medicines,
                    'today_orders' => (int)$todayOrders,
                    'pending_approvals' => (int)$pendingApprovals,
                    'monthly_orders' => (int)$monthlyOrders,
                    'revenue' => (float)$revenue
                ]
            );


        /* =================================================
           USERS
        ================================================= */

        case 'users':

            $sql = "
                SELECT
                    user_id,
                    full_name,
                    email,
                    username,
                    phone,
                    address,
                    role,
                    status
                FROM users
                WHERE role='Patient'
                ORDER BY user_id DESC
            ";

            response(
                true,
                'Users loaded.',
                rows($conn->query($sql))
            );


        /* =================================================
           GET USER FOR EDIT
        ================================================= */

        case 'get_user':

            $id = (int)input('user_id');

            if ($id <= 0) {
                response(false, 'Invalid user ID.');
            }


            $stmt = $conn->prepare("
                SELECT
                    user_id,
                    full_name,
                    email,
                    username,
                    phone,
                    address,
                    role,
                    status
                FROM users
                WHERE user_id=?
                AND role='Patient'
            ");

            $stmt->bind_param('i', $id);

            $stmt->execute();

            $user = $stmt->get_result()->fetch_assoc();


            if (!$user) {
                response(false, 'User not found.');
            }


            response(
                true,
                'User loaded.',
                $user
            );


        /* =================================================
           UPDATE USER
        ================================================= */

        case 'update_user':

            $id = (int)input('user_id');

            $name = input('full_name');
            $email = input('email');
            $username = input('username');
            $phone = input('phone');
            $address = input('address');


            if ($id <= 0) {
                response(false, 'Invalid user ID.');
            }


            if ($name === '' || $email === '' || $username === '') {
                response(
                    false,
                    'Name, email and username are required.'
                );
            }


            /* Check duplicate email/username */

            $check = $conn->prepare("
                SELECT user_id
                FROM users
                WHERE (email=? OR username=?)
                AND user_id<>?
                LIMIT 1
            ");

            $check->bind_param(
                'ssi',
                $email,
                $username,
                $id
            );

            $check->execute();

            if ($check->get_result()->num_rows > 0) {

                response(
                    false,
                    'Email or username already exists.'
                );
            }


            $stmt = $conn->prepare("
                UPDATE users
                SET
                    full_name=?,
                    email=?,
                    username=?,
                    phone=?,
                    address=?
                WHERE user_id=?
                AND role='Patient'
            ");

            $stmt->bind_param(
                'sssssi',
                $name,
                $email,
                $username,
                $phone,
                $address,
                $id
            );

            $stmt->execute();


            response(
                true,
                'User updated successfully.'
            );


        /* =================================================
           USER STATUS
        ================================================= */

        case 'user_status':

            $id = (int)input('user_id');
            $status = input('status');


            if (!in_array($status, ['Active', 'Inactive'])) {
                response(false, 'Invalid status.');
            }


            $stmt = $conn->prepare("
                UPDATE users
                SET status=?
                WHERE user_id=?
                AND role='Patient'
            ");

            $stmt->bind_param(
                'si',
                $status,
                $id
            );

            $stmt->execute();


            response(
                true,
                'User status updated.'
            );


        /* =================================================
           ADD USER
        ================================================= */

        case 'add_user':

            $name = input('full_name');
            $email = input('email');
            $username = input('username');
            $password = input('password');
            $phone = input('phone');
            $address = input('address');


            if (
                $name === '' ||
                $email === '' ||
                $username === '' ||
                $password === ''
            ) {
                response(false, 'Please fill all required fields.');
            }


            $check = $conn->prepare("
                SELECT user_id
                FROM users
                WHERE email=? OR username=?
                LIMIT 1
            ");

            $check->bind_param(
                'ss',
                $email,
                $username
            );

            $check->execute();


            if ($check->get_result()->num_rows > 0) {
                response(
                    false,
                    'Email or username already exists.'
                );
            }


            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );


            $stmt = $conn->prepare("
                INSERT INTO users
                (
                    full_name,
                    email,
                    username,
                    password_hash,
                    phone,
                    address,
                    role,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, 'Patient', 'Active')
            ");


            $stmt->bind_param(
                'ssssss',
                $name,
                $email,
                $username,
                $passwordHash,
                $phone,
                $address
            );


            $stmt->execute();


            response(
                true,
                'User added successfully.'
            );


        /* =================================================
           PHARMACISTS
        ================================================= */

        case 'pharmacists':

            $sql = "
                SELECT
                    user_id,
                    user_id AS pharmacist_id,
                    CONCAT('PH', LPAD(user_id, 3, '0')) AS pharmacist_code,
                    full_name,
                    email,
                    username,
                    phone,
                    address,
                    status
                FROM users
                WHERE role='Pharmacist'
                ORDER BY user_id DESC
            ";


            response(
                true,
                'Pharmacists loaded.',
                rows($conn->query($sql))
            );


        /* =================================================
           GET PHARMACIST
        ================================================= */

        case 'get_pharmacist':

            $id = (int)input('user_id');


            if ($id <= 0) {
                response(false, 'Invalid pharmacist ID.');
            }


            $stmt = $conn->prepare("
                SELECT
                    user_id,
                    full_name,
                    email,
                    username,
                    phone,
                    address,
                    role,
                    status
                FROM users
                WHERE user_id=?
                AND role='Pharmacist'
            ");


            $stmt->bind_param(
                'i',
                $id
            );

            $stmt->execute();


            $pharmacist =
                $stmt->get_result()->fetch_assoc();


            if (!$pharmacist) {
                response(false, 'Pharmacist not found.');
            }


            response(
                true,
                'Pharmacist loaded.',
                $pharmacist
            );


        /* =================================================
           UPDATE PHARMACIST
        ================================================= */

        case 'update_pharmacist':

            $id = (int)input('user_id');

            $name = input('full_name');
            $email = input('email');
            $username = input('username');
            $phone = input('phone');
            $address = input('address');


            if ($id <= 0) {
                response(false, 'Invalid pharmacist ID.');
            }


            if (
                $name === '' ||
                $email === '' ||
                $username === ''
            ) {
                response(
                    false,
                    'Name, email and username are required.'
                );
            }


            $check = $conn->prepare("
                SELECT user_id
                FROM users
                WHERE (email=? OR username=?)
                AND user_id<>?
                LIMIT 1
            ");


            $check->bind_param(
                'ssi',
                $email,
                $username,
                $id
            );


            $check->execute();


            if ($check->get_result()->num_rows > 0) {
                response(
                    false,
                    'Email or username already exists.'
                );
            }


            $stmt = $conn->prepare("
                UPDATE users
                SET
                    full_name=?,
                    email=?,
                    username=?,
                    phone=?,
                    address=?
                WHERE user_id=?
                AND role='Pharmacist'
            ");


            $stmt->bind_param(
                'sssssi',
                $name,
                $email,
                $username,
                $phone,
                $address,
                $id
            );


            $stmt->execute();


            response(
                true,
                'Pharmacist updated successfully.'
            );


        /* =================================================
           PHARMACIST STATUS
        ================================================= */

        case 'pharmacist_status':

            $id = (int)input('user_id');
            $status = input('status');


            if (!in_array($status, ['Active', 'Inactive'])) {
                response(false, 'Invalid status.');
            }


            $stmt = $conn->prepare("
                UPDATE users
                SET status=?
                WHERE user_id=?
                AND role='Pharmacist'
            ");


            $stmt->bind_param(
                'si',
                $status,
                $id
            );


            $stmt->execute();


            response(
                true,
                'Pharmacist status updated.'
            );


        /* =================================================
           ADD PHARMACIST
        ================================================= */

        case 'add_pharmacist':

            $name = input('full_name');
            $email = input('email');
            $username = input('username');
            $password = input('password');
            $phone = input('phone');
            $address = input('address');


            if (
                $name === '' ||
                $email === '' ||
                $username === '' ||
                $password === ''
            ) {
                response(false, 'Please fill all required fields.');
            }


            $check = $conn->prepare("
                SELECT user_id
                FROM users
                WHERE email=? OR username=?
                LIMIT 1
            ");


            $check->bind_param(
                'ss',
                $email,
                $username
            );


            $check->execute();


            if ($check->get_result()->num_rows > 0) {
                response(
                    false,
                    'Email or username already exists.'
                );
            }


            $passwordHash =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


            $stmt = $conn->prepare("
                INSERT INTO users
                (
                    full_name,
                    email,
                    username,
                    password_hash,
                    phone,
                    address,
                    role,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, 'Pharmacist', 'Active')
            ");


            $stmt->bind_param(
                'ssssss',
                $name,
                $email,
                $username,
                $passwordHash,
                $phone,
                $address
            );


            $stmt->execute();


            response(
                true,
                'Pharmacist added successfully.'
            );


        /* =================================================
           SUPPLIERS
        ================================================= */

        case 'suppliers':

            $sql = "
                SELECT
                    supplier_id,
                    supplier_name,
                    contact_person,
                    phone,
                    email,
                    address
                FROM suppliers
                ORDER BY supplier_id DESC
            ";


            response(
                true,
                'Suppliers loaded.',
                rows($conn->query($sql))
            );


        /* =================================================
           GET SUPPLIER
        ================================================= */

        case 'get_supplier':

            $id = (int)input('supplier_id');


            if ($id <= 0) {
                response(false, 'Invalid supplier ID.');
            }


            $stmt = $conn->prepare("
                SELECT
                    supplier_id,
                    supplier_name,
                    contact_person,
                    phone,
                    email,
                    address
                FROM suppliers
                WHERE supplier_id=?
            ");


            $stmt->bind_param(
                'i',
                $id
            );


            $stmt->execute();


            $supplier =
                $stmt->get_result()->fetch_assoc();


            if (!$supplier) {
                response(false, 'Supplier not found.');
            }


            response(
                true,
                'Supplier loaded.',
                $supplier
            );


        /* =================================================
           UPDATE SUPPLIER
        ================================================= */

        case 'update_supplier':

            $id = (int)input('supplier_id');

            $name = input('supplier_name');
            $contact = input('contact_person');
            $phone = input('phone');
            $email = input('email');
            $address = input('address');


            if ($id <= 0) {
                response(false, 'Invalid supplier ID.');
            }


            if ($name === '') {
                response(
                    false,
                    'Supplier name is required.'
                );
            }


            $stmt = $conn->prepare("
                UPDATE suppliers
                SET
                    supplier_name=?,
                    contact_person=?,
                    phone=?,
                    email=?,
                    address=?
                WHERE supplier_id=?
            ");


            $stmt->bind_param(
                'sssssi',
                $name,
                $contact,
                $phone,
                $email,
                $address,
                $id
            );


            $stmt->execute();


            response(
                true,
                'Supplier updated successfully.'
            );


        /* =================================================
           ADD SUPPLIER
        ================================================= */

        case 'add_supplier':

            $name = input('supplier_name');
            $contact = input('contact_person');
            $phone = input('phone');
            $email = input('email');
            $address = input('address');


            if ($name === '') {
                response(
                    false,
                    'Supplier name is required.'
                );
            }


            $stmt = $conn->prepare("
                INSERT INTO suppliers
                (
                    supplier_name,
                    contact_person,
                    phone,
                    email,
                    address
                )
                VALUES (?, ?, ?, ?, ?)
            ");


            $stmt->bind_param(
                'sssss',
                $name,
                $contact,
                $phone,
                $email,
                $address
            );


            $stmt->execute();


            response(
                true,
                'Supplier added successfully.'
            );


        /* =================================================
           DELETE SUPPLIER
        ================================================= */

        case 'delete_supplier':

            $id = (int)input('supplier_id');


            if ($id <= 0) {
                response(false, 'Invalid supplier ID.');
            }


            $stmt = $conn->prepare("
                DELETE FROM suppliers
                WHERE supplier_id=?
            ");


            $stmt->bind_param(
                'i',
                $id
            );


            $stmt->execute();


            if ($stmt->affected_rows <= 0) {
                response(false, 'Supplier not found.');
            }


            response(
                true,
                'Supplier deleted successfully.'
            );


        /* =================================================
           STOCK APPROVALS
        ================================================= */

        case 'stock_approvals':

            /*
             * Your database uses notifications for
             * pharmacist stock requests.
             */

            $sql = "
                SELECT
                    notification_id AS request_id,
                    medicine_name,
                    CAST(
                        SUBSTRING_INDEX(
                            details,
                            ': ',
                            -1
                        ) AS UNSIGNED
                    ) AS quantity,
                    submitted_date AS requested_at,
                    admin_status AS status
                FROM notifications
                WHERE request_type='Restock Request'
                ORDER BY submitted_date DESC
            ";


            response(
                true,
                'Stock approvals loaded.',
                rows($conn->query($sql))
            );


        /* =================================================
           APPROVE STOCK
        ================================================= */

        case 'approve_stock':

            $id = (int)input('request_id');


            if ($id <= 0) {
                response(false, 'Invalid request ID.');
            }


            $stmt = $conn->prepare("
                SELECT
                    notification_id,
                    medicine_id,
                    medicine_name,
                    details,
                    admin_status
                FROM notifications
                WHERE notification_id=?
            ");


            $stmt->bind_param(
                'i',
                $id
            );


            $stmt->execute();


            $notification =
                $stmt->get_result()->fetch_assoc();


            if (!$notification) {
                response(false, 'Stock request not found.');
            }


            if ($notification['admin_status'] !== 'Pending') {
                response(false, 'Request already processed.');
            }


            preg_match(
                '/Quantity requested:\s*(\d+)/',
                $notification['details'] ?? '',
                $match
            );


            $quantity =
                isset($match[1])
                    ? (int)$match[1]
                    : 0;


            if ($quantity <= 0) {
                response(
                    false,
                    'Invalid stock quantity.'
                );
            }


            $conn->begin_transaction();


            try {

                $updateMedicine = $conn->prepare("
                    UPDATE medicines
                    SET stock_quantity =
                        stock_quantity + ?
                    WHERE medicine_id=?
                ");


                $updateMedicine->bind_param(
                    'ii',
                    $quantity,
                    $notification['medicine_id']
                );


                $updateMedicine->execute();


                $updateNotification = $conn->prepare("
                    UPDATE notifications
                    SET admin_status='Approved'
                    WHERE notification_id=?
                ");


                $updateNotification->bind_param(
                    'i',
                    $id
                );


                $updateNotification->execute();


                $conn->commit();


                response(
                    true,
                    'Stock request approved.'
                );

            } catch (Throwable $e) {

                $conn->rollback();

                throw $e;
            }


        /* =================================================
           REJECT STOCK
        ================================================= */

        case 'reject_stock':

            $id = (int)input('request_id');


            if ($id <= 0) {
                response(false, 'Invalid request ID.');
            }


            $stmt = $conn->prepare("
                UPDATE notifications
                SET admin_status='Rejected'
                WHERE notification_id=?
                AND admin_status='Pending'
            ");


            $stmt->bind_param(
                'i',
                $id
            );


            $stmt->execute();


            if ($stmt->affected_rows <= 0) {
                response(
                    false,
                    'Request not found or already processed.'
                );
            }


            response(
                true,
                'Stock request rejected.'
            );


        /* =================================================
           NOTIFICATIONS
        ================================================= */

        case 'notifications':

            $sql = "
                SELECT
                    notification_id,
                    request_type,
                    medicine_id,
                    medicine_name,
                    details,
                    admin_status,
                    submitted_date,
                    'High' AS priority
                FROM notifications
                ORDER BY submitted_date DESC
                LIMIT 100
            ";


            response(
                true,
                'Notifications loaded.',
                rows($conn->query($sql))
            );


        /* =================================================
           NOTIFICATION ACTION
        ================================================= */

        case 'notification_action':

            $id = (int)input('notification_id');
            $status = input('status');


            if (
                !in_array(
                    $status,
                    ['Approved', 'Rejected']
                )
            ) {
                response(
                    false,
                    'Invalid notification status.'
                );
            }


            $stmt = $conn->prepare("
                UPDATE notifications
                SET admin_status=?
                WHERE notification_id=?
            ");


            $stmt->bind_param(
                'si',
                $status,
                $id
            );


            $stmt->execute();


            response(
                true,
                'Notification status updated.'
            );


        /* =================================================
           LOGOUT
        ================================================= */

        case 'logout':

            session_unset();

            session_destroy();


            response(
                true,
                'Logged out successfully.'
            );


        /* =================================================
           DEFAULT
        ================================================= */

        default:

            response(
                false,
                'Unknown action: ' . $action
            );
    }


} catch (Throwable $e) {

    if (
        isset($conn) &&
        $conn->errno
    ) {
        @$conn->rollback();
    }


    http_response_code(500);


    response(
        false,
        $e->getMessage()
    );
}

?>