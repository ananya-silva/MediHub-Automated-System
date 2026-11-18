<?php

require_once __DIR__ . "/config.php";
require_once __DIR__ . "/db.php";

/* =====================================================
   CHECK PATIENT LOGIN
===================================================== */

if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "Patient"
) {
    header("Location: login.php");
    exit;
}

$patient_id = (int) $_SESSION["user_id"];

$patient_name =
    $_SESSION["name"] ??
    $_SESSION["full_name"] ??
    "Patient";

$patient_email =
    $_SESSION["email"] ??
    "";


/* =====================================================
   VARIABLES
===================================================== */

$success = false;
$error = "";

$old_subject = "";
$old_message = "";


/* =====================================================
   SUBMIT INQUIRY
===================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $old_subject = trim($_POST["subject"] ?? "");
    $old_message = trim($_POST["message"] ?? "");


    /* =================================================
       VALIDATION
    ================================================= */

    if ($old_subject === "" || $old_message === "") {

        $error = "Please fill all required fields.";

    } elseif (mb_strlen($old_subject) > 150) {

        $error = "Subject is too long.";

    } elseif (mb_strlen($old_message) > 5000) {

        $error = "Message is too long.";

    } else {

        try {

            /* =============================================
               STEP 1
               SAVE INQUIRY TO DATABASE
            ============================================= */

            $stmt = $conn->prepare("
                INSERT INTO inquiries
                (
                    patient_id,
                    subject,
                    message
                )
                VALUES
                (
                    ?,
                    ?,
                    ?
                )
            ");

            if (!$stmt) {

                throw new Exception(
                    "Database error: " . $conn->error
                );
            }

            $stmt->bind_param(
                "iss",
                $patient_id,
                $old_subject,
                $old_message
            );

            if (!$stmt->execute()) {

                throw new Exception(
                    "Inquiry could not be saved."
                );
            }

            $inquiry_id = (int) $conn->insert_id;

            $stmt->close();


            /* =============================================
               STEP 2
               GET ADMIN + PHARMACIST EMAILS
            ============================================= */

            $emails = [];

            $email_stmt = $conn->prepare("
                SELECT email
                FROM users
                WHERE role IN ('Admin', 'Pharmacist')
                  AND status = 'Active'
                  AND email IS NOT NULL
                  AND email <> ''
            ");

            if ($email_stmt) {

                $email_stmt->execute();

                $result = $email_stmt->get_result();

                while ($row = $result->fetch_assoc()) {

                    $email = trim($row["email"]);

                    if (
                        $email !== "" &&
                        filter_var(
                            $email,
                            FILTER_VALIDATE_EMAIL
                        )
                    ) {

                        $emails[] = $email;
                    }
                }

                $email_stmt->close();
            }


            /* =============================================
               STEP 3
               SEND EMAIL USING PHPMailer + GMAIL SMTP
            ============================================= */

            if (!empty($emails)) {

                /*
                 * PHPMailer files
                 */

                require_once __DIR__ .
                    "/PHPMailer/src/Exception.php";

                require_once __DIR__ .
                    "/PHPMailer/src/PHPMailer.php";

                require_once __DIR__ .
                    "/PHPMailer/src/SMTP.php";


                $mail =
                    new PHPMailer\PHPMailer\PHPMailer(true);


                /* =========================================
                   GMAIL SMTP SETTINGS
                ========================================= */

                $mail->isSMTP();

                $mail->Host = "smtp.gmail.com";

                $mail->SMTPAuth = true;

                /*
                 * CHANGE THIS
                 * Your Gmail address
                 */

                $mail->Username =
                    "YOUR_GMAIL@gmail.com";


                /*
                 * CHANGE THIS
                 * Your Gmail App Password
                 *
                 * Do NOT use your normal Gmail password.
                 */

                $mail->Password =
                    "YOUR_16_DIGIT_APP_PASSWORD";


                $mail->SMTPSecure =
                    PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;

                $mail->Port = 587;

                $mail->CharSet = "UTF-8";


                /* =========================================
                   SENDER
                ========================================= */

                $mail->setFrom(
                    "YOUR_GMAIL@gmail.com",
                    "MediHub Pharmacy"
                );


                /* =========================================
                   ADMIN + PHARMACIST EMAILS
                ========================================= */

                foreach (array_unique($emails) as $email) {

                    $mail->addAddress($email);

                }


                /* =========================================
                   REPLY TO PATIENT
                ========================================= */

                if (
                    $patient_email !== "" &&
                    filter_var(
                        $patient_email,
                        FILTER_VALIDATE_EMAIL
                    )
                ) {

                    $mail->addReplyTo(
                        $patient_email,
                        $patient_name
                    );

                }


                /* =========================================
                   EMAIL SUBJECT
                ========================================= */

                $mail->Subject =
                    "MediHub - New Patient Inquiry";


                /* =========================================
                   HTML EMAIL
                ========================================= */

                $mail->isHTML(true);

                $safe_name =
                    htmlspecialchars(
                        $patient_name,
                        ENT_QUOTES,
                        "UTF-8"
                    );

                $safe_email =
                    htmlspecialchars(
                        $patient_email,
                        ENT_QUOTES,
                        "UTF-8"
                    );

                $safe_subject =
                    htmlspecialchars(
                        $old_subject,
                        ENT_QUOTES,
                        "UTF-8"
                    );

                $safe_message =
                    nl2br(
                        htmlspecialchars(
                            $old_message,
                            ENT_QUOTES,
                            "UTF-8"
                        )
                    );


                $mail->Body = "

                <div style='
                    font-family: Arial, sans-serif;
                    max-width: 650px;
                    margin: auto;
                    padding: 25px;
                    background: #ffffff;
                    border: 1px solid #dddddd;
                    border-radius: 10px;
                '>

                    <h2 style='
                        color: #299d82;
                        margin-bottom: 20px;
                    '>
                        MediHub Patient Inquiry
                    </h2>

                    <p>
                        A new inquiry has been submitted
                        by a patient.
                    </p>

                    <hr>

                    <p>
                        <strong>Inquiry ID:</strong>
                        #{$inquiry_id}
                    </p>

                    <p>
                        <strong>Patient Name:</strong>
                        {$safe_name}
                    </p>

                    <p>
                        <strong>Patient Email:</strong>
                        {$safe_email}
                    </p>

                    <p>
                        <strong>Subject:</strong>
                        {$safe_subject}
                    </p>

                    <p>
                        <strong>Message:</strong>
                    </p>

                    <div style='
                        background: #f2f4f3;
                        padding: 15px;
                        border-radius: 8px;
                        line-height: 1.6;
                    '>
                        {$safe_message}
                    </div>

                    <br>

                    <p>
                        Please login to the MediHub system
                        to review and respond to this inquiry.
                    </p>

                    <hr>

                    <p style='
                        color: #777777;
                        font-size: 13px;
                    '>
                        MediHub Pharmacy System
                    </p>

                </div>

                ";


                /* =========================================
                   PLAIN TEXT EMAIL
                ========================================= */

                $mail->AltBody =
                    "MediHub Patient Inquiry\n\n" .
                    "Inquiry ID: #" . $inquiry_id . "\n" .
                    "Patient Name: " . $patient_name . "\n" .
                    "Patient Email: " . $patient_email . "\n\n" .
                    "Subject: " . $old_subject . "\n\n" .
                    "Message:\n" . $old_message . "\n\n" .
                    "Please login to the MediHub system " .
                    "to review and respond to this inquiry.";


                /* =========================================
                   SEND
                ========================================= */

                $mail->send();
            }


            /* =============================================
               STEP 4
               SUCCESS
            ============================================= */

            $success = true;

            $old_subject = "";
            $old_message = "";


        } catch (Throwable $e) {

            $error =
                "Inquiry was saved, but the email could not be sent. " .
                $e->getMessage();
        }
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

<title>MediHub - Contact Us</title>


<!-- Bootstrap -->

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<!-- Font Awesome -->

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
>


<!-- Contact CSS -->

<link
    rel="stylesheet"
    href="ContactStyle.css"
>


</head>

<body>

<!-- =====================================================
     HEADER
===================================================== -->

<header class="main-header">

<div class="header-top">


    <!-- LOGO -->

    <div class="logo-area">

        <a href="Home.php">

            <img
                src="images/logo.jpeg"
                alt="MediHub Logo"
            >

        </a>

    </div>


    <!-- BRAND -->

    <div class="brand-name">

        <h1>
            MediHub
        </h1>

    </div>


    <!-- HEADER ACTIONS -->

    <div class="header-actions">


        <?php if (isset($_SESSION["user_id"])): ?>

            <!-- LOGOUT -->

            <a
                href="logout.php"
                class="login-btn"
            >

                <i
                    class="fa-solid fa-right-from-bracket"
                ></i>

                Logout

            </a>


        <?php else: ?>

            <!-- LOGIN -->

            <a
                href="login.php"
                class="login-btn"
            >

                <i
                    class="fa-solid fa-right-to-bracket"
                ></i>

                Login/Register

            </a>

        <?php endif; ?>


        <!-- CART -->

        <a
            href="cart.php"
            class="cart-icon"
            title="Shopping Cart"
        >

            <i
                class="fa-solid fa-cart-shopping"
            ></i>

        </a>

    </div>

</div>


<!-- NAVIGATION -->

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

<!-- =====================================================
     HERO
===================================================== -->

<section class="contact-hero">

<div class="contact-hero-overlay">

    <h2>
        Contact Us
    </h2>

</div>


</section>

<!-- =====================================================
     CONTACT SECTION
===================================================== -->

<section class="contact-section">


<div class="contact-container">


    <!-- =================================================
         CONTACT INFORMATION
    ================================================== -->

    <div class="contact-info">

        <h2>
            MediHub
        </h2>


        <!-- ADDRESS -->

        <div class="contact-item">

            <div class="contact-icon">

                <i
                    class="fa-solid fa-location-dot"
                ></i>

            </div>

            <div class="contact-text">

                <strong>
                    Address :
                </strong>

                <span>
                    No.78, Baseline Road,
                    Colombo 09, Sri Lanka
                </span>

            </div>

        </div>


        <!-- PHONE -->

        <div class="contact-item">

            <div class="contact-icon">

                <i
                    class="fa-solid fa-phone"
                ></i>

            </div>

            <div class="contact-text">

                <strong>
                    Phone :
                </strong>

                <span>
                    +94 11 234 5678
                </span>

            </div>

        </div>


        <!-- EMAIL -->

        <div class="contact-item">

            <div class="contact-icon">

                <i
                    class="fa-solid fa-envelope"
                ></i>

            </div>

            <div class="contact-text">

                <strong>
                    Email :
                </strong>

                <span>
                    info@medihub.lk
                </span>

            </div>

        </div>

    </div>



    <!-- =================================================
         INQUIRY FORM
    ================================================== -->

    <div class="inquiry-form">

        <h2>
            Inquiry Form
        </h2>


        <!-- SUCCESS MESSAGE -->

        <?php if ($success): ?>

            <div
                class="alert alert-success"
                role="alert"
            >

                <i
                    class="fa-solid fa-circle-check"
                ></i>

                <strong>
                    Inquiry submitted successfully!
                </strong>

                <br>

                Your inquiry has been sent to
                the MediHub Admin and Pharmacist.

            </div>

        <?php endif; ?>


        <!-- ERROR MESSAGE -->

        <?php if ($error): ?>

            <div
                class="alert alert-danger"
                role="alert"
            >

                <i
                    class="fa-solid fa-circle-exclamation"
                ></i>

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <!-- FORM -->

        <form
            action="contact.php"
            method="POST"
        >


            <!-- NAME -->

            <div class="form-row">

                <label for="name">
                    Name:
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?= htmlspecialchars($patient_name) ?>"
                    readonly
                >

            </div>


            <!-- EMAIL -->

            <div class="form-row">

                <label for="email">
                    Email:
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars($patient_email) ?>"
                    readonly
                >

            </div>


            <!-- SUBJECT -->

            <div class="form-row">

                <label for="subject">
                    Subject:
                </label>

                <input
                    type="text"
                    id="subject"
                    name="subject"
                    value="<?= htmlspecialchars($old_subject) ?>"
                    maxlength="150"
                    placeholder="Enter inquiry subject"
                    required
                >

            </div>


            <!-- MESSAGE -->

            <div class="form-row message-row">

                <label for="message">
                    Message:
                </label>

                <textarea
                    id="message"
                    name="message"
                    rows="5"
                    maxlength="5000"
                    placeholder="Enter your message"
                    required
                ><?= htmlspecialchars($old_message) ?></textarea>

            </div>


            <!-- SUBMIT -->

            <button
                type="submit"
                class="contact-submit"
            >

                <i
                    class="fa-solid fa-paper-plane"
                ></i>

                Submit Inquiry

            </button>

        </form>

    </div>

</div>


</section>

<!-- =====================================================
     FOOTER
===================================================== -->

<footer class="footer">


<div class="footer-content">


    <!-- ADDRESS -->

    <div class="footer-column address">

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


    <!-- CONTACT -->

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


    <!-- SOCIAL MEDIA -->

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


<!-- COPYRIGHT -->

<div class="copyright">

    Copyright © 2026 MediHub Pharmacy.
    All Rights Reserved by: CSE5015

</div>


</footer>

<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

<!-- Custom JS -->

<script src="script.js"></script>

</body>

</html>
