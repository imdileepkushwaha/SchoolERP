<?php
// admin/teachers.php
$page_title = "Manage Teachers";
require_once 'includes/header.php';
?>

<div class="table-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="color: var(--admin-primary);">Teacher List</h3>
        <button class="btn-admin" style="width: auto; padding: 10px 20px;"><i class="fas fa-plus"></i> Add New Teacher</button>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Employee ID</th>
                <th>Subject</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="5" style="text-align: center;">No teachers found.</td>
            </tr>
        </tbody>
    </table>
</div>

</main>
</div>
</body>
</html>
