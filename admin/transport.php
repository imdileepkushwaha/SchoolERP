<?php
$page_title = "Transport";
require_once 'includes/init.php';
require_once '../includes/db_connect.php';
require_once 'includes/erp_helpers.php';

ensureErpSchema($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_vehicle') {
        $pdo->prepare("INSERT INTO transport_vehicles (vehicle_no, model, capacity, driver_name, driver_phone) VALUES (?,?,?,?,?)")
            ->execute([trim($_POST['vehicle_no']), trim($_POST['model'] ?? ''), (int)($_POST['capacity'] ?? 40), trim($_POST['driver_name'] ?? ''), trim($_POST['driver_phone'] ?? '')]);
        $_SESSION['success_msg'] = 'Vehicle added.';
    } elseif ($action === 'add_route') {
        $pdo->prepare("INSERT INTO transport_routes (name, vehicle_id, fare) VALUES (?,?,?)")
            ->execute([trim($_POST['route_name']), (int)($_POST['vehicle_id'] ?? 0) ?: null, (float)($_POST['fare'] ?? 0)]);
        $_SESSION['success_msg'] = 'Route added.';
    } elseif ($action === 'add_stop' && isset($_POST['route_id'])) {
        $pdo->prepare("INSERT INTO transport_stops (route_id, stop_name, pickup_time, sort_order) VALUES (?,?,?,?)")
            ->execute([(int)$_POST['route_id'], trim($_POST['stop_name']), $_POST['pickup_time'] ?: null, (int)($_POST['sort_order'] ?? 0)]);
        $_SESSION['success_msg'] = 'Stop added.';
    } elseif ($action === 'assign_student') {
        $studentId = (int) ($_POST['student_id'] ?? 0);
        if ($studentId <= 0) {
            $_SESSION['error_msg'] = 'Please select a student.';
        } else {
            $pdo->prepare("INSERT INTO student_transport (student_id, route_id, stop_id) VALUES (?,?,?) ON DUPLICATE KEY UPDATE route_id=VALUES(route_id), stop_id=VALUES(stop_id)")
                ->execute([$studentId, (int)$_POST['route_id'], (int)($_POST['stop_id'] ?? 0) ?: null]);
            $_SESSION['success_msg'] = 'Student assigned to route.';
        }
    }
    header('Location: transport.php' . (isset($_GET['q']) ? '?q=' . urlencode($_GET['q']) : ''));
    exit;
}

require_once 'includes/header.php';
$vehicles = $pdo->query("SELECT * FROM transport_vehicles ORDER BY vehicle_no")->fetchAll(PDO::FETCH_ASSOC);
$routes = $pdo->query("SELECT r.*, v.vehicle_no, v.capacity AS vehicle_capacity, v.driver_name, v.driver_phone FROM transport_routes r LEFT JOIN transport_vehicles v ON v.id = r.vehicle_id ORDER BY r.name")->fetchAll(PDO::FETCH_ASSOC);
$stopsByRoute = [];
$totalStops = 0;
foreach ($routes as $r) {
    $stmt = $pdo->prepare("SELECT * FROM transport_stops WHERE route_id = ? ORDER BY sort_order, stop_name");
    $stmt->execute([$r['id']]);
    $stopsByRoute[$r['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalStops += count($stopsByRoute[$r['id']]);
}
$assignments = $pdo->query(
    "SELECT st.*, s.name, s.ad_no, s.class, s.section, r.name AS route_name FROM student_transport st
     INNER JOIN students s ON s.id = st.student_id INNER JOIN transport_routes r ON r.id = st.route_id ORDER BY s.name"
)->fetchAll(PDO::FETCH_ASSOC);

$search = trim($_GET['q'] ?? '');
$searchResults = [];
if ($search !== '') {
    $like = '%' . $search . '%';
    $stmt = $pdo->prepare("SELECT id, ad_no, name, class, section FROM students WHERE status='Active' AND (name LIKE ? OR ad_no LIKE ?) LIMIT 12");
    $stmt->execute([$like, $like]);
    $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$activeVehicles = count(array_filter($vehicles, fn($v) => ($v['status'] ?? 'Active') === 'Active'));
$totalCapacity = array_sum(array_column($vehicles, 'capacity'));

$vehicleStats = [];
foreach ($vehicles as $v) {
    $vehicleStats[$v['id']] = ['routes' => 0, 'students' => 0];
}
$routeVehicleMap = [];
foreach ($routes as $r) {
    $routeVehicleMap[$r['id']] = $r['vehicle_id'] ?? null;
    if (!empty($r['vehicle_id'])) {
        $vehicleStats[$r['vehicle_id']]['routes']++;
    }
}
foreach ($assignments as $a) {
    $vid = $routeVehicleMap[$a['route_id']] ?? null;
    if ($vid && isset($vehicleStats[$vid])) {
        $vehicleStats[$vid]['students']++;
    }
}
?>
<div class="content-top-bar">
    <div class="content-top-main">
        <div class="content-top-icon icon-blue"><i class="fas fa-bus"></i></div>
        <div class="content-top-title">
            <h2>Transport Management</h2>
            <p class="content-top-breadcrumb">
                <a href="dashboard.php">Dashboard</a>
                <i class="fas fa-chevron-right"></i>
                <span>Transport</span>
            </p>
        </div>
    </div>
    <div class="content-top-actions">
        <a href="students.php" class="btn-header-action btn-header-outline"><i class="fas fa-user-graduate"></i> Students</a>
    </div>
</div>

<div class="cls-stat-strip cols-4">
    <div class="cls-stat-card">
        <div class="cls-stat-icon"><i class="fas fa-bus"></i></div>
        <div><span>Vehicles</span><strong><?php echo $activeVehicles; ?></strong></div>
    </div>
    <div class="cls-stat-card">
        <div class="cls-stat-icon cls-stat-blue"><i class="fas fa-route"></i></div>
        <div><span>Routes</span><strong><?php echo count($routes); ?></strong></div>
    </div>
    <div class="cls-stat-card">
        <div class="cls-stat-icon cls-stat-green"><i class="fas fa-user-check"></i></div>
        <div><span>Students Assigned</span><strong><?php echo count($assignments); ?></strong></div>
    </div>
    <div class="cls-stat-card">
        <div class="cls-stat-icon cls-stat-blue"><i class="fas fa-map-pin"></i></div>
        <div><span>Total Stops</span><strong><?php echo $totalStops; ?></strong></div>
    </div>
</div>

<div class="details-grid section-mb">
    <div class="form-section-card">
        <div class="section-card-header">
            <div class="section-card-icon section-icon-school"><i class="fas fa-bus"></i></div>
            <div><h4>Add Vehicle</h4><p>Register school bus or van</p></div>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add_vehicle">
            <div class="form-grid form-grid-2 form-grid-spaced">
                <div class="form-field"><label>Vehicle Number</label><input type="text" name="vehicle_no" class="form-input" placeholder="e.g. DL-01-AB-1234" required></div>
                <div class="form-field"><label>Model</label><input type="text" name="model" class="form-input" placeholder="e.g. Tata Starbus"></div>
                <div class="form-field"><label>Seating Capacity</label><input type="number" name="capacity" class="form-input" value="40" min="1"></div>
                <div class="form-field"><label>Driver Name</label><input type="text" name="driver_name" class="form-input" placeholder="Driver full name"></div>
                <div class="form-field form-field-full"><label>Driver Phone</label><input type="text" name="driver_phone" class="form-input" placeholder="10-digit mobile"></div>
            </div>
            <div class="form-actions-end"><button type="submit" class="btn-header-action btn-header-primary"><i class="fas fa-plus"></i> Add Vehicle</button></div>
        </form>
    </div>
    <div class="form-section-card">
        <div class="section-card-header">
            <div class="section-card-icon section-icon-school"><i class="fas fa-route"></i></div>
            <div><h4>Add Route</h4><p>Link route to a vehicle and set fare</p></div>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add_route">
            <div class="form-grid form-grid-2 form-grid-spaced">
                <div class="form-field"><label>Route Name</label><input type="text" name="route_name" class="form-input" placeholder="e.g. City Center Route" required></div>
                <div class="form-field"><label>Vehicle</label>
                    <select name="vehicle_id" class="form-input form-select">
                        <option value="">No vehicle</option>
                        <?php foreach ($vehicles as $v): ?>
                        <option value="<?php echo $v['id']; ?>"><?php echo htmlspecialchars($v['vehicle_no']); ?><?php echo $v['model'] ? ' — ' . htmlspecialchars($v['model']) : ''; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field"><label>Monthly Fare (₹)</label><input type="number" step="0.01" name="fare" class="form-input" value="0" min="0"></div>
            </div>
            <div class="form-actions-end"><button type="submit" class="btn-header-action btn-header-primary"><i class="fas fa-plus"></i> Add Route</button></div>
        </form>
    </div>
</div>

<?php if ($vehicles): ?>
<div class="form-section-card section-mb">
    <div class="section-card-header">
        <div class="section-card-icon section-icon-school"><i class="fas fa-shuttle-van"></i></div>
        <div><h4>Fleet Overview</h4><p><?php echo count($vehicles); ?> vehicle(s) · <?php echo $totalCapacity; ?> total seats</p></div>
    </div>
    <div class="erp-vehicle-grid">
        <?php foreach ($vehicles as $v):
            $isActive = ($v['status'] ?? 'Active') === 'Active';
            $stats = $vehicleStats[$v['id']] ?? ['routes' => 0, 'students' => 0];
            $cap = (int) $v['capacity'];
            $used = $stats['students'];
            $fillPct = $cap > 0 ? min(100, round($used / $cap * 100)) : 0;
        ?>
        <article class="erp-vehicle-card <?php echo $isActive ? 'is-active' : 'is-inactive'; ?>">
            <div class="erp-vehicle-card-top">
                <div class="erp-vehicle-plate">
                    <span class="erp-vehicle-plate-label">Vehicle No.</span>
                    <strong><?php echo htmlspecialchars($v['vehicle_no']); ?></strong>
                </div>
                <span class="erp-vehicle-status"><?php echo htmlspecialchars($v['status'] ?? 'Active'); ?></span>
            </div>
            <div class="erp-vehicle-visual">
                <div class="erp-vehicle-bus-icon"><i class="fas fa-bus-alt"></i></div>
                <p class="erp-vehicle-model"><?php echo htmlspecialchars(displayVal($v['model'], 'Model not specified')); ?></p>
            </div>
            <div class="erp-vehicle-stats">
                <div class="erp-vehicle-stat">
                    <i class="fas fa-chair"></i>
                    <strong><?php echo $cap; ?></strong>
                    <span>Seats</span>
                </div>
                <div class="erp-vehicle-stat">
                    <i class="fas fa-route"></i>
                    <strong><?php echo $stats['routes']; ?></strong>
                    <span>Routes</span>
                </div>
                <div class="erp-vehicle-stat">
                    <i class="fas fa-user-graduate"></i>
                    <strong><?php echo $used; ?></strong>
                    <span>Students</span>
                </div>
            </div>
            <div class="erp-vehicle-capacity">
                <div class="erp-vehicle-capacity-head">
                    <span>Seat usage</span>
                    <strong><?php echo $used; ?> / <?php echo $cap; ?></strong>
                </div>
                <div class="erp-occupancy-bar"><div class="erp-occupancy-fill <?php echo $fillPct >= 90 ? 'is-full' : ''; ?>" style="width:<?php echo $fillPct; ?>%"></div></div>
            </div>
            <?php if ($v['driver_name'] || $v['driver_phone']): ?>
            <div class="erp-vehicle-driver">
                <div class="erp-vehicle-driver-avatar"><i class="fas fa-id-badge"></i></div>
                <div class="erp-vehicle-driver-info">
                    <span class="erp-vehicle-driver-label">Assigned Driver</span>
                    <strong><?php echo htmlspecialchars($v['driver_name'] ?: 'Not assigned'); ?></strong>
                    <?php if ($v['driver_phone']): ?>
                    <a href="tel:<?php echo htmlspecialchars(preg_replace('/\D/', '', $v['driver_phone'])); ?>" class="erp-vehicle-driver-phone">
                        <i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($v['driver_phone']); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="erp-vehicle-driver erp-vehicle-driver-empty">
                <i class="fas fa-user-slash"></i> No driver assigned
            </div>
            <?php endif; ?>
        </article>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="form-section-card section-mb">
    <div class="section-card-header">
        <div class="section-card-icon section-icon-school"><i class="fas fa-map-marked-alt"></i></div>
        <div><h4>Routes & Stops</h4><p>Manage pickup points for each route</p></div>
    </div>
    <?php if (empty($routes)): ?>
    <div class="tab-empty-state tab-empty-pad-sm">
        <div class="tab-empty-icon"><i class="fas fa-route"></i></div>
        <h3>No routes yet</h3>
        <p>Add a route above to start adding stops.</p>
    </div>
    <?php else: foreach ($routes as $r):
        $stops = $stopsByRoute[$r['id']] ?? [];
    ?>
    <div class="erp-route-panel">
        <div class="erp-route-header">
            <div class="erp-route-title">
                <span class="erp-route-icon"><i class="fas fa-route"></i></span>
                <div>
                    <strong><?php echo htmlspecialchars($r['name']); ?></strong>
                    <span>
                        <?php if ($r['vehicle_no']): ?><i class="fas fa-bus"></i> <?php echo htmlspecialchars($r['vehicle_no']); ?> · <?php endif; ?>
                        <?php echo count($stops); ?> stop(s) · ₹<?php echo number_format((float) $r['fare'], 0); ?>/mo
                    </span>
                </div>
            </div>
            <?php if ($r['driver_name']): ?>
            <span class="promo-next-pill"><i class="fas fa-user"></i> <?php echo htmlspecialchars($r['driver_name']); ?></span>
            <?php endif; ?>
        </div>
        <form method="POST" class="erp-stop-add-row">
            <input type="hidden" name="action" value="add_stop">
            <input type="hidden" name="route_id" value="<?php echo $r['id']; ?>">
            <div class="form-field erp-stop-field"><label>Stop name</label><input type="text" name="stop_name" class="form-input" placeholder="e.g. Main Market" required></div>
            <div class="form-field erp-stop-field-sm"><label>Pickup time</label><input type="time" name="pickup_time" class="form-input"></div>
            <div class="form-field erp-stop-field-sm"><label>Order</label><input type="number" name="sort_order" class="form-input" value="<?php echo count($stops) + 1; ?>" min="0"></div>
            <button type="submit" class="btn-header-action btn-header-outline btn-sm"><i class="fas fa-plus"></i> Add Stop</button>
        </form>
        <?php if ($stops): ?>
        <div class="erp-stop-chips">
            <?php foreach ($stops as $i => $stop): ?>
            <div class="erp-stop-chip">
                <span class="erp-stop-num"><?php echo $i + 1; ?></span>
                <div>
                    <strong><?php echo htmlspecialchars($stop['stop_name']); ?></strong>
                    <?php if ($stop['pickup_time']): ?><span><i class="fas fa-clock"></i> <?php echo substr($stop['pickup_time'], 0, 5); ?></span><?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="erp-route-empty">No stops on this route yet.</p>
        <?php endif; ?>
    </div>
    <?php endforeach; endif; ?>
</div>

<div class="form-section-card section-mb">
    <div class="section-card-header">
        <div class="section-card-icon section-icon-school"><i class="fas fa-user-plus"></i></div>
        <div><h4>Assign Student to Route</h4><p>Search by name or admission number</p></div>
    </div>
    <form method="GET" class="category-add-row">
        <div class="form-field form-field-grow"><label>Find student</label><input type="text" name="q" class="form-input" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name or admission no."></div>
        <div class="form-field category-add-btn-wrap"><label>&nbsp;</label><button type="submit" class="btn-header-action btn-header-primary category-add-btn"><i class="fas fa-search"></i> Search</button></div>
    </form>
    <?php if ($search !== '' && empty($searchResults)): ?>
    <div class="tab-empty-state tab-empty-pad-sm"><p>No students found.</p></div>
    <?php endif; ?>
</div>

<?php if ($searchResults): ?>
<div class="erp-search-results student-search-results">
<?php foreach ($searchResults as $sr): ?>
<form method="POST" class="erp-search-item student-search-card student-portal-card">
    <input type="hidden" name="action" value="assign_student">
    <input type="hidden" name="student_id" value="<?php echo $sr['id']; ?>">
    <div class="student-search-main">
        <div class="student-search-avatar"><i class="fas fa-user-graduate"></i></div>
        <div class="student-search-info">
            <strong><?php echo htmlspecialchars($sr['name']); ?></strong>
            <span><?php echo htmlspecialchars($sr['ad_no']); ?></span>
            <div class="student-search-meta">
                <span class="student-search-class-pill"><i class="fas fa-school"></i> Class <?php echo htmlspecialchars($sr['class']); ?> · <?php echo htmlspecialchars($sr['section'] ?? 'A'); ?></span>
            </div>
        </div>
    </div>
    <div class="student-search-actions">
        <span class="student-search-actions-label">Assign to route</span>
        <select name="route_id" class="form-input form-select erp-assign-select" required data-student="<?php echo $sr['id']; ?>" title="Route">
            <?php foreach ($routes as $r): ?><option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['name']); ?></option><?php endforeach; ?>
        </select>
        <select name="stop_id" class="form-input form-select erp-assign-select erp-stop-select" data-student="<?php echo $sr['id']; ?>" title="Stop">
            <option value="">Any stop</option>
        </select>
        <button type="submit" class="btn-header-action btn-header-primary btn-sm"><i class="fas fa-bus"></i> Assign</button>
    </div>
</form>
<?php endforeach; ?>
</div>
<?php endif; ?>

<div class="table-container">
    <div class="table-toolbar">
        <strong>Assigned Students</strong>
        <span class="toolbar-meta"><?php echo count($assignments); ?> student(s) on transport</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Admission No</th><th>Student</th><th>Class</th><th>Route</th></tr></thead>
            <tbody>
            <?php if ($assignments): foreach ($assignments as $a): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($a['ad_no']); ?></strong></td>
                <td><?php echo htmlspecialchars($a['name']); ?></td>
                <td><span class="promo-next-pill"><?php echo htmlspecialchars($a['class']); ?> · <?php echo htmlspecialchars($a['section'] ?? 'A'); ?></span></td>
                <td><?php echo htmlspecialchars($a['route_name']); ?></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="4" class="table-empty-cell">No students assigned to transport yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    var stopsByRoute = <?php echo json_encode($stopsByRoute); ?>;
    document.querySelectorAll('select[name="route_id"].erp-assign-select').forEach(function (routeSel) {
        var studentId = routeSel.getAttribute('data-student');
        var stopSel = document.querySelector('select.erp-stop-select[data-student="' + studentId + '"]');
        function fillStops() {
            if (!stopSel) return;
            var rid = routeSel.value;
            stopSel.innerHTML = '<option value="">Any stop</option>';
            (stopsByRoute[rid] || []).forEach(function (s) {
                var opt = document.createElement('option');
                opt.value = s.id;
                opt.textContent = s.stop_name + (s.pickup_time ? ' (' + s.pickup_time.substring(0, 5) + ')' : '');
                stopSel.appendChild(opt);
            });
        }
        routeSel.addEventListener('change', fillStops);
        fillStops();
    });
})();
</script>
<?php require_once 'includes/footer.php'; ?>
