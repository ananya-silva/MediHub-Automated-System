<?php

require_once __DIR__ . "/config.php";
require_once __DIR__ . '/db.php';

$medicineId = (int) ($_GET["id"] ?? 0);

if ($medicineId <= 0) {
    header("Location: search_medicine.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT m.*, c.category_name, s.supplier_name
    FROM medicines m
    LEFT JOIN categories c ON c.category_id = m.category_id
    LEFT JOIN suppliers s ON s.supplier_id = m.supplier_id
    WHERE m.medicine_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $medicineId);
$stmt->execute();

$medicine = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$medicine) {
    http_response_code(404);
    exit("Medicine not found.");
}

$name = $medicine["medicine_name"];
$nameLower = strtolower($name);

$image = strpos($nameLower, "paracetamol") !== false
    ? "images/paracetamol.jpg"
    : (
        (strpos($nameLower, "amoxicillin") !== false ||
         strpos($nameLower, "azithromycin") !== false)
            ? "images/amoxicillin.jpeg"
            : (
                strpos($nameLower, "vitamin") !== false
                    ? "images/vitamin c.jpg"
                    : "images/paracetamol.jpg"
            )
    );

$stock = (int) $medicine["stock_quantity"];

$loggedIn = isset($_SESSION["user_id"]);

?>

<!doctype html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width,initial-scale=1"
    >

    <title>
        MediHub - <?php echo htmlspecialchars($name); ?>
    </title>

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <style>
        body {
            margin: 0;
            background: #f5f8f7;
            font-family: Georgia, serif;
            color: #222;
        }

        .header {
            background: #299d82;
            color: #fff;
        }

        .top {
            min-height: 82px;
            padding: 10px 5%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
        }

        .logo {
            width: 58px;
            height: 58px;
            object-fit: contain;
        }

        .brand {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            font-size: 31px;
        }

        .actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .actions a {
            color: #fff;
            text-decoration: none;
        }

        .login {
            border: 1px solid #fff;
            border-radius: 22px;
            padding: 7px 16px;
        }

        .nav {
            display: flex;
            gap: 13px;
            padding: 0 5% 11px;
        }

        .nav a {
            color: #fff;
            text-decoration: none;
        }

        .hero {
            height: 220px;
            background:
                linear-gradient(
                    90deg,
                    rgba(0, 0, 0, .6),
                    rgba(0, 0, 0, .1)
                ),
                url("images/search medicine.png") center / cover;

            display: flex;
            align-items: center;
            padding: 0 7%;
            color: #fff;
        }

        .hero h1 {
            font-size: 40px;
        }

        .card-detail {
            max-width: 1050px;
            margin: -45px auto 55px;
            position: relative;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 10px 35px rgba(0, 0, 0, .11);
            overflow: hidden;
            display: grid;
            grid-template-columns: 45% 55%;
        }

        .image-box {
            min-height: 470px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 35px;
            background: #fbfdfc;
        }

        .image-box img {
            max-width: 90%;
            max-height: 380px;
            object-fit: contain;
        }

        .info {
            padding: 42px;
        }

        .category {
            color: #299d82;
            text-transform: uppercase;
            font-weight: 700;
            font-size: 13px;
        }

        .info h2 {
            font-size: 34px;
            margin: 8px 0;
        }

        .dose {
            color: #666;
            font-size: 17px;
        }

        .price {
            font-size: 29px;
            color: #299d82;
            font-weight: 700;
            margin: 20px 0;
        }

        .line {
            padding: 11px 0;
            border-bottom: 1px solid #edf0ef;
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }

        .line span:first-child {
            color: #777;
        }

        .stock {
            font-weight: 700;
            color: #198754;
        }

        .rx {
            display: inline-block;
            background: #fff3cd;
            color: #856404;
            padding: 7px 11px;
            border-radius: 20px;
            font-size: 13px;
            margin-top: 17px;
        }

        .buttons {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .buttons a {
            flex: 1;
            text-align: center;
            text-decoration: none;
            padding: 12px;
            border-radius: 9px;
        }

        .back {
            border: 1px solid #299d82;
            color: #299d82;
        }

        .cart {
            background: #299d82;
            color: #fff;
        }

        .note {
            font-size: 13px;
            color: #777;
            margin-top: 12px;
        }

        @media (max-width: 750px) {

            .brand {
                position: static;
                transform: none;
                font-size: 25px;
            }

            .nav {
                overflow: auto;
                white-space: nowrap;
            }

            .card-detail {
                grid-template-columns: 1fr;
                margin: -25px 12px 30px;
            }

            .image-box {
                min-height: 280px;
            }

            .info {
                padding: 25px;
            }

            .info h2 {
                font-size: 28px;
            }
        }
    </style>

</head>

<body>

    <header class="header">

        <div class="top">

            <a href="Home.php">
                <img
                    class="logo"
                    src="images/logo.jpeg"
                    alt="MediHub"
                >
            </a>

            <div class="brand">
                MediHub
            </div>

            <div class="actions">

                <?php if ($loggedIn): ?>

                    <a
                        class="login"
                        href="logout.php"
                    >
                        Logout
                    </a>

                <?php else: ?>

                    <a
                        class="login"
                        href="login.php"
                    >
                        Login / Register
                    </a>

                <?php endif; ?>

                <a href="cart.php">
                    <i class="fa-solid fa-cart-shopping fa-lg"></i>
                </a>

            </div>

        </div>

        <nav class="nav">

            <a href="Home.php">
                Home
            </a>

            <span>|</span>

            <a href="about.php">
                About Us
            </a>

            <span>|</span>

            <a href="search_medicine.php">
                Search Medicines
            </a>

            <span>|</span>

            <a href="contact.php">
                Contact Us
            </a>

        </nav>

    </header>


    <section class="hero">

        <div>

            <h1>
                Medicine Details
            </h1>

            <p>
                Reliable medicine information from MediHub Pharmacy.
            </p>

        </div>

    </section>


    <main class="card-detail">

        <div class="image-box">

            <img
                src="<?php echo htmlspecialchars($image); ?>"
                alt="<?php echo htmlspecialchars($name); ?>"
            >

        </div>


        <div class="info">

            <div class="category">
                <?php
                echo htmlspecialchars(
                    $medicine["category_name"] ?? "General"
                );
                ?>
            </div>

            <h2>
                <?php echo htmlspecialchars($name); ?>
            </h2>

            <div class="dose">
                <?php
                echo htmlspecialchars(
                    $medicine["dosage"] ?? "Standard dosage"
                );
                ?>
            </div>

            <div class="price">
                LKR <?php echo number_format(
                    (float) $medicine["price"],
                    2
                ); ?>
            </div>


            <div class="line">

                <span>
                    Availability
                </span>

                <span class="stock">
                    <?php
                    echo $stock > 0
                        ? "In Stock ({$stock})"
                        : "Out of Stock";
                    ?>
                </span>

            </div>


            <div class="line">

                <span>
                    Expiry Date
                </span>

                <span>
                    <?php
                    echo $medicine["expiry_date"]
                        ? date(
                            "d M Y",
                            strtotime($medicine["expiry_date"])
                        )
                        : "Not specified";
                    ?>
                </span>

            </div>


            <div class="line">

                <span>
                    Supplier
                </span>

                <span>
                    <?php
                    echo htmlspecialchars(
                        $medicine["supplier_name"]
                            ?? "MediHub Supplier"
                    );
                    ?>
                </span>

            </div>


            <?php if ((int) $medicine["requires_prescription"] === 1): ?>

                <div class="rx">

                    <i class="fa-solid fa-file-prescription me-1"></i>

                    Prescription required before purchase

                </div>

            <?php endif; ?>


            <div class="buttons">

                <a
                    class="back"
                    href="search_medicine.php"
                >
                    <i class="fa-solid fa-arrow-left me-1"></i>
                    Back to Medicines
                </a>


                <?php if ($stock > 0): ?>

                    <a
                        class="cart"
                        href="Add_to_cart.php?id=<?php echo $medicineId; ?>"
                    >
                        <i class="fa-solid fa-cart-plus me-1"></i>
                        Add to Cart
                    </a>

                <?php endif; ?>

            </div>


            <?php if (!$loggedIn): ?>

                <div class="note">
                    Please log in to add medicines to your cart.
                </div>

            <?php endif; ?>

        </div>

    </main>

</body>

</html>