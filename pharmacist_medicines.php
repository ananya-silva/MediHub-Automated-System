<?php

require_once __DIR__ . '/dashboard_common.php';

requireDashboardRole('Pharmacist');

require_once __DIR__ . '/dashboard_nav.php';


renderPageStart(
    'Pharmacist',
    'pharmacist_medicines.php',
    'Manage Medicines'
);


require __DIR__ . '/medicine_management_core.php';


renderPageEnd();

?>