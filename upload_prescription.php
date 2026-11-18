<?php

require_once __DIR__ . "/config.php";
require_once __DIR__ . '/db.php';
require_once __DIR__ . "/functions.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================
   LOGIN CHECK
========================================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}


/* =========================================
   PATIENT CHECK
========================================= */

$user_id = (int) $_SESSION["user_id"];
$userRole = $_SESSION["role"] ?? "";

if ($userRole !== "Patient") {
    header("Location: Home.php");
    exit;
}


/* =========================================
   USER DETAILS
========================================= */

$userName = $_SESSION["name"] ?? "";

$stmt = $conn->prepare(
    "SELECT full_name, email
     FROM users
     WHERE user_id = ?
     LIMIT 1"
);

if ($stmt) {

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $stmt->close();

    if ($user) {
        $userName = $user["full_name"];
    }
}


/* =========================================
   MESSAGE
========================================= */

$message = "";
$messageType = "";


/* =========================================
   UPLOAD PRESCRIPTION
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    /* =========================================
       CHECK FILE
    ========================================= */

    if (!isset($_FILES["prescription"])) {

        $message = "Please select a prescription file.";
        $messageType = "error";

    } else {

        $file = $_FILES["prescription"];


        /* =========================================
           UPLOAD ERROR CHECK
        ========================================= */

        if ($file["error"] !== UPLOAD_ERR_OK) {

            $message = "There was an error uploading the file.";
            $messageType = "error";

        } else {

            $originalName = $file["name"];
            $fileSize = $file["size"];
            $tmpName = $file["tmp_name"];


            /* =========================================
               FILE EXTENSION
            ========================================= */

            $extension = strtolower(
                pathinfo($originalName, PATHINFO_EXTENSION)
            );


            /* =========================================
               ALLOWED FILE TYPES
            ========================================= */

            $allowedExtensions = [
                "jpg",
                "jpeg",
                "png",
                "pdf"
            ];


            if (!in_array($extension, $allowedExtensions, true)) {

                $message =
                    "Invalid file type. Please upload JPG, JPEG, PNG or PDF.";

                $messageType = "error";


            /* =========================================
               FILE SIZE
               Maximum 5MB
            ========================================= */

            } elseif ($fileSize > 5 * 1024 * 1024) {

                $message =
                    "File size must be less than 5MB.";

                $messageType = "error";


            } else {


                /* =========================================
                   UPLOAD DIRECTORY
                ========================================= */

                $uploadDirectory =
                    __DIR__ . "/uploads/prescriptions/";


                if (!is_dir($uploadDirectory)) {

                    if (!mkdir($uploadDirectory, 0777, true)) {

                        $message =
                            "Unable to create upload directory.";

                        $messageType = "error";
                    }
                }


                /* =========================================
                   CONTINUE ONLY IF DIRECTORY EXISTS
                ========================================= */

                if (
                    $messageType !== "error" &&
                    is_dir($uploadDirectory)
                ) {


                    /* =========================================
                       CREATE UNIQUE FILE NAME
                    ========================================= */

                    $newFileName =
                        "prescription_" .
                        $user_id . "_" .
                        time() . "_" .
                        uniqid() .
                        "." .
                        $extension;


                    $filePath =
                        $uploadDirectory .
                        $newFileName;


                    /* =========================================
                       MOVE UPLOADED FILE
                    ========================================= */

                    if (
                        move_uploaded_file(
                            $tmpName,
                            $filePath
                        )
                    ) {


                        /* =========================================
                           DATABASE FILE PATH
                        ========================================= */

                        $databasePath =
                            "uploads/prescriptions/" .
                            $newFileName;


                        /* =========================================
                           INSERT INTO DATABASE

                           Table columns:

                           prescription_id
                           patient_id
                           file_path
                           status
                           reviewed_by
                           upload_date
                        ========================================= */

                        $stmt = $conn->prepare(
                            "INSERT INTO prescriptions
                            (
                                patient_id,
                                file_path,
                                status
                            )
                            VALUES
                            (
                                ?,
                                ?,
                                'Pending'
                            )"
                        );


                        /* =========================================
                           PREPARE CHECK
                        ========================================= */

                        if ($stmt) {

                            $stmt->bind_param(
                                "is",
                                $user_id,
                                $databasePath
                            );


                            /* =========================================
                               EXECUTE
                            ========================================= */

                            if ($stmt->execute()) {

                                $message =
                                    "Prescription uploaded successfully. " .
                                    "Our pharmacist will review it.";

                                $messageType = "success";


                            } else {

                                /* Delete file if database fails */

                                if (file_exists($filePath)) {
                                    unlink($filePath);
                                }

                                $message =
                                    "Unable to save prescription information.";

                                $messageType = "error";
                            }


                            $stmt->close();


                        } else {

                            /* Delete file if prepare fails */

                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }

                            $message =
                                "Database error occurred.";

                            $messageType = "error";
                        }


                    } else {

                        $message =
                            "Unable to upload the prescription.";

                        $messageType = "error";
                    }
                }
            }
        }
    }
}


/* =========================================
   PAGE DATA
========================================= */

$pageTitle = "MediHub - Upload Prescription";

$homeLink = "Home.php";

$dashboardLink = "dashboard.php";

$cartLink = "cart.php";

$aboutLink = "about.php";

$searchMedicineLink = "search_medicine.php";

$contactLink = "contact.php";

$logoutLink = "logout.php";

?>
<!doctype html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>MediHub - Upload Prescription</title>

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

    <!-- Upload Prescription CSS -->
    <link rel="stylesheet" href="UploadPrescriptionStyle.css">

</head>


<body>


<!-- =====================================================
     HEADER
===================================================== -->

<header class="main-header">

    <div class="header-top">

        <!-- Logo -->
        <div class="logo-area">

            <a href="Home.php">

                <img
                    src="images/logo.jpeg"
                    alt="MediHub Logo"
                >

            </a>

        </div>


        <!-- Website Name -->
        <div class="brand-name">

            <h1>MediHub</h1>

        </div>


        <!-- Header Actions -->
        <div class="header-actions">

            <a
                href="login.php"
                class="login-btn"
            >
                Login/Register
            </a>


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
     PAGE TITLE
===================================================== -->

<section class="page-header">

    <h2>
        Upload Prescription
    </h2>

    <p>
        Upload your prescription and let our pharmacy team review your order.
    </p>

</section>



<!-- =====================================================
     UPLOAD PRESCRIPTION SECTION
===================================================== -->

<section class="upload-section">

    <div class="upload-container">


        <!-- Upload Icon -->

        <div class="upload-icon">

            <i class="fa-solid fa-file-prescription"></i>

        </div>


        <h2>
            Prescription Details
        </h2>
<?php if ($message !== ""): ?>
    <div class="alert <?php echo $messageType === "success" ? "alert-success" : "alert-danger"; ?> mt-3" role="alert">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>


        <p class="upload-description">
            Please provide the required details and upload a valid prescription.
        </p>


        <!-- =================================================
             UPLOAD FORM
        ================================================== -->

        <form
    action="upload_prescription.php"
    method="POST"
    enctype="multipart/form-data"
>


            <!-- Patient Name -->

            <div class="form-group">

                <label for="patientName">
                    Patient Name
                </label>

                <input
                    type="text"
                    id="patientName"
                    name="patient_name"
                    placeholder="Enter patient name"
                    required
                >

            </div>



            <!-- Contact Number -->

            <div class="form-group">

                <label for="phone">
                    Contact Number
                </label>

                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    placeholder="Enter contact number"
                    required
                >

            </div>



            <!-- Prescription File -->

            <div class="form-group">

                <label for="prescription">
                    Prescription File
                </label>

                <div class="file-upload">

                    <input
                        type="file"
                        id="prescription"
                        name="prescription"
                        accept=".jpg,.jpeg,.png,.pdf"
                        required
                    >

                    <label
                        for="prescription"
                        class="file-label"
                    >

                        <i class="fa-solid fa-cloud-arrow-up"></i>

                        <span>
                            Choose Prescription
                        </span>

                    </label>

                </div>

                <small>
                    Accepted formats: JPG, JPEG, PNG, PDF
                </small>

            </div>



            <!-- Additional Notes -->

            <div class="form-group">

                <label for="notes">
                    Additional Notes
                </label>

                <textarea
                    id="notes"
                    name="notes"
                    rows="4"
                    placeholder="Enter any additional information..."
                ></textarea>

            </div>



            <!-- Submit -->

            <button
                type="submit"
                class="submit-btn"
            >

                <i class="fa-solid fa-upload"></i>

                Submit Prescription

            </button>


        </form>

    </div>

</section>



<!-- =====================================================
     INFORMATION SECTION
===================================================== -->

<section class="upload-info">

    <div class="container">

        <h2>
            Prescription Upload Guidelines
        </h2>


        <div class="guideline-container">


            <div class="guideline-card">

                <i class="fa-solid fa-image"></i>

                <h3>
                    Clear Prescription
                </h3>

                <p>
                    Make sure the uploaded prescription is clear and readable.
                </p>

            </div>



            <div class="guideline-card">

                <i class="fa-solid fa-file-pdf"></i>

                <h3>
                    Supported Files
                </h3>

                <p>
                    Upload JPG, JPEG, PNG or PDF prescription files.
                </p>

            </div>



            <div class="guideline-card">

                <i class="fa-solid fa-user-doctor"></i>

                <h3>
                    Pharmacist Review
                </h3>

                <p>
                    Our pharmacist will review the prescription before processing.
                </p>

            </div>


        </div>

    </div>

</section>



<!-- =====================================================
     FOOTER
===================================================== -->

<footer class="footer">

    <div class="footer-content">


        <!-- Address -->

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



        <!-- Contact -->

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



        <!-- Social -->

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


    <!-- Copyright -->

    <div class="copyright">

        Copyright © 2026 MediHub Pharmacy.
        All Rights Reserved by: CSE5015

    </div>

</footer>


</body>

</html>
