
<?php

/* =====================================================
   MEDIHUB CHECKOUT
   Order + Stock Deduction
===================================================== */

require_once __DIR__ . "/db.php";

/* =====================================================
   SESSION
===================================================== */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =====================================================
   HELPER FUNCTION
===================================================== */

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars(
            (string)$value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

/* =====================================================
   CHECK PATIENT LOGIN
===================================================== */

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$patient_id = (int)$_SESSION['user_id'];

/* =====================================================
   CHECK CART
===================================================== */

if (
    !isset($_SESSION['cart']) ||
    !is_array($_SESSION['cart']) ||
    empty($_SESSION['cart'])
) {
    header("Location: cart.php");
    exit;
}

/* =====================================================
   LOAD CART ITEMS
===================================================== */

$cart_items = [];
$total = 0.00;
$cart_count = 0;
$error = '';

foreach ($_SESSION['cart'] as $medicine_id => $quantity) {

    $medicine_id = (int)$medicine_id;
    $quantity = (int)$quantity;

    if ($medicine_id <= 0 || $quantity <= 0) {
        unset($_SESSION['cart'][$medicine_id]);
        continue;
    }

    $stmt = $conn->prepare("
        SELECT
            m.medicine_id,
            m.medicine_name,
            m.dosage,
            m.price,
            m.stock_quantity,
            m.requires_prescription,
            m.image_path,
            c.category_name
        FROM medicines m
        LEFT JOIN categories c
            ON c.category_id = m.category_id
        WHERE m.medicine_id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        $error = "Unable to load medicine information.";
        break;
    }

    $stmt->bind_param("i", $medicine_id);
    $stmt->execute();

    $medicine = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (!$medicine) {
        unset($_SESSION['cart'][$medicine_id]);
        continue;
    }

    $stock = (int)$medicine['stock_quantity'];

    /* =================================================
       REMOVE OUT OF STOCK MEDICINE
    ================================================= */

    if ($stock <= 0) {
        unset($_SESSION['cart'][$medicine_id]);
        continue;
    }

    /* =================================================
       LIMIT CART QUANTITY TO STOCK
    ================================================= */

    if ($quantity > $stock) {

        $quantity = $stock;

        $_SESSION['cart'][$medicine_id] = $quantity;
    }

    $price = (float)$medicine['price'];

    $subtotal = $price * $quantity;

    /* =================================================
       MEDICINE IMAGE
    ================================================= */

    $image = trim(
        (string)($medicine['image_path'] ?? '')
    );

    if ($image === '') {

        $name = strtolower(
            (string)$medicine['medicine_name']
        );

        if (str_contains($name, 'paracetamol')) {

            $image = "images/paracetamol.jpg";

        } elseif (
            str_contains($name, 'amoxicillin') ||
            str_contains($name, 'azithromycin')
        ) {

            $image = "images/amoxicillin.jpeg";

        } elseif (str_contains($name, 'vitamin')) {

            $image = "images/vitamin c.jpg";

        } else {

            $image = "images/search medicine.png";
        }
    }

    $cart_items[] = [

        'id' => $medicine_id,

        'name' =>
            $medicine['medicine_name'],

        'dosage' =>
            $medicine['dosage'] ?? '',

        'category' =>
            $medicine['category_name'] ?? 'General',

        'price' =>
            $price,

        'quantity' =>
            $quantity,

        'subtotal' =>
            $subtotal,

        'image' =>
            $image,

        'stock_quantity' =>
            $stock,

        'requires_prescription' =>
            (int)$medicine['requires_prescription']
    ];

    $total += $subtotal;

    $cart_count += $quantity;
}

/* =====================================================
   CART EMPTY
===================================================== */

if (empty($cart_items)) {

    header("Location: cart.php");
    exit;
}

/* =====================================================
   PLACE ORDER
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        /* =================================================
           START TRANSACTION
        ================================================= */

        $conn->begin_transaction();

        $locked_items = [];
        $final_total = 0.00;

        /* =================================================
           STEP 1
           LOCK MEDICINE ROWS
        ================================================= */

        foreach ($cart_items as $item) {

            $medicine_id =
                (int)$item['id'];

            $requested_quantity =
                (int)$item['quantity'];

            /*
             * FOR UPDATE prevents another order
             * from changing this medicine while
             * this transaction is running.
             */

            $stmt = $conn->prepare("
                SELECT
                    medicine_id,
                    medicine_name,
                    price,
                    stock_quantity
                FROM medicines
                WHERE medicine_id = ?
                FOR UPDATE
            ");

            if (!$stmt) {

                throw new Exception(
                    "Unable to check medicine stock."
                );
            }

            $stmt->bind_param(
                "i",
                $medicine_id
            );

            $stmt->execute();

            $current =
                $stmt
                    ->get_result()
                    ->fetch_assoc();

            $stmt->close();

            if (!$current) {

                throw new Exception(
                    "Medicine is no longer available."
                );
            }

            $current_stock =
                (int)$current['stock_quantity'];

            /* =================================================
               CHECK STOCK
            ================================================= */

            if ($current_stock <= 0) {

                throw new Exception(
                    $current['medicine_name'] .
                    " is out of stock."
                );
            }

            if ($requested_quantity > $current_stock) {

                throw new Exception(
                    $current['medicine_name'] .
                    " has only " .
                    $current_stock .
                    " item(s) available."
                );
            }

            $price =
                (float)$current['price'];

            $subtotal =
                $price * $requested_quantity;

            $final_total += $subtotal;

            $locked_items[] = [

                'medicine_id' =>
                    $medicine_id,

                'name' =>
                    $current['medicine_name'],

                'quantity' =>
                    $requested_quantity,

                'price' =>
                    $price,

                'subtotal' =>
                    $subtotal,

                'stock_before' =>
                    $current_stock
            ];
        }

        /* =================================================
           STEP 2
           CREATE ORDER
        ================================================= */

        $stmt = $conn->prepare("
            INSERT INTO orders
            (
                patient_id,
                prescription_id,
                total_amount,
                status
            )
            VALUES
            (
                ?,
                NULL,
                ?,
                'Pending'
            )
        ");

        if (!$stmt) {

            throw new Exception(
                "Order preparation failed: " .
                $conn->error
            );
        }

        $stmt->bind_param(
            "id",
            $patient_id,
            $final_total
        );

        if (!$stmt->execute()) {

            throw new Exception(
                "Order creation failed: " .
                $stmt->error
            );
        }

        $order_id =
            (int)$conn->insert_id;

        $stmt->close();

        /* =================================================
           STEP 3
           PREPARE ORDER ITEM INSERT
        ================================================= */

        $item_stmt = $conn->prepare("
            INSERT INTO order_item
            (
                order_id,
                medicine_id,
                quantity,
                price
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?
            )
        ");

        if (!$item_stmt) {

            throw new Exception(
                "Order item preparation failed: " .
                $conn->error
            );
        }

        /* =================================================
           STEP 4
           PREPARE STOCK UPDATE
        ================================================= */

        $stock_stmt = $conn->prepare("
            UPDATE medicines
            SET stock_quantity = stock_quantity - ?
            WHERE medicine_id = ?
              AND stock_quantity >= ?
        ");

        if (!$stock_stmt) {

            throw new Exception(
                "Stock update preparation failed: " .
                $conn->error
            );
        }

        /* =================================================
           STEP 5
           INSERT ITEMS + DEDUCT STOCK
        ================================================= */

        foreach ($locked_items as $item) {

            $medicine_id =
                (int)$item['medicine_id'];

            $quantity =
                (int)$item['quantity'];

            $price =
                (float)$item['price'];

            /* =============================================
               INSERT ORDER ITEM
            ============================================= */

            $item_stmt->bind_param(
                "iiid",
                $order_id,
                $medicine_id,
                $quantity,
                $price
            );

            if (!$item_stmt->execute()) {

                throw new Exception(
                    "Order item could not be saved: " .
                    $item_stmt->error
                );
            }

            /* =============================================
               DEDUCT STOCK
               
               Example:
               
               Existing stock = 8
               Customer buys = 2
               
               8 - 2 = 6
            ============================================= */

            $stock_stmt->bind_param(
                "iii",
                $quantity,
                $medicine_id,
                $quantity
            );

            if (!$stock_stmt->execute()) {

                throw new Exception(
                    "Stock update failed: " .
                    $stock_stmt->error
                );
            }

            /* =============================================
               VERIFY STOCK UPDATE
            ============================================= */

            if ($stock_stmt->affected_rows !== 1) {

                throw new Exception(
                    "Stock was not updated for " .
                    $item['name']
                );
            }
        }

        $item_stmt->close();
        $stock_stmt->close();

        /* =================================================
           STEP 6
           COMMIT
        ================================================= */

        $conn->commit();

        /* =================================================
           STEP 7
           SAVE SUCCESS PAGE DATA
        ================================================= */

        $success_items = [];

        foreach ($locked_items as $item) {

            $success_items[] = [

                'name' =>
                    $item['name'],

                'quantity' =>
                    $item['quantity'],

                'subtotal' =>
                    $item['subtotal']
            ];
        }

        $_SESSION['last_order_id'] =
            $order_id;

        $_SESSION['last_order_total'] =
            $final_total;

        $_SESSION['last_order_items'] =
            $success_items;

        /* =================================================
           STEP 8
           CLEAR CART
        ================================================= */

        $_SESSION['cart'] = [];

        /* =================================================
           STEP 9
           REDIRECT
        ================================================= */

        header(
            "Location: order_success.php"
        );

        exit;

    } catch (Throwable $e) {

        /* =================================================
           ROLLBACK
        ================================================= */

        if ($conn->errno === 0 || $conn->ping()) {
            $conn->rollback();
        }

        $error =
            "Order could not be placed. " .
            $e->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>MediHub - Checkout</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: #f5f8f7;
    color: #26332f;
}

.header {
    background: #299d82;
    color: white;
    padding: 16px 6%;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.logo {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
}

.header a {
    color: white;
    text-decoration: none;
    font-weight: 600;
}

.container {
    max-width: 1100px;
    margin: 35px auto;
    padding: 0 18px;
}

.title {
    margin-bottom: 25px;
}

.title h1 {
    margin: 0 0 5px;
}

.title p {
    color: #6b7773;
}

.grid {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 22px;
}

.card {
    background: white;
    border: 1px solid #e2ebe7;
    border-radius: 15px;
    padding: 22px;
    box-shadow: 0 4px 15px rgba(0,0,0,.04);
}

.card h2 {
    margin-top: 0;
}

.item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px 0;
    border-bottom: 1px solid #edf1ef;
}

.item:last-child {
    border-bottom: 0;
}

.item img {
    width: 65px;
    height: 65px;
    object-fit: contain;
    border: 1px solid #e5ece9;
    border-radius: 9px;
}

.item-info {
    flex: 1;
}

.item-info strong {
    display: block;
    margin-bottom: 4px;
}

.item-info small {
    color: #71807b;
}

.item-price {
    font-weight: bold;
    color: #299d82;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #edf1ef;
}

.total {
    display: flex;
    justify-content: space-between;
    font-size: 22px;
    font-weight: bold;
    color: #299d82;
    padding-top: 20px;
}

.btn {
    width: 100%;
    padding: 13px;
    border: 0;
    border-radius: 9px;
    background: #299d82;
    color: white;
    font-weight: bold;
    font-size: 15px;
    cursor: pointer;
    margin-top: 18px;
}

.btn:hover {
    background: #20765f;
}

.back {
    display: inline-block;
    margin-top: 15px;
    color: #299d82;
    text-decoration: none;
    font-weight: 600;
}

.error {
    background: #ffeaea;
    color: #a33f3f;
    padding: 13px 15px;
    border-radius: 9px;
    margin-bottom: 20px;
}

.stock {
    color: #71807b;
    font-size: 12px;
    margin-top: 4px;
}

@media(max-width:800px) {

    .grid {
        grid-template-columns: 1fr;
    }

}

</style>

</head>

<body>

<header class="header">

    <a href="Home.php">

        <img
            src="images/logo.jpeg"
            class="logo"
            alt="MediHub"
        >

    </a>

    <strong>MediHub Checkout</strong>

    <a href="cart.php">
        Back to Cart
    </a>

</header>

<main class="container">

    <div class="title">

        <h1>Checkout</h1>

        <p>
            Review your order before placing it.
        </p>

    </div>

    <?php if ($error !== ''): ?>

        <div class="error">

            <?=e($error)?>

        </div>

    <?php endif; ?>

    <div class="grid">

        <div class="card">

            <h2>
                Order Items
            </h2>

            <?php foreach ($cart_items as $item): ?>

                <div class="item">

                    <img
                        src="<?=e($item['image'])?>"
                        alt="<?=e($item['name'])?>"
                    >

                    <div class="item-info">

                        <strong>
                            <?=e($item['name'])?>
                        </strong>

                        <small>
                            <?=e($item['dosage'])?>
                        </small>

                        <div class="stock">
                            Available stock:
                            <?=number_format($item['stock_quantity'])?>
                        </div>

                        <div class="stock">
                            Quantity:
                            <?=number_format($item['quantity'])?>
                        </div>

                    </div>

                    <div class="item-price">

                        LKR
                        <?=number_format(
                            $item['subtotal'],
                            2
                        )?>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

        <div class="card">

            <h2>
                Order Summary
            </h2>

            <div class="summary-row">

                <span>
                    Total Items
                </span>

                <strong>
                    <?=number_format($cart_count)?>
                </strong>

            </div>

            <div class="summary-row">

                <span>
                    Subtotal
                </span>

                <strong>
                    LKR
                    <?=number_format(
                        $total,
                        2
                    )?>
                </strong>

            </div>

            <div class="summary-row">

                <span>
                    Delivery
                </span>

                <strong>
                    Free
                </strong>

            </div>

            <div class="total">

                <span>
                    Total
                </span>

                <span>
                    LKR
                    <?=number_format(
                        $total,
                        2
                    )?>
                </span>

            </div>

            <form method="POST">

                <button
                    type="submit"
                    class="btn"
                >
                    Place Order
                </button>

            </form>

            <a
                href="cart.php"
                class="back"
            >
                ← Back to Cart
            </a>

        </div>

    </div>

</main>

</body>

</html>

