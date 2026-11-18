<?php

require_once __DIR__ . '/dashboard_common.php';
requireDashboardRole('Admin');
require_once __DIR__ . '/dashboard_nav.php';

renderPageStart(
    'Admin',
    'admin_reports.php',
    'Reports'
);

?>

<style>

.report-page{
    max-width:1100px;
    margin:0 auto;
}

.report-intro{
    margin-bottom:25px;
}

.report-intro h2{
    margin:0 0 6px;
    font-size:28px;
}

.report-intro p{
    margin:0;
    color:var(--muted);
}

.reports-panel{
    background:#fff;
    border:1px solid var(--border);
    border-radius:15px;
    overflow:hidden;
}

.reports-heading{
    padding:20px 25px;
    border-bottom:1px solid var(--border);
    font-size:20px;
    font-weight:700;
}

.report-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:20px;
    padding:25px;
}

.report-card{
    border:1px solid var(--border);
    border-radius:12px;
    padding:20px;
}

.report-card h3{
    margin:0 0 18px;
    font-size:18px;
}

.report-card ol{
    margin:0;
    padding-left:25px;
}

.report-card li{
    padding:11px 0;
    border-bottom:1px solid var(--border);
    font-size:14px;
}

.report-card li:last-child{
    border-bottom:0;
}

.download-pdf{
    display:inline-block;
    margin-top:6px;
    padding:7px 11px;
    background:#eaf6f2;
    color:var(--mh);
    border-radius:7px;
    font-size:12px;
    font-weight:700;
    text-decoration:none;
}

.download-pdf:hover{
    background:var(--mh);
    color:#fff;
}

@media(max-width:900px){

    .report-grid{
        grid-template-columns:1fr;
    }

}

</style>


<div class="main">

    <div class="report-page">


        <!-- PAGE INTRO -->

        <div class="report-intro">

            <h2>
                Reports
            </h2>

            <p>
                View and download pharmacy management reports.
            </p>

        </div>


        <!-- REPORTS -->

        <div class="reports-panel">


            <div class="reports-heading">
                Reports
            </div>


            <div class="report-grid">


                <!-- WEEKLY REPORTS -->

                <div class="report-card">

                    <h3>
                        Weekly Reports
                    </h3>

                    <ol>

                        <li>
                            Sales Report

                            <a
                                href="report.php?type=sales&period=weekly"
                                class="download-pdf"
                                target="_blank"
                            >
                                [Download PDF]
                            </a>
                        </li>

                        <li>
                            Medicine Report

                            <a
                                href="report.php?type=medicine&period=weekly"
                                class="download-pdf"
                                target="_blank"
                            >
                                [Download PDF]
                            </a>
                        </li>

                        <li>
                            Inventory / Stock Report

                            <a
                                href="report.php?type=inventory&period=weekly"
                                class="download-pdf"
                                target="_blank"
                            >
                                [Download PDF]
                            </a>
                        </li>

                        <li>
                            Supplier Report

                            <a
                                href="report.php?type=supplier&period=weekly"
                                class="download-pdf"
                                target="_blank"
                            >
                                [Download PDF]
                            </a>
                        </li>

                    </ol>

                </div>


                <!-- MONTHLY REPORTS -->

                <div class="report-card">

                    <h3>
                        Monthly Reports
                    </h3>

                    <ol>

                        <li>
                            Sales Report

                            <a
                                href="report.php?type=sales&period=monthly"
                                class="download-pdf"
                                target="_blank"
                            >
                                [Download PDF]
                            </a>
                        </li>

                        <li>
                            Medicine Report

                            <a
                                href="report.php?type=medicine&period=monthly"
                                class="download-pdf"
                                target="_blank"
                            >
                                [Download PDF]
                            </a>
                        </li>

                        <li>
                            Inventory / Stock Report

                            <a
                                href="report.php?type=inventory&period=monthly"
                                class="download-pdf"
                                target="_blank"
                            >
                                [Download PDF]
                            </a>
                        </li>

                        <li>
                            Supplier Report

                            <a
                                href="report.php?type=supplier&period=monthly"
                                class="download-pdf"
                                target="_blank"
                            >
                                [Download PDF]
                            </a>
                        </li>

                    </ol>

                </div>


                <!-- YEARLY REPORTS -->

                <div class="report-card">

                    <h3>
                        Yearly Reports
                    </h3>

                    <ol>

                        <li>
                            Sales Report

                            <a
                                href="report.php?type=sales&period=yearly"
                                class="download-pdf"
                                target="_blank"
                            >
                                [Download PDF]
                            </a>
                        </li>

                        <li>
                            Medicine Report

                            <a
                                href="report.php?type=medicine&period=yearly"
                                class="download-pdf"
                                target="_blank"
                            >
                                [Download PDF]
                            </a>
                        </li>

                        <li>
                            Inventory / Stock Report

                            <a
                                href="report.php?type=inventory&period=yearly"
                                class="download-pdf"
                                target="_blank"
                            >
                                [Download PDF]
                            </a>
                        </li>

                        <li>
                            Supplier Report

                            <a
                                href="report.php?type=supplier&period=yearly"
                                class="download-pdf"
                                target="_blank"
                            >
                                [Download PDF]
                            </a>
                        </li>

                    </ol>

                </div>


            </div>

        </div>

    </div>

</div>


<?php

renderPageEnd();

?>