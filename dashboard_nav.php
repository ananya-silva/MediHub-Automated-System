<?php

function navLinks(string $role): array
{
    if ($role === 'Admin') {
        return [
            [
                'admin_dashboard.php',
                'Dashboard',
                'gauge'
            ],
            [
                'admin_medicines.php',
                'Manage Medicines',
                'pills'
            ],
            [
                'admin_users.php',
                'Manage Users',
                'users'
            ],
            [
                'admin_pharmacists.php',
                'Manage Pharmacists',
                'user-doctor'
            ],
            [
                'admin_suppliers.php',
                'Manage Suppliers',
                'truck-medical'
            ],
            [
                'admin_stock_approvals.php',
                'Stock Approvals  Alerts',
                'clipboard-check'
            ],
            [
                'admin_analytics.php',
                'Analytics & Revenue',
                'chart-line'
            ],
            [
                'admin_reports.php',
                'Reports',
                'file-lines'
            ]
        ];
    }

    return [
        [
            'pharmacist_dashboard.php',
            'Dashboard',
            'gauge'
        ],
        [
            'pharmacist_medicines.php',
            'Manage Medicines',
            'pills'
        ],
        [
            'pharmacist_orders.php',
            'Process Orders',
            'list-check'
        ],
        [
            'pharmacist_inventory.php',
            'Inventory',
            'boxes-stacked'
        ],
        [
            'pharmacist_reports.php',
            'Reports',
            'chart-line'
        ],
        [
            'pharmacist_inquiries.php',
            'Inquiries',
            'comments'
        ],
        [
            'pharmacist_notifications.php',
            'Notifications',
            'bell'
        ]
    ];
}


function renderSidebar(string $role, string $active): void
{
    echo '<aside class="sidebar">
        <div class="brand">
            <img src="images/logo.jpeg" alt="MediHub">
            <span>MediHub</span>
        </div>

        <nav class="nav">';

    foreach (navLinks($role) as $n) {

        $is = $active === $n[0]
            ? ' active'
            : '';

        echo '<a class="' . $is . '"
                href="' . e($n[0]) . '">
                <i class="fa-solid fa-' . $n[2] . '"></i>
                <span>' . e($n[1]) . '</span>
              </a>';
    }

    echo '<a class="logout" href="logout.php">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
          </a>
        </nav>
    </aside>';
}


function renderTopbar(string $role): void
{
    echo '<header class="topbar">

        <button
            class="mobile-menu"
            onclick="document.querySelector(\'.sidebar\').classList.toggle(\'open\')"
        >
            <i class="fa-solid fa-bars"></i>
        </button>

        <h1>
            MediHub ' . e($role) . ' Panel
        </h1>

        <span class="user-chip">
            <i class="fa-solid fa-user-circle"></i>
            ' . e($_SESSION['name'] ?? $role) . '
        </span>

    </header>';
}


function renderPageStart(
    string $role,
    string $active,
    string $title
): void {
    echo '<!doctype html>
    <html lang="en">

    <head>

        <meta charset="utf-8">

        <meta
            name="viewport"
            content="width=device-width,initial-scale=1"
        >

        <title>' . e($title) . ' - MediHub</title>

        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        >

        <style>
            ' . dashboardStyles() . '
        </style>

    </head>

    <body>

        <div class="layout">';

    renderSidebar(
        $role,
        $active
    );

    echo '<div class="content">';

    renderTopbar(
        $role
    );

    echo '<main class="main">';
}


function renderPageEnd(): void
{
    echo '</main>
        </div>
    </div>

    </body>

    </html>';
}
?>