<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';


/* =========================================================
   RESPONSE FUNCTION
========================================================= */

function response($success, $message = '', $data = [])
{
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);

    exit;
}


/* =========================================================
   POST HELPER
========================================================= */

function post($key, $default = '')
{
    return trim($_POST[$key] ?? $default);
}


/* =========================================================
   USER ROLE
========================================================= */

function getUserRole()
{
    return $_SESSION['role'] ?? '';
}


/* =========================================================
   LOGIN CHECK
========================================================= */

if (!isset($_SESSION['user_id'])) {

    http_response_code(401);

    response(false, 'Please login first.');
}


if (strtolower(getUserRole()) !== 'pharmacist') {

    http_response_code(403);

    response(false, 'Access denied. Pharmacist access required.');
}


$action = $_POST['action'] ?? $_GET['action'] ?? '';


try {

    switch ($action) {


        /* =====================================================
           SESSION
        ===================================================== */

        case 'session':

            response(true, 'Session active.', [

                'user_id' => $_SESSION['user_id'],

                'name' => $_SESSION['name'] ?? 'Pharmacist',

                'role' => $_SESSION['role']

            ]);

            break;


        /* =====================================================
           DASHBOARD
        ===================================================== */

        case 'dashboard':

            $pending = $conn->query("
                SELECT COUNT(*)
                FROM orders
                WHERE status = 'Pending'
            ")->fetch_row()[0];


            $lowStock = $conn->query("
                SELECT COUNT(*)
                FROM medicines
                WHERE stock_quantity <= min_stock_level
            ")->fetch_row()[0];


            $todayOrders = $conn->query("
                SELECT COUNT(*)
                FROM orders
                WHERE DATE(order_date) = CURDATE()
            ")->fetch_row()[0];


            $medicineCount = $conn->query("
                SELECT COUNT(*)
                FROM medicines
            ")->fetch_row()[0];


            response(true, 'Dashboard loaded.', [

                'pending_orders' => (int)$pending,

                'low_stock' => (int)$lowStock,

                'today_orders' => (int)$todayOrders,

                'total_medicines' => (int)$medicineCount

            ]);

            break;


        /* =====================================================
           ORDER STATUS
        ===================================================== */

        case 'order_status':

            $pending = 0;
            $approved = 0;
            $rejected = 0;


            $result = $conn->query("
                SELECT status, COUNT(*) AS total
                FROM orders
                GROUP BY status
            ");


            while ($row = $result->fetch_assoc()) {

                if ($row['status'] === 'Pending') {
                    $pending = (int)$row['total'];
                }

                if ($row['status'] === 'Approved') {
                    $approved = (int)$row['total'];
                }

                if ($row['status'] === 'Rejected') {
                    $rejected = (int)$row['total'];
                }
            }


            $total = $pending + $approved + $rejected;


            $pendingPercent = 0;
            $approvedPercent = 0;
            $rejectedPercent = 0;


            if ($total > 0) {

                $pendingPercent =
                    round(($pending / $total) * 100);

                $approvedPercent =
                    round(($approved / $total) * 100);

                $rejectedPercent =
                    round(($rejected / $total) * 100);
            }


            response(true, 'Order status loaded.', [

                'pending' => $pending,

                'approved' => $approved,

                'rejected' => $rejected,

                'total' => $total,

                'pending_percent' => $pendingPercent,

                'approved_percent' => $approvedPercent,

                'rejected_percent' => $rejectedPercent

            ]);

            break;


        /* =====================================================
           CATEGORIES
        ===================================================== */

        case 'categories':

            $result = $conn->query("
                SELECT category_id, category_name
                FROM categories
                ORDER BY category_name
            ");


            $data = [];


            while ($row = $result->fetch_assoc()) {

                $data[] = $row;
            }


            response(true, 'Categories loaded.', $data);

            break;


        /* =====================================================
           SUPPLIERS
        ===================================================== */

        case 'suppliers':

            $result = $conn->query("
                SELECT
                    supplier_id,
                    supplier_name,
                    phone,
                    email
                FROM suppliers
                ORDER BY supplier_name
            ");


            $data = [];


            while ($row = $result->fetch_assoc()) {

                $data[] = $row;
            }


            response(true, 'Suppliers loaded.', $data);

            break;


        /* =====================================================
           MEDICINES
        ===================================================== */

        case 'medicines':

            $result = $conn->query("
                SELECT

                    m.medicine_id,

                    m.medicine_name,

                    m.dosage,

                    m.price,

                    m.stock_quantity,

                    m.min_stock_level,

                    m.expiry_date,

                    m.requires_prescription,

                    m.category_id,

                    c.category_name,

                    m.supplier_id,

                    s.supplier_name,

                    s.phone AS supplier_phone

                FROM medicines m

                LEFT JOIN categories c
                    ON c.category_id = m.category_id

                LEFT JOIN suppliers s
                    ON s.supplier_id = m.supplier_id

                ORDER BY m.medicine_id DESC
            ");


            $data = [];


            while ($row = $result->fetch_assoc()) {

                $data[] = $row;
            }


            response(true, 'Medicines loaded.', $data);

            break;


        /* =====================================================
           ADD MEDICINE
        ===================================================== */

        case 'add_medicine':

            $name = post('medicine_name');

            $category =
                (int)($_POST['category_id'] ?? 0);

            $supplier =
                (int)($_POST['supplier_id'] ?? 0);

            $dosage = post('dosage');

            $price =
                (float)($_POST['price'] ?? 0);

            $stock =
                (int)($_POST['stock_quantity'] ?? 0);

            $minStock =
                (int)($_POST['min_stock_level'] ?? 10);

            $expiry = post('expiry_date');

            $prescription =
                (int)($_POST['requires_prescription'] ?? 0);


            if (
                $name === '' ||
                $category <= 0 ||
                $supplier <= 0 ||
                $price < 0 ||
                $stock < 0 ||
                $expiry === ''
            ) {

                response(
                    false,
                    'Please fill all required fields.'
                );
            }


            $stmt = $conn->prepare("
                INSERT INTO medicines
                (
                    category_id,
                    supplier_id,
                    medicine_name,
                    dosage,
                    price,
                    stock_quantity,
                    min_stock_level,
                    expiry_date,
                    requires_prescription
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");


            $stmt->bind_param(
                "iissdiisi",
                $category,
                $supplier,
                $name,
                $dosage,
                $price,
                $stock,
                $minStock,
                $expiry,
                $prescription
            );


            if (!$stmt->execute()) {

                response(
                    false,
                    'Failed to add medicine.'
                );
            }


            $medicineId = $stmt->insert_id;


            createNotification(
                $conn,
                'Add Medicine',
                $medicineId,
                $name,
                'New medicine added by pharmacist.',
                'Pending'
            );


            response(
                true,
                'Medicine added successfully.',
                [
                    'medicine_id' => $medicineId
                ]
            );

            break;


        /* =====================================================
           UPDATE MEDICINE
        ===================================================== */

        case 'update_medicine':

            $id =
                (int)($_POST['medicine_id'] ?? 0);

            $name =
                post('medicine_name');

            $category =
                (int)($_POST['category_id'] ?? 0);

            $supplier =
                (int)($_POST['supplier_id'] ?? 0);

            $dosage =
                post('dosage');

            $price =
                (float)($_POST['price'] ?? 0);

            $stock =
                (int)($_POST['stock_quantity'] ?? 0);

            $minStock =
                (int)($_POST['min_stock_level'] ?? 10);

            $expiry =
                post('expiry_date');

            $prescription =
                (int)($_POST['requires_prescription'] ?? 0);


            if (
                $id <= 0 ||
                $name === '' ||
                $category <= 0 ||
                $supplier <= 0 ||
                $price < 0 ||
                $stock < 0 ||
                $expiry === ''
            ) {

                response(
                    false,
                    'Invalid medicine details.'
                );
            }


            $stmt = $conn->prepare("
                UPDATE medicines

                SET

                    category_id = ?,

                    supplier_id = ?,

                    medicine_name = ?,

                    dosage = ?,

                    price = ?,

                    stock_quantity = ?,

                    min_stock_level = ?,

                    expiry_date = ?,

                    requires_prescription = ?

                WHERE medicine_id = ?
            ");


            $stmt->bind_param(
                "iissdiisii",
                $category,
                $supplier,
                $name,
                $dosage,
                $price,
                $stock,
                $minStock,
                $expiry,
                $prescription,
                $id
            );


            if (!$stmt->execute()) {

                response(
                    false,
                    'Failed to update medicine.'
                );
            }


            createNotification(
                $conn,
                'Update Medicine',
                $id,
                $name,
                'Medicine updated by pharmacist.',
                'Pending'
            );


            response(
                true,
                'Medicine updated successfully.'
            );

            break;


        /* =====================================================
           DELETE MEDICINE
        ===================================================== */

        case 'delete_medicine':

            $id =
                (int)($_POST['medicine_id'] ?? 0);


            if ($id <= 0) {

                response(
                    false,
                    'Invalid medicine ID.'
                );
            }


            $stmt = $conn->prepare("
                SELECT medicine_name
                FROM medicines
                WHERE medicine_id = ?
            ");


            $stmt->bind_param("i", $id);

            $stmt->execute();


            $result =
                $stmt->get_result();

            $medicine =
                $result->fetch_assoc();


            if (!$medicine) {

                response(
                    false,
                    'Medicine not found.'
                );
            }


            $delete = $conn->prepare("
                DELETE FROM medicines
                WHERE medicine_id = ?
            ");


            $delete->bind_param("i", $id);


            if (!$delete->execute()) {

                response(
                    false,
                    'Unable to delete medicine.'
                );
            }


            createNotification(
                $conn,
                'Delete Medicine',
                $id,
                $medicine['medicine_name'],
                'Medicine deleted by pharmacist.',
                'Pending'
            );


            response(
                true,
                'Medicine deleted successfully.'
            );

            break;


        /* =====================================================
           ORDERS
        ===================================================== */

        case 'orders':

            $result = $conn->query("
                SELECT

                    o.order_id,

                    o.patient_id,

                    u.full_name AS patient,

                    o.total_amount,

                    o.status,

                    o.order_date,

                    o.prescription_id,

                    COALESCE(
                        GROUP_CONCAT(
                            CONCAT(
                                m.medicine_name,
                                ' x',
                                oi.quantity
                            )
                            SEPARATOR ', '
                        ),
                        ''
                    ) AS medicines

                FROM orders o

                JOIN users u
                    ON u.user_id = o.patient_id

                LEFT JOIN order_item oi
                    ON oi.order_id = o.order_id

                LEFT JOIN medicines m
                    ON m.medicine_id = oi.medicine_id

                GROUP BY o.order_id

                ORDER BY o.order_date DESC
            ");


            $data = [];


            while ($row = $result->fetch_assoc()) {

                $data[] = $row;
            }


            response(
                true,
                'Orders loaded.',
                $data
            );

            break;


        /* =====================================================
           ORDER DETAILS
        ===================================================== */

        case 'order_details':

            $orderId =
                (int)(
                    $_POST['order_id']
                    ?? $_GET['order_id']
                    ?? 0
                );


            if ($orderId <= 0) {

                response(
                    false,
                    'Invalid order ID.'
                );
            }


            $stmt = $conn->prepare("
                SELECT

                    o.*,

                    u.full_name AS patient,

                    u.email

                FROM orders o

                JOIN users u
                    ON u.user_id = o.patient_id

                WHERE o.order_id = ?
            ");


            $stmt->bind_param(
                "i",
                $orderId
            );

            $stmt->execute();


            $order =
                $stmt
                    ->get_result()
                    ->fetch_assoc();


            if (!$order) {

                response(
                    false,
                    'Order not found.'
                );
            }


            $stmt = $conn->prepare("
                SELECT

                    oi.*,

                    m.medicine_name

                FROM order_item oi

                JOIN medicines m
                    ON m.medicine_id = oi.medicine_id

                WHERE oi.order_id = ?
            ");


            $stmt->bind_param(
                "i",
                $orderId
            );

            $stmt->execute();


            $result =
                $stmt->get_result();


            $items = [];


            while ($row = $result->fetch_assoc()) {

                $items[] = $row;
            }


            $order['items'] = $items;


            response(
                true,
                'Order details loaded.',
                $order
            );

            break;


        /* =====================================================
           APPROVE ORDER
        ===================================================== */

        case 'approve_order':

            $orderId =
                (int)(
                    $_POST['order_id'] ?? 0
                );


            if ($orderId <= 0) {

                response(
                    false,
                    'Invalid order ID.'
                );
            }


            $stmt = $conn->prepare("
                UPDATE orders

                SET status = 'Approved'

                WHERE order_id = ?

                AND status = 'Pending'
            ");


            $stmt->bind_param(
                "i",
                $orderId
            );

            $stmt->execute();


            if ($stmt->affected_rows > 0) {

                // Fetch patient details for the email notification.
                $mailStmt = $conn->prepare("
                    SELECT u.full_name, u.email
                    FROM orders o
                    JOIN users u ON u.user_id = o.patient_id
                    WHERE o.order_id = ?
                    LIMIT 1
                ");

                if ($mailStmt) {
                    $mailStmt->bind_param('i', $orderId);
                    $mailStmt->execute();
                    $patient = $mailStmt->get_result()->fetch_assoc();
                    $mailStmt->close();

                    if ($patient && !empty($patient['email'])) {
                        sendOrderStatusEmail(
                            (string)$patient['full_name'],
                            (string)$patient['email'],
                            $orderId,
                            'Approved'
                        );
                    }
                }

                response(
                    true,
                    'Order approved successfully. Patient email notification sent.'
                );
            }


            response(
                false,
                'Order could not be approved.'
            );

            break;


        /* =====================================================
           REJECT ORDER
        ===================================================== */

        case 'reject_order':

            $orderId =
                (int)(
                    $_POST['order_id'] ?? 0
                );


            if ($orderId <= 0) {

                response(
                    false,
                    'Invalid order ID.'
                );
            }


            $stmt = $conn->prepare("
                UPDATE orders

                SET status = 'Rejected'

                WHERE order_id = ?

                AND status = 'Pending'
            ");


            $stmt->bind_param(
                "i",
                $orderId
            );

            $stmt->execute();


            if ($stmt->affected_rows > 0) {

                // Fetch patient details for the email notification.
                $mailStmt = $conn->prepare("
                    SELECT u.full_name, u.email
                    FROM orders o
                    JOIN users u ON u.user_id = o.patient_id
                    WHERE o.order_id = ?
                    LIMIT 1
                ");

                if ($mailStmt) {
                    $mailStmt->bind_param('i', $orderId);
                    $mailStmt->execute();
                    $patient = $mailStmt->get_result()->fetch_assoc();
                    $mailStmt->close();

                    if ($patient && !empty($patient['email'])) {
                        sendOrderStatusEmail(
                            (string)$patient['full_name'],
                            (string)$patient['email'],
                            $orderId,
                            'Rejected'
                        );
                    }
                }

                response(
                    true,
                    'Order rejected successfully. Patient email notification sent.'
                );
            }


            response(
                false,
                'Order could not be rejected.'
            );

            break;


        /* =====================================================
           LOW STOCK
        ===================================================== */

        case 'low_stock':

            $result = $conn->query("
                SELECT

                    medicine_id,

                    medicine_name,

                    stock_quantity,

                    min_stock_level

                FROM medicines

                WHERE stock_quantity <= min_stock_level

                ORDER BY stock_quantity ASC
            ");


            $data = [];


            while ($row = $result->fetch_assoc()) {

                $data[] = $row;
            }


            response(
                true,
                'Low stock loaded.',
                $data
            );

            break;


        /* =====================================================
           INVENTORY
        ===================================================== */

        case 'inventory':

            $result = $conn->query("
                SELECT

                    m.medicine_id,

                    m.medicine_name,

                    m.stock_quantity,

                    m.min_stock_level,

                    m.expiry_date,

                    s.supplier_name,

                    s.phone

                FROM medicines m

                LEFT JOIN suppliers s
                    ON s.supplier_id = m.supplier_id

                ORDER BY m.medicine_name
            ");


            $data = [];


            while ($row = $result->fetch_assoc()) {

                $row['low_stock'] =
                    $row['stock_quantity']
                    <= $row['min_stock_level'];


                $row['near_expiry'] =
                    strtotime($row['expiry_date'])
                    <= strtotime('+30 days');


                $data[] = $row;
            }


            response(
                true,
                'Inventory loaded.',
                $data
            );

            break;


        /* =====================================================
           RESTOCK REQUEST
        ===================================================== */

        case 'restock_request':

            $medicineId =
                (int)(
                    $_POST['medicine_id'] ?? 0
                );

            $quantity =
                (int)(
                    $_POST['quantity'] ?? 0
                );


            if (
                $medicineId <= 0 ||
                $quantity <= 0
            ) {

                response(
                    false,
                    'Enter a valid quantity.'
                );
            }


            $pharmacistId =
                (int)$_SESSION['user_id'];


            $check = $conn->prepare("
                SELECT medicine_name
                FROM medicines
                WHERE medicine_id = ?
            ");


            $check->bind_param(
                "i",
                $medicineId
            );

            $check->execute();


            $medicine =
                $check
                    ->get_result()
                    ->fetch_assoc();


            if (!$medicine) {

                response(
                    false,
                    'Medicine not found.'
                );
            }


            $stmt = $conn->prepare("
                INSERT INTO stock_requests
                (
                    medicine_id,
                    pharmacist_id,
                    quantity,
                    status
                )
                VALUES (?, ?, ?, 'Pending')
            ");


            $stmt->bind_param(
                "iii",
                $medicineId,
                $pharmacistId,
                $quantity
            );


            if (!$stmt->execute()) {

                response(
                    false,
                    'Restock request failed.'
                );
            }


            createNotification(
                $conn,
                'Restock Request',
                $medicineId,
                $medicine['medicine_name'],
                'Quantity requested: ' . $quantity,
                'Pending'
            );


            response(
                true,
                'Restock request submitted successfully.'
            );

            break;


        /* =====================================================
           INQUIRIES
        ===================================================== */

        case 'inquiries':

            $result = $conn->query("
                SELECT

                    i.inquiry_id,

                    i.patient_id,

                    u.full_name AS patient_name,

                    i.subject,

                    i.message,

                    i.reply,

                    i.created_at

                FROM inquiries i

                JOIN users u
                    ON u.user_id = i.patient_id

                ORDER BY i.created_at DESC
            ");


            $data = [];


            while ($row = $result->fetch_assoc()) {

                $data[] = $row;
            }


            response(
                true,
                'Inquiries loaded.',
                $data
            );

            break;


        /* =====================================================
           REPLY INQUIRY
        ===================================================== */

        case 'reply_inquiry':

            $inquiryId =
                (int)(
                    $_POST['inquiry_id'] ?? 0
                );

            $reply =
                post('reply');


            if (
                $inquiryId <= 0 ||
                $reply === ''
            ) {

                response(
                    false,
                    'Reply cannot be empty.'
                );
            }


            $pharmacistId =
                (int)$_SESSION['user_id'];


            $stmt = $conn->prepare("
                UPDATE inquiries

                SET

                    reply = ?,

                    replied_by = ?,

                    replied_at = NOW()

                WHERE inquiry_id = ?
            ");


            $stmt->bind_param(
                "sii",
                $reply,
                $pharmacistId,
                $inquiryId
            );


            if (!$stmt->execute()) {

                response(
                    false,
                    'Reply failed.'
                );
            }


            response(
                true,
                'Reply sent successfully.'
            );

            break;


        /* =====================================================
           NOTIFICATIONS
        ===================================================== */

        case 'notifications':

            $result = $conn->query("
                SELECT

                    notification_id,

                    request_type,

                    medicine_id,

                    medicine_name,

                    details,

                    admin_status,

                    submitted_date

                FROM notifications

                ORDER BY submitted_date DESC

                LIMIT 100
            ");


            $data = [];


            while ($row = $result->fetch_assoc()) {

                $data[] = $row;
            }


            response(
                true,
                'Notifications loaded.',
                $data
            );

            break;


        /* =====================================================
           MARK ORDER READY FOR PICKUP
        ===================================================== */

        case 'ready_order':

            $orderId = (int)(
                $_POST['order_id'] ?? 0
            );

            if ($orderId <= 0) {
                response(false, 'Invalid order ID.');
            }

            $stmt = $conn->prepare("
                UPDATE orders
                SET status = 'Ready'
                WHERE order_id = ?
                AND status = 'Approved'
            " );

            $stmt->bind_param('i', $orderId);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                response(true, 'Order marked as Ready to Pickup.');
            }

            response(false, 'Only approved orders can be marked Ready to Pickup.');

            break;


        /* =====================================================
           REPORTS
        ===================================================== */

        case 'reports':

            $sales = $conn->query("
                SELECT
                    COALESCE(SUM(total_amount), 0)

                FROM orders

                WHERE status IN
                (
                    'Approved',
                    'Ready',
                    'Delivered'
                )

                AND YEAR(order_date) =
                    YEAR(CURDATE())

                AND MONTH(order_date) =
                    MONTH(CURDATE())
            ")->fetch_row()[0];


            $medicineCount = $conn->query("
                SELECT COUNT(*)
                FROM medicines
            ")->fetch_row()[0];


            $totalStock = $conn->query("
                SELECT
                    COALESCE(SUM(stock_quantity), 0)

                FROM medicines
            ")->fetch_row()[0];


            response(
                true,
                'Reports loaded.',
                [

                    'monthly_sales' =>
                        (float)$sales,

                    'medicine_count' =>
                        (int)$medicineCount,

                    'total_stock' =>
                        (int)$totalStock

                ]
            );

            break;


        /* =====================================================
           LOGOUT
        ===================================================== */

        case 'logout':

            session_unset();

            session_destroy();


            response(
                true,
                'Logged out successfully.'
            );

            break;


        /* =====================================================
           UNKNOWN ACTION
        ===================================================== */

        default:

            response(
                false,
                'Unknown action.'
            );
    }


} catch (Throwable $e) {

    http_response_code(500);

    response(
        false,
        'Server error: ' . $e->getMessage()
    );
}


/* =========================================================
   CREATE NOTIFICATION
========================================================= */

function createNotification(
    $conn,
    $type,
    $medicineId,
    $medicineName,
    $details,
    $status = 'Pending'
) {

    $stmt = $conn->prepare("
        INSERT INTO notifications
        (
            request_type,
            medicine_id,
            medicine_name,
            details,
            admin_status,
            submitted_date
        )

        VALUES (?, ?, ?, ?, ?, NOW())
    ");


    if (!$stmt) {
        return;
    }


    $stmt->bind_param(
        "sisss",
        $type,
        $medicineId,
        $medicineName,
        $details,
        $status
    );


    $stmt->execute();
}

?>