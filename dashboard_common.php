<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';


function requireDashboardRole(string $role): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $sessionRole = trim((string) ($_SESSION['role'] ?? ''));
    $userId = (int) ($_SESSION['user_id'] ?? 0);

    if (
        $userId <= 0 ||
        strcasecmp($sessionRole, $role) !== 0
    ) {
        http_response_code(403);

        exit(
            'Access denied. Please login with the correct account.'
        );
    }
}


function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


function medicineImageFromRow(array $medicine): string
{
    $stored = trim(
        (string) ($medicine['image_path'] ?? '')
    );

    if ($stored !== '') {
        return $stored;
    }

    $name = strtolower(
        (string) ($medicine['medicine_name'] ?? '')
    );

    if (str_contains($name, 'paracetamol')) {
        return 'images/paracetamol.jpg';
    }

    if (
        str_contains($name, 'amoxicillin') ||
        str_contains($name, 'azithromycin')
    ) {
        return 'images/amoxicillin.jpeg';
    }

    if (str_contains($name, 'vitamin')) {
        return 'images/vitamin c.jpg';
    }

    return 'images/search medicine.png';
}


function sendLowStockAlert(mysqli $conn, int $medicineId): void
{
    $stmt = $conn->prepare(
        "SELECT medicine_name, stock_quantity, min_stock_level
         FROM medicines
         WHERE medicine_id=?
         LIMIT 1"
    );

    if (!$stmt) {
        return;
    }

    $stmt->bind_param(
        'i',
        $medicineId
    );

    $stmt->execute();

    $medicine = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (
        !$medicine ||
        (int) $medicine['stock_quantity'] >
        (int) $medicine['min_stock_level']
    ) {
        return;
    }

    $name = (string) $medicine['medicine_name'];
    $stock = (int) $medicine['stock_quantity'];
    $min = (int) $medicine['min_stock_level'];

    $priority = $stock === 0
        ? 'Critical'
        : 'High';

    $details = $stock === 0
        ? "{$name} is out of stock."
        : "{$name} is low in stock. Current stock: {$stock}; minimum level: {$min}.";

    $check = $conn->prepare(
        "SELECT notification_id
         FROM notifications
         WHERE request_type='Low Stock Alert'
           AND medicine_id=?
           AND DATE(submitted_date)=CURDATE()
         LIMIT 1"
    );

    $exists = false;

    if ($check) {

        $check->bind_param(
            'i',
            $medicineId
        );

        $check->execute();

        $exists = $check->get_result()->num_rows > 0;

        $check->close();
    }

    if (!$exists) {

        $ins = $conn->prepare(
            "INSERT INTO notifications
            (
                pharmacist_id,
                request_type,
                medicine_id,
                medicine_name,
                details,
                priority,
                admin_status
            )
            VALUES
            (
                NULL,
                'Low Stock Alert',
                ?,
                ?,
                ?,
                ?,
                'Pending'
            )"
        );

        if ($ins) {

            $ins->bind_param(
                'isss',
                $medicineId,
                $name,
                $details,
                $priority
            );

            $ins->execute();

            $ins->close();
        }
    }

    $emails = [];

    $res = $conn->query(
        "SELECT email
         FROM users
         WHERE role IN ('Admin','Pharmacist')
           AND status='Active'
           AND email<>''"
    );

    if ($res) {

        while ($row = $res->fetch_assoc()) {
            $emails[] = $row['email'];
        }
    }

    if ($emails) {

        $to = implode(
            ',',
            $emails
        );

        $subject = $stock === 0
            ? "MediHub Alert: {$name} is Out of Stock"
            : "MediHub Alert: Low Stock - {$name}";

        $body =
            "MediHub Pharmacy Stock Alert\n\n" .
            "Medicine: {$name}\n" .
            "Current Stock: {$stock}\n" .
            "Minimum Stock Level: {$min}\n" .
            "Priority: {$priority}\n\n" .
            "Please review the inventory and arrange a restock if required.\n\n" .
            "MediHub System";

        @mail(
            $to,
            $subject,
            $body,
            "From: medihub@localhost\r\n" .
            "Content-Type: text/plain; charset=UTF-8\r\n"
        );
    }
}


function uploadMedicineImage(array $file): string
{
    if (
        !isset($file['error']) ||
        $file['error'] === UPLOAD_ERR_NO_FILE
    ) {
        return '';
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException(
            'Medicine image upload failed.'
        );
    }

    if ($file['size'] > 3 * 1024 * 1024) {
        throw new RuntimeException(
            'Image must be 3MB or smaller.'
        );
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);

    $mime = $finfo->file(
        $file['tmp_name']
    );

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException(
            'Only JPG, PNG or WEBP images are allowed.'
        );
    }

    $dir = __DIR__ . '/images/medicines';

    if (
        !is_dir($dir) &&
        !mkdir($dir, 0775, true)
    ) {
        throw new RuntimeException(
            'Could not create image folder.'
        );
    }

    $filename =
        'medicine_' .
        bin2hex(random_bytes(8)) .
        '.' .
        $allowed[$mime];

    if (
        !move_uploaded_file(
            $file['tmp_name'],
            $dir . '/' . $filename
        )
    ) {
        throw new RuntimeException(
            'Could not save medicine image.'
        );
    }

    return 'images/medicines/' . $filename;
}


function dashboardStyles(): string
{
    return <<<'CSS'
:root {
    --mh: #299d82;
    --mh-dark: #20765f;
    --bg: #f5f8f7;
    --text: #25312e;
    --muted: #6b7773;
    --border: #e3ebe8;
    --danger: #dc5c5c;
    --warning: #d49a2a;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: var(--bg);
    color: var(--text);
}

a {
    text-decoration: none;
}

.layout {
    display: flex;
    min-height: 100vh;
}

.sidebar {
    width: 250px;
    background: #fff;
    border-right: 1px solid var(--border);
    position: fixed;
    inset: 0 auto 0 0;
    padding: 24px 15px;
    z-index: 20;
}

.brand {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0 12px 24px;
    border-bottom: 1px solid var(--border);
    font-size: 24px;
    font-weight: 800;
    color: var(--mh);
}

.brand img {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    object-fit: cover;
}

.nav {
    padding-top: 18px;
}

.nav a {
    display: flex;
    align-items: center;
    gap: 12px;
    color: #596560;
    padding: 12px 14px;
    border-radius: 10px;
    margin: 5px 0;
    font-size: 14px;
}

.nav a:hover,
.nav a.active {
    background: #eaf6f2;
    color: var(--mh);
    font-weight: 700;
}

.nav .logout {
    margin-top: 24px;
    color: #b74f4f;
}

.content {
    margin-left: 250px;
    width: calc(100% - 250px);
}

.topbar {
    height: 74px;
    background: #fff;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 30px;
    position: sticky;
    top: 0;
    z-index: 10;
}

.topbar h1 {
    font-size: 22px;
    margin: 0;
}

.user-chip {
    color: var(--muted);
    font-size: 14px;
}

.main {
    padding: 30px;
    max-width: 1500px;
    margin: auto;
}

.welcome {
    margin-bottom: 25px;
}

.welcome h2 {
    margin: 0 0 5px;
    font-size: 28px;
}

.welcome p {
    margin: 0;
    color: var(--muted);
}

.stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-bottom: 25px;
}

.stat {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 4px 16px rgba(20, 60, 50, .04);
}

.stat .icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    background: #eaf6f2;
    color: var(--mh);
    display: grid;
    place-items: center;
    margin-bottom: 15px;
}

.stat small {
    color: var(--muted);
}

.stat strong {
    display: block;
    font-size: 27px;
    margin-top: 5px;
}

.panel {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 15px;
    padding: 22px;
    margin-bottom: 22px;
}

.panel-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 18px;
}

.panel h3 {
    margin: 0;
    font-size: 19px;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    border: 0;
    border-radius: 9px;
    padding: 10px 15px;
    background: var(--mh);
    color: #fff;
    cursor: pointer;
    font-weight: 700;
}

.btn:hover {
    background: var(--mh-dark);
    color: #fff;
}

.btn.secondary {
    background: #eef3f1;
    color: #42504c;
}

.btn.danger {
    background: #fff0f0;
    color: #b64e4e;
}

.table-wrap {
    overflow: auto;
}

.table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.table th,
.table td {
    padding: 13px 12px;
    border-bottom: 1px solid var(--border);
    text-align: left;
    white-space: nowrap;
}

.table th {
    font-size: 12px;
    text-transform: uppercase;
    color: #71807b;
    background: #fafcfb;
    font-weight: 700;
}

.badge {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
}

.ok {
    background: #eaf7f0;
    color: #21845c;
}

.low {
    background: #fff5df;
    color: #9a6a13;
}

.out {
    background: #ffeaea;
    color: #b54343;
}

.pending {
    background: #fff4dd;
    color: #9b6b12;
}

.approved {
    background: #eaf7f0;
    color: #21845c;
}

.rejected {
    background: #ffeaea;
    color: #b54343;
}

.ready {
    background: #eaf7f0;
    color: #21845c;
}

.delivered {
    background: #eaf7f0;
    color: #21845c;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}

.field {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.field.full {
    grid-column: 1 / -1;
}

.field label {
    font-size: 13px;
    font-weight: 700;
    color: #4e5c57;
}

.field input,
.field select,
.field textarea {
    width: 100%;
    padding: 11px 12px;
    border: 1px solid #d7e2de;
    border-radius: 9px;
    outline: none;
    background: #fff;
}

.field input:focus,
.field select:focus,
.field textarea:focus {
    border-color: var(--mh);
    box-shadow: 0 0 0 3px #eaf6f2;
}

.actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 18px;
}

.notice {
    padding: 12px 14px;
    border-radius: 10px;
    margin-bottom: 18px;
    background: #eaf7f0;
    color: #237555;
}

.notice.error {
    background: #ffeaea;
    color: #a33f3f;
}

.medicine-thumb {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    object-fit: contain;
    background: #f8faf9;
    border: 1px solid var(--border);
}

.empty {
    text-align: center;
    padding: 45px;
    color: var(--muted);
}

.cards2 {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 18px;
}

.metric-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid var(--border);
}

.chart-row {
    display: grid;
    grid-template-columns: 100px 1fr 100px;
    gap: 10px;
    align-items: center;
    margin: 12px 0;
    font-size: 13px;
}

.bar {
    height: 12px;
    background: #e9efed;
    border-radius: 20px;
    overflow: hidden;
}

.bar span {
    display: block;
    height: 100%;
    background: var(--mh);
    border-radius: 20px;
}

.mobile-menu {
    display: none;
}

@media (max-width: 1000px) {

    .stats {
        grid-template-columns: repeat(2, 1fr);
    }

    .form-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 750px) {

    .sidebar {
        transform: translateX(-100%);
        transition: .2s;
    }

    .sidebar.open {
        transform: translateX(0);
    }

    .content {
        margin-left: 0;
        width: 100%;
    }

    .mobile-menu {
        display: block;
        border: 0;
        background: none;
        font-size: 22px;
        color: var(--mh);
    }

    .topbar {
        padding: 0 16px;
    }

    .main {
        padding: 18px;
    }

    .stats,
    .cards2 {
        grid-template-columns: 1fr;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .field.full {
        grid-column: auto;
    }
}
CSS;
}