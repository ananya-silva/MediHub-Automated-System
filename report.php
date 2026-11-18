<?php
session_start();
require_once __DIR__ . '/db.php';

/* =====================================================
   ACCESS CONTROL
===================================================== */

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    http_response_code(403);
    exit('Access denied. Please login first.');
}

$sessionRole = $_SESSION['role'];

if (!in_array($sessionRole, ['Admin', 'Pharmacist'], true)) {
    http_response_code(403);
    exit('Access denied.');
}

/* =====================================================
   INPUT
   The role query parameter is NOT trusted.
   The logged-in session role is always used.
===================================================== */

$type = strtolower(trim($_GET['type'] ?? ''));
$period = strtolower(trim($_GET['period'] ?? 'monthly'));

$allowedTypes = ['medicine', 'sales', 'inventory', 'supplier'];
$allowedPeriods = ['weekly', 'monthly', 'yearly'];

if (!in_array($type, $allowedTypes, true)) {
    http_response_code(400);
    exit('Invalid report type.');
}

if (!in_array($period, $allowedPeriods, true)) {
    http_response_code(400);
    exit('Invalid report period.');
}

/* Pharmacists cannot view supplier reports. */
if ($sessionRole === 'Pharmacist' && $type === 'supplier') {
    http_response_code(403);
    exit('You do not have permission to view supplier reports.');
}

/* =====================================================
   REPORT INFORMATION
===================================================== */

$periodLabels = [
    'weekly' => 'Weekly',
    'monthly' => 'Monthly',
    'yearly' => 'Yearly'
];

$typeLabels = [
    'medicine' => 'Medicine',
    'sales' => 'Sales',
    'inventory' => 'Inventory / Stock',
    'supplier' => 'Supplier'
];

$title = $periodLabels[$period] . ' ' . $typeLabels[$type] . ' Report';

switch ($period) {
    case 'weekly':
        $interval = '7 DAY';
        $periodDescription = 'Last 7 days';
        break;

    case 'yearly':
        $interval = '1 YEAR';
        $periodDescription = 'Last 1 year';
        break;

    default:
        $interval = '1 MONTH';
        $periodDescription = 'Last 1 month';
        break;
}

/* =====================================================
   DASHBOARD BACK LINK
   These are the actual dashboard filenames in MediHub.
===================================================== */

$dashboardUrl = ($sessionRole === 'Admin')
    ? 'admin_dashboard.php'
    : 'pharmacist_dashboard.php';

/* =====================================================
   QUERY HELPER
===================================================== */

function getRows(mysqli $conn, string $sql): array
{
    $result = $conn->query($sql);

    if ($result === false) {
        error_log('MediHub report query failed: ' . $conn->error);
        return [];
    }

    $rows = [];

    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    $result->free();

    return $rows;
}

$headers = [];
$data = [];

/* =====================================================
   MEDICINE REPORT
   Shows medicines added during the selected period.
===================================================== */

if ($type === 'medicine') {

    $headers = [
        'Medicine',
        'Dosage',
        'Price (LKR)',
        'Stock',
        'Minimum Stock',
        'Expiry Date',
        'Prescription Required'
    ];

    $data = getRows($conn, "
        SELECT
            medicine_name,
            COALESCE(dosage, '-') AS dosage,
            price,
            stock_quantity,
            COALESCE(min_stock_level, 0) AS min_stock_level,
            COALESCE(DATE_FORMAT(expiry_date, '%Y-%m-%d'), '-') AS expiry_date,
            CASE
                WHEN requires_prescription = 1 THEN 'Yes'
                ELSE 'No'
            END AS prescription_required
        FROM medicines
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL $interval)
        ORDER BY medicine_name ASC
    ");
}

/* =====================================================
   SALES REPORT
   Approved, Ready and Delivered orders are counted as sales.
===================================================== */

elseif ($type === 'sales') {

    $headers = [
        'Date',
        'Orders',
        'Revenue (LKR)'
    ];

    $data = getRows($conn, "
        SELECT
            DATE(order_date) AS report_date,
            COUNT(*) AS orders,
            COALESCE(SUM(total_amount), 0.00) AS revenue
        FROM orders
        WHERE status IN ('Approved', 'Ready', 'Delivered')
          AND order_date >= DATE_SUB(NOW(), INTERVAL $interval)
        GROUP BY DATE(order_date)
        ORDER BY report_date DESC
    ");
}

/* =====================================================
   INVENTORY REPORT
   Current stock snapshot. Historical stock movements are not
   stored in the current database schema, so the selected
   period is shown in the report header only.
===================================================== */

elseif ($type === 'inventory') {

    $headers = [
        'Medicine',
        'Stock',
        'Minimum Stock',
        'Stock Status',
        'Expiry Date',
        'Supplier'
    ];

    $data = getRows($conn, "
        SELECT
            m.medicine_name,
            m.stock_quantity,
            COALESCE(m.min_stock_level, 0) AS min_stock_level,
            CASE
                WHEN m.stock_quantity <= COALESCE(m.min_stock_level, 0)
                    THEN 'Low Stock'
                ELSE 'Available'
            END AS stock_status,
            COALESCE(DATE_FORMAT(m.expiry_date, '%Y-%m-%d'), '-') AS expiry_date,
            COALESCE(s.supplier_name, '-') AS supplier_name
        FROM medicines m
        LEFT JOIN suppliers s
            ON s.supplier_id = m.supplier_id
        ORDER BY m.medicine_name ASC
    ");
}

/* =====================================================
   SUPPLIER REPORT
   Current supplier directory because suppliers has no
   created_at/date field in the current database schema.
===================================================== */

elseif ($type === 'supplier') {

    $headers = [
        'Supplier',
        'Contact Person',
        'Phone',
        'Email',
        'Address'
    ];

    $data = getRows($conn, "
        SELECT
            supplier_name,
            COALESCE(contact_person, '-') AS contact_person,
            COALESCE(phone, '-') AS phone,
            COALESCE(email, '-') AS email,
            COALESCE(address, '-') AS address
        FROM suppliers
        ORDER BY supplier_name ASC
    ");
}

/* =====================================================
   TOTALS
===================================================== */

$totalRows = count($data);
$totalRevenue = 0.00;
$totalOrders = 0;

if ($type === 'sales') {
    foreach ($data as $row) {
        $totalOrders += (int) $row['orders'];
        $totalRevenue += (float) $row['revenue'];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - MediHub</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 35px;
            background: #f2f4f3;
            color: #222;
            font-family: Arial, Helvetica, sans-serif;
        }

        .report-container {
            max-width: 1200px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.08);
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 22px;
        }

        h1 {
            margin: 0 0 8px;
            color: #2A9B7A;
            font-size: 28px;
        }

        .meta {
            color: #666;
            line-height: 1.7;
            font-size: 14px;
        }

        .actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            border: 0;
            border-radius: 7px;
            padding: 10px 16px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }

        .print-btn {
            background: #2A9B7A;
            color: #fff;
        }

        .back-btn {
            background: #e9eceb;
            color: #333;
        }

        .summary {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin: 20px 0;
        }

        .summary-card {
            min-width: 170px;
            padding: 15px 18px;
            background: #f7faf9;
            border: 1px solid #e0e8e5;
            border-radius: 8px;
        }

        .summary-label {
            margin: 0 0 5px;
            color: #666;
            font-size: 13px;
        }

        .summary-value {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }

        th,
        td {
            border: 1px solid #d9dfdd;
            padding: 11px 10px;
            text-align: left;
            font-size: 14px;
        }

        th {
            background: #2A9B7A;
            color: #fff;
            font-weight: 600;
        }

        tbody tr:nth-child(even) {
            background: #f8faf9;
        }

        .empty {
            text-align: center;
            color: #777;
            padding: 25px;
        }

        @media (max-width: 700px) {
            body {
                padding: 15px;
            }

            .report-container {
                padding: 18px;
            }

            .report-header {
                flex-direction: column;
            }

            .actions {
                width: 100%;
            }

            .btn {
                flex: 1;
                text-align: center;
            }
        }

        @media print {
            body {
                padding: 0;
                background: #fff;
            }

            .report-container {
                max-width: 100%;
                padding: 0;
                box-shadow: none;
                border-radius: 0;
            }

            .actions {
                display: none;
            }

            h1 {
                color: #000;
            }
        }
    </style>
</head>
<body>

<div class="report-container">

    <div class="report-header">
        <div>
            <h1><?= htmlspecialchars($title) ?></h1>

            <div class="meta">
                <strong>MediHub</strong><br>
                Role: <?= htmlspecialchars($sessionRole) ?><br>
                Period: <?= htmlspecialchars($periodDescription) ?><br>
                Generated: <?= htmlspecialchars(date('Y-m-d H:i:s')) ?>
            </div>
        </div>

        <div class="actions">
            <button type="button" class="btn print-btn" onclick="window.print()">
                Print / Save as PDF
            </button>

            <a href="<?= htmlspecialchars($dashboardUrl) ?>" class="btn back-btn">
                Back to Dashboard
            </a>
        </div>
    </div>

    <div class="summary">
        <div class="summary-card">
            <p class="summary-label">Records</p>
            <p class="summary-value"><?= $totalRows ?></p>
        </div>

        <?php if ($type === 'sales'): ?>
            <div class="summary-card">
                <p class="summary-label">Total Orders</p>
                <p class="summary-value"><?= $totalOrders ?></p>
            </div>

            <div class="summary-card">
                <p class="summary-label">Total Revenue</p>
                <p class="summary-value">
                    LKR <?= number_format($totalRevenue, 2) ?>
                </p>
            </div>
        <?php endif; ?>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <?php foreach ($headers as $header): ?>
                        <th><?= htmlspecialchars($header) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>

            <tbody>
                <?php if (!empty($data)): ?>
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <?php foreach ($row as $value): ?>
                                <td><?= htmlspecialchars((string) $value) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td class="empty" colspan="<?= count($headers) ?>">
                            No records found for this report period.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>
