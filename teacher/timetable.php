<?php
$page_title = 'Timetable';
$page_subtitle = 'Your weekly teaching schedule';
require_once 'includes/init.php';

$grid = getTeacherTimetable($pdo, $teacherId);
$days = getWeekDays();
$periods = range(1, 8);
$periodDefaults = defaultPeriodTimes();
$filledCount = countTeacherWeeklyPeriods($pdo, $teacherId);

require_once 'includes/layout_header.php';
?>

<div class="tp-stat-grid" style="grid-template-columns:repeat(3,1fr)">
    <div class="tp-stat-card">
        <div class="tp-stat-icon blue"><i class="fas fa-calendar-check"></i></div>
        <div><span>Scheduled Periods</span><strong><?php echo $filledCount; ?></strong></div>
    </div>
    <div class="tp-stat-card">
        <div class="tp-stat-icon green"><i class="fas fa-calendar-week"></i></div>
        <div><span>Working Days</span><strong>Mon – Sat</strong></div>
    </div>
    <div class="tp-stat-card">
        <div class="tp-stat-icon orange"><i class="fas fa-clock"></i></div>
        <div><span>Periods / Day</span><strong>8</strong></div>
    </div>
</div>

<div class="tp-card">
    <div class="tp-card-head">
        <h3><i class="fas fa-table"></i> Weekly Grid</h3>
        <span style="font-size:0.82rem;color:var(--tp-muted)">Today: <strong><?php echo date('l, d M Y'); ?></strong></span>
    </div>
    <?php if ($filledCount): ?>
    <div class="tp-tt-grid">
        <div class="tp-tt-row">
            <div class="tp-tt-day">Day</div>
            <?php foreach ($periods as $p): ?><div class="tp-tt-day">P<?php echo $p; ?></div><?php endforeach; ?>
        </div>
        <?php foreach ($days as $day):
            $isToday = ($day === date('l'));
        ?>
        <div class="tp-tt-row" <?php echo $isToday ? 'style="outline:2px solid #93c5fd;border-radius:10px;padding:2px"' : ''; ?>>
            <div class="tp-tt-day"><?php echo substr($day, 0, 3); ?></div>
            <?php foreach ($periods as $p):
                $slot = $grid[$day][$p] ?? null;
                $filled = $slot && (trim($slot['class_name'] ?? '') !== '' || trim($slot['subject_name'] ?? '') !== '');
            ?>
            <div class="tp-tt-slot <?php echo $filled ? 'filled' : 'free'; ?>">
                <?php if ($filled): ?>
                <strong><?php echo htmlspecialchars($slot['subject_name'] ?: $teacher['subject']); ?></strong><br>
                <?php echo htmlspecialchars($slot['class_name']); ?><?php echo $slot['section_name'] ? ' · ' . htmlspecialchars($slot['section_name']) : ''; ?>
                <?php if ($slot['room_no']): ?><br><em>R<?php echo htmlspecialchars($slot['room_no']); ?></em><?php endif; ?>
                <?php else: ?>Free<?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="tp-table-wrap" style="margin-top:24px">
        <table class="tp-table">
            <thead><tr><th>Day</th><th>Period</th><th>Time</th><th>Class</th><th>Subject</th><th>Room</th></tr></thead>
            <tbody>
            <?php foreach ($days as $day):
                if (empty($grid[$day])) continue;
                foreach ($grid[$day] as $slot):
                    if (empty($slot['class_name']) && empty($slot['subject_name'])) continue;
            ?>
            <tr>
                <td><strong><?php echo $day; ?></strong></td>
                <td>P<?php echo (int) $slot['period_no']; ?></td>
                <td><?php
                    $def = $periodDefaults[(int) $slot['period_no']] ?? ['—', '—'];
                    echo $slot['start_time'] ? substr($slot['start_time'], 0, 5) . ' – ' . substr($slot['end_time'], 0, 5) : $def[0] . ' – ' . $def[1];
                ?></td>
                <td><?php echo htmlspecialchars($slot['class_name']); ?><?php echo $slot['section_name'] ? ' (' . htmlspecialchars($slot['section_name']) . ')' : ''; ?></td>
                <td><?php echo htmlspecialchars($slot['subject_name'] ?: $teacher['subject']); ?></td>
                <td><?php echo displayVal($slot['room_no']); ?></td>
            </tr>
            <?php endforeach; endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="tp-empty"><i class="fas fa-calendar-alt"></i><p>No timetable has been set up yet.<br>Please contact the administrator.</p></div>
    <?php endif; ?>
</div>

<?php require_once 'includes/layout_footer.php'; ?>
