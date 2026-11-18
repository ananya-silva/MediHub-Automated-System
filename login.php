<?php
require_once __DIR__ . "/config.php";
require_once __DIR__ . '/db.php';
require_once __DIR__ . "/functions.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif ($password === "") {
        $error = "Password is required.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } else {
        $stmt = $conn->prepare("SELECT user_id, full_name, email, 
        password_hash, role, status FROM users WHERE email = ? LIMIT 1");
        if (!$stmt) {
            $error = "Database error. Please try again.";
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$user || !password_verify($password, $user["password_hash"])) {
                $error = "Invalid email or password.";
            } elseif (isset($user["status"]) && strcasecmp((string)$user["status"], "Active") !== 0) {
                $error = "Your account is inactive. Please contact the administrator.";
            } else {
                session_regenerate_id(true);
                $_SESSION["user_id"] = (int)$user["user_id"];
                $_SESSION["name"] = $user["full_name"];
                $_SESSION["email"] = $user["email"];
                $_SESSION["role"] = $user["role"];

                /* =============================================
                   PATIENT LOGIN EMAIL ALERT
                   Send an email every time a patient logs in.
                ============================================= */

                if ($user["role"] === "Patient") {
                    sendPatientLoginAlert(
                        $user["full_name"],
                        $user["email"]
                    );
                }

                if ($user["role"] === "Admin") {
                    header("Location: admin_dashboard.php");
                } elseif ($user["role"] === "Pharmacist") {
                    header("Location: pharmacist_dashboard.php");
                } else {
                    header("Location: Home.php");
                }
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - MediHub</title>

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

    <!-- Login CSS -->
    <link rel="stylesheet" href="login.css">
</head>


<body>
<?php if ($error !== ""): ?><div class="alert alert-danger text-center m-3"><?php echo clean($error); ?></div><?php endif; ?>


<!-- =========================================
     HEADER
========================================= -->

<header class="main-header">

    <div class="header-top">


        <!-- Logo -->

        <div class="logo-area">

            <img
                src="images/logo.jpeg"
                alt="MediHub Logo"
            >

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
                href="#"
                class="cart-icon"
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



<!-- =========================================
     LOGIN SECTION
========================================= -->

<main class="login-section">

    <div class="container">

        <div class="login-wrapper">


            <h2>
                Login
            </h2>


            <p class="welcome-text">
                Welcome Back! Sign in to your account
            </p>



            <!-- =================================
                 LOGIN FORM
            ================================= -->

            <form
                action="login.php"
                method="POST"
                onsubmit="return validateLogin()"
            >


                <!-- Email -->

                <div class="form-row">

                    <label for="email">
                        Email :
                    </label>


                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                    >


                    <small
                        id="emailError"
                        class="text-danger"
                    ></small>

                </div>



                <!-- Password -->

                <div class="form-row">

                    <label for="password">
                        Password :
                    </label>


                    <div class="password-box">


                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                        >


                        <i
                            class="fa-regular fa-eye-slash password-icon"
                            onclick="togglePassword('password', this)"
                        ></i>


                    </div>


                    <small
                        id="passwordError"
                        class="text-danger"
                    ></small>

                </div>



                <!-- Forgot Password -->

                <div class="forgot-password">

                    <a href="#">
                        Forgot password?
                    </a>

                </div>



                <!-- Login Button -->

                <button
                    type="submit"
                    class="submit-btn"
                >
                    Login
                </button>



                <!-- Register Link -->

                <p class="account-text">

                    Don't have an account?

                    <a href="register.php">
                        Register Here
                    </a>

                </p>


            </form>


        </div>

    </div>

</main>



<!-- =========================================
     FOOTER
========================================= -->

<footer class="footer">


    <div class="footer-content">


        <!-- Address -->

        <div class="footer-column address">


            <img
                src="images/logo.jpeg"
                alt="MediHub Logo"
            >


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



        <!-- Social Media -->

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



<!-- =========================================
     JAVASCRIPT
========================================= -->

<script src="script.js"></script>


</body>

</html>
