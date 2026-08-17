<?php
$page_title = "Library";
require_once 'includes/init.php';
require_once '../includes/db_connect.php';
require_once 'includes/erp_helpers.php';
require_once 'includes/module_helpers.php';

ensureErpSchema($pdo);
ensureLibrarySchema($pdo);
assertSchoolLicenseActive($pdo);
requireModule($pdo, 'library');
handleClassApiRequest($pdo);

function libraryIssueQuery(array $src): string {
    $q = [];
    foreach (['q', 'class', 'section', 'session_id', 'year'] as $key) {
        $val = trim((string) ($src[$key] ?? ''));
        if ($val === '') {
            continue;
        }
        if (in_array($key, ['session_id', 'year'], true) && (int) $val === 0) {
            continue;
        }
        $q[$key] = $val;
    }
    return $q ? ('?' . http_build_query($q)) : '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_category') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            $_SESSION['error_msg'] = 'Category name is required.';
        } else {
            try {
                $pdo->prepare('INSERT INTO library_categories (name) VALUES (?)')->execute([$name]);
                $_SESSION['success_msg'] = 'Category added.';
            } catch (PDOException $e) {
                $_SESSION['error_msg'] = 'Category already exists.';
            }
        }
    } elseif ($action === 'add_book') {
        $title = trim($_POST['title'] ?? '');
        $copies = max(1, (int) ($_POST['copies'] ?? 1));
        if ($title === '') {
            $_SESSION['error_msg'] = 'Book title is required.';
        } else {
            $pdo->prepare(
                'INSERT INTO library_books (category_id, title, author, isbn, publisher, copies, available, shelf) VALUES (?,?,?,?,?,?,?,?)'
            )->execute([
                (int) ($_POST['category_id'] ?? 0) ?: null,
                $title,
                trim($_POST['author'] ?? '') ?: null,
                trim($_POST['isbn'] ?? '') ?: null,
                trim($_POST['publisher'] ?? '') ?: null,
                $copies,
                $copies,
                trim($_POST['shelf'] ?? '') ?: null,
            ]);
            $_SESSION['success_msg'] = 'Book added.';
        }
    } elseif ($action === 'issue_book') {
        $studentId = (int) ($_POST['student_id'] ?? 0);
        $bookId = (int) ($_POST['book_id'] ?? 0);
        $due = trim($_POST['due_date'] ?? '');
        if ($studentId <= 0 || $bookId <= 0) {
            $_SESSION['error_msg'] = 'Select a student and a book.';
        } else {
            $book = $pdo->prepare('SELECT * FROM library_books WHERE id = ? AND status = "Active"');
            $book->execute([$bookId]);
            $book = $book->fetch(PDO::FETCH_ASSOC);
            if (!$book) {
                $_SESSION['error_msg'] = 'Book not found.';
            } elseif ((int) $book['available'] < 1) {
                $_SESSION['error_msg'] = 'No copies available.';
            } else {
                $dup = $pdo->prepare("SELECT id FROM library_issues WHERE book_id = ? AND student_id = ? AND status = 'Issued'");
                $dup->execute([$bookId, $studentId]);
                if ($dup->fetch()) {
                    $_SESSION['error_msg'] = 'This student already has this book issued.';
                } else {
                    if ($due === '') {
                        $due = date('Y-m-d', strtotime('+14 days'));
                    }
                    $pdo->prepare("INSERT INTO library_issues (book_id, student_id, issue_date, due_date, status) VALUES (?,?,CURDATE(),?,'Issued')")
                        ->execute([$bookId, $studentId, $due]);
                    $pdo->prepare('UPDATE library_books SET available = available - 1 WHERE id = ? AND available > 0')->execute([$bookId]);
                    $_SESSION['success_msg'] = 'Book issued.';
                }
            }
        }
    } elseif ($action === 'return_book' && isset($_POST['issue_id'])) {
        $issueId = (int) $_POST['issue_id'];
        $fine = max(0, (float) ($_POST['fine'] ?? 0));
        $stmt = $pdo->prepare("SELECT * FROM library_issues WHERE id = ? AND status = 'Issued'");
        $stmt->execute([$issueId]);
        $issue = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$issue) {
            $_SESSION['error_msg'] = 'Issue record not found.';
        } else {
            if ($fine <= 0 && !empty($issue['due_date']) && $issue['due_date'] < date('Y-m-d')) {
                $days = (int) ((strtotime(date('Y-m-d')) - strtotime($issue['due_date'])) / 86400);
                $perDay = (float) (function_exists('getSetting') ? getSetting($pdo, 'library_fine_per_day', '5') : 5);
                $fine = max(0, $days * $perDay);
            }
            $pdo->prepare("UPDATE library_issues SET status = 'Returned', return_date = CURDATE(), fine = ? WHERE id = ?")
                ->execute([$fine, $issueId]);
            $pdo->prepare('UPDATE library_books SET available = available + 1 WHERE id = ?')->execute([(int) $issue['book_id']]);
            $_SESSION['success_msg'] = $fine > 0 ? ('Book returned. Fine: ₹' . number_format($fine, 2)) : 'Book returned.';
        }
    } elseif ($action === 'mark_lost' && isset($_POST['issue_id'])) {
        $issueId = (int) ($_POST['issue_id'] ?? 0);
        $fine = max(0, (float) ($_POST['fine'] ?? 0));
        $stmt = $pdo->prepare("SELECT * FROM library_issues WHERE id = ? AND status = 'Issued'");
        $stmt->execute([$issueId]);
        $issue = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$issue) {
            $_SESSION['error_msg'] = 'Issue record not found.';
        } else {
            if ($fine <= 0) {
                $fine = 200;
            }
            $pdo->prepare("UPDATE library_issues SET status = 'Lost', return_date = CURDATE(), fine = ?, remarks = CONCAT(IFNULL(remarks,''), IF(IFNULL(remarks,'')='','',' '), '[Lost]') WHERE id = ?")
                ->execute([$fine, $issueId]);
            // Issued copy was already removed from available; reduce catalogue copies permanently.
            $pdo->prepare('UPDATE library_books SET copies = GREATEST(copies - 1, 0) WHERE id = ?')
                ->execute([(int) $issue['book_id']]);
            $_SESSION['success_msg'] = 'Book marked lost. Fine: ₹' . number_format($fine, 2);
        }
    } elseif ($action === 'update_book' && isset($_POST['id'])) {
        $bookId = (int) $_POST['id'];
        $title = trim($_POST['title'] ?? '');
        $copies = max(1, (int) ($_POST['copies'] ?? 1));
        if ($title === '') {
            $_SESSION['error_msg'] = 'Book title is required.';
        } else {
            $stmt = $pdo->prepare('SELECT copies, available FROM library_books WHERE id = ?');
            $stmt->execute([$bookId]);
            $cur = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$cur) {
                $_SESSION['error_msg'] = 'Book not found.';
            } else {
                $issued = (int) $cur['copies'] - (int) $cur['available'];
                if ($copies < $issued) {
                    $_SESSION['error_msg'] = "Copies cannot be less than currently issued ($issued).";
                } else {
                    $available = $copies - $issued;
                    $pdo->prepare(
                        'UPDATE library_books SET category_id=?, title=?, author=?, isbn=?, publisher=?, copies=?, available=?, shelf=? WHERE id=?'
                    )->execute([
                        (int) ($_POST['category_id'] ?? 0) ?: null,
                        $title,
                        trim($_POST['author'] ?? '') ?: null,
                        trim($_POST['isbn'] ?? '') ?: null,
                        trim($_POST['publisher'] ?? '') ?: null,
                        $copies,
                        $available,
                        trim($_POST['shelf'] ?? '') ?: null,
                        $bookId,
                    ]);
                    $_SESSION['success_msg'] = 'Book updated.';
                }
            }
        }
    } elseif ($action === 'update_category' && isset($_POST['id'])) {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            $_SESSION['error_msg'] = 'Category name is required.';
        } else {
            try {
                $pdo->prepare('UPDATE library_categories SET name=? WHERE id=?')->execute([$name, (int) $_POST['id']]);
                $_SESSION['success_msg'] = 'Category updated.';
            } catch (PDOException $e) {
                $_SESSION['error_msg'] = 'Category name already exists.';
            }
        }
    } elseif ($action === 'delete_category' && isset($_POST['id'])) {
        $catId = (int) $_POST['id'];
        $used = $pdo->prepare("SELECT COUNT(*) FROM library_books WHERE category_id = ? AND status = 'Active'");
        $used->execute([$catId]);
        if ((int) $used->fetchColumn() > 0) {
            $_SESSION['error_msg'] = 'Reassign or remove books in this category first.';
        } else {
            $pdo->prepare("UPDATE library_categories SET status = 'Inactive' WHERE id = ?")->execute([$catId]);
            $_SESSION['success_msg'] = 'Category deleted.';
        }
    } elseif ($action === 'delete_book' && isset($_POST['id'])) {
        $bookId = (int) $_POST['id'];
        $open = $pdo->prepare("SELECT COUNT(*) FROM library_issues WHERE book_id = ? AND status = 'Issued'");
        $open->execute([$bookId]);
        if ((int) $open->fetchColumn() > 0) {
            $_SESSION['error_msg'] = 'Cannot delete — copies are still issued.';
        } else {
            $pdo->prepare("UPDATE library_books SET status = 'Inactive' WHERE id = ?")->execute([$bookId]);
            $_SESSION['success_msg'] = 'Book removed.';
        }
    }
    header('Location: library.php' . libraryIssueQuery($_POST));
    exit;
}

require_once 'includes/header.php';
$stats = getLibraryStats($pdo);
$categories = $pdo->query("SELECT * FROM library_categories WHERE status='Active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$books = $pdo->query(
    "SELECT b.*, c.name AS category_name
     FROM library_books b
     LEFT JOIN library_categories c ON c.id = b.category_id
     WHERE b.status = 'Active'
     ORDER BY b.title"
)->fetchAll(PDO::FETCH_ASSOC);
$issues = $pdo->query(
    "SELECT i.*, b.title, b.author, s.name, s.ad_no, s.class
     FROM library_issues i
     INNER JOIN library_books b ON b.id = i.book_id
     INNER JOIN students s ON s.id = i.student_id
     WHERE i.status = 'Issued'
     ORDER BY i.due_date IS NULL, i.due_date ASC, i.id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$search = trim($_GET['q'] ?? '');
$filterClass = trim($_GET['class'] ?? '');
$filterSection = trim($_GET['section'] ?? '');
$filterSessionId = (int) ($_GET['session_id'] ?? 0);
$filterYear = (int) ($_GET['year'] ?? 0);
$class_options = getClassOptions($pdo);
$sessions = getAllSessions($pdo);
$sectionOptions = $filterClass !== '' ? getSectionOptions($pdo, $filterClass) : [];
$yearOptions = [];
try {
    $yearOptions = $pdo->query("SELECT DISTINCT YEAR(created_at) FROM students WHERE created_at IS NOT NULL ORDER BY YEAR(created_at) DESC")->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
}
$yearOptions = array_values(array_unique(array_filter(array_map('intval', $yearOptions))));
$nowYear = (int) date('Y');
if (!in_array($nowYear, $yearOptions, true)) {
    array_unshift($yearOptions, $nowYear);
}
for ($y = $nowYear; $y >= $nowYear - 8; $y--) {
    if (!in_array($y, $yearOptions, true)) {
        $yearOptions[] = $y;
    }
}
rsort($yearOptions, SORT_NUMERIC);

$hasIssueFilter = $search !== '' || $filterClass !== '' || $filterSessionId > 0 || $filterYear > 0;
$searchResults = [];
if ($hasIssueFilter) {
    $sql = "SELECT id, ad_no, name, class, section, roll FROM students WHERE status = 'Active'";
    $params = [];
    if ($search !== '') {
        $sql .= " AND (name LIKE ? OR ad_no LIKE ? OR roll LIKE ?)";
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
    if ($filterClass !== '') {
        $sql .= " AND class = ?";
        $params[] = $filterClass;
        if ($filterSection !== '') {
            $sql .= " AND section = ?";
            $params[] = $filterSection;
        }
    }
    if ($filterYear > 0) {
        $sql .= " AND (YEAR(created_at) = ? OR ad_no LIKE ?)";
        $params[] = $filterYear;
        $params[] = '%' . $filterYear . '%';
    }
    if ($filterSessionId > 0) {
        $sessionRow = null;
        foreach ($sessions as $sess) {
            if ((int) $sess['id'] === $filterSessionId) {
                $sessionRow = $sess;
                break;
            }
        }
        if ($sessionRow && !empty($sessionRow['start_date'])) {
            $sql .= " AND DATE(created_at) BETWEEN ? AND ?";
            $params[] = $sessionRow['start_date'];
            $params[] = !empty($sessionRow['end_date']) ? $sessionRow['end_date'] : date('Y-m-d');
        }
    }
    $sql .= " ORDER BY class ASC, section ASC, name ASC LIMIT 80";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$availableBooks = array_filter($books, fn($b) => (int) $b['available'] > 0);
?>
<div class="content-top-bar">
    <div class="content-top-main">
        <div class="content-top-icon icon-purple"><i class="fas fa-book"></i></div>
        <div class="content-top-title">
            <h2>Library</h2>
            <p class="content-top-breadcrumb">
                <a href="dashboard.php">Dashboard</a>
                <i class="fas fa-chevron-right"></i>
                <span>Library</span>
            </p>
        </div>
    </div>
    <div class="content-top-actions">
        <a href="students.php" class="btn-header-action btn-header-outline"><i class="fas fa-user-graduate"></i> Students</a>
    </div>
</div>

<div class="cls-stat-strip cols-4">
    <div class="cls-stat-card">
        <div class="cls-stat-icon"><i class="fas fa-book"></i></div>
        <div><span>Titles</span><strong><?php echo $stats['books']; ?></strong></div>
    </div>
    <div class="cls-stat-card">
        <div class="cls-stat-icon cls-stat-blue"><i class="fas fa-layer-group"></i></div>
        <div><span>Copies</span><strong><?php echo $stats['copies']; ?></strong></div>
    </div>
    <div class="cls-stat-card">
        <div class="cls-stat-icon cls-stat-green"><i class="fas fa-check"></i></div>
        <div><span>Available</span><strong><?php echo $stats['available']; ?></strong></div>
    </div>
    <div class="cls-stat-card">
        <div class="cls-stat-icon"><i class="fas fa-hand-holding"></i></div>
        <div><span>Issued<?php echo $stats['overdue'] ? ' · ' . $stats['overdue'] . ' overdue' : ''; ?></span><strong><?php echo $stats['issued']; ?></strong></div>
    </div>
</div>

<div class="details-grid section-mb">
    <div class="form-section-card">
        <div class="section-card-header">
            <div class="section-card-icon section-icon-school"><i class="fas fa-tags"></i></div>
            <div><h4>Add Category</h4><p>Textbook, fiction, reference…</p></div>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add_category">
            <div class="form-grid form-grid-1 form-grid-spaced">
                <div class="form-field"><label>Category Name</label><input type="text" name="name" class="form-input" placeholder="e.g. Science Reference" required></div>
            </div>
            <div class="form-actions-end"><button type="submit" class="btn-header-action btn-header-primary"><i class="fas fa-plus"></i> Add Category</button></div>
        </form>
        <?php if ($categories): ?>
        <div class="table-wrap" style="margin-top:14px">
            <table>
                <thead><tr><th>Category</th><th class="th-actions">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($categories as $c): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($c['name']); ?></strong></td>
                    <td>
                        <div class="table-action-btns">
                            <button type="button" class="action-btn edit-btn" title="Edit"
                                data-erp-edit-lib-cat
                                data-id="<?php echo (int) $c['id']; ?>"
                                data-name="<?php echo htmlspecialchars($c['name'], ENT_QUOTES); ?>">
                                <i class="fas fa-pen"></i>
                            </button>
                            <form method="POST" style="margin:0" onsubmit="return confirm('Delete this category?');">
                                <input type="hidden" name="action" value="delete_category">
                                <input type="hidden" name="id" value="<?php echo (int) $c['id']; ?>">
                                <button type="submit" class="action-btn delete-btn" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <div class="form-section-card">
        <div class="section-card-header">
            <div class="section-card-icon section-icon-school"><i class="fas fa-book"></i></div>
            <div><h4>Add Book</h4><p>Register a title and copies</p></div>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add_book">
            <div class="form-grid form-grid-2 form-grid-spaced">
                <div class="form-field"><label>Title</label><input type="text" name="title" class="form-input" required></div>
                <div class="form-field"><label>Author</label><input type="text" name="author" class="form-input"></div>
                <div class="form-field">
                    <label>Category</label>
                    <select name="category_id" class="form-input">
                        <option value="">— Select —</option>
                        <?php foreach ($categories as $c): ?>
                        <option value="<?php echo (int) $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field"><label>ISBN</label><input type="text" name="isbn" class="form-input"></div>
                <div class="form-field"><label>Publisher</label><input type="text" name="publisher" class="form-input"></div>
                <div class="form-field"><label>Copies</label><input type="number" name="copies" class="form-input" min="1" value="1"></div>
                <div class="form-field"><label>Shelf / Rack</label><input type="text" name="shelf" class="form-input" placeholder="A-12"></div>
            </div>
            <div class="form-actions-end"><button type="submit" class="btn-header-action btn-header-primary"><i class="fas fa-plus"></i> Add Book</button></div>
        </form>
    </div>
</div>

<div class="form-section-card section-mb">
    <div class="section-card-header">
        <div class="section-card-icon section-icon-school"><i class="fas fa-hand-holding"></i></div>
        <div><h4>Issue Book</h4><p>Filter by name, class, section, session and year, then issue a title</p></div>
    </div>
    <form method="GET" class="lib-issue-filter-form">
        <div class="lib-issue-filters">
            <div class="form-field">
                <label><i class="fas fa-user"></i> Name</label>
                <input type="text" name="q" class="form-input" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name, admission or roll">
            </div>
            <div class="form-field">
                <label><i class="fas fa-school"></i> Class</label>
                <select name="class" id="libIssueClass" class="form-input form-select">
                    <option value="">All classes</option>
                    <?php foreach ($class_options as $c): ?>
                    <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $filterClass === $c ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label><i class="fas fa-layer-group"></i> Section</label>
                <select name="section" id="libIssueSection" class="form-input form-select">
                    <option value="">All sections</option>
                    <?php foreach ($sectionOptions as $sec): ?>
                    <option value="<?php echo htmlspecialchars($sec); ?>" <?php echo $filterSection === $sec ? 'selected' : ''; ?>><?php echo htmlspecialchars($sec); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label><i class="fas fa-calendar-alt"></i> Session</label>
                <select name="session_id" class="form-input form-select">
                    <option value="">All sessions</option>
                    <?php foreach ($sessions as $sess): ?>
                    <option value="<?php echo (int) $sess['id']; ?>" <?php echo $filterSessionId === (int) $sess['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($sess['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label><i class="fas fa-calendar"></i> Year</label>
                <select name="year" class="form-input form-select">
                    <option value="">All years</option>
                    <?php foreach ($yearOptions as $y): ?>
                    <option value="<?php echo (int) $y; ?>" <?php echo $filterYear === (int) $y ? 'selected' : ''; ?>><?php echo (int) $y; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field lib-issue-filter-btn">
                <label>&nbsp;</label>
                <button type="submit" class="btn-header-action btn-header-primary"><i class="fas fa-search"></i> Find</button>
            </div>
        </div>
    </form>
    <?php if (!$hasIssueFilter): ?>
    <p class="muted">Use the filters above to find a student.</p>
    <?php elseif (!$searchResults): ?>
    <p class="muted">No students match these filters.</p>
    <?php else: ?>
    <form method="POST">
        <input type="hidden" name="action" value="issue_book">
        <input type="hidden" name="q" value="<?php echo htmlspecialchars($search); ?>">
        <input type="hidden" name="class" value="<?php echo htmlspecialchars($filterClass); ?>">
        <input type="hidden" name="section" value="<?php echo htmlspecialchars($filterSection); ?>">
        <input type="hidden" name="session_id" value="<?php echo (int) $filterSessionId; ?>">
        <input type="hidden" name="year" value="<?php echo (int) $filterYear; ?>">
        <div class="form-grid form-grid-2 form-grid-spaced">
            <div class="form-field">
                <label>Student <span class="muted">(<?php echo count($searchResults); ?> found)</span></label>
                <select name="student_id" class="form-input" required>
                    <?php foreach ($searchResults as $s):
                        $sec = trim((string) ($s['section'] ?? '')) ?: 'A';
                    ?>
                    <option value="<?php echo (int) $s['id']; ?>"><?php echo htmlspecialchars($s['name'] . ' · ' . $s['ad_no'] . ' · ' . $s['class'] . '-' . $sec); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label>Book</label>
                <select name="book_id" class="form-input" required>
                    <option value="">— Select —</option>
                    <?php foreach ($availableBooks as $b): ?>
                    <option value="<?php echo (int) $b['id']; ?>"><?php echo htmlspecialchars($b['title'] . ' (' . $b['available'] . ' left)'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label>Due date</label>
                <input type="date" name="due_date" class="form-input" value="<?php echo date('Y-m-d', strtotime('+14 days')); ?>">
            </div>
        </div>
        <div class="form-actions-end"><button type="submit" class="btn-header-action btn-header-primary"><i class="fas fa-check"></i> Issue Book</button></div>
    </form>
    <?php endif; ?>
</div>

<?php if ($issues): ?>
<div class="form-section-card section-mb">
    <div class="section-card-header">
        <div class="section-card-icon section-icon-school"><i class="fas fa-clock"></i></div>
        <div><h4>Issued books</h4><p>Mark return when the student brings the book back</p></div>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Student</th><th>Adm No</th><th>Book</th><th>Issued</th><th>Due</th><th>Fine (₹)</th><th class="th-actions">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($issues as $i):
                $overdue = !empty($i['due_date']) && $i['due_date'] < date('Y-m-d');
                $suggestedFine = 0;
                if ($overdue) {
                    $days = (int) ((strtotime(date('Y-m-d')) - strtotime($i['due_date'])) / 86400);
                    $perDay = (float) (function_exists('getSetting') ? getSetting($pdo, 'library_fine_per_day', '5') : 5);
                    $suggestedFine = max(0, $days * $perDay);
                }
            ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($i['name']); ?></strong></td>
                <td><?php echo htmlspecialchars($i['ad_no']); ?></td>
                <td><?php echo htmlspecialchars($i['title']); ?></td>
                <td><?php echo htmlspecialchars($i['issue_date']); ?></td>
                <td><?php echo htmlspecialchars($i['due_date'] ?: '—'); ?><?php echo $overdue ? ' <span class="badge-cancelled">Overdue</span>' : ''; ?></td>
                <td>
                    <input type="number" step="0.01" min="0" form="return-<?php echo (int) $i['id']; ?>" name="fine" class="form-input" style="width:90px" value="<?php echo $suggestedFine > 0 ? $suggestedFine : ''; ?>" placeholder="0">
                </td>
                <td style="white-space:nowrap">
                    <button type="submit" form="return-<?php echo (int) $i['id']; ?>" class="action-btn" title="Return book" onclick="return confirm('Mark this book as returned?');"><i class="fas fa-undo"></i></button>
                    <button type="submit" form="lost-<?php echo (int) $i['id']; ?>" class="action-btn delete-btn" title="Mark lost" onclick="var f=document.getElementById('lost-fine-<?php echo (int) $i['id']; ?>'); var src=document.querySelector('#return-<?php echo (int) $i['id']; ?> input[name=fine]'); if(f&&src) f.value=src.value||200; return confirm('Mark this book as LOST and charge fine?');"><i class="fas fa-book-dead"></i></button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="form-section-card">
    <div class="section-card-header">
        <div class="section-card-icon section-icon-school"><i class="fas fa-list"></i></div>
        <div><h4>Book catalogue</h4><p><?php echo count($books); ?> active titles</p></div>
    </div>
    <?php if (!$books): ?>
    <p class="muted">No books yet. Add a title above.</p>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Title</th><th>Author</th><th>Category</th><th>Shelf</th><th>Copies</th><th>Available</th><th class="th-actions">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($books as $b): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($b['title']); ?></strong><?php if ($b['isbn']): ?><div class="muted"><?php echo htmlspecialchars($b['isbn']); ?></div><?php endif; ?></td>
                <td><?php echo htmlspecialchars($b['author'] ?: '—'); ?></td>
                <td><?php echo htmlspecialchars($b['category_name'] ?: '—'); ?></td>
                <td><?php echo htmlspecialchars($b['shelf'] ?: '—'); ?></td>
                <td><?php echo (int) $b['copies']; ?></td>
                <td><?php echo (int) $b['available']; ?></td>
                <td>
                    <div class="table-action-btns">
                        <button type="button" class="action-btn edit-btn" title="Edit"
                            data-erp-edit-book
                            data-id="<?php echo (int) $b['id']; ?>"
                            data-title="<?php echo htmlspecialchars($b['title'], ENT_QUOTES); ?>"
                            data-author="<?php echo htmlspecialchars($b['author'] ?? '', ENT_QUOTES); ?>"
                            data-category="<?php echo (int) ($b['category_id'] ?? 0); ?>"
                            data-isbn="<?php echo htmlspecialchars($b['isbn'] ?? '', ENT_QUOTES); ?>"
                            data-publisher="<?php echo htmlspecialchars($b['publisher'] ?? '', ENT_QUOTES); ?>"
                            data-copies="<?php echo (int) $b['copies']; ?>"
                            data-shelf="<?php echo htmlspecialchars($b['shelf'] ?? '', ENT_QUOTES); ?>">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button type="submit" form="book-delete-<?php echo (int) $b['id']; ?>" class="action-btn delete-btn" title="Remove book" onclick="return confirm('Remove this book from catalogue?');"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php foreach ($issues as $i): ?>
<form method="POST" id="return-<?php echo (int) $i['id']; ?>" class="hidden-form">
    <input type="hidden" name="action" value="return_book">
    <input type="hidden" name="issue_id" value="<?php echo (int) $i['id']; ?>">
</form>
<form method="POST" id="lost-<?php echo (int) $i['id']; ?>" class="hidden-form">
    <input type="hidden" name="action" value="mark_lost">
    <input type="hidden" name="issue_id" value="<?php echo (int) $i['id']; ?>">
    <input type="hidden" name="fine" id="lost-fine-<?php echo (int) $i['id']; ?>" value="200">
</form>
<?php endforeach; ?>
<?php foreach ($books as $b): ?>
<form method="POST" id="book-delete-<?php echo (int) $b['id']; ?>" class="hidden-form">
    <input type="hidden" name="action" value="delete_book">
    <input type="hidden" name="id" value="<?php echo (int) $b['id']; ?>">
</form>
<?php endforeach; ?>

<div class="fs-modal" id="bookEditModal" aria-hidden="true">
    <div class="fs-modal-backdrop" data-book-modal-close></div>
    <div class="fs-modal-panel" role="dialog" aria-modal="true" aria-labelledby="bookEditModalTitle">
        <div class="fs-modal-header">
            <div class="fs-modal-header-icon is-edit"><i class="fas fa-book"></i></div>
            <div>
                <h3 id="bookEditModalTitle">Edit Book</h3>
                <p>Update catalogue details and copies</p>
            </div>
            <button type="button" class="fs-modal-close" data-book-modal-close aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="fs-modal-form">
            <input type="hidden" name="action" value="update_book">
            <input type="hidden" name="id" id="bookEditId" value="">
            <div class="fs-modal-body">
                <div class="form-field"><label for="bookEditTitle">Title</label><input type="text" name="title" id="bookEditTitle" class="form-input" required></div>
                <div class="form-field"><label for="bookEditAuthor">Author</label><input type="text" name="author" id="bookEditAuthor" class="form-input"></div>
                <div class="form-field"><label for="bookEditCategory">Category</label>
                    <select name="category_id" id="bookEditCategory" class="form-input form-select">
                        <option value="">—</option>
                        <?php foreach ($categories as $c): ?>
                        <option value="<?php echo (int) $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field"><label for="bookEditIsbn">ISBN</label><input type="text" name="isbn" id="bookEditIsbn" class="form-input"></div>
                <div class="form-field"><label for="bookEditPublisher">Publisher</label><input type="text" name="publisher" id="bookEditPublisher" class="form-input"></div>
                <div class="form-field"><label for="bookEditCopies">Copies</label><input type="number" name="copies" id="bookEditCopies" class="form-input" min="1" required></div>
                <div class="form-field"><label for="bookEditShelf">Shelf</label><input type="text" name="shelf" id="bookEditShelf" class="form-input"></div>
            </div>
            <div class="fs-modal-footer">
                <button type="button" class="btn-header-action btn-header-outline" data-book-modal-close>Cancel</button>
                <button type="submit" class="btn-header-action btn-header-primary"><i class="fas fa-check"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<div class="fs-modal" id="libCatEditModal" aria-hidden="true">
    <div class="fs-modal-backdrop" data-libcat-modal-close></div>
    <div class="fs-modal-panel" role="dialog" aria-modal="true" aria-labelledby="libCatEditModalTitle">
        <div class="fs-modal-header">
            <div class="fs-modal-header-icon is-edit"><i class="fas fa-tags"></i></div>
            <div>
                <h3 id="libCatEditModalTitle">Edit Category</h3>
                <p>Update library category name</p>
            </div>
            <button type="button" class="fs-modal-close" data-libcat-modal-close aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="fs-modal-form">
            <input type="hidden" name="action" value="update_category">
            <input type="hidden" name="id" id="libCatEditId" value="">
            <div class="fs-modal-body">
                <div class="form-field"><label for="libCatEditName">Category Name</label><input type="text" name="name" id="libCatEditName" class="form-input" required></div>
            </div>
            <div class="fs-modal-footer">
                <button type="button" class="btn-header-action btn-header-outline" data-libcat-modal-close>Cancel</button>
                <button type="submit" class="btn-header-action btn-header-primary"><i class="fas fa-check"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>
<script>
(function () {
    var classSel = document.getElementById('libIssueClass');
    var sectionSel = document.getElementById('libIssueSection');
    if (classSel && sectionSel) {
        classSel.addEventListener('change', function () {
            var cls = this.value;
            var current = sectionSel.value;
            sectionSel.innerHTML = '<option value="">All sections</option>';
            if (!cls) return;
            fetch('library.php?action=sections&class=' + encodeURIComponent(cls))
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    (data.sections || []).forEach(function (sec) {
                        var opt = document.createElement('option');
                        opt.value = sec;
                        opt.textContent = sec;
                        if (sec === current) opt.selected = true;
                        sectionSel.appendChild(opt);
                    });
                })
                .catch(function () {});
        });
    }

    function wire(modalId, openSel, closeSel, fill, focusId) {
        var modal = document.getElementById(modalId);
        if (!modal) return;
        if (modal.parentElement !== document.body) document.body.appendChild(modal);
        function open() {
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('fs-modal-open');
            var el = document.getElementById(focusId);
            if (el) setTimeout(function () { el.focus(); if (el.select) el.select(); }, 120);
        }
        function close() {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            if (!document.querySelector('.fs-modal.is-open')) document.body.classList.remove('fs-modal-open');
        }
        document.querySelectorAll(openSel).forEach(function (btn) {
            btn.addEventListener('click', function () { fill(btn); open(); });
        });
        modal.querySelectorAll(closeSel).forEach(function (el) { el.addEventListener('click', close); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal.classList.contains('is-open')) close();
        });
    }
    wire('bookEditModal', '[data-erp-edit-book]', '[data-book-modal-close]', function (btn) {
        document.getElementById('bookEditId').value = btn.getAttribute('data-id') || '';
        document.getElementById('bookEditTitle').value = btn.getAttribute('data-title') || '';
        document.getElementById('bookEditAuthor').value = btn.getAttribute('data-author') || '';
        document.getElementById('bookEditCategory').value = btn.getAttribute('data-category') || '';
        document.getElementById('bookEditIsbn').value = btn.getAttribute('data-isbn') || '';
        document.getElementById('bookEditPublisher').value = btn.getAttribute('data-publisher') || '';
        document.getElementById('bookEditCopies').value = btn.getAttribute('data-copies') || '1';
        document.getElementById('bookEditShelf').value = btn.getAttribute('data-shelf') || '';
    }, 'bookEditTitle');
    wire('libCatEditModal', '[data-erp-edit-lib-cat]', '[data-libcat-modal-close]', function (btn) {
        document.getElementById('libCatEditId').value = btn.getAttribute('data-id') || '';
        document.getElementById('libCatEditName').value = btn.getAttribute('data-name') || '';
    }, 'libCatEditName');
})();
</script>
<?php require_once 'includes/footer.php'; ?>
