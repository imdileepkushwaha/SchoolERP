<?php
$page_title = "Hostel Management";
require_once 'includes/init.php';
require_once '../includes/db_connect.php';
require_once 'includes/erp_helpers.php';

ensureErpSchema($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_hostel') {
        $pdo->prepare("INSERT INTO hostels (name, address) VALUES (?,?)")->execute([trim($_POST['name']), trim($_POST['address'] ?? '')]);
        $_SESSION['success_msg'] = 'Hostel added.';
    } elseif ($action === 'add_room' && isset($_POST['hostel_id'])) {
        $pdo->prepare("INSERT INTO hostel_rooms (hostel_id, room_no, room_type, capacity) VALUES (?,?,?,?)")
            ->execute([(int)$_POST['hostel_id'], trim($_POST['room_no']), trim($_POST['room_type'] ?? 'Standard'), (int)($_POST['capacity'] ?? 2)]);
        $_SESSION['success_msg'] = 'Room added.';
    } elseif ($action === 'allot') {
        $studentId = (int) $_POST['student_id'];
        $roomId = (int) $_POST['room_id'];
        $room = $pdo->prepare("SELECT * FROM hostel_rooms WHERE id = ?");
        $room->execute([$roomId]);
        $room = $room->fetch(PDO::FETCH_ASSOC);
        if ($room && getHostelRoomOccupancy($pdo, $roomId) >= (int)$room['capacity']) {
            $_SESSION['error_msg'] = 'Room is full.';
        } else {
            $pdo->prepare(
                "INSERT INTO hostel_allotments (student_id, room_id, allotted_from, status) VALUES (?,?,CURDATE(),'Active')
                 ON DUPLICATE KEY UPDATE room_id=VALUES(room_id), allotted_from=CURDATE(), allotted_to=NULL, status='Active'"
            )->execute([$studentId, $roomId]);
            $pdo->prepare("UPDATE students SET hostel_name=(SELECT h.name FROM hostels h INNER JOIN hostel_rooms hr ON hr.hostel_id=h.id WHERE hr.id=?), room_no=?, room_type=? WHERE id=?")
                ->execute([$roomId, $room['room_no'], $room['room_type'], $studentId]);
            $_SESSION['success_msg'] = 'Room allotted.';
        }
    }
    header('Location: hostel.php');
    exit;
}

require_once 'includes/header.php';
$hostels = $pdo->query("SELECT * FROM hostels WHERE status='Active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$rooms = $pdo->query(
    "SELECT hr.*, h.name AS hostel_name, (SELECT COUNT(*) FROM hostel_allotments ha WHERE ha.room_id=hr.id AND ha.status='Active') AS occupied
     FROM hostel_rooms hr INNER JOIN hostels h ON h.id = hr.hostel_id WHERE hr.status='Active' ORDER BY h.name, hr.room_no"
)->fetchAll(PDO::FETCH_ASSOC);
$allotments = $pdo->query(
    "SELECT ha.*, s.name, s.ad_no, hr.room_no, h.name AS hostel_name FROM hostel_allotments ha
     INNER JOIN students s ON s.id = ha.student_id INNER JOIN hostel_rooms hr ON hr.id = ha.room_id
     INNER JOIN hostels h ON h.id = hr.hostel_id WHERE ha.status='Active' ORDER BY h.name, hr.room_no"
)->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="content-top-bar">
    <div class="content-top-main">
        <div class="content-top-icon icon-purple"><i class="fas fa-bed"></i></div>
        <div class="content-top-title"><h2>Hostel Management</h2><p class="content-top-breadcrumb"><a href="dashboard.php">Dashboard</a><i class="fas fa-chevron-right"></i><span>Hostel</span></p></div>
    </div>
</div>

<div class="details-grid section-mb">
    <div class="form-section-card form-section-flush">
        <h4>Add Hostel</h4>
        <form method="POST"><input type="hidden" name="action" value="add_hostel">
            <div class="form-field"><input type="text" name="name" class="form-input" placeholder="Hostel name" required></div>
            <div class="form-field"><input type="text" name="address" class="form-input" placeholder="Address"></div>
            <button type="submit" class="btn-header-action btn-header-outline">Add</button>
        </form>
    </div>
    <div class="form-section-card form-section-flush">
        <h4>Add Room</h4>
        <form method="POST"><input type="hidden" name="action" value="add_room">
            <div class="form-field"><select name="hostel_id" class="form-input form-select" required><?php foreach ($hostels as $h): ?><option value="<?php echo $h['id']; ?>"><?php echo htmlspecialchars($h['name']); ?></option><?php endforeach; ?></select></div>
            <div class="form-field"><input type="text" name="room_no" class="form-input" placeholder="Room no" required></div>
            <div class="form-field"><input type="text" name="room_type" class="form-input" value="Standard"></div>
            <div class="form-field"><input type="number" name="capacity" class="form-input" value="2" min="1"></div>
            <button type="submit" class="btn-header-action btn-header-outline">Add Room</button>
        </form>
    </div>
</div>

<div class="form-section-card section-mb">
    <h4>Allot Room to Student</h4>
    <form method="POST" class="category-add-row">
        <input type="hidden" name="action" value="allot">
        <div class="form-field"><label>Student ID</label><input type="number" name="student_id" class="form-input" required></div>
        <div class="form-field"><label>Room</label><select name="room_id" class="form-input form-select" required>
            <?php foreach ($rooms as $rm): $free = (int)$rm['capacity'] - (int)$rm['occupied']; ?>
            <option value="<?php echo $rm['id']; ?>" <?php echo $free <= 0 ? 'disabled' : ''; ?>><?php echo htmlspecialchars($rm['hostel_name'] . ' — ' . $rm['room_no']); ?> (<?php echo $rm['occupied']; ?>/<?php echo $rm['capacity']; ?>)</option>
            <?php endforeach; ?>
        </select></div>
        <div class="form-field category-add-btn-wrap"><label>&nbsp;</label><button type="submit" class="btn-header-action btn-header-primary category-add-btn">Allot</button></div>
    </form>
</div>

<div class="table-container">
    <div class="table-toolbar"><strong>Rooms</strong></div>
    <table><thead><tr><th>Hostel</th><th>Room</th><th>Type</th><th>Occupancy</th></tr></thead><tbody>
    <?php foreach ($rooms as $rm): ?><tr><td><?php echo htmlspecialchars($rm['hostel_name']); ?></td><td><?php echo htmlspecialchars($rm['room_no']); ?></td><td><?php echo htmlspecialchars($rm['room_type']); ?></td><td><?php echo $rm['occupied']; ?>/<?php echo $rm['capacity']; ?></td></tr><?php endforeach; ?>
    </tbody></table>
</div>

<?php if ($allotments): ?>
<div class="table-container section-mb"><div class="table-toolbar"><strong>Active Allotments</strong></div>
<table><thead><tr><th>Student</th><th>Hostel</th><th>Room</th><th>From</th></tr></thead><tbody>
<?php foreach ($allotments as $a): ?><tr><td><?php echo htmlspecialchars($a['name']); ?></td><td><?php echo htmlspecialchars($a['hostel_name']); ?></td><td><?php echo htmlspecialchars($a['room_no']); ?></td><td><?php echo htmlspecialchars($a['allotted_from']); ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>
