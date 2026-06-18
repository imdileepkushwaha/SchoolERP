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
        $pdo->prepare("INSERT INTO student_transport (student_id, route_id, stop_id) VALUES (?,?,?) ON DUPLICATE KEY UPDATE route_id=VALUES(route_id), stop_id=VALUES(stop_id)")
            ->execute([(int)$_POST['student_id'], (int)$_POST['route_id'], (int)($_POST['stop_id'] ?? 0) ?: null]);
        $_SESSION['success_msg'] = 'Student assigned to route.';
    }
    header('Location: transport.php');
    exit;
}

require_once 'includes/header.php';
$vehicles = $pdo->query("SELECT * FROM transport_vehicles ORDER BY vehicle_no")->fetchAll(PDO::FETCH_ASSOC);
$routes = $pdo->query("SELECT r.*, v.vehicle_no FROM transport_routes r LEFT JOIN transport_vehicles v ON v.id = r.vehicle_id ORDER BY r.name")->fetchAll(PDO::FETCH_ASSOC);
$stopsByRoute = [];
foreach ($routes as $r) {
    $stmt = $pdo->prepare("SELECT * FROM transport_stops WHERE route_id = ? ORDER BY sort_order, stop_name");
    $stmt->execute([$r['id']]);
    $stopsByRoute[$r['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$assignments = $pdo->query(
    "SELECT st.*, s.name, s.ad_no, s.class, r.name AS route_name FROM student_transport st
     INNER JOIN students s ON s.id = st.student_id INNER JOIN transport_routes r ON r.id = st.route_id ORDER BY s.name"
)->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="content-top-bar">
    <div class="content-top-main">
        <div class="content-top-icon icon-blue"><i class="fas fa-bus"></i></div>
        <div class="content-top-title"><h2>Transport Management</h2><p class="content-top-breadcrumb"><a href="dashboard.php">Dashboard</a><i class="fas fa-chevron-right"></i><span>Transport</span></p></div>
    </div>
</div>

<div class="details-grid section-mb">
    <div class="form-section-card form-section-flush">
        <h4>Add Vehicle</h4>
        <form method="POST" class="form-grid form-grid-1">
            <input type="hidden" name="action" value="add_vehicle">
            <div class="form-field"><label>Vehicle No</label><input type="text" name="vehicle_no" class="form-input" required></div>
            <div class="form-field"><label>Model</label><input type="text" name="model" class="form-input"></div>
            <div class="form-field"><label>Capacity</label><input type="number" name="capacity" class="form-input" value="40"></div>
            <div class="form-field"><label>Driver</label><input type="text" name="driver_name" class="form-input"></div>
            <div class="form-field"><label>Driver Phone</label><input type="text" name="driver_phone" class="form-input"></div>
            <button type="submit" class="btn-header-action btn-header-outline">Add Vehicle</button>
        </form>
    </div>
    <div class="form-section-card form-section-flush">
        <h4>Add Route</h4>
        <form method="POST" class="form-grid form-grid-1">
            <input type="hidden" name="action" value="add_route">
            <div class="form-field"><label>Route Name</label><input type="text" name="route_name" class="form-input" required></div>
            <div class="form-field"><label>Vehicle</label><select name="vehicle_id" class="form-input form-select"><option value="">—</option><?php foreach ($vehicles as $v): ?><option value="<?php echo $v['id']; ?>"><?php echo htmlspecialchars($v['vehicle_no']); ?></option><?php endforeach; ?></select></div>
            <div class="form-field"><label>Monthly Fare (Rs.)</label><input type="number" step="0.01" name="fare" class="form-input" value="0"></div>
            <button type="submit" class="btn-header-action btn-header-outline">Add Route</button>
        </form>
    </div>
</div>

<?php foreach ($routes as $r): ?>
<div class="form-section-card section-mb">
    <strong><?php echo htmlspecialchars($r['name']); ?></strong> <?php if ($r['vehicle_no']): ?>(<?php echo htmlspecialchars($r['vehicle_no']); ?>)<?php endif; ?>
    <form method="POST" class="section-add-inline" style="margin-top:10px">
        <input type="hidden" name="action" value="add_stop">
        <input type="hidden" name="route_id" value="<?php echo $r['id']; ?>">
        <input type="text" name="stop_name" class="form-input" placeholder="Stop name" required>
        <input type="time" name="pickup_time" class="form-input">
        <button type="submit" class="btn-header-action btn-header-outline btn-sm">Add Stop</button>
    </form>
    <?php if (!empty($stopsByRoute[$r['id']])): ?>
    <ul class="erp-stop-list"><?php foreach ($stopsByRoute[$r['id']] as $stop): ?><li><?php echo htmlspecialchars($stop['stop_name']); ?> <?php if ($stop['pickup_time']): ?>— <?php echo substr($stop['pickup_time'],0,5); ?><?php endif; ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<div class="form-section-card section-mb">
    <h4>Assign Student to Route</h4>
    <form method="POST" class="category-add-row">
        <input type="hidden" name="action" value="assign_student">
        <div class="form-field"><label>Student ID</label><input type="number" name="student_id" class="form-input" placeholder="From student list" required></div>
        <div class="form-field"><label>Route</label><select name="route_id" class="form-input form-select" required><?php foreach ($routes as $r): ?><option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['name']); ?></option><?php endforeach; ?></select></div>
        <div class="form-field category-add-btn-wrap"><label>&nbsp;</label><button type="submit" class="btn-header-action btn-header-primary category-add-btn">Assign</button></div>
    </form>
</div>

<?php if ($assignments): ?>
<div class="table-container"><table><thead><tr><th>Student</th><th>Class</th><th>Route</th></tr></thead><tbody>
<?php foreach ($assignments as $a): ?><tr><td><?php echo htmlspecialchars($a['name']); ?> (<?php echo htmlspecialchars($a['ad_no']); ?>)</td><td><?php echo htmlspecialchars($a['class']); ?></td><td><?php echo htmlspecialchars($a['route_name']); ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>
