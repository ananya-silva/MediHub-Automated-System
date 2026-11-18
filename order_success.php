<?php

require_once __DIR__ . "/config.php";

/* =====================================================
   CHECK PATIENT LOGIN
===================================================== */

if (!isset($_SESSION["user_id"])) {

    header("Location: login.php");
    exit;

}


/* =====================================================
   GET ORDER DETAILS FROM SESSION
===================================================== */

$order_id =
    $_SESSION["last_order_id"] ?? null;

$order_total =
    $_SESSION["last_order_total"] ?? 0;

$order_items =
    $_SESSION["last_order_items"] ?? [];


/* =====================================================
   CHECK ORDER
===================================================== */

if (!$order_id) {

    header("Location: Home.php");
    exit;

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

    <title>
        MediHub - Order Successful
    </title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <link
        rel="stylesheet"
        href="checkoutstyle.css"
    >


    <style>
/* =====================================================
   GENERAL
===================================================== */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {
    font-family: Georgia, serif;
    background-color: #ffffff;
    color: #222;
}

a {
    text-decoration: none;
}


/* =====================================================
   HEADER
===================================================== */

.main-header {
    width: 100%;
    background-color: #ffffff;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
}


/* =====================================================
   HEADER TOP
===================================================== */

.header-top {
    width: 100%;
    min-height: 90px;

    display: flex;
    align-items: center;

    padding: 10px 6%;

    position: relative;
}


/* =====================================================
   LOGO
===================================================== */

.logo-area {
    display: flex;
    align-items: center;
    justify-content: center;
}

.logo-area img {
    width: 75px;
    height: 75px;

    object-fit: cover;

    border-radius: 50%;
}


/* =====================================================
   WEBSITE NAME
===================================================== */

.brand-name {
    position: absolute;

    left: 50%;
    transform: translateX(-50%);
}

.brand-name h1 {
    margin: 0;

    color: #299d82;

    font-size: 32px;
    font-weight: 700;
}


/* =====================================================
   HEADER ACTIONS
===================================================== */

.header-actions {
    margin-left: auto;

    display: flex;
    align-items: center;

    gap: 20px;
}


/* Dashboard Button */

.login-btn {
    display: inline-block;

    background-color: #299d82;
    color: #ffffff;

    padding: 10px 20px;

    border-radius: 6px;

    font-size: 15px;
    font-weight: 600;

    transition: 0.3s;
}

.login-btn:hover {
    background-color: #217d63;
    color: #ffffff;
}


/* Cart Icon */

.cart-icon {
    position: relative;

    display: flex;
    align-items: center;
    justify-content: center;

    color: #299d82;

    font-size: 24px;

    transition: 0.3s;
}

.cart-icon:hover {
    color: #217d63;
}


/* Cart Count */

.cart-count {
    position: absolute;

    top: -12px;
    right: -12px;

    width: 21px;
    height: 21px;

    display: flex;
    align-items: center;
    justify-content: center;

    background-color: #e74c3c;
    color: #ffffff;

    border-radius: 50%;

    font-size: 12px;
    font-weight: bold;
}


/* =====================================================
   NAVIGATION
===================================================== */

.navigation {
    width: 100%;

    display: flex;
    align-items: center;
    justify-content: center;

    gap: 15px;

    padding: 14px 10px;

    background-color: #299d82;
}

.navigation a {
    color: #ffffff;

    font-size: 15px;
    font-weight: 500;

    transition: 0.3s;
}

.navigation a:hover {
    color: #d9fff1;
}

.navigation span {
    color: #ffffff;
}


/* =====================================================
   SUCCESS SECTION
===================================================== */

.success-section {
    width: 100%;

    min-height: 500px;

    padding: 70px 20px;

    background-color: #f2f4f3;
}


/* =====================================================
   SUCCESS CARD
===================================================== */

.success-card {
    width: 100%;
    max-width: 700px;

    margin: 0 auto;

    padding: 40px;

    background-color: #ffffff;

    border-radius: 15px;

    box-shadow:
        0 5px 20px rgba(0, 0, 0, 0.10);

    text-align: center;
}


/* =====================================================
   SUCCESS ICON
===================================================== */

.success-icon {
    width: 80px;
    height: 80px;

    margin: 0 auto 20px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 50%;

    background-color: #d4edda;

    color: #28a745;

    font-size: 40px;
}


/* =====================================================
   SUCCESS TITLE
===================================================== */

.success-card h2 {
    margin-bottom: 15px;

    color: #198754;

    font-size: 28px;
}


/* =====================================================
   ORDER ID
===================================================== */

.order-id {
    margin: 20px 0;

    font-size: 20px;

    font-weight: bold;

    color: #333;
}


/* =====================================================
   ORDER SUMMARY
===================================================== */

.order-summary {
    margin-top: 30px;

    padding-top: 20px;

    text-align: left;

    border-top: 1px solid #ddd;
}


/* =====================================================
   ORDER ITEM
===================================================== */

.order-item {
    display: flex;

    align-items: center;
    justify-content: space-between;

    padding: 12px 0;

    border-bottom: 1px solid #eee;

    font-size: 15px;
}

.order-item:last-child {
    border-bottom: none;
}


/* =====================================================
   TOTAL
===================================================== */

.total-row {
    display: flex;

    align-items: center;
    justify-content: space-between;

    padding-top: 20px;

    font-size: 20px;

    font-weight: bold;

    color: #299d82;
}


/* =====================================================
   SUCCESS BUTTONS
===================================================== */

.success-buttons {
    margin-top: 30px;

    display: flex;

    justify-content: center;

    gap: 15px;

    flex-wrap: wrap;
}

.success-buttons a {
    display: inline-block;

    padding: 12px 25px;

    border-radius: 8px;

    font-size: 15px;

    font-weight: bold;

    transition: 0.3s;
}


/* Dashboard Button */

.dashboard-btn {
    background-color: #299d82;
    color: #ffffff;
}

.dashboard-btn:hover {
    background-color: #217d63;
    color: #ffffff;
}


/* Medicine Button */

.medicine-btn {
    background-color: #0d6efd;
    color: #ffffff;
}

.medicine-btn:hover {
    background-color: #0b5ed7;
    color: #ffffff;
}


/* =====================================================
   FOOTER
===================================================== */

.footer {
    width: 100%;

    background-color: #299d82;

    color: #ffffff;
}

.footer-content {
    width: 100%;

    display: flex;
    justify-content: center;
    align-items: center;

    padding: 25px 30px;
}

.footer-column {
    min-height: 75px;

    padding: 0 25px;

    border-left: 1px solid rgba(255, 255, 255, 0.6);
}

.footer-column:first-child {
    border-left: none;
}

.footer-column p {
    margin: 3px 0;

    font-size: 13px;

    line-height: 1.5;
}

.address {
    display: flex;

    align-items: center;

    gap: 12px;
}

.address img {
    width: 60px;
    height: 60px;

    object-fit: contain;
}

.copyright {
    width: 100%;

    padding: 10px;

    text-align: center;

    background-color: #217d63;

    font-size: 13px;
}


/* =====================================================
   TABLET
===================================================== */

@media screen and (max-width: 800px) {

    .header-top {
        padding: 8px 25px;
    }

    .navigation {
        padding: 12px 20px;
    }

    .footer-content {
        flex-wrap: wrap;
    }

}


/* =====================================================
   MOBILE
===================================================== */

@media screen and (max-width: 767px) {

    .header-top {
        min-height: 80px;

        padding: 8px 15px;
    }

    .logo-area img {
        width: 55px;
        height: 55px;
    }

    .brand-name {
        position: static;

        transform: none;

        margin-left: auto;
        margin-right: auto;
    }

    .brand-name h1 {
        font-size: 25px;
    }

    .header-actions {
        gap: 8px;
    }

    .login-btn {
        padding: 7px 10px;

        font-size: 12px;
    }

    .cart-icon {
        font-size: 21px;
    }


    /* Navigation */

    .navigation {
        justify-content: center;

        flex-wrap: wrap;

        padding: 8px 10px;

        gap: 7px;
    }

    .navigation a {
        font-size: 12px;
    }


    /* Success */

    .success-section {
        padding: 40px 15px;
    }

    .success-card {
        padding: 25px 20px;
    }

    .success-card h2 {
        font-size: 24px;
    }

    .success-icon {
        width: 70px;
        height: 70px;

        font-size: 32px;
    }

    .order-item {
        font-size: 14px;
    }

    .total-row {
        font-size: 18px;
    }


    /* Footer */

    .footer-content {
        flex-direction: column;

        align-items: flex-start;

        padding: 20px;
    }

    .footer-column {
        width: 100%;

        padding: 15px 0;

        border-left: none;

        border-top: 1px solid rgba(255, 255, 255, 0.5);
    }

    .footer-column:first-child {
        border-top: none;
    }

}


/* =====================================================
   SMALL MOBILE
===================================================== */

@media screen and (max-width: 500px) {

    .brand-name h1 {
        font-size: 22px;
    }

    .login-btn {
        padding: 6px 8px;

        font-size: 11px;
    }

    .cart-icon {
        font-size: 19px;
    }

    .navigation a {
        font-size: 11px;
    }

    .success-section {
        padding: 30px 10px;
    }

    .success-card {
        padding: 22px 15px;
    }

    .success-card h2 {
        font-size: 21px;
    }

    .order-id {
        font-size: 17px;
    }

    .success-buttons {
        flex-direction: column;
    }

    .success-buttons a {
        width: 100%;
    }

    .copyright {
        font-size: 11px;
    }
}

</style>

</head>


<body>


<!-- =====================================================
     HEADER
===================================================== -->

<header class="main-header">

    <div class="header-top">


        <div class="logo-area">

            <a href="Home.php">

                <img
                    src="images/logo.jpeg"
                    alt="MediHub Logo"
                >

            </a>

        </div>


        <div class="brand-name">

            <h1>
                MediHub
            </h1>

        </div>


        <div class="header-actions">

            <a
                href="Home.php"
                class="login-btn"
            >
                Dashboard
            </a>


            <a
                href="search_medicine.php"
                class="cart-icon"
                title="Search Medicines"
            >

                <i class="fa-solid fa-pills"></i>

            </a>

        </div>

    </div>


    <nav class="navigation">

        <a href="Home.php">
            Home
        </a>

        <span>|</span>

        <a href="about.php">
            About Us
        </a>

        <span>|</span>

        <a href="search_medicine.php">
            Search Medicine
        </a>

        <span>|</span>

        <a href="contact.php">
            Contact Us
        </a>

    </nav>

</header>



<!-- =====================================================
     HERO
===================================================== -->

<section class="hero">

    <div class="hero-overlay">

        <h2>
            Order Successful
        </h2>

    </div>

</section>



<!-- =====================================================
     SUCCESS MESSAGE
===================================================== -->

<section class="success-section">

    <div class="container">

        <div class="success-card">


            <!-- SUCCESS ICON -->

            <div class="success-icon">

                <i class="fa-solid fa-check"></i>

            </div>


            <h2>
                Order Placed Successfully!
            </h2>


            <p>
                Thank you for ordering from MediHub.
            </p>


            <!-- ORDER ID -->

            <div class="order-id">

                Order ID:

                #<?php

                echo htmlspecialchars(
                    $order_id
                );

                ?>

            </div>


            <!-- ORDER SUMMARY -->

            <div class="order-summary">

                <h4>
                    Order Summary
                </h4>


                <?php

                foreach (
                    $order_items
                    as $item
                ):

                ?>

                    <div class="order-item">

                        <div>

                            <strong>

                                <?php

                                echo htmlspecialchars(
                                    $item["name"]
                                );

                                ?>

                            </strong>

                            <br>

                            <small>

                                Quantity:

                                <?php

                                echo (int)
                                    $item["quantity"];

                                ?>

                            </small>

                        </div>


                        <div>

                            LKR

                            <?php

                            echo number_format(
                                $item["subtotal"],
                                2
                            );

                            ?>

                        </div>

                    </div>


                <?php

                endforeach;

                ?>


                <!-- TOTAL -->

                <div class="total-row">

                    <span>
                        Total
                    </span>

                    <span>

                        LKR

                        <?php

                        echo number_format(
                            $order_total,
                            2
                        );

                        ?>

                    </span>

                </div>

            </div>



            <!-- BUTTONS -->

            <div class="success-buttons">


                <a
                    href="Home.php"
                    class="dashboard-btn"
                >

                    <i
                        class="fa-solid fa-house"
                    ></i>

                    Dashboard

                </a>


                <a
                    href="search_medicine.php"
                    class="medicine-btn"
                >

                    <i
                        class="fa-solid fa-pills"
                    ></i>

                    Continue Shopping

                </a>


            </div>


        </div>

    </div>

</section>



<!-- =====================================================
     FOOTER
===================================================== -->

<footer class="footer">

    <div class="container">

        <div class="row footer-content g-4">


            <div
                class="col-12 col-md-4 footer-column address"
            >

                <a href="Home.php">

                    <img
                        src="images/logo.jpeg"
                        alt="MediHub Logo"
                    >

                </a>


                <p>

                    No.78, Baseline<br>
                    Road, Colombo 09,<br>
                    Sri Lanka

                </p>

            </div>



            <div
                class="col-12 col-md-4 footer-column"
            >

                <p>
                    Phone : +94 11 234 5678
                </p>

                <p>
                    +94 77 456 7890
                </p>

                <p>
                    +94 71 987 6543
                </p>

                <p>
                    Email : info@medihub.lk
                </p>

            </div>



            <div
                class="col-12 col-md-4 footer-column"
            >

                <p>
                    Facebook : facebook.com/medihub.lk
                </p>

                <p>
                    Instagram : instagram.com/medihub.lk
                </p>

                <p>
                    Whatsapp : +94 77 456 7890
                </p>

            </div>


        </div>

    </div>


    <div class="copyright">

        Copyright © 2026 MediHub Pharmacy.

        All Rights Reserved by: CSE5015

    </div>

</footer>


</body>

</html>