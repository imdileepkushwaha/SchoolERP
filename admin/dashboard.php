<?php
// admin/dashboard.php
$page_title = "Dashboard";
require_once 'includes/header.php';
require_once '../includes/db_connect.php';

// Mock stats for UI
$stats = [
    ['icon' => 'fa-user-graduate', 'color' => '#ff7e67', 'count' => '20,000', 'label' => 'Total Student'],
    ['icon' => 'fa-chalkboard-teacher', 'color' => '#4318ff', 'count' => '20,000', 'label' => 'Total Student'],
    ['icon' => 'fa-users', 'color' => '#9333ea', 'count' => '20,000', 'label' => 'Total Student'],
    ['icon' => 'fa-user-shield', 'color' => '#14b8a6', 'count' => '20,000', 'label' => 'Total Student'],
    ['icon' => 'fa-money-bill-alt', 'color' => '#10b981', 'count' => '20,000', 'label' => 'Total Student'],
    ['icon' => 'fa-file-invoice', 'color' => '#3b82f6', 'count' => '20,000', 'label' => 'Total Student'],
];
?>

<div class="page-title-block">
    <h1>Dashboard</h1>
    <p>School <i class="fas fa-arrow-right" style="font-size:0.7rem; margin:0 5px;"></i> Manage your school, track attendance, expense, and net worth.</p>
</div>

<div class="dashboard-grid">
    <!-- Left Column (Wider) -->
    <div class="col-left">
        
        <!-- 6 Stats Cards -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 20px;">
            <?php foreach($stats as $stat): ?>
            <div class="widget-card stat-box">
                <div class="stat-icon" style="background-color: <?php echo $stat['color']; ?>;">
                    <i class="fas <?php echo $stat['icon']; ?>"></i>
                </div>
                <div class="stat-info">
                    <h4><?php echo $stat['label']; ?></h4>
                    <h2><?php echo $stat['count']; ?></h2>
                    <p class="stat-trend trend-up">10% ▲ <span class="trend-text">+5 This Month</span></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Revenue Statistic Chart -->
        <div class="widget-card" style="margin-bottom: 20px;">
            <div class="widget-title">
                <span>Revenue Statistic</span>
            </div>
            <div id="revenueChart"></div>
        </div>

        <!-- Notice Board & Leave Requests -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div class="widget-card">
                <div class="widget-title">
                    <span>Notice Board</span>
                    <i class="fas fa-ellipsis-v" style="color: var(--text-muted); cursor:pointer;"></i>
                </div>
                <!-- Notice Items -->
                <div class="list-item">
                    <img src="https://ui-avatars.com/api/?name=Admin&background=random" class="list-avatar">
                    <div class="list-content">
                        <h5>Admin</h5>
                        <p>Lorem Ipsum is simply dummy text of the printing and typesetting...</p>
                        <span style="font-size:0.75rem; color:var(--text-muted);">25 Jan 2024</span>
                    </div>
                </div>
                <div class="list-item">
                    <img src="https://ui-avatars.com/api/?name=Kathryn+Murphy&background=random" class="list-avatar">
                    <div class="list-content">
                        <h5>Kathryn Murphy</h5>
                        <p>Lorem Ipsum is simply dummy text of the printing and typesetting...</p>
                        <span style="font-size:0.75rem; color:var(--text-muted);">25 Jan 2024</span>
                    </div>
                </div>
            </div>

            <div class="widget-card">
                <div class="widget-title">
                    <span>Leave Requests</span>
                    <i class="fas fa-ellipsis-v" style="color: var(--text-muted); cursor:pointer;"></i>
                </div>
                <!-- Leave Items -->
                <div class="list-item" style="align-items: center;">
                    <img src="https://ui-avatars.com/api/?name=Darlene+Robertson&background=random" class="list-avatar">
                    <div class="list-content">
                        <h5>Darlene Robertson</h5>
                        <p>English Teacher</p>
                    </div>
                    <div class="list-meta">
                        <strong>3 Days</strong>
                        Apply on: 10 April
                    </div>
                </div>
                <div class="list-item" style="align-items: center;">
                    <img src="https://ui-avatars.com/api/?name=Esther+Howard&background=random" class="list-avatar">
                    <div class="list-content">
                        <h5>Esther Howard</h5>
                        <p>English Teacher</p>
                    </div>
                    <div class="list-meta">
                        <strong>3 Days</strong>
                        Apply on: 10 April
                    </div>
                </div>
            </div>
        </div>
        
        <!-- User Overview -->
        <div class="widget-card">
            <div class="widget-title">
                <span>User Overview</span>
                <i class="fas fa-ellipsis-v" style="color: var(--text-muted); cursor:pointer;"></i>
            </div>
            <div id="userOverviewChart" style="display:flex; justify-content:center;"></div>
            <div style="display:flex; justify-content:space-around; margin-top:20px;">
                <div style="text-align:center;">
                    <p style="color:var(--text-muted); font-size:0.8rem;"><span style="color:#10b981;">●</span> Student</p>
                    <h3 style="color:var(--text-dark);">750</h3>
                </div>
                <div style="text-align:center;">
                    <p style="color:var(--text-muted); font-size:0.8rem;"><span style="color:#f59e0b;">●</span> Teacher</p>
                    <h3 style="color:var(--text-dark);">56</h3>
                </div>
                <div style="text-align:center;">
                    <p style="color:var(--text-muted); font-size:0.8rem;"><span style="color:#4318ff;">●</span> Staffs</p>
                    <h3 style="color:var(--text-dark);">15</h3>
                </div>
            </div>
        </div>

    </div>

    <!-- Right Column (Narrower) -->
    <div class="col-right">
        
        <!-- Student Attendance -->
        <div class="widget-card" style="margin-bottom: 20px;">
            <div class="widget-title">
                <span>Student Attendance</span>
            </div>
            <!-- Mock Progress Bars -->
            <div style="display:flex; height:30px; border-radius:5px; overflow:hidden; margin-bottom:20px;">
                <div style="width:50%; background:#14b8a6;"></div>
                <div style="width:20%; background:#f97316;"></div>
                <div style="width:15%; background:#a855f7;"></div>
                <div style="width:15%; background:#22c55e;"></div>
            </div>
            <ul style="font-size:0.9rem; color:var(--text-muted);">
                <li style="display:flex; justify-content:space-between; margin-bottom:10px;"><span style="color:#14b8a6;">● Present</span> <strong>87%</strong></li>
                <li style="display:flex; justify-content:space-between; margin-bottom:10px;"><span style="color:#f97316;">● Absent</span> <strong>40%</strong></li>
                <li style="display:flex; justify-content:space-between; margin-bottom:10px;"><span style="color:#a855f7;">● Late</span> <strong>20%</strong></li>
                <li style="display:flex; justify-content:space-between; margin-bottom:10px;"><span style="color:#22c55e;">● Half day</span> <strong>20%</strong></li>
            </ul>
        </div>

        <!-- Upcoming Events -->
        <div class="widget-card" style="margin-bottom: 20px;">
            <div class="widget-title">
                <span>Upcoming Events</span>
            </div>
            <div style="border-left: 3px solid #a855f7; padding-left:15px; margin-bottom:15px; display:flex; justify-content:space-between;">
                <div>
                    <h5 style="color:var(--text-dark); margin-bottom:5px;">09:00 - 09:45 AM</h5>
                    <p style="font-size:0.8rem; color:var(--text-muted);">Marketing Strategy Kickoff</p>
                    <p style="font-size:0.75rem; color:#14b8a6; margin-top:5px;">Lead by Robert Fox</p>
                </div>
                <button style="border:none; background:#f1f5f9; padding:5px 15px; border-radius:5px; cursor:pointer;">View</button>
            </div>
            <div style="border-left: 3px solid #f97316; padding-left:15px; margin-bottom:15px; display:flex; justify-content:space-between;">
                <div>
                    <h5 style="color:var(--text-dark); margin-bottom:5px;">11:15 - 12:00 AM</h5>
                    <p style="font-size:0.8rem; color:var(--text-muted);">Product Design Brainstorm</p>
                    <p style="font-size:0.75rem; color:#14b8a6; margin-top:5px;">Lead by Leslie Alexander</p>
                </div>
                <button style="border:none; background:#f1f5f9; padding:5px 15px; border-radius:5px; cursor:pointer;">View</button>
            </div>
        </div>

        <!-- Top Teachers -->
        <div class="widget-card">
            <div class="widget-title">
                <span>Top Teachers</span>
                <i class="fas fa-ellipsis-v" style="color: var(--text-muted); cursor:pointer;"></i>
            </div>
            <div class="list-item" style="align-items: center;">
                <img src="https://ui-avatars.com/api/?name=Theresa+Webb&background=random" class="list-avatar">
                <div class="list-content">
                    <h5>Theresa Webb</h5>
                    <p>example@gmail.com</p>
                </div>
                <div class="list-meta" style="text-align:right;">
                    <strong style="font-size:0.85rem;">Mathematics</strong>
                </div>
            </div>
            <div class="list-item" style="align-items: center;">
                <img src="https://ui-avatars.com/api/?name=Darrell+Steward&background=random" class="list-avatar">
                <div class="list-content">
                    <h5>Darrell Steward</h5>
                    <p>example@gmail.com</p>
                </div>
                <div class="list-meta" style="text-align:right;">
                    <strong style="font-size:0.85rem;">Physics</strong>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
// ApexCharts Initialization
document.addEventListener('DOMContentLoaded', function() {
    
    // Revenue Statistic Bar Chart
    var revenueOptions = {
        series: [{
            name: 'Total Fee',
            data: [25, 35, 60, 40, 20, 15, 45, 20, 80, 15, 5, 40]
        }, {
            name: 'Collected Fee',
            data: [15, 10, 24, 30, 25, 10, 15, 10, 25, 10, 5, 20]
        }],
        chart: {
            type: 'bar',
            height: 350,
            toolbar: { show: false }
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '55%',
                endingShape: 'rounded'
            },
        },
        dataLabels: { enabled: false },
        stroke: { show: true, width: 2, colors: ['transparent'] },
        xaxis: {
            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        },
        fill: { opacity: 1 },
        colors: ['#14b8a6', '#f97316'],
        legend: { position: 'top' }
    };
    var revenueChart = new ApexCharts(document.querySelector("#revenueChart"), revenueOptions);
    revenueChart.render();

    // User Overview Pie Chart
    var userOverviewOptions = {
        series: [60, 30, 10],
        labels: ['Student', 'Teacher', 'Staffs'],
        chart: {
            type: 'donut',
            width: 280
        },
        colors: ['#10b981', '#f59e0b', '#4318ff'],
        dataLabels: {
            enabled: true,
            formatter: function (val) {
                return val + "%"
            }
        },
        legend: { show: false }
    };
    var userOverviewChart = new ApexCharts(document.querySelector("#userOverviewChart"), userOverviewOptions);
    userOverviewChart.render();
});
</script>

</main>
</div>
</body>
</html>
