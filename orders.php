<?php

require_once __DIR__ . "/config.php";
require_once __DIR__ . '/db.php';

if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "Patient"
) {
    header("Location: login.php");
    exit;
}

$patient_id = (int) $_SESSION["user_id"];
$patient_name = $_SESSION["name"] ?? "Patient";


$sql = "
    SELECT
        o.order_id,
        o.total_amount,
        o.status,
        o.order_date,
        GROUP_CONCAT(
            CONCAT(m.medicine_name, ' x', oi.quantity)
            SEPARATOR ', '
        ) AS items
    FROM orders o
    LEFT JOIN order_item oi
        ON o.order_id = oi.order_id
    LEFT JOIN medicines m
        ON oi.medicine_id = m.medicine_id
    WHERE o.patient_id = ?
    GROUP BY
        o.order_id,
        o.total_amount,
        o.status,
        o.order_date
    ORDER BY o.order_date DESC
";


$stmt = $conn->prepare($sql);

if (!$stmt) {
    die(
        "Unable to load order tracking. Please check the database tables " .
        "(orders, order_item and medicines).<br><small>" .
        htmlspecialchars($conn->error) .
        "</small>"
    );
}

$stmt->bind_param("i", $patient_id);
$stmt->execute();

$orders = $stmt->get_result();

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
        MediHub - Order Tracking
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="HomeStyle.css"
    >

    <style>
        body {
            font-family: Georgia, serif;
            background: #f5f7f6;
            color: #222;
        }

        .tracking-wrap {
            max-width: 1050px;
            margin: 50px auto;
            padding: 0 20px;
        }

        .tracking-card {
            background: #fff;
            border-radius: 14px;
            padding: 28px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, .08);
            margin-bottom: 20px;
        }

        .topbar {
            background: #299d82;
            color: #fff;
            padding: 18px 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .topbar a {
            color: #fff;
            text-decoration: none;
        }

        .logout {
            background: #dc3545;
            padding: 8px 15px;
            border-radius: 6px;
        }

        .status {
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 20px;
            background: #e8f5ef;
            color: #217d63;
        }

        .empty {
            text-align: center;
            padding: 50px;
        }
    </style>

</head>

<body>

    <header class="topbar">

        <strong style="font-size:28px">
            MediHub
        </strong>

        <div>

            Welcome,
            <?php echo htmlspecialchars($patient_name); ?>

            &nbsp; |

            <a href="Home.php">
                Home
            </a>

            &nbsp; |

            <a
                class="logout"
                href="logout.php"
            >
                Logout
            </a>

        </div>

    </header>


    <div class="tracking-wrap">

        <h2 class="mb-4">
            Order Tracking
        </h2>


        <?php if ($orders->num_rows === 0): ?>

            <div class="tracking-card empty">

                <h4>
                    No orders found
                </h4>

                <p>
                    You have not placed any medicine orders yet.
                </p>

                <a
                    href="search_medicine.php"
                    class="btn btn-success"
                >
                    Shop Medicines
                </a>

            </div>

        <?php else: ?>


            <?php while ($order = $orders->fetch_assoc()): ?>

                <div class="tracking-card">

                    <div
                        class="d-flex justify-content-between align-items-center flex-wrap gap-2"
                    >

                        <h4 class="mb-0">

                            Order #
                            <?php echo (int) $order["order_id"]; ?>

                        </h4>


                        <span class="status">

                            <?php
                            echo htmlspecialchars(
                                $order["status"]
                            );
                            ?>

                        </span>

                    </div>


                    <hr>


                    <p>

                        <strong>
                            Items:
                        </strong>

                        <?php
                        echo htmlspecialchars(
                            $order["items"] ?? "Order items"
                        );
                        ?>

                    </p>


                    <p>

                        <strong>
                            Total:
                        </strong>

                        LKR
                        <?php
                        echo number_format(
                            (float) $order["total_amount"],
                            2
                        );
                        ?>

                    </p>


                    <p>

                        <strong>
                            Order Date:
                        </strong>

                        <?php
                        echo htmlspecialchars(
                            $order["order_date"]
                        );
                        ?>

                    </p>


                    <div class="mt-3">

                        <strong>
                            Tracking:
                        </strong>


                        <?php

                        $steps = [
                            "Pending",
                            "Processing",
                            "Approved",
                            "Ready",
                            "Delivered"
                        ];

                        $current = $order["status"];

                        $currentIndex = array_search(
                            $current,
                            $steps,
                            true
                        );

                        if ($currentIndex === false) {
                            $currentIndex = 0;
                        }

                        ?>


                        <div class="mt-2">

                            <?php foreach ($steps as $i => $step): ?>

                                <span
                                    class="badge
                                    <?php
                                    echo $i <= $currentIndex
                                        ? 'bg-success'
                                        : 'bg-secondary';
                                    ?>
                                    me-1 mb-1"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $step === 'Ready'
                                            ? 'Ready to Pickup'
                                            : $step
                                    );
                                    ?>

                                </span>

                            <?php endforeach; ?>

                        </div>

                    </div>

                </div>

            <?php endwhile; ?>


        <?php endif; ?>

    </div>

</body>

</html>