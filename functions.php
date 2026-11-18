<?php

/* =====================================================
   PHPMailer
===================================================== */

require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


/* =====================================================
   GENERAL FUNCTIONS
===================================================== */

function redirect($url)
{
    header("Location: " . $url);
    exit;
}


function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}


function currentUserId()
{
    return isset($_SESSION['user_id'])
        ? $_SESSION['user_id']
        : null;
}


function currentRole()
{
    return isset($_SESSION['role'])
        ? $_SESSION['role']
        : null;
}


function requireLogin()
{
    if (!isLoggedIn()) {
        http_response_code(401);
        die("Please login first.");
    }
}


function requireRole($role)
{
    requireLogin();

    if (currentRole() !== $role) {
        http_response_code(403);
        die("Access denied.");
    }
}


function clean($value)
{
    return htmlspecialchars(
        trim($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}


function isPost()
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}


/* =====================================================
   MEDIHUB EMAIL
   PHPMailer + Gmail SMTP
===================================================== */

function sendMediHubEmail(
    string $to,
    string $subject,
    string $body
): bool {

    $to = trim($to);

    /* =================================================
       VALIDATE EMAIL
    ================================================= */

    if (
        $to === '' ||
        !filter_var($to, FILTER_VALIDATE_EMAIL)
    ) {
        error_log(
            'MediHub Email Error: Invalid recipient email - ' . $to
        );

        return false;
    }


    $mail = new PHPMailer(true);


    try {

        /* =================================================
           SMTP CONFIGURATION
        ================================================= */

        $mail->isSMTP();

        $mail->Host = 'smtp.gmail.com';

        $mail->SMTPAuth = true;

        /*
         * MediHub sender Gmail
         */

        $mail->Username = 'pharmacymedihub425@gmail.com';


        /*
         * IMPORTANT:
         * Put your NEW Gmail App Password here.
         *
         * Do NOT use your normal Gmail password.
         */

        $mail->Password = 'rxmovpysxvjgodrp';


        /*
         * Gmail SMTP security
         */

        $mail->SMTPSecure =
            PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port = 587;


        /* =================================================
           EMAIL SETTINGS
        ================================================= */

        $mail->CharSet = 'UTF-8';

        $mail->setFrom(
            'pharmacymedihub425@gmail.com',
            'MediHub Pharmacy'
        );

        $mail->addAddress($to);

        $mail->isHTML(false);

        $mail->Subject = $subject;

        $mail->Body = $body;


        /* =================================================
           SEND EMAIL
        ================================================= */

        $mail->send();

        return true;


    } catch (Exception $e) {

    echo "<pre>";
    echo "EMAIL ERROR:\n";
    echo htmlspecialchars($mail->ErrorInfo);
    echo "\n\nEXCEPTION:\n";
    echo htmlspecialchars($e->getMessage());
    echo "</pre>";

    return false;
}
}


/* =====================================================
   PATIENT LOGIN EMAIL ALERT
===================================================== */

function sendPatientLoginAlert(
    string $patientName,
    string $patientEmail
): bool {

    $subject =
        'MediHub - New Login to Your Patient Dashboard';


    $body =
        "Dear {$patientName},\n\n" .

        "You have successfully logged in to your " .
        "MediHub patient dashboard.\n\n" .

        "Login time: " .
        date('d M Y, h:i A') .
        "\n\n" .

        "If you did not make this login, " .
        "please contact MediHub support immediately.\n\n" .

        "Thank you,\n" .
        "MediHub Pharmacy";


    return sendMediHubEmail(
        $patientEmail,
        $subject,
        $body
    );
}


/* =====================================================
   ORDER APPROVED / REJECTED / READY EMAIL
===================================================== */

function sendOrderStatusEmail(
    string $patientName,
    string $patientEmail,
    int $orderId,
    string $status
): bool {

    /* =================================================
       ALLOWED STATUS
    ================================================= */

    if (
        !in_array(
            $status,
            ['Approved', 'Rejected', 'Ready'],
            true
        )
    ) {
        return false;
    }


    /* =================================================
       APPROVED
    ================================================= */

    if ($status === 'Approved') {

        $subject =
            "MediHub - Order #{$orderId} Approved";

        $message =
            "Good news! Your MediHub medicine order " .
            "has been approved by our pharmacist.\n\n" .

            "Order Number: #{$orderId}\n" .
            "Status: Approved\n\n" .

            "Please log in to your MediHub account " .
            "to view your order details.\n\n";
    }


    /* =================================================
       REJECTED
    ================================================= */

    elseif ($status === 'Rejected') {

        $subject =
            "MediHub - Order #{$orderId} Rejected";

        $message =
            "Your MediHub medicine order has been " .
            "rejected by our pharmacist.\n\n" .

            "Order Number: #{$orderId}\n" .
            "Status: Rejected\n\n" .

            "Please log in to your MediHub account " .
            "to view your order details or contact " .
            "the pharmacy for more information.\n\n";
    }


    /* =================================================
       READY TO PICKUP
    ================================================= */

    else {

        $subject =
            "MediHub - Order #{$orderId} Ready for Pickup";

        $message =
            "Good news! Your MediHub medicine order " .
            "is now ready for pickup.\n\n" .

            "Order Number: #{$orderId}\n" .
            "Status: Ready to Pickup\n\n" .

            "Your medicines have been prepared and are " .
            "ready to be collected from the pharmacy.\n\n" .

            "Please visit the pharmacy to collect your order.\n\n";
    }


    /* =================================================
       COMPLETE EMAIL BODY
    ================================================= */

    $body =
        "Dear {$patientName},\n\n" .
        $message .
        "Thank you,\n" .
        "MediHub Pharmacy";


    /* =================================================
       SEND EMAIL
    ================================================= */

    return sendMediHubEmail(
        $patientEmail,
        $subject,
        $body
    );
}