<?php
// admin/students.php
$page_title = "Student List";
require_once 'includes/header.php';
require_once '../includes/db_connect.php';

// Fetch students from the database
try {
    $stmt = $pdo->query("SELECT * FROM students ORDER BY id ASC");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database query failed: " . $e->getMessage());
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2 style="color: var(--text-dark); margin-bottom: 5px; font-size: 1.5rem; font-weight: 600;">Student List</h2>
        <div style="color: var(--text-muted); font-size: 0.9rem;">Dashboard / Student List</div>
    </div>
    <button class="btn-admin" style="background-color: var(--green-active); width: auto; padding: 10px 20px; border-radius: 6px;"><i class="fas fa-plus"></i> Add Student</button>
</div>

<div class="table-container">
    <div class="table-toolbar">
        <div class="toolbar-left">
            <a href="student_export.php" class="toolbar-btn" style="text-decoration: none; color: var(--text-dark);">
                <i class="far fa-file-excel" style="color: #10b981;"></i> Export to Excel
            </a>
            <div class="toolbar-search">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search students...">
            </div>
        </div>
        <div class="toolbar-right">
            <button class="toolbar-btn">
                Filter <i class="fas fa-chevron-down" style="font-size: 0.7rem; margin-left: 5px;"></i>
            </button>
            <div style="display: flex; align-items: center; gap: 10px; color: var(--text-muted); font-size: 0.9rem;">
                Rows per page:
                <select class="toolbar-select" id="rowsPerPageSelect">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="1000">All</option>
                </select>
            </div>
        </div>
    </div>
    
    <div class="table-wrapper">
        <?php if (count($students) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th style="width: 40px;"><input type="checkbox" class="custom-checkbox" id="selectAll"></th>
                    <th>S.L <i class="fas fa-sort" style="color: #cbd5e1; margin-left: 5px; font-size: 0.8rem;"></i></th>
                    <th>Admission No</th>
                    <th>Name <i class="fas fa-sort" style="color: #cbd5e1; margin-left: 5px; font-size: 0.8rem;"></i></th>
                    <th>Class <i class="fas fa-sort" style="color: #cbd5e1; margin-left: 5px; font-size: 0.8rem;"></i></th>
                    <th>Mobile Number <i class="fas fa-sort" style="color: #cbd5e1; margin-left: 5px; font-size: 0.8rem;"></i></th>
                    <th>Status <i class="fas fa-sort" style="color: #cbd5e1; margin-left: 5px; font-size: 0.8rem;"></i></th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="studentTableBody">
                <?php foreach($students as $student): ?>
                <tr class="student-row">
                    <td><input type="checkbox" class="custom-checkbox row-checkbox" value="<?php echo $student['id']; ?>"></td>
                    <td><?php echo str_pad($student['id'], 2, '0', STR_PAD_LEFT); ?></td>
                    <td><a href="#" class="teal-link"><?php echo htmlspecialchars($student['ad_no']); ?></a></td>
                    <td>
                        <div class="student-name-cell">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($student['name']); ?>&background=random" alt="Avatar">
                            <div class="name-info">
                                <strong><?php echo htmlspecialchars($student['name']); ?></strong>
                                <span>Roll No: <?php echo htmlspecialchars($student['roll']); ?></span>
                            </div>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($student['class']); ?></td>
                    <td><?php echo htmlspecialchars($student['mobile']); ?></td>
                    <td>
                        <span class="status-badge <?php echo $student['status'] == 'Active' ? 'badge-active' : 'badge-inactive'; ?>">
                            <?php echo htmlspecialchars($student['status']); ?>
                        </span>
                    </td>
                    <td>
                        <div style="display: flex; gap: 8px;">
                            <a href="student_view.php?id=<?php echo $student['id']; ?>" class="action-btn view-btn" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="student_edit.php?id=<?php echo $student['id']; ?>" class="action-btn edit-btn" title="Edit">
                                <i class="fas fa-pen"></i>
                            </a>
                            <a href="student_delete.php?id=<?php echo $student['id']; ?>" class="action-btn delete-btn btn-delete-confirm" title="Delete">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <tr id="noResultsRow" style="display: none;">
                    <td colspan="8" class="no-results-cell">
                        <div class="empty-state" style="padding: 40px 20px;">
                            <i class="fas fa-search empty-state-icon" style="font-size: 3rem;"></i>
                            <h3>No matching records found</h3>
                            <p>We couldn't find any students matching your search criteria.</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-user-graduate empty-state-icon"></i>
            <h3>No Students Found</h3>
            <p>It looks like there are currently no students in the database. Add a new student to start managing their records here.</p>
            <button class="btn-admin" style="width: auto; padding: 10px 20px; border-radius: 6px;">
                <i class="fas fa-plus"></i> Add First Student
            </button>
        </div>
        <?php endif; ?>
    </div>

    <?php if (count($students) > 0): ?>
    <div class="table-footer">
        <div class="showing-entries" id="showingEntriesText">
            Showing 1 to <?php echo count($students); ?> of <?php echo count($students); ?> entries
        </div>
        <div class="pagination">
            <div class="page-item"><i class="fas fa-angle-double-left" style="font-size: 0.7rem;"></i></div>
            <div class="page-item"><i class="fas fa-angle-left" style="font-size: 0.8rem;"></i></div>
            <div class="page-item active">1</div>
            <div class="page-item"><i class="fas fa-angle-right" style="font-size: 0.8rem;"></i></div>
            <div class="page-item"><i class="fas fa-angle-double-right" style="font-size: 0.7rem;"></i></div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Checkbox Select All Logic
    const selectAll = document.getElementById('selectAll');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            rowCheckboxes.forEach(cb => {
                // Only check visible rows
                if (cb.closest('tr').style.display !== 'none') {
                    cb.checked = selectAll.checked;
                }
            });
        });

        rowCheckboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                const visibleCheckboxes = Array.from(rowCheckboxes).filter(c => c.closest('tr').style.display !== 'none');
                const allChecked = visibleCheckboxes.every(c => c.checked);
                const someChecked = visibleCheckboxes.some(c => c.checked);
                selectAll.checked = allChecked && visibleCheckboxes.length > 0;
                selectAll.indeterminate = someChecked && !allChecked;
            });
        });
    }

    // 2. Search and Pagination Logic
    const searchInput = document.getElementById('searchInput');
    const rowsSelect = document.getElementById('rowsPerPageSelect');
    const tableRows = document.querySelectorAll('#studentTableBody tr.student-row');
    const entriesText = document.getElementById('showingEntriesText');
    const noResultsRow = document.getElementById('noResultsRow');

    function applyFilters() {
        if (!searchInput || !rowsSelect) return;
        
        const searchTerm = searchInput.value.toLowerCase();
        const limit = parseInt(rowsSelect.value);
        let matchCount = 0;
        let visibleCount = 0;

        tableRows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const matchesSearch = text.includes(searchTerm);

            if (matchesSearch) {
                matchCount++;
                if (visibleCount < limit) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            } else {
                row.style.display = 'none';
                // uncheck hidden rows
                const cb = row.querySelector('.row-checkbox');
                if (cb) cb.checked = false;
            }
        });

        // Show/Hide no results row
        if (noResultsRow) {
            if (matchCount === 0 && tableRows.length > 0) {
                noResultsRow.style.display = '';
            } else {
                noResultsRow.style.display = 'none';
            }
        }

        // Update footer text
        if (entriesText) {
            if (matchCount === 0) {
                entriesText.textContent = `No entries found matching "${searchInput.value}"`;
            } else {
                entriesText.textContent = `Showing 1 to ${visibleCount} of ${matchCount} entries`;
            }
        }
        
        // Reset select all state when filter changes
        if (selectAll) {
            selectAll.checked = false;
            selectAll.indeterminate = false;
        }
    }

    if (searchInput) searchInput.addEventListener('input', applyFilters);
    if (rowsSelect) rowsSelect.addEventListener('change', applyFilters);
});
</script>

<?php require_once 'includes/footer.php'; ?>
