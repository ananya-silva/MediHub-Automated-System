<?php
require_once __DIR__.'/dashboard_common.php';
requireDashboardRole('Admin');
require_once __DIR__.'/dashboard_nav.php';

$today=(float)$conn->query("SELECT COALESCE(SUM(total_amount),0)t FROM orders WHERE status IN ('Approved','Ready','Delivered') AND DATE(order_date)=CURDATE()")->fetch_assoc()['t'];

$week=(float)$conn->query("SELECT COALESCE(SUM(total_amount),0)t FROM orders WHERE status IN ('Approved','Ready','Delivered') AND order_date>=DATE_SUB(CURDATE(),INTERVAL 7 DAY)")->fetch_assoc()['t'];

$month=(float)$conn->query("SELECT COALESCE(SUM(total_amount),0)t FROM orders WHERE status IN ('Approved','Ready','Delivered') AND YEAR(order_date)=YEAR(CURDATE()) AND MONTH(order_date)=MONTH(CURDATE())")->fetch_assoc()['t'];

$year=(float)$conn->query("SELECT COALESCE(SUM(total_amount),0)t FROM orders WHERE status IN ('Approved','Ready','Delivered') AND YEAR(order_date)=YEAR(CURDATE())")->fetch_assoc()['t'];

$rows=$conn->query("SELECT DATE(order_date)d,COUNT(*) orders,SUM(total_amount) revenue FROM orders WHERE status IN ('Approved','Ready','Delivered') AND order_date>=DATE_SUB(CURDATE(),INTERVAL 30 DAY) GROUP BY DATE(order_date) ORDER BY d DESC");

$data=[];
$max=1;

while($r=$rows->fetch_assoc()){
    $data[]=$r;
    $max=max($max,(float)$r['revenue']);
}

renderPageStart('Admin','admin_analytics.php','Analytics & Revenue');
?>

<div class="welcome">
    <h2>Analytics & Revenue</h2>
    <p>Live business metrics calculated from approved, ready and delivered orders.</p>
</div>

<div class="stats">

    <div class="stat">
        <small>Today</small>
        <strong>LKR <?=number_format($today,2)?></strong>
    </div>

    <div class="stat">
        <small>Last 7 Days</small>
        <strong>LKR <?=number_format($week,2)?></strong>
    </div>

    <div class="stat">
        <small>This Month</small>
        <strong>LKR <?=number_format($month,2)?></strong>
    </div>

    <div class="stat">
        <small>This Year</small>
        <strong>LKR <?=number_format($year,2)?></strong>
    </div>

</div>


<!-- PROFESSIONAL ANALYTICS SECTION -->

<div class="analytics-grid">

    <!-- Revenue Distribution -->
    <div class="panel chart-panel">

        <div class="panel-head">
            <div>
                <h3>Revenue Distribution</h3>
                <p>Comparison of revenue across different periods</p>
            </div>
        </div>

        <div class="pie-wrapper">
            <canvas id="revenuePieChart"></canvas>
        </div>

    </div>


    <!-- Revenue Trend -->
    <div class="panel chart-panel">

        <div class="panel-head">
            <div>
                <h3>Revenue Trend</h3>
                <p>Revenue generated during the last 30 days</p>
            </div>
        </div>

        <div class="line-wrapper">
            <canvas id="revenueLineChart"></canvas>
        </div>

    </div>

</div>


<!-- LAST 30 DAYS -->

<div class="panel">

    <div class="panel-head">
        <h3>Last 30 Days Revenue</h3>
    </div>

    <?php foreach($data as $r): ?>

        <div class="chart-row">

            <span>
                <?=date('d M',strtotime($r['d']))?>
            </span>

            <div class="bar">
                <span style="width:<?=((float)$r['revenue']/$max)*100?>%"></span>
            </div>

            <strong>
                LKR <?=number_format($r['revenue'],2)?>
            </strong>

        </div>

    <?php endforeach; ?>

</div>


<!-- CHART.JS -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

const todayRevenue = <?=json_encode($today)?>;
const weekRevenue = <?=json_encode($week)?>;
const monthRevenue = <?=json_encode($month)?>;
const yearRevenue = <?=json_encode($year)?>;


/* =========================
   PIE / DOUGHNUT CHART
========================= */

const pieCanvas = document.getElementById('revenuePieChart');

new Chart(pieCanvas, {

    type: 'doughnut',

    data: {

        labels: [
            'Today',
            'Last 7 Days',
            'This Month',
            'This Year'
        ],

        datasets: [{

            data: [
                todayRevenue,
                weekRevenue,
                monthRevenue,
                yearRevenue
            ],

            borderWidth: 2,

            hoverOffset: 8

        }]

    },

    options: {

        responsive: true,

        maintainAspectRatio: false,

        cutout: '65%',

        plugins: {

            legend: {

                position: 'bottom',

                labels: {

                    padding: 20,

                    usePointStyle: true,

                    font: {
                        size: 13
                    }

                }

            },

            tooltip: {

                callbacks: {

                    label: function(context) {

                        return ' LKR ' +
                            Number(context.raw).toLocaleString(
                                'en-LK',
                                {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                }
                            );

                    }

                }

            }

        }

    }

});


/* =========================
   LAST 30 DAYS LINE CHART
========================= */

const revenueLabels = [
<?php

$chartData = array_reverse($data);

foreach($chartData as $r):

?>
    "<?=date('d M',strtotime($r['d']))?>",
<?php endforeach; ?>
];


const revenueValues = [
<?php

foreach($chartData as $r):

?>
    <?=((float)$r['revenue'])?>,
<?php endforeach; ?>
];


const lineCanvas = document.getElementById('revenueLineChart');

new Chart(lineCanvas, {

    type: 'line',

    data: {

        labels: revenueLabels,

        datasets: [{

            label: 'Revenue',

            data: revenueValues,

            tension: 0.4,

            fill: true,

            pointRadius: 4,

            pointHoverRadius: 7,

            borderWidth: 3

        }]

    },

    options: {

        responsive: true,

        maintainAspectRatio: false,

        interaction: {

            intersect: false,

            mode: 'index'

        },

        plugins: {

            legend: {

                display: false

            },

            tooltip: {

                callbacks: {

                    label: function(context) {

                        return ' LKR ' +
                            Number(context.raw).toLocaleString(
                                'en-LK',
                                {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                }
                            );

                    }

                }

            }

        },

        scales: {

            y: {

                beginAtZero: true,

                ticks: {

                    callback: function(value) {

                        return 'LKR ' +
                            Number(value).toLocaleString('en-LK');

                    }

                }

            },

            x: {

                grid: {

                    display: false

                }

            }

        }

    }

});

</script>


<style>

.analytics-grid {

    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 20px;

    margin-top: 20px;

}


.chart-panel {

    min-height: 430px;

}


.panel-head p {

    margin: 5px 0 0;

    font-size: 13px;

    opacity: 0.65;

}


.pie-wrapper {

    position: relative;

    height: 330px;

    display: flex;

    align-items: center;

    justify-content: center;

}


.pie-wrapper canvas {

    max-width: 350px;

    max-height: 350px;

}


.line-wrapper {

    position: relative;

    height: 330px;

    width: 100%;

}


.chart-row {

    display: grid;

    grid-template-columns: 70px 1fr 150px;

    align-items: center;

    gap: 15px;

    margin-bottom: 14px;

}


.chart-row > span {

    font-size: 13px;

    font-weight: 600;

}


.chart-row strong {

    text-align: right;

    font-size: 13px;

}


.bar {

    height: 10px;

    background: #eeeeee;

    border-radius: 20px;

    overflow: hidden;

}


.bar span {

    display: block;

    height: 100%;

    border-radius: 20px;

}


@media (max-width: 900px) {

    .analytics-grid {

        grid-template-columns: 1fr;

    }

}


@media (max-width: 600px) {

    .chart-row {

        grid-template-columns: 60px 1fr;

    }

    .chart-row strong {

        grid-column: 2;

        text-align: left;

    }

}

</style>

<?php
renderPageEnd();
?> 