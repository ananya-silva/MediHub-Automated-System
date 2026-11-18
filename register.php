<?php

require_once __DIR__ . "/config.php";
require_once __DIR__ . '/db.php';
require_once __DIR__ . "/functions.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* ================= GET FORM DATA ================= */

    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";
    $phone = trim($_POST["phone"] ?? "");
    $address = trim($_POST["address"] ?? "");


    /* ================= VALIDATION ================= */

    if (
        $full_name === "" ||
        $email === "" ||
        $username === "" ||
        $password === "" ||
        $confirm_password === "" ||
        $phone === "" ||
        $address === ""
    ) {

        $message = "Please fill all required fields.";

    } elseif (strlen($full_name) < 2) {

        $message = "Full name must contain at least 2 characters.";

    } elseif (!preg_match("/^[A-Za-z ]+$/", $full_name)) {

        $message = "Full name can contain letters and spaces only.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";

    } elseif (strlen($username) < 3) {

        $message = "Username must contain at least 3 characters.";

    } elseif (!preg_match("/^[A-Za-z0-9_]+$/", $username)) {

        $message = "Username can contain letters, numbers and underscore only.";

    } elseif (strlen($password) < 8) {

        $message = "Password must contain at least 8 characters.";

    } elseif ($password !== $confirm_password) {

        $message = "Passwords do not match.";

    } elseif (!preg_match("/^[0-9]{10}$/", $phone)) {

        $message = "Phone number must contain exactly 10 digits.";

    } else {


        /* ================= CHECK EMAIL / USERNAME ================= */

        $check = $conn->prepare(
            "SELECT user_id
             FROM users
             WHERE email = ? OR username = ?
             LIMIT 1"
        );

        if (!$check) {
            die("Database error: " . $conn->error);
        }

        $check->bind_param(
            "ss",
            $email,
            $username
        );

        $check->execute();

        $result = $check->get_result();


        if ($result->num_rows > 0) {

            $message = "Email or username already registered.";

            $check->close();

        } else {

            $check->close();


            /* ================= HASH PASSWORD ================= */

            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );


            /* ================= INSERT USER ================= */

            $stmt = $conn->prepare(
                "INSERT INTO users
                (
                    full_name,
                    email,
                    username,
                    password_hash,
                    phone,
                    address,
                    role
                )
                VALUES (?, ?, ?, ?, ?, ?, 'Patient')"
            );

            if (!$stmt) {
                die("Database error: " . $conn->error);
            }

            $stmt->bind_param(
                "ssssss",
                $full_name,
                $email,
                $username,
                $hashedPassword,
                $phone,
                $address
            );


            /* ================= SAVE USER ================= */

            if ($stmt->execute()) {

                $stmt->close();

                // All files are in the same Medi_Hub folder
                redirect("login.php");

            } else {

                $message = "Registration failed. Please try again.";

                $stmt->close();
            }
        }
    }
}

?>


<?php if ($message !== ""): ?>

    <p class="error-message">
        <?php echo clean($message); ?>
    </p>

<?php endif; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Register - MediHub</title>

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

    <!-- Register CSS -->
    <link rel="stylesheet" href="register.css">
</head>

<body>

<!-- =========================================
     HEADER
========================================= -->

<header class="main-header">

    <div class="header-top">

        <!-- Logo -->
        <div class="logo-area">
            <img src="images/logo.jpeg" alt="MediHub Logo">
        </div>

        <!-- Website Name -->
        <div class="brand-name">
            <h1>MediHub</h1>
        </div>

        <!-- Header Actions -->
        <div class="header-actions">

            <a href="login.php" class="login-btn">
                Login/Register
            </a>

            <a href="#" class="cart-icon">
                <i class="fa-solid fa-cart-shopping"></i>
            </a>

        </div>

    </div>


    <!-- Navigation -->
    <nav class="navigation">

        <a href="Home.php">Home</a>

        <span>|</span>

        <a href="about.php">About Us</a>

        <span>|</span>

        <a href="search_medicine.php">Search Medicine</a>

        <span>|</span>

        <a href="contact.php">Contact Us</a>

    </nav>

</header>


<!-- =========================================
     REGISTER SECTION
========================================= -->

<main class="register-section">

    <div class="container">

        <div class="register-wrapper">

            <h2>Register</h2>

            <p class="welcome-text">
                Create an Account
            </p>


            <!-- REGISTER FORM -->

            <form
                action="register.php"
                method="POST"
                onsubmit="return validateRegister()"
            >


                <!-- Full Name -->

                <div class="form-row">

                    <label for="fullname">
                        Full name :
                    </label>

                    <input
                        type="text"
                        id="fullname"
                        name="full_name"
                        class="form-control"
                    >

                    <small
                        id="fullNameError"
                        class="text-danger"
                    ></small>

                </div>


                <!-- Username -->

                <div class="form-row">

                    <label for="username">
                        Username :
                    </label>

                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control"
                    >

                    <small
                        id="usernameError"
                        class="text-danger"
                    ></small>

                </div>


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
                        id="registerEmailError"
                        class="text-danger"
                    ></small>

                </div>


                <!-- Phone Number -->

                <div class="form-row">

                    <label for="phone">
                        Phone number :
                    </label>

                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        class="form-control"
                    >

                    <small
                        id="phoneError"
                        class="text-danger"
                    ></small>

                </div>


                <!-- Address -->

                <div class="form-row">

                    <label for="address">
                        Address :
                    </label>

                    <textarea
                        id="address"
                        name="address"
                        class="form-control"
                        rows="3"
                    ></textarea>

                    <small
                        id="addressError"
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
                        id="registerPasswordError"
                        class="text-danger"
                    ></small>

                </div>


                <!-- Confirm Password -->

                <div class="form-row">

                    <label for="confirmPassword">
                        Confirm Password :
                    </label>

                    <div class="password-box">

                        <input
                            type="password"
                            id="confirmPassword"
                            name="confirm_password"
                            class="form-control"
                        >

                        <i
                            class="fa-regular fa-eye-slash password-icon"
                            onclick="togglePassword('confirmPassword', this)"
                        ></i>

                    </div>

                    <small
                        id="confirmPasswordError"
                        class="text-danger"
                    ></small>

                </div>


                <!-- Register Button -->

                <button
                    type="submit"
                    class="submit-btn"
                >
                    Register
                </button>


                <!-- Login Link -->

                <p class="account-text">

                    Already have an account?

                    <a href="login.php">
                        Login Here
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
