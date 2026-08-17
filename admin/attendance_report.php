<?php
$page_title = "Attendance Report";
require_once 'includes/init.php';
require_once '../includes/db_connect.php';
require_once 'includes/erp_helpers.php';

ensureErpSchema($pdo);
$class_options = getClassOptions($pdo);
$class = trim($_GET['class'] ?? '');
$section = trim($_GET['section'] ?? 'A');
$month = (int) ($_GET['month'] ?? date('n'));
$year = (int) ($_GET['year'] ?? date('Y'));

$report = ($class !== '') ? getAttendanceMonthlyReport($pdo, $class, $section, $year, $month) : null;

if (isset($_GET['export']) && $_GET['export'] === 'csv' && $report) {
    $filename = 'attendance_' . preg_replace('/\W+/', '_', $class . '_' . $section) . '_' . $year . '_' . sprintf('%02d', $month) . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($out, ['Student', 'Admission No', 'Roll', 'Present', 'Absent', 'Late', 'Half Day', 'Percentage']);
    foreach ($report['students'] as $s) {
        $rec = $report['records'][$s['id']] ?? [];
        $counts = ['Present' => 0, 'Absent' => 0, 'Late' => 0, 'Half Day' => 0];
        foreach ($rec as $st) {
            if (isset($counts[$st])) {
                $counts[$st]++;
            }
        }
        $total = array_sum($counts);
        $pct = $total ? round(($counts['Present'] + $counts['Late'] * 0.5) / $total * 100) : 0;
        fputcsv($out, [
            $s['name'],
            $s['ad_no'] ?? '',
            $s['roll'] ?? '',
            $counts['Present'],
            $counts['Absent'],
            $counts['Late'],
            $counts['Half Day'],
            $pct,
        ]);
    }
    fclose($out);
    exit;
}

require_once 'includes/header.php';
?>
<div class="content-top-bar">
    <div class="content-top-main">
        <div class="content-top-icon icon-blue"><i class="fas fa-chart-bar"></i></div>
        <div class="content-top-title">
            <h2>Monthly Attendance Report</h2>
            <p class="content-top-breadcrumb"><a href="dashboard.php">Dashboard</a><i class="fas fa-chevron-right"></i><a href="attendance.php">Attendance</a><i class="fas fa-chevron-right"></i><span>Report</span></p>
        </div>
    </div>
    <?php if ($report): ?>
    <div class="content-top-actions">
        <a href="attendance_report.php?<?php echo http_build_query(['class' => $class, 'section' => $section, 'month' => $month, 'year' => $year, 'export' => 'csv']); ?>" class="btn-header-action btn-header-outline"><i class="fas fa-file-csv"></i> Export CSV</a>
        <button type="button" class="btn-header-action btn-header-primary" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
    </div>
    <?php endif; ?>
</div>

<div class="form-section-card section-mb no-print">
    <form method="GET" class="category-add-form">
        <div class="category-add-row erp-filter-row-4">
            <div class="form-field"><label>Class</label><select name="class" class="form-input form-select" required><option value="">Select</option><?php foreach ($class_options as $c): ?><option value="<?php echo htmlspecialchars($c); ?>" <?php echo $class === $c ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option><?php endforeach; ?></select></div>
            <div class="form-field"><label>Section</label><select name="section" class="form-input form-select"><?php foreach (($class ? getSectionOptions($pdo, $class) : ['A']) as $sec): ?><option value="<?php echo htmlspecialchars($sec); ?>" <?php echo $section === $sec ? 'selected' : ''; ?>><?php echo htmlspecialchars($sec); ?></option><?php endforeach; ?></select></div>
            <div class="form-field"><label>Month</label><select name="month" class="form-input form-select"><?php for ($m = 1; $m <= 12; $m++): ?><option value="<?php echo $m; ?>" <?php echo $month === $m ? 'selected' : ''; ?>><?php echo date('F', mktime(0,0,0,$m,1)); ?></option><?php endfor; ?></select></div>
            <div class="form-field"><label>Year</label><input type="number" name="year" class="form-input" value="<?php echo $year; ?>"></div>
            <div class="form-field category-add-btn-wrap"><label>&nbsp;</label><button type="submit" class="btn-header-action btn-header-primary category-add-btn">View Report</button></div>
        </div>
    </form>
</div>

<?php if ($report): ?>
<div class="table-container">
    <div class="table-toolbar"><strong><?php echo htmlspecialchars($class); ?> (<?php echo htmlspecialchars($section); ?>) — <?php echo date('F Y', strtotime($report['start'])); ?></strong></div>
    <div class="table-wrapper table-scroll-x">
        <table>
            <thead><tr><th>Student</th><th>Roll</th><th>Present</th><th>Absent</th><th>Late</th><th>Half Day</th><th>%</th></tr></thead>
            <tbody>
            <?php foreach ($report['students'] as $s):
                $rec = $report['records'][$s['id']] ?? [];
                $counts = ['Present'=>0,'Absent'=>0,'Late'=>0,'Half Day'=>0];
                foreach ($rec as $st) { if (isset($counts[$st])) $counts[$st]++; }
                $total = array_sum($counts);
                $pct = $total ? round(($counts['Present'] + $counts['Late'] * 0.5) / $total * 100) : 0;
            ?>
            <tr>
                <td><?php echo htmlspecialchars($s['name']); ?></td>
                <td><?php echo htmlspecialchars($s['roll']); ?></td>
                <td><?php echo $counts['Present']; ?></td>
                <td><?php echo $counts['Absent']; ?></td>
                <td><?php echo $counts['Late']; ?></td>
                <td><?php echo $counts['Half Day']; ?></td>
                <td><?php echo $pct; ?>%</td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<style>@media print{.no-print,.sidebar,.top-header,.content-top-actions{display:none!important}body{background:#fff}}</style>
<?php require_once 'includes/footer.php'; ?>
