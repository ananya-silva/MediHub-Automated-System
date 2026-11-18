<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: search_medicine.php');
    exit;
}

$stmt = $conn->prepare(
    'SELECT medicine_id, medicine_name, price, stock_quantity
     FROM medicines
     WHERE medicine_id = ?
     LIMIT 1'
);

$stmt->bind_param('i', $id);
$stmt->execute();

$m = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$m) {
    header('Location: search_medicine.php');
    exit;
}

if ((int) $m['stock_quantity'] <= 0) {
    header(
        'Location: medicine_details.php?id=' . $id . '&error=out_of_stock'
    );
    exit;
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$current = (int) ($_SESSION['cart'][$id] ?? 0);

$new = min(
    (int) $m['stock_quantity'],
    $current + max(1, (int) ($_GET['quantity'] ?? 1))
);

$_SESSION['cart'][$id] = $new;

header('Location: cart.php');
exit;

?>