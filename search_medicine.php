<?php

require_once __DIR__ . "/config.php";
require_once __DIR__ . '/db.php';


$search = trim($_GET["search"] ?? "");
$categoryId = (int) ($_GET["category"] ?? 0);
$sort = $_GET["sort"] ?? "name";
$prescription = $_GET["prescription"] ?? "";


$categories = [];

$catResult = $conn->query("
    SELECT
        c.category_id,
        c.category_name,
        c.description,
        COUNT(m.medicine_id) AS medicine_count
    FROM categories c
    LEFT JOIN medicines m
        ON m.category_id = c.category_id
        AND m.stock_quantity > 0
    GROUP BY
        c.category_id,
        c.category_name,
        c.description
    ORDER BY c.category_name
");

if ($catResult) {
    $categories = $catResult->fetch_all(MYSQLI_ASSOC);
}


$orderBy = "m.medicine_name ASC";

if ($sort === "price_low") {
    $orderBy = "m.price ASC, m.medicine_name ASC";
}

if ($sort === "price_high") {
    $orderBy = "m.price DESC, m.medicine_name ASC";
}

if ($sort === "newest") {
    $orderBy = "m.created_at DESC";
}


$sql = "
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
        c.category_id,
        c.category_name,
        s.supplier_name
    FROM medicines m
    LEFT JOIN categories c
        ON c.category_id = m.category_id
    LEFT JOIN suppliers s
        ON s.supplier_id = m.supplier_id
    WHERE m.stock_quantity > 0
";


$params = [];
$types = "";


if ($search !== "") {

    $sql .= "
        AND (
            m.medicine_name LIKE ?
            OR m.dosage LIKE ?
            OR c.category_name LIKE ?
        )
    ";

    $term = "%" . $search . "%";

    $params = [
        $term,
        $term,
        $term
    ];

    $types = "sss";
}


if ($categoryId > 0) {

    $sql .= " AND m.category_id = ?";

    $params[] = $categoryId;

    $types .= "i";
}


if ($prescription === "yes") {

    $sql .= " AND m.requires_prescription = 1";

} elseif ($prescription === "no") {

    $sql .= " AND m.requires_prescription = 0";
}


$sql .= " ORDER BY " . $orderBy;


$stmt = $conn->prepare($sql);

$medicines = [];

if ($stmt) {

    if ($types !== "") {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();

    $medicines = $stmt
        ->get_result()
        ->fetch_all(MYSQLI_ASSOC);

    $stmt->close();
}


function medicineImage(array $medicine): string
{
    if (!empty($medicine["image_path"])) {
        return $medicine["image_path"];
    }

    $name = strtolower(
        trim($medicine["medicine_name"] ?? "")
    );

    if (
        strpos($name, "paracetamol") !== false
        || strpos($name, "acetaminophen") !== false
    ) {
        return "images/paracetamol.jpg";
    }

    if (
        strpos($name, "amoxicillin") !== false
        || strpos($name, "azithromycin") !== false
    ) {
        return "images/amoxicillin.jpeg";
    }

    if (strpos($name, "vitamin") !== false) {
        return "images/vitamin c.jpg";
    }

    return "images/search medicine.png";
}


$loggedIn = isset($_SESSION["user_id"]);
$role = $_SESSION["role"] ?? "";

?>


<!doctype html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        MediHub - Search Medicines
    </title>


    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <link
        rel="stylesheet"
        href="HomeStyle.css"
    >


    <style>

        :root {
            --mh: #299d82;
            --mh-dark: #217a69;
            --bg: #f5f8f7;
        }

        body {
            font-family: Georgia, serif;
            background: var(--bg);
            color: #222;
        }

        .mh-header {
            background: var(--mh);
            color: #fff;
        }

        .mh-top {
            min-height: 82px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 5%;
            position: relative;
        }

        .mh-logo {
            width: 58px;
            height: 58px;
            object-fit: contain;
        }

        .mh-brand {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            font-size: 31px;
        }

        .mh-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .mh-actions a {
            color: #fff;
            text-decoration: none;
        }

        .mh-login {
            border: 1px solid #fff;
            border-radius: 22px;
            padding: 7px 16px;
        }

        .mh-nav {
            display: flex;
            gap: 13px;
            padding: 0 5% 11px;
            align-items: center;
        }

        .mh-nav a {
            color: #fff;
            text-decoration: none;
        }

        .mh-nav a:hover {
            text-decoration: underline;
        }


        .page-hero {
            background:
                linear-gradient(
                    90deg,
                    rgba(0, 0, 0, .55),
                    rgba(0, 0, 0, .15)
                ),
                url("images/search medicine.png")
                center / cover;

            height: 260px;
            display: flex;
            align-items: center;
            padding: 0 7%;
            color: #fff;
        }

        .page-hero h1 {
            font-size: 42px;
            margin: 0;
        }

        .page-hero p {
            font-size: 17px;
            margin-top: 8px;
        }


        .search-panel {
            background: #fff;
            margin: -35px auto 25px;
            position: relative;
            max-width: 1100px;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 28px rgba(0, 0, 0, .10);
        }

        .search-main {
            display: flex;
            gap: 10px;
        }

        .search-main input {
            flex: 1;
            height: 50px;
            border: 1px solid #d5dedb;
            border-radius: 9px;
            padding: 0 16px;
            font-size: 16px;
        }

        .search-main button {
            border: 0;
            background: var(--mh);
            color: #fff;
            border-radius: 9px;
            padding: 0 25px;
            font-weight: 600;
        }

        .filter-row {
            display: grid;
            grid-template-columns: 1.3fr 1fr 1fr;
            gap: 12px;
            margin-top: 14px;
        }

        .filter-row select {
            height: 46px;
            border: 1px solid #d5dedb;
            border-radius: 9px;
            padding: 0 12px;
            background: #fff;
        }

        .clear-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            border: 1px solid #d5dedb;
            border-radius: 9px;
            color: #555;
        }


        .section-wrap {
            max-width: 1100px;
            margin: auto;
            padding: 10px 20px 55px;
        }

        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: end;
            margin: 18px 0;
        }

        .section-title h2 {
            color: var(--mh);
            font-size: 28px;
            margin: 0;
        }

        .result-count {
            color: #666;
        }


        .category-grid {
            display: grid;
            grid-template-columns:
                repeat(auto-fit, minmax(180px, 1fr));
            gap: 13px;
            margin-bottom: 28px;
        }

        .category-card {
            background: #fff;
            border: 1px solid #e2e9e7;
            border-radius: 12px;
            padding: 17px;
            text-decoration: none;
            color: #333;
            transition: .2s;
        }

        .category-card:hover,
        .category-card.active {
            border-color: var(--mh);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(0, 0, 0, .07);
        }

        .category-card i {
            color: var(--mh);
            font-size: 21px;
        }

        .category-card strong {
            display: block;
            margin-top: 8px;
        }

        .category-card small {
            color: #777;
        }


        .medicine-grid {
            display: grid;
            grid-template-columns:
                repeat(auto-fit, minmax(230px, 1fr));
            gap: 20px;
        }

        .medicine-card-real {
            background: #fff;
            border: 1px solid #e1e8e5;
            border-radius: 15px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: .2s;
        }

        .medicine-card-real:hover {
            transform: translateY(-4px);
            box-shadow: 0 9px 25px rgba(0, 0, 0, .10);
        }

        .med-img {
            height: 175px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
            background: #fff;
        }

        .med-img img {
            max-width: 100%;
            max-height: 150px;
            object-fit: contain;
        }

        .med-body {
            padding: 17px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .med-cat {
            font-size: 13px;
            color: var(--mh);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .3px;
        }

        .med-name {
            font-size: 20px;
            margin: 5px 0;
        }

        .med-dose {
            color: #777;
            font-size: 14px;
        }

        .med-price {
            font-size: 20px;
            color: var(--mh);
            font-weight: 700;
            margin: 12px 0 5px;
        }

        .stock-good {
            color: #198754;
            font-size: 13px;
        }

        .stock-low {
            color: #b7791f;
            font-size: 13px;
        }

        .rx-badge {
            display: inline-block;
            background: #fff3cd;
            color: #856404;
            border-radius: 20px;
            padding: 4px 9px;
            font-size: 12px;
            margin-top: 8px;
            width: max-content;
        }

        .med-actions {
            display: flex;
            gap: 8px;
            margin-top: auto;
            padding-top: 15px;
        }

        .med-actions a {
            flex: 1;
            text-align: center;
            text-decoration: none;
            border-radius: 8px;
            padding: 9px 6px;
            font-size: 14px;
        }

        .details {
            border: 1px solid var(--mh);
            color: var(--mh);
        }

        .add {
            background: var(--mh);
            color: #fff;
        }


        .empty {
            background: #fff;
            border: 1px dashed #cbd5d1;
            border-radius: 14px;
            text-align: center;
            padding: 45px;
            color: #666;
        }


        .footer-real {
            background: var(--mh);
            color: #fff;
            text-align: center;
            padding: 22px;
        }


        @media (max-width: 700px) {

            .mh-brand {
                position: static;
                transform: none;
                font-size: 25px;
            }

            .mh-top {
                padding: 10px 15px;
            }

            .mh-nav {
                padding-left: 15px;
                overflow: auto;
                white-space: nowrap;
            }

            .page-hero {
                height: 220px;
                padding: 0 25px;
            }

            .page-hero h1 {
                font-size: 32px;
            }

            .search-panel {
                margin: -25px 12px 20px;
                padding: 16px;
            }

            .search-main {
                flex-direction: column;
            }

            .search-main button {
                height: 46px;
            }

            .filter-row {
                grid-template-columns: 1fr;
            }

            .section-wrap {
                padding-left: 12px;
                padding-right: 12px;
            }
        }

    </style>

</head>


<body>


<header class="mh-header">

    <div class="mh-top">

        <a href="Home.php">

            <img
                class="mh-logo"
                src="images/logo.jpeg"
                alt="MediHub"
            >

        </a>


        <div class="mh-brand">
            MediHub
        </div>


        <div class="mh-actions">

            <?php if ($loggedIn): ?>

                <a
                    class="mh-login"
                    href="logout.php"
                >
                    Logout
                </a>

            <?php else: ?>

                <a
                    class="mh-login"
                    href="login.php"
                >
                    Login / Register
                </a>

            <?php endif; ?>


            <a
                href="cart.php"
                title="Cart"
            >
                <i class="fa-solid fa-cart-shopping fa-lg"></i>
            </a>

        </div>

    </div>


    <nav class="mh-nav">

        <a href="Home.php">
            Home
        </a>

        <span>|</span>

        <a href="search_medicine.php">
            Search Medicines
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


<section class="page-hero">

    <div>

        <h1>
            Find Your Medicine
        </h1>

        <p>
            Search medicines, browse categories and check availability before ordering.
        </p>

    </div>

</section>


<main class="section-wrap">


    <form
        class="search-panel"
        method="get"
        action="search_medicine.php"
    >

        <div class="search-main">

            <input
                name="search"
                value="<?php echo htmlspecialchars($search); ?>"
                placeholder="Search by medicine name, dosage or category..."
                autocomplete="off"
            >


            <button type="submit">

                <i class="fa-solid fa-magnifying-glass me-2"></i>

                Search

            </button>

        </div>


        <div class="filter-row">

            <select
                name="category"
                onchange="this.form.submit()"
            >

                <option value="0">
                    All Categories
                </option>


                <?php foreach ($categories as $cat): ?>

                    <option
                        value="<?php echo (int) $cat['category_id']; ?>"
                        <?php echo $categoryId === (int) $cat['category_id'] ? 'selected' : ''; ?>
                    >
                        <?php echo htmlspecialchars($cat['category_name']); ?>
                    </option>

                <?php endforeach; ?>

            </select>


            <select
                name="prescription"
                onchange="this.form.submit()"
            >

                <option value="">
                    Prescription: All
                </option>

                <option
                    value="yes"
                    <?php echo $prescription === 'yes' ? 'selected' : ''; ?>
                >
                    Prescription Required
                </option>

                <option
                    value="no"
                    <?php echo $prescription === 'no' ? 'selected' : ''; ?>
                >
                    No Prescription
                </option>

            </select>


            <select
                name="sort"
                onchange="this.form.submit()"
            >

                <option
                    value="name"
                    <?php echo $sort === 'name' ? 'selected' : ''; ?>
                >
                    Sort: Name
                </option>

                <option
                    value="price_low"
                    <?php echo $sort === 'price_low' ? 'selected' : ''; ?>
                >
                    Price: Low to High
                </option>

                <option
                    value="price_high"
                    <?php echo $sort === 'price_high' ? 'selected' : ''; ?>
                >
                    Price: High to Low
                </option>

                <option
                    value="newest"
                    <?php echo $sort === 'newest' ? 'selected' : ''; ?>
                >
                    Newest Added
                </option>

            </select>

        </div>


        <?php if (
            $search !== ''
            || $categoryId > 0
            || $prescription !== ''
            || $sort !== 'name'
        ): ?>

            <div class="mt-3">

                <a
                    class="text-decoration-none"
                    style="color:var(--mh)"
                    href="search_medicine.php"
                >

                    <i class="fa-solid fa-rotate-left me-1"></i>

                    Clear all filters

                </a>

            </div>

        <?php endif; ?>

    </form>


    <div class="section-title">

        <h2>
            Browse Categories
        </h2>

        <span class="result-count">
            <?php echo count($categories); ?> categories
        </span>

    </div>


    <div class="category-grid">

        <a
            class="category-card <?php echo $categoryId === 0 ? 'active' : ''; ?>"
            href="search_medicine.php"
        >

            <i class="fa-solid fa-pills"></i>

            <strong>
                All Medicines
            </strong>

            <small>
                View all available medicines
            </small>

        </a>


        <?php foreach ($categories as $cat): ?>

            <a
                class="category-card <?php echo $categoryId === (int) $cat['category_id'] ? 'active' : ''; ?>"
                href="search_medicine.php?category=<?php echo (int) $cat['category_id']; ?>"
            >

                <i class="fa-solid fa-capsules"></i>

                <strong>
                    <?php echo htmlspecialchars($cat['category_name']); ?>
                </strong>

                <small>
                    <?php echo (int) $cat['medicine_count']; ?> available
                </small>

            </a>

        <?php endforeach; ?>

    </div>


    <div class="section-title">

        <h2>

            <?php echo $search !== ''
                ? 'Search Results'
                : (
                    $categoryId > 0
                        ? 'Category Medicines'
                        : 'Available Medicines'
                );
            ?>

        </h2>


        <span class="result-count">

            <?php echo count($medicines); ?>

            result<?php echo count($medicines) !== 1 ? 's' : ''; ?>

        </span>

    </div>


    <?php if (!$medicines): ?>

        <div class="empty">

            <i
                class="fa-solid fa-magnifying-glass fa-2x mb-3"
                style="color:var(--mh)"
            ></i>

            <h4>
                No medicines found
            </h4>

            <p>
                Try another medicine name, category or filter.
            </p>

            <a
                href="search_medicine.php"
                class="btn"
                style="background:var(--mh);color:#fff"
            >
                View All Medicines
            </a>

        </div>


    <?php else: ?>

        <div class="medicine-grid">


            <?php foreach ($medicines as $medicine): ?>

                <?php

                $stock = (int) $medicine['stock_quantity'];

                ?>


                <article class="medicine-card-real">


                    <div class="med-img">

                        <img
                            src="<?php echo htmlspecialchars(medicineImage($medicine)); ?>"
                            alt="<?php echo htmlspecialchars($medicine['medicine_name']); ?>"
                        >

                    </div>


                    <div class="med-body">


                        <span class="med-cat">

                            <?php echo htmlspecialchars(
                                $medicine['category_name'] ?? 'General'
                            ); ?>

                        </span>


                        <h3 class="med-name">

                            <?php echo htmlspecialchars(
                                $medicine['medicine_name']
                            ); ?>

                        </h3>


                        <div class="med-dose">

                            <?php echo htmlspecialchars(
                                $medicine['dosage'] ?? 'Standard dosage'
                            ); ?>

                        </div>


                        <div class="med-price">

                            LKR

                            <?php echo number_format(
                                (float) $medicine['price'],
                                2
                            ); ?>

                        </div>


                        <div
                            class="<?php echo $stock <= (int) $medicine['min_stock_level']
                                ? 'stock-low'
                                : 'stock-good'; ?>"
                        >

                            <i class="fa-solid fa-circle-check me-1"></i>

                            <?php echo $stock <= (int) $medicine['min_stock_level']
                                ? "Only {$stock} left"
                                : "In stock ({$stock})"; ?>

                        </div>


                        <?php if (
                            (int) $medicine['requires_prescription'] === 1
                        ): ?>

                            <span class="rx-badge">

                                <i class="fa-solid fa-file-prescription me-1"></i>

                                Prescription required

                            </span>

                        <?php endif; ?>


                        <div class="med-actions">

                            <a
                                class="details"
                                href="medicine_details.php?id=<?php echo (int) $medicine['medicine_id']; ?>"
                            >
                                View Details
                            </a>


                            <a
                                class="add"
                                href="Add_to_cart.php?id=<?php echo (int) $medicine['medicine_id']; ?>"
                            >
                                Add to Cart
                            </a>

                        </div>


                    </div>

                </article>

            <?php endforeach; ?>


        </div>

    <?php endif; ?>


</main>


<footer class="footer-real">

    © 2026 MediHub Pharmacy · Your Health, Our Priority

</footer>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>