<?php
$page_title = 'My Library';
require_once 'includes/init.php';
require_once __DIR__ . '/../admin/includes/module_helpers.php';
require_once __DIR__ . '/../admin/includes/library_helpers.php';

assertSchoolLicenseActive($pdo);
requireModule($pdo, 'library', 'dashboard.php');
ensureLibrarySchema($pdo);

$stmt = $pdo->prepare(
    "SELECT i.*, b.title, b.author, b.isbn
     FROM library_issues i
     INNER JOIN library_books b ON b.id = i.book_id
     WHERE i.student_id = ?
     ORDER BY i.status ASC, i.issue_date DESC"
);
$stmt->execute([(int) $student['id']]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/layout_header.php';
?>
<div class="sp-card">
    <div class="sp-card-head"><h3><i class="fas fa-book"></i> Issued books</h3></div>
    <?php if (!$rows): ?>
    <div class="sp-empty"><div class="sp-empty-icon"><i class="fas fa-book"></i></div><strong>No library records</strong><p>Books issued to you will appear here with due dates.</p></div>
    <?php else: ?>
    <div class="sp-list">
        <?php foreach ($rows as $r):
            $open = ($r['status'] ?? '') === 'Issued';
            $overdue = $open && !empty($r['due_date']) && $r['due_date'] < date('Y-m-d');
        ?>
        <div class="sp-list-item">
            <div>
                <strong><?php echo htmlspecialchars($r['title']); ?></strong>
                <small><?php echo htmlspecialchars($r['author'] ?: 'Library'); ?> · Issued <?php echo htmlspecialchars($r['issue_date']); ?></small>
            </div>
            <span><?php echo $open ? ($overdue ? 'Overdue' : 'Due ' . htmlspecialchars($r['due_date'] ?: '')) : 'Returned'; ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php require_once 'includes/layout_footer.php'; ?>
