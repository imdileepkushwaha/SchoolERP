<?php
$page_title = "Student Documents";
require_once 'includes/init.php';
require_once '../includes/db_connect.php';
require_once 'includes/erp_helpers.php';

ensureErpSchema($pdo);
$studentId = (int) ($_GET['student_id'] ?? $_POST['student_id'] ?? 0);
$student = null;
if ($studentId) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_doc']) && $student) {
    $docType = trim($_POST['doc_type'] ?? 'Other');
    $upload = uploadStudentDocument($_FILES['document'] ?? [], $studentId, $docType);
    if ($upload === false) {
        $_SESSION['error_msg'] = 'Invalid file. Max 5MB.';
    } elseif ($upload) {
        $pdo->prepare("INSERT INTO student_documents (student_id, doc_type, file_path, original_name) VALUES (?,?,?,?)")
            ->execute([$studentId, $docType, $upload['path'], $upload['original']]);
        $_SESSION['success_msg'] = 'Document uploaded.';
    }
    header('Location: student_documents.php?student_id=' . $studentId);
    exit;
}

if (isset($_GET['delete']) && $studentId) {
    $docId = (int) $_GET['delete'];
    $stmt = $pdo->prepare("SELECT * FROM student_documents WHERE id = ? AND student_id = ?");
    $stmt->execute([$docId, $studentId]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($doc) {
        $full = __DIR__ . '/' . $doc['file_path'];
        if (file_exists($full)) @unlink($full);
        $pdo->prepare("DELETE FROM student_documents WHERE id = ?")->execute([$docId]);
        $_SESSION['success_msg'] = 'Document deleted.';
    }
    header('Location: student_documents.php?student_id=' . $studentId);
    exit;
}

require_once 'includes/header.php';
$search = trim($_GET['q'] ?? '');
$results = [];
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT id, ad_no, name, class FROM students WHERE name LIKE ? OR ad_no LIKE ? LIMIT 15");
    $like = '%' . $search . '%';
    $stmt->execute([$like, $like]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$docs = $student ? getStudentDocuments($pdo, $studentId) : [];
$docTypes = ['Birth Certificate', 'Aadhaar', 'Transfer Certificate', 'Previous Marksheet', 'Other'];
?>
<div class="content-top-bar">
    <div class="content-top-main">
        <div class="content-top-icon icon-purple"><i class="fas fa-folder-open"></i></div>
        <div class="content-top-title"><h2>Student Documents</h2><p class="content-top-breadcrumb"><a href="students.php">Students</a><i class="fas fa-chevron-right"></i><span>Documents</span></p></div>
    </div>
</div>

<div class="form-section-card section-mb">
    <form method="GET" class="category-add-row">
        <div class="form-field form-field-grow"><label>Find Student</label><input type="text" name="q" class="form-input" value="<?php echo htmlspecialchars($search); ?>"></div>
        <div class="form-field category-add-btn-wrap"><label>&nbsp;</label><button type="submit" class="btn-header-action btn-header-primary category-add-btn">Search</button></div>
    </form>
    <?php foreach ($results as $r): ?>
    <p><a href="student_documents.php?student_id=<?php echo $r['id']; ?>" class="teal-link"><?php echo htmlspecialchars($r['name']); ?> — <?php echo htmlspecialchars($r['ad_no']); ?></a></p>
    <?php endforeach; ?>
</div>

<?php if ($student): ?>
<div class="form-section-card section-mb">
    <h4><?php echo htmlspecialchars($student['name']); ?> (<?php echo htmlspecialchars($student['ad_no']); ?>)</h4>
    <form method="POST" enctype="multipart/form-data" class="category-add-row">
        <input type="hidden" name="upload_doc" value="1">
        <input type="hidden" name="student_id" value="<?php echo $studentId; ?>">
        <div class="form-field"><label>Document Type</label><select name="doc_type" class="form-input form-select"><?php foreach ($docTypes as $t): ?><option><?php echo $t; ?></option><?php endforeach; ?></select></div>
        <div class="form-field"><label>File</label><input type="file" name="document" class="form-input" required accept=".pdf,.jpg,.jpeg,.png,.webp"></div>
        <div class="form-field category-add-btn-wrap"><label>&nbsp;</label><button type="submit" class="btn-header-action btn-header-primary category-add-btn"><i class="fas fa-upload"></i> Upload</button></div>
    </form>
</div>
<div class="table-container">
    <table><thead><tr><th>Type</th><th>File</th><th>Uploaded</th><th></th></tr></thead><tbody>
    <?php foreach ($docs as $d): ?>
    <tr>
        <td><?php echo htmlspecialchars($d['doc_type']); ?></td>
        <td><a href="<?php echo htmlspecialchars($d['file_path']); ?>" target="_blank" class="teal-link"><?php echo htmlspecialchars($d['original_name'] ?? basename($d['file_path'])); ?></a></td>
        <td><?php echo htmlspecialchars($d['uploaded_at']); ?></td>
        <td><a href="student_documents.php?student_id=<?php echo $studentId; ?>&delete=<?php echo $d['id']; ?>" class="action-btn delete-btn btn-delete-confirm" onclick="return confirm('Delete?');"><i class="fas fa-trash"></i></a></td>
    </tr>
    <?php endforeach; ?>
    </tbody></table>
</div>
<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>
