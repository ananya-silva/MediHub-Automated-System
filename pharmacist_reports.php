<?php

require_once __DIR__ . '/dashboard_common.php';
requireDashboardRole('Pharmacist');
require_once __DIR__ . '/dashboard_nav.php';

renderPageStart(
    'Pharmacist',
    'pharmacist_reports.php',
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
    font-weight:700;
}

.report-intro p{
    margin:0;
    color:var(--muted);
}


/* Main Reports Panel */

.reports-panel{
    background:#fff;
    border:1px solid var(--border);
    border-radius:15px;
    overflow:hidden;
}


/* Section Heading */

.reports-heading{
    padding:20px 25px;
    border-bottom:1px solid var(--border);
    font-size:20px;
    font-weight:700;
}


/* Monthly Reports */

.monthly-reports{
    padding:25px;
    max-width:850px;
}

.monthly-reports h3{
    margin:0 0 20px;
    font-size:18px;
    font-weight:700;
}

.monthly-reports ol{
    margin:0;
    padding-left:28px;
}

.monthly-reports li{
    padding:14px 5px;
    border-bottom:1px solid var(--border);
    font-size:15px;
}

.monthly-reports li:last-child{
    border-bottom:0;
}


/* Download Button */

.download-pdf{
    display:inline-block;
    margin-left:8px;
    padding:7px 12px;
    background:#eaf6f2;
    color:var(--mh);
    border-radius:7px;
    font-size:13px;
    font-weight:700;
    text-decoration:none;
}

.download-pdf:hover{
    background:var(--mh);
    color:#fff;
}


/* Responsive */

@media(max-width:700px){

    .monthly-reports{
        padding:18px;
    }

    .monthly-reports li{
        font-size:14px;
    }

    .download-pdf{
        display:block;
        width:max-content;
        margin:8px 0 0 0;
    }

}

</style>


<div class="main">

    <div class="report-page">


        <!-- =================================================
             PAGE INTRO
        ================================================== -->

        <div class="report-intro">

            <h2>
                Reports
            </h2>

            <p>
                View and download pharmacy reports.
            </p>

        </div>


        <!-- =================================================
             REPORTS PANEL
        ================================================== -->

        <div class="reports-panel">


            <!-- Section Heading -->

            <div class="reports-heading">

                Reports

            </div>


            <!-- Monthly Reports -->

            <div class="monthly-reports">

                <h3>
                    Monthly Reports
                </h3>


                <ol>


                    <!-- Sales Report -->

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


                    <!-- Medicine Report -->

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


                    <!-- Inventory Report -->

                    <li>

                        Inventory Report

                        <a
                            href="report.php?type=inventory&period=monthly"
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


<?php

renderPageEnd();

?>