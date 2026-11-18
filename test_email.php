<?php

require_once __DIR__ . '/functions.php';

$testEmail = 'abikrishna840@gmail.com';

$result = sendMediHubEmail(
    $testEmail,
    'MediHub Email Test',
    "Hello,\n\nThis is a test email from MediHub Pharmacy.\n\nThank you."
);

if ($result) {
    echo "SUCCESS: Email sent successfully.";
} else {
    echo "FAILED: Email could not be sent.";
}