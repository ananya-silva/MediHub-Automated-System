
<?php

/* =====================================================
   CART.PHP
   Patient Cart - Independent Page
===================================================== */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';


/* =====================================================
   HELPER FUNCTION
===================================================== */

function e($value): string
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =====================================================
   MEDICINE IMAGE
===================================================== */

function getCartMedicineImage(array $medicine): string
{
    $stored = trim(
        (string)($medicine['image_path'] ?? '')
    );

    /*
       Database image path
    */
    if ($stored !== '') {

        $fullPath = __DIR__ . '/' . $stored;

        if (file_exists($fullPath)) {
            return $stored;
        }
    }


    /*
       Fallback based on medicine name
    */

    $name = strtolower(
        (string)($medicine['medicine_name'] ?? '')
    );


    if (strpos($name, 'paracetamol') !== false) {
        return 'images/paracetamol.jpg';
    }


    if (
        strpos($name, 'amoxicillin') !== false ||
        strpos($name, 'azithromycin') !== false
    ) {
        return 'images/amoxicillin.jpeg';
    }


    if (strpos($name, 'vitamin') !== false) {
        return 'images/vitamin c.jpg';
    }


    /*
       Generic fallback
    */

    return 'images/search medicine.png';
}


/* =====================================================
   CART SESSION
===================================================== */

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}


/* =====================================================
   REMOVE / CLEAR CART
===================================================== */

if (isset($_GET['action'])) {

    $action = $_GET['action'];

    $id = (int)(
        $_GET['id'] ?? 0
    );


    if ($action === 'remove' && $id > 0) {

        unset(
            $_SESSION['cart'][$id]
        );
    }


    if ($action === 'clear') {

        $_SESSION['cart'] = [];
    }


    header('Location: cart.php');

    exit;
}


/* =====================================================
   UPDATE CART
===================================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['update_cart'])
) {

    $quantities =
        $_POST['quantity'] ?? [];


    foreach ($quantities as $id => $qty) {

        $id = (int)$id;

        $qty = max(
            0,
            (int)$qty
        );


        if ($id <= 0) {
            continue;
        }


        if ($qty === 0) {

            unset(
                $_SESSION['cart'][$id]
            );

        } else {

            $_SESSION['cart'][$id] = $qty;
        }
    }


    header('Location: cart.php');

    exit;
}


/* =====================================================
   LOAD CART ITEMS
===================================================== */

$items = [];

$total = 0;

$count = 0;


foreach (
    $_SESSION['cart']
    as $id => $qty
) {

    $id = (int)$id;

    $qty = (int)$qty;


    if ($id <= 0 || $qty <= 0) {

        unset(
            $_SESSION['cart'][$id]
        );

        continue;
    }


    /* =================================================
       GET MEDICINE
    ================================================= */

    $stmt = $conn->prepare("
        SELECT
            m.medicine_id,
            m.medicine_name,
            m.dosage,
            m.price,
            m.stock_quantity,
            m.min_stock_level,
            m.expiry_date,
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
        continue;
    }


    $stmt->bind_param(
        'i',
        $id
    );


    $stmt->execute();


    $result =
        $stmt->get_result();


    $medicine =
        $result->fetch_assoc();


    $stmt->close();


    /* =================================================
       MEDICINE NOT FOUND
    ================================================= */

    if (!$medicine) {

        unset(
            $_SESSION['cart'][$id]
        );

        continue;
    }


    /* =================================================
       STOCK CHECK
    ================================================= */

    $stock =
        (int)$medicine['stock_quantity'];


    /*
       Cart quantity cannot be greater
       than available stock
    */

    if ($qty > $stock) {

        $qty = $stock;


        if ($qty > 0) {

            $_SESSION['cart'][$id] =
                $qty;

        } else {

            unset(
                $_SESSION['cart'][$id]
            );

            continue;
        }
    }


    if ($qty <= 0) {

        unset(
            $_SESSION['cart'][$id]
        );

        continue;
    }


    /* =================================================
       SUBTOTAL
    ================================================= */

    $price =
        (float)$medicine['price'];


    $subtotal =
        $qty * $price;


    $medicine['quantity'] =
        $qty;


    $medicine['subtotal'] =
        $subtotal;


    $items[] =
        $medicine;


    $total +=
        $subtotal;


    $count +=
        $qty;
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

<title>MediHub - Shopping Cart</title>


<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
>


<style>

* {
    box-sizing: border-box;
}


body {

    margin: 0;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: #f5f8f7;

    color: #26332f;
}


/* =====================================================
   HEADER
===================================================== */

.header {

    background: #299d82;

    color: white;

    padding: 13px 6%;

    display: flex;

    align-items: center;

    justify-content: space-between;

    box-shadow:
        0 2px 8px rgba(
            0,
            0,
            0,
            .08
        );
}


.header a {

    color: white;

    text-decoration: none;
}


.logo {

    width: 45px;

    height: 45px;

    border-radius: 50%;

    object-fit: cover;
}


.brand {

    display: flex;

    align-items: center;

    gap: 10px;

    font-size: 21px;

    font-weight: 800;
}


/* =====================================================
   NAVIGATION
===================================================== */

.navbar {

    background: white;

    border-bottom:
        1px solid #e3ebe8;

    padding:
        13px 6%;

    display: flex;

    gap: 26px;
}


.navbar a {

    color: #52615c;

    text-decoration: none;

    font-size: 14px;

    font-weight: 600;

    transition: .2s;
}


.navbar a:hover {

    color: #299d82;
}


/* =====================================================
   MAIN
===================================================== */

.container {

    max-width: 1150px;

    margin: 32px auto;

    padding:
        0 18px;
}


/* =====================================================
   HERO
===================================================== */

.hero {

    background:
        linear-gradient(
            90deg,
            #20765f,
            #299d82
        );

    color: white;

    border-radius: 16px;

    padding:
        25px 30px;

    margin-bottom: 22px;

    box-shadow:
        0 5px 18px
        rgba(
            32,
            118,
            95,
            .12
        );
}


.hero h1 {

    margin:
        0 0 6px;

    font-size: 27px;
}


.hero p {

    margin: 0;

    opacity: .9;

    font-size: 14px;
}


/* =====================================================
   LAYOUT
===================================================== */

.cart-layout {

    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        330px;

    gap: 20px;

    align-items: start;
}


/* =====================================================
   CARD
===================================================== */

.card {

    background: white;

    border:
        1px solid #e1ebe7;

    border-radius: 15px;

    padding: 21px;

    box-shadow:
        0 4px 16px
        rgba(
            20,
            60,
            50,
            .04
        );
}


/* =====================================================
   CARD HEADER
===================================================== */

.card-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 10px;

    margin-bottom: 12px;
}


.card-header h2 {

    margin: 0;

    font-size: 19px;
}


.clear-cart {

    color: #b64f4f;

    text-decoration: none;

    font-size: 13px;

    font-weight: 600;
}


.clear-cart:hover {

    text-decoration: underline;
}


/* =====================================================
   CART ITEM
===================================================== */

.cart-item {

    display: grid;

    grid-template-columns:
        72px
        minmax(0, 1fr)
        100px
        75px
        65px;

    gap: 15px;

    align-items: center;

    padding:
        16px 0;

    border-bottom:
        1px solid #edf1ef;
}


.cart-item:last-child {

    border-bottom: 0;
}


/* =====================================================
   IMAGE
===================================================== */

.medicine-image {

    width: 68px;

    height: 68px;

    object-fit: contain;

    padding: 5px;

    background: #fafcfb;

    border:
        1px solid #e6eeeb;

    border-radius: 10px;
}


/* =====================================================
   MEDICINE DETAILS
===================================================== */

.medicine-name {

    font-size: 15px;

    font-weight: 700;

    color: #26332f;

    margin-bottom: 4px;
}


.medicine-category {

    color: #71807a;

    font-size: 12px;
}


.medicine-dosage {

    color: #89938f;

    font-size: 12px;

    margin-top: 3px;
}


/* =====================================================
   PRICE
===================================================== */

.item-price {

    color: #299d82;

    font-size: 14px;

    font-weight: 700;

    white-space: nowrap;
}


/* =====================================================
   QUANTITY
===================================================== */

.quantity {

    width: 70px;

    height: 38px;

    border:
        1px solid #d6e2de;

    border-radius: 8px;

    padding: 7px;

    text-align: center;

    outline: none;

    font-size: 14px;
}


.quantity:focus {

    border-color: #299d82;

    box-shadow:
        0 0 0 3px #eaf6f2;
}


/* =====================================================
   REMOVE
===================================================== */

.remove {

    color: #b64f4f;

    font-size: 12px;

    text-decoration: none;

    font-weight: 600;
}


.remove:hover {

    text-decoration: underline;
}


/* =====================================================
   UPDATE BUTTON
===================================================== */

.update-area {

    display: flex;

    justify-content: flex-end;

    margin-top: 16px;
}


.btn {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    border: 0;

    border-radius: 9px;

    padding:
        11px 16px;

    background: #299d82;

    color: white;

    font-weight: 700;

    font-size: 13px;

    cursor: pointer;

    text-decoration: none;

    transition: .2s;
}


.btn:hover {

    background: #20765f;
}


.btn-secondary {

    background: #eef4f1;

    color: #40504b;
}


.btn-secondary:hover {

    background: #e1ece8;

    color: #40504b;
}


/* =====================================================
   SUMMARY
===================================================== */

.summary h2 {

    margin:
        0 0 16px;

    font-size: 20px;
}


.summary-row {

    display: flex;

    justify-content: space-between;

    gap: 15px;

    padding:
        10px 0;

    color: #596660;

    font-size: 14px;
}


.summary-row strong {

    color: #26332f;
}


.summary-total {

    display: flex;

    justify-content: space-between;

    gap: 15px;

    padding-top: 15px;

    margin-top: 8px;

    border-top:
        1px solid #e2ebe7;

    font-size: 20px;

    font-weight: 800;

    color: #299d82;
}


.checkout-btn {

    width: 100%;

    margin-top: 17px;

    padding:
        13px 15px;
}


/* =====================================================
   EMPTY CART
===================================================== */

.empty-cart {

    text-align: center;

    padding:
        60px 20px;

    color: #697670;
}


.empty-cart i {

    color: #299d82;

    margin-bottom: 15px;
}


.empty-cart h2 {

    margin:
        5px 0 8px;

    color: #35423e;
}


.empty-cart p {

    font-size: 14px;

    margin-bottom: 20px;
}


/* =====================================================
   RESPONSIVE
===================================================== */

@media(max-width: 850px) {

    .cart-layout {

        grid-template-columns: 1fr;
    }

}


@media(max-width: 700px) {

    .navbar {

        overflow-x: auto;

        white-space: nowrap;
    }


    .cart-item {

        grid-template-columns:
            65px
            1fr
            auto;
    }


    .item-price {

        grid-column: 2;
    }


    .quantity {

        grid-column: 3;

        grid-row: 1 / 3;
    }


    .remove {

        grid-column: 2;
    }

}


@media(max-width: 480px) {

    .header {

        padding:
            12px 18px;
    }


    .header > a:last-child {

        font-size: 12px;
    }


    .container {

        margin-top: 20px;
    }


    .hero {

        padding: 22px;
    }


    .hero h1 {

        font-size: 23px;
    }


    .card {

        padding: 16px;
    }

}

</style>

</head>


<body>


<!-- =====================================================
     HEADER
===================================================== -->

<header class="header">

    <a href="Home.php" class="brand">

        <img
            class="logo"
            src="images/logo.jpeg"
            alt="MediHub"
        >

        <span>MediHub</span>

    </a>


    <a href="search_medicine.php">

        <i class="fa-solid fa-pills"></i>

        Continue Shopping

    </a>

</header>


<!-- =====================================================
     NAVIGATION
===================================================== -->

<nav class="navbar">

    <a href="Home.php">
        Home
    </a>

    <a href="about.php">
        About Us
    </a>

    <a href="search_medicine.php">
        Search Medicines
    </a>

    <a href="contact.php">
        Contact Us
    </a>

</nav>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="container">


<section class="hero">

    <h1>
        Your Shopping Cart
    </h1>

    <p>
        Review your medicines and quantities before checkout.
    </p>

</section>


<?php if (empty($items)): ?>


<!-- =====================================================
     EMPTY CART
===================================================== -->

<div class="card empty-cart">

    <i
        class="fa-solid fa-cart-shopping fa-3x"
    ></i>


    <h2>
        Your cart is empty
    </h2>


    <p>
        Add medicines from our medicine catalogue
        to continue.
    </p>


    <a
        class="btn"
        href="search_medicine.php"
    >

        <i class="fa-solid fa-pills"></i>

        Browse Medicines

    </a>

</div>


<?php else: ?>


<!-- =====================================================
     CART + SUMMARY
===================================================== -->

<div class="cart-layout">


<!-- =====================================================
     CART ITEMS
===================================================== -->

<section class="card">

    <div class="card-header">

        <h2>
            Cart Items (<?= $count ?>)
        </h2>


        <a
            class="clear-cart"
            href="cart.php?action=clear"
            onclick="return confirm('Clear all items from your cart?');"
        >

            <i class="fa-solid fa-trash-can"></i>

            Clear Cart

        </a>

    </div>


<form
    method="post"
    action="cart.php"
>


<?php foreach ($items as $medicine): ?>


<div class="cart-item">


    <!-- MEDICINE IMAGE -->

    <img
        class="medicine-image"
        src="<?= e(getCartMedicineImage($medicine)) ?>"
        alt="<?= e($medicine['medicine_name']) ?>"
    >


    <!-- MEDICINE DETAILS -->

    <div>

        <div class="medicine-name">

            <?= e(
                $medicine['medicine_name']
            ) ?>

        </div>


        <?php if (!empty($medicine['category_name'])): ?>

            <div class="medicine-category">

                <?= e(
                    $medicine['category_name']
                ) ?>

            </div>

        <?php endif; ?>


        <?php if (!empty($medicine['dosage'])): ?>

            <div class="medicine-dosage">

                <?= e(
                    $medicine['dosage']
                ) ?>

            </div>

        <?php endif; ?>

    </div>


    <!-- PRICE -->

    <div class="item-price">

        LKR
        <?= number_format(
            (float)$medicine['price'],
            2
        ) ?>

    </div>


    <!-- QUANTITY -->

    <input
        class="quantity"
        type="number"
        name="quantity[<?= (int)$medicine['medicine_id'] ?>]"
        value="<?= (int)$medicine['quantity'] ?>"
        min="1"
        max="<?= (int)$medicine['stock_quantity'] ?>"
        title="Maximum available stock: <?= (int)$medicine['stock_quantity'] ?>"
    >


    <!-- REMOVE -->

    <a
        class="remove"
        href="cart.php?action=remove&id=<?= (int)$medicine['medicine_id'] ?>"
    >

        Remove

    </a>


</div>


<?php endforeach; ?>


<!-- UPDATE -->

<div class="update-area">

    <button
        type="submit"
        name="update_cart"
        class="btn btn-secondary"
    >

        <i class="fa-solid fa-rotate"></i>

        Update Cart

    </button>

</div>


</form>

</section>


<!-- =====================================================
     ORDER SUMMARY
===================================================== -->

<aside class="card summary">

    <h2>
        Order Summary
    </h2>


    <div class="summary-row">

        <span>
            Items
        </span>

        <strong>
            <?= $count ?>
        </strong>

    </div>


    <div class="summary-row">

        <span>
            Subtotal
        </span>

        <strong>
            LKR
            <?= number_format(
                $total,
                2
            ) ?>
        </strong>

    </div>


    <div class="summary-row">

        <span>
            Delivery
        </span>

        <span>
            Calculated at checkout
        </span>

    </div>


    <div class="summary-total">

        <span>
            Total
        </span>

        <span>
            LKR
            <?= number_format(
                $total,
                2
            ) ?>
        </span>

    </div>


    <a
        class="btn checkout-btn"
        href="checkout.php"
    >

        <i class="fa-solid fa-lock"></i>

        Proceed to Checkout

    </a>

</aside>


</div>


<?php endif; ?>


</main>

</body>

</html>

