<?php
// admin/student_edit.php
$page_title = "Edit Student";
require_once 'includes/header.php';
require_once '../includes/db_connect.php';

if (!isset($_GET['id'])) {
    echo "<script>window.location.href='students.php';</script>";
    exit;
}

$id = $_GET['id'];

// Fetch current data
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    echo "<div class='table-container' style='padding: 30px;'><h3>Student not found.</h3></div>";
    require_once 'includes/footer.php';
    exit;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $class = $_POST['class'];
    $mobile = $_POST['mobile'];

    $update_stmt = $pdo->prepare("UPDATE students SET name = ?, class = ?, mobile = ? WHERE id = ?");
    if ($update_stmt->execute([$name, $class, $mobile, $id])) {
        $_SESSION['success_msg'] = "Student data updated successfully!";
        echo "<script>window.location.href='students.php';</script>";
        exit;
    } else {
        $_SESSION['error_msg'] = "Failed to update student.";
    }
}
?>

<div class="table-container" style="padding: 30px;">
    <h3 style="margin-bottom: 20px; color: var(--text-dark);">Edit Student: <?php echo htmlspecialchars($student['name']); ?></h3>
    
    <form method="POST" action="">
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; color: var(--text-muted); font-size: 0.9rem;">Student Name</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($student['name']); ?>" style="width: 100%; max-width: 400px; padding: 10px 15px; border: 1px solid var(--border-color); border-radius: 6px; outline: none; font-family: inherit;" required>
        </div>
        
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; color: var(--text-muted); font-size: 0.9rem;">Class</label>
            <input type="text" name="class" value="<?php echo htmlspecialchars($student['class']); ?>" style="width: 100%; max-width: 400px; padding: 10px 15px; border: 1px solid var(--border-color); border-radius: 6px; outline: none; font-family: inherit;" required>
        </div>
        
        <div style="margin-bottom: 25px;">
            <label style="display: block; margin-bottom: 5px; color: var(--text-muted); font-size: 0.9rem;">Mobile Number</label>
            <input type="text" name="mobile" value="<?php echo htmlspecialchars($student['mobile']); ?>" style="width: 100%; max-width: 400px; padding: 10px 15px; border: 1px solid var(--border-color); border-radius: 6px; outline: none; font-family: inherit;" required>
        </div>
        
        <button type="submit" class="btn-admin" style="padding: 10px 25px; border: none; border-radius: 6px; color: white; cursor: pointer; font-family: inherit; font-weight: 500;">
            Update Student
        </button>
        <a href="students.php" style="margin-left: 15px; color: var(--text-muted); font-size: 0.95rem; text-decoration: none;">Cancel</a>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
