<?php
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/db.php";

$logged_in = isset($_SESSION["user_id"]);
$patient_name = $_SESSION["name"] ?? "";
$patient_role = $_SESSION["role"] ?? "";
$is_patient = $logged_in && strcasecmp((string)$patient_role, "Patient") === 0;
$home_categories = [];
$cat_result = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_name");
if ($cat_result) {
    $home_categories = $cat_result->fetch_all(MYSQLI_ASSOC);
}

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>MediHub - Home</title>

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Home CSS -->
    <link rel="stylesheet" href="HomeStyle.css">
<style>
.patient-welcome{display:flex;align-items:center;gap:12px;flex-wrap:wrap;font-size:15px;font-weight:600;color:#299d82}
.patient-welcome{display:flex;align-items:center;gap:10px;flex-wrap:wrap;font-size:15px;font-weight:700;color:#fff;background:rgba(0,0,0,.12);padding:7px 10px;border-radius:8px}.logout-btn{background:#dc3545;color:#fff;padding:9px 16px;border-radius:6px;text-decoration:none;font-weight:700;display:inline-block}.logout-btn:hover{background:#bb2d3b;color:#fff}
.logout-btn:hover{background:#bb2d3b;color:#fff}
</style>
</head>

<body>

<!-- HEADER -->

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
            <h1>MediHub</h1>
        </div>

        <div class="header-actions">

            <?php if ($is_patient): ?>
                <div class="patient-welcome">
                    <span>Welcome, <?php echo htmlspecialchars($patient_name); ?>!</span>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            <?php else: ?>
                <a href="login.php" class="login-btn">Login/Register</a>
            <?php endif; ?>

            <a
                href="cart.php"
                class="cart-icon"
                title="Shopping Cart"
            >
                <i class="fa-solid fa-cart-shopping"></i>
            </a>

        </div>

    </div>

    <!-- Navigation -->

    <nav class="navigation">

        <a href="Home.php">
            Home
        </a>

        <span>|</span>

       <a href="search_medicine.php">
            Search Medicine
        </a>

        <span>|</span>
         <a href="about.php">
            About Us
        </a>
        

        <span>|</span>

        <a href="contact.php">
            Contact Us
        </a>

    </nav>

</header>


<!-- HERO -->

<section class="home-hero">

    <div class="hero-content">

        <h2>
            "Your Trusted Hospital<br>
            Pharmacy"<br>
            Your Health Our Priority
        </h2>

    </div>

</section>


<!-- SEARCH SECTION -->

<section class="search-section">

    <div class="container">

        <form action="search_medicine.php" method="GET">

            <!-- Search Row -->
            <div class="search-container">

                <div class="home-search-box">

                    <i class="fa-solid fa-bars"></i>

                    <input
                        type="text"
                        name="search"
                        placeholder="Search medicines..."
                    >

                    <button
                        type="submit"
                        class="search-button"
                        aria-label="Search"
                    >
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>

                </div>

                <a
                    href="upload_prescription.php"
                    class="upload-btn"
                >
                    Upload Prescription
                </a>

            </div>

            <!-- Filter Row -->
            <div class="search-filters">

                <div class="filter">

                    <label for="category">
                        Category
                    </label>

                    <select id="category" name="category">

                        <option value="">All Categories</option>
                        <?php foreach ($home_categories as $cat): ?>
                            <option value="<?php echo (int)$cat["category_id"]; ?>">
                                <?php echo htmlspecialchars($cat["category_name"]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <i class="fa-solid fa-chevron-down"></i>

                </div>


                <div class="filter">

                    <label for="manufacturer">
                        Manufacturer
                    </label>

                    <select id="manufacturer" name="manufacturer">

                        <option value="">
                            Select Manufacturer
                        </option>

                        <option value="ABC Pharma">
                            ABC Pharma
                        </option>

                        <option value="Health Pharma">
                            Health Pharma
                        </option>

                        <option value="Nature Pharma">
                            Nature Pharma
                        </option>

                    </select>

                    <i class="fa-solid fa-chevron-down"></i>

                </div>

            </div>

        </form>

    </div>

</section>


<!-- FEATURED MEDICINES -->

<section class="featured-section py-5">

    <div class="container">

        <h2 class="featured-title text-center mb-4">
            Featured Medicines
        </h2>

        <div
            id="medicineCarousel"
            class="carousel slide"
            data-bs-ride="carousel"
        >

            <div class="carousel-inner">


                <!-- SLIDE 1 -->

                <div class="carousel-item active">

                    <div class="row g-4 justify-content-center">


                        <!-- Medicine 1 -->

                        <div class="col-12 col-sm-6 col-lg-4">

                            <div class="medicine-card h-100">

                                <div class="medicine-image">

                                    <img
                                        src="images/panadol.png"
                                        alt="Paracetamol 500mg"
                                        class="img-fluid"
                                    >

                                </div>

                                <h3>
                                    Paracetamol 500mg
                                </h3>

                                <p>
                                    Category: Pain Relief
                                </p>

                                <strong>
                                    LKR 200.00 
                                </strong>

                                <p class="stock">
                                    Stock: In Stock
                                </p>

                                <!-- IMPORTANT -->
                                <a
                                    href="medicine_details.php?id=1"
                                    class="details-btn"
                                >
                                    View Details
                                </a>

                            </div>

                        </div>


                        <!-- Medicine 2 -->

                        <div class="col-12 col-sm-6 col-lg-4">

                            <div class="medicine-card h-100">

                                <div class="medicine-image">

                                    <img
                                        src="images/amoxicillin.jpeg"
                                        alt="Amoxicillin 500mg"
                                        class="img-fluid"
                                    >

                                </div>

                                <h3>
                                    Amoxicillin 500mg 
                                </h3>

                                <p>
                                    Category: Antibiotics
                                </p>

                                <strong>
                                    LKR 420.00
                                </strong>

                                <p class="stock">
                                    Stock: In Stock
                                </p>

                                <a
                                    href="medicine_details.php?id=2"
                                    class="details-btn"
                                >
                                    View Details
                                </a>

                            </div>

                        </div>


                        <!-- Medicine 3 -->

                        <div class="col-12 col-sm-6 col-lg-4">

                            <div class="medicine-card h-100">

                                <div class="medicine-image">

                                    <img
                                        src="images/vitamin c.jpg"
                                        alt="Vitamin C 1000mg"
                                        class="img-fluid"
                                    >

                                </div>

                                <h3>
                                    Vitamin C 1000mg
                                </h3>

                                <p>
                                    Category: Supplements
                                </p>

                                <strong>
                                    LKR 280.00
                                </strong>

                                <p class="stock">
                                    Stock: In Stock
                                </p>

                                <a
                                    href="medicine_details.php?id=3"
                                    class="details-btn"
                                >
                                    View Details
                                </a>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- SLIDE 2 -->

                <div class="carousel-item">

                    <div class="row g-4 justify-content-center">


                        <!-- Medicine 4 -->

                        <div class="col-12 col-sm-6 col-lg-4">

                            <div class="medicine-card h-100">

                                <div class="medicine-image">

                                    <img
                                        src="images/ibuprofen.png"
                                        alt="Ibuprofen 200mg"
                                        class="img-fluid"
                                    >

                                </div>

                                <h3>
                                    Ibuprofen 200mg
                                </h3>

                                <p>
                                    Category: Pain Relief
                                </p>

                                <strong>
                                    LKR 600.00
                                </strong>

                                <p class="stock">
                                    Stock: In Stock
                                </p>

                                <a
                                    href="medicine_details.php?id=4"
                                    class="details-btn"
                                >
                                    View Details
                                </a>

                            </div>

                        </div>


                        <!-- Medicine 5 -->

                        <div class="col-12 col-sm-6 col-lg-4">

                            <div class="medicine-card h-100">

                                <div class="medicine-image">

                                    <img
                                        src="images/azithromycin.png" 
                                        alt="Azithromycin 250mg" 
                                        class="img-fluid"
                                    >

                                </div>

                                <h3>
                                    Azithromycin 250mg
                                </h3>

                                <p>
                                    Category: Antibiotics
                                </p>

                                <strong>
                                    LKR 700.00 
                                </strong>

                                <p class="stock">
                                    Stock: In Stock
                                </p>

                                <a
                                    href="medicine_details.php?id=2"
                                    class="details-btn"
                                >
                                    View Details
                                </a>

                            </div>

                        </div>


                        <!-- Medicine 6 -->

                        <div class="col-12 col-sm-6 col-lg-4">

                            <div class="medicine-card h-100">

                                <div class="medicine-image">

                                    <img
                                        src="images/lactulose.png"
                                        alt="Lactulose Syrup"
                                        class="img-fluid"
                                    >

                                </div>

                                <h3>
                                    Lactulose Syrup 120ml
                                </h3>

                                <p>
                                    Category: Digestive Health 
                                </p>

                                <strong>
                                    LKR 875.00
                                </strong>

                                <p class="stock">
                                    Stock: In Stock
                                </p>

                                <a
                                    href="medicine_details.php?id=5"
                                    class="details-btn"
                                >
                                    View Details
                                </a>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- Previous -->

            <button
                class="carousel-control-prev"
                type="button"
                data-bs-target="#medicineCarousel"
                data-bs-slide="prev"
            >

                <span class="carousel-control-prev-icon"></span>

                <span class="visually-hidden">
                    Previous
                </span>

            </button>


            <!-- Next -->

            <button
                class="carousel-control-next"
                type="button"
                data-bs-target="#medicineCarousel"
                data-bs-slide="next"
            >

                <span class="carousel-control-next-icon"></span>

                <span class="visually-hidden">
                    Next
                </span>

            </button>


            <!-- Indicators -->

            <div class="carousel-indicators">

                <button
                    type="button"
                    data-bs-target="#medicineCarousel"
                    data-bs-slide-to="0"
                    class="active"
                ></button>

                <button
                    type="button"
                    data-bs-target="#medicineCarousel"
                    data-bs-slide-to="1"
                ></button>

            </div>

        </div>

    </div>

</section>


<!-- SERVICES -->

<section class="services-section py-5">

    <div class="container">

        <h2 class="services-title text-center mb-4">
            MediHub Services
        </h2>

        <div class="row g-4 justify-content-center">


            <div class="col-12 col-sm-6 col-lg-3">

                <a
                    href="search_medicine.php"
                    class="service-card h-100"
                >

                    <h3>
                        Online Medicine Ordering
                    </h3>

                    <img
                        src="images/service 1.png"
                        alt="Online Medicine Ordering"
                        class="img-fluid"
                    >

                </a>

            </div>


            <div class="col-12 col-sm-6 col-lg-3">

                <a
                    href="upload_prescription.php"
                    class="service-card h-100"
                >

                    <h3>
                        Prescription Upload
                    </h3>

                    <img
                        src="images/service 2.png"
                        alt="Prescription Upload"
                        class="img-fluid"
                    >

                </a>

            </div>


            <div class="col-12 col-sm-6 col-lg-3">

                <a
                    href="orders.php"
                    class="service-card h-100"
                >

                    <h3>
                        Order Tracking
                    </h3>

                    <img
                        src="images/service 3.png"
                        alt="Order Tracking"
                        class="img-fluid"
                    >

                </a>

            </div>


            <div class="col-12 col-sm-6 col-lg-3">

                <a
                    href="contact.php"
                    class="service-card h-100"
                >

                    <h3>
                        Hospital Pharmacy Support
                    </h3>

                    <img
                        src="images/service 4.jpg"
                        alt="Hospital Pharmacy Support"
                        class="img-fluid"
                    >

                </a>

            </div>

        </div>

    </div>

</section>


<!-- FOOTER -->

<footer class="footer">

    <div class="footer-content">

        <div class="footer-column address">

            <a href="Home.php">

                <img
                    src="images/logo.jpeg"
                    alt="MediHub Logo"
                >

            </a>

            <p>
                No.78, Baseline<br>
                Road, Colombo 09, Sri Lanka
            </p>

        </div>


        <div class="footer-column">

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


        <div class="footer-column">

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


    <div class="copyright">

        Copyright © 2026 MediHub Pharmacy.
        All Rights Reserved by: CSE5015

    </div>

</footer>


<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

<script src="script.js"></script>

</body>

</html>
