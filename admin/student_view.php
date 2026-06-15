<?php
// admin/student_view.php
$page_title = "Student Details";
require_once 'includes/header.php';
require_once '../includes/db_connect.php';

if (!isset($_GET['id'])) {
    echo "<script>window.location.href='students.php';</script>";
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    echo "<div class='table-container' style='padding: 30px;'><h3>Student not found.</h3></div>";
    require_once 'includes/footer.php';
    exit;
}
?>

<div class="page-header-actions">
    <div>
        <h2 style="margin: 0; color: var(--text-dark); font-size: 1.5rem;">Student Details</h2>
        <div class="breadcrumb">
            <a href="dashboard.php" style="color: var(--text-muted); text-decoration: none;">Dashboard</a> / 
            <a href="students.php" style="color: var(--text-muted); text-decoration: none;">Student</a> / 
            <span style="color: var(--text-dark);">Student Details</span>
        </div>
    </div>
    <a href="#" class="btn-outline">
        <i class="fas fa-lock"></i> Login Details
    </a>
</div>

<!-- Top Section -->
<div class="student-view-container">
    <!-- Left Sidebar -->
    <div class="profile-sidebar">
        <div class="avatar-wrapper">
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($student['name']); ?>&background=random&size=150" alt="Avatar">
        </div>
        <h3><?php echo htmlspecialchars($student['name']); ?></h3>
        
        <div class="student-badge-info">
            <span class="badge badge-adno">Admission No: <?php echo htmlspecialchars($student['ad_no']); ?></span>
            <span class="badge badge-roll">Roll No: <?php echo htmlspecialchars($student['roll']); ?></span>
        </div>
        
        <div class="action-buttons-group">
            <a href="#" class="btn-outline suspend">
                <i class="far fa-window-close"></i> Suspend
            </a>
            <a href="student_edit.php?id=<?php echo $student['id']; ?>" class="btn-admin">
                <i class="fas fa-pen"></i> Edit
            </a>
        </div>
    </div>
    
    <!-- Right Personal Info -->
    <div class="personal-info-card">
        <div class="info-header">
            <h3>Personal Info</h3>
            <span class="status-badge <?php echo $student['status'] == 'Active' ? 'badge-active' : 'badge-inactive'; ?>" style="font-size: 0.9rem; padding: 6px 15px;">
                <?php echo htmlspecialchars($student['status']); ?>
            </span>
        </div>
        
        <div class="info-list">
            <div class="info-row-item">
                <div class="label">Class</div>
                <div class="value"><?php echo htmlspecialchars($student['class']); ?></div>
            </div>
            
            <div class="info-row-item">
                <div class="label">Section</div>
                <div class="value">A</div>
            </div>
            
            <div class="info-row-item">
                <div class="label">Roll No</div>
                <div class="value"><?php echo htmlspecialchars($student['roll']); ?></div>
            </div>
            
            <div class="info-row-item">
                <div class="label">Gender</div>
                <div class="value"><?php echo htmlspecialchars($student['gender']); ?></div>
            </div>
            
            <div class="info-row-item">
                <div class="label">Date Of Birth</div>
                <div class="value"><?php echo htmlspecialchars($student['dob']); ?></div>
            </div>
            
            <div class="info-row-item">
                <div class="label">Category</div>
                <div class="value"><?php echo htmlspecialchars($student['category']); ?></div>
            </div>
            
            <div class="info-row-item" style="border-bottom: none;">
                <div class="label">Academic Year</div>
                <div class="value">Jun 2025/2026</div>
            </div>
            
            <div class="info-row-item" style="border-bottom: none;">
                <div class="label">Phone Number</div>
                <div class="value teal-text"><?php echo htmlspecialchars($student['mobile']); ?></div>
            </div>
            
            <div class="info-row-item" style="grid-column: 1 / -1; border-bottom: none; border-top: 1px dashed #e2e8f0; padding-top: 15px;">
                <div class="label">Email</div>
                <div class="value teal-text"><?php echo strtolower(str_replace(' ', '', $student['name'])); ?>@example.com</div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs -->
<div class="custom-tabs">
    <a href="#" class="tab-link active" data-target="tab-details"><i class="far fa-user"></i> Student Details</a>
    <a href="#" class="tab-link" data-target="tab-attendance"><i class="far fa-calendar-check"></i> Attendance</a>
    <a href="#" class="tab-link" data-target="tab-leave"><i class="far fa-clock"></i> Leave</a>
    <a href="#" class="tab-link" data-target="tab-fees"><i class="fas fa-file-invoice-dollar"></i> Fees</a>
    <a href="#" class="tab-link" data-target="tab-exam"><i class="far fa-edit"></i> Exam</a>
    <a href="#" class="tab-link" data-target="tab-library"><i class="fas fa-book"></i> Library</a>
</div>

<div class="tab-content-container">

<!-- Details Tab Content -->
<div id="tab-details" class="tab-pane active">
    <!-- Parent Details -->
    <div class="data-card" style="margin-bottom: 25px;">
    <h4><i class="fas fa-users"></i> Parent Guardian Detail</h4>
    
    <div class="details-grid full-width" style="margin-bottom: 0;">
        <div class="parent-row">
            <div class="parent-avatar">
                <img src="https://ui-avatars.com/api/?name=Robert+Fox&background=f1f5f9" alt="Father">
            </div>
            <div class="parent-info-block">
                <strong>Robert Fox</strong>
                <span>Father</span>
            </div>
            <div class="parent-info-block">
                <strong>Phone</strong>
                <span>+1 9854 65642</span>
            </div>
            <div class="parent-info-block">
                <strong>Email</strong>
                <span>father@example.com</span>
            </div>
        </div>
        
        <div class="parent-row">
            <div class="parent-avatar">
                <img src="https://ui-avatars.com/api/?name=Brooklyn+Simmons&background=f1f5f9" alt="Mother">
            </div>
            <div class="parent-info-block">
                <strong>Brooklyn Simmons</strong>
                <span>Mother</span>
            </div>
            <div class="parent-info-block">
                <strong>Phone</strong>
                <span>+1 9854 65642</span>
            </div>
            <div class="parent-info-block">
                <strong>Email</strong>
                <span>mother@example.com</span>
            </div>
        </div>
        
        <div class="parent-row">
            <div class="parent-avatar">
                <img src="https://ui-avatars.com/api/?name=Robert+Fox&background=f1f5f9" alt="Guardian">
            </div>
            <div class="parent-info-block">
                <strong>Robert Fox</strong>
                <span>Guardian (Father)</span>
            </div>
            <div class="parent-info-block">
                <strong>Phone</strong>
                <span>+1 9854 65642</span>
            </div>
            <div class="parent-info-block">
                <strong>Email</strong>
                <span>father@example.com</span>
            </div>
        </div>
    </div>
</div>

<!-- 2 Column Grids -->
<div class="details-grid">
    <!-- Previous School -->
    <div class="data-card">
        <h4><i class="fas fa-school"></i> Previous School Details</h4>
        <div class="card-data-grid">
            <div class="card-data-col">
                <strong>Previous School Name</strong>
                <span>Stuyvesant High School</span>
            </div>
            <div class="card-data-col">
                <strong>Current School Name</strong>
                <span>Bronx High School of Science</span>
            </div>
        </div>
    </div>
    
    <!-- Address -->
    <div class="data-card">
        <h4><i class="fas fa-map-marker-alt"></i> Address</h4>
        <div class="card-data-grid" style="flex-direction: column; gap: 15px;">
            <div class="card-data-col">
                <strong>Current Address</strong>
                <span>8502 Preston Rd. Inglewood, Maine 98380</span>
            </div>
            <div class="card-data-col">
                <strong>Permanent Address</strong>
                <span>2118 Thornridge Cir. Syracuse, Connecticut 35624</span>
            </div>
        </div>
    </div>
</div>

<div class="details-grid">
    <!-- Bank Details -->
    <div class="data-card">
        <h4><i class="fas fa-university"></i> Bank Details</h4>
        <div class="card-data-grid">
            <div class="card-data-col">
                <strong>Bank Name</strong>
                <span>Bank of America</span>
            </div>
            <div class="card-data-col">
                <strong>Branch</strong>
                <span>New York</span>
            </div>
            <div class="card-data-col">
                <strong>IFSC Code</strong>
                <span>5283209832</span>
            </div>
        </div>
    </div>
    
    <!-- Medical Details -->
    <div class="data-card">
        <h4><i class="fas fa-heartbeat"></i> Medical Details</h4>
        <div class="card-data-grid">
            <div class="card-data-col">
                <strong>Blood Group</strong>
                <span>O+</span>
            </div>
            <div class="card-data-col">
                <strong>Height</strong>
                <span>5.2</span>
            </div>
            <div class="card-data-col">
                <strong>Weight</strong>
                <span>60kg</span>
            </div>
        </div>
    </div>
</div>

<div class="details-grid">
    <!-- Documents -->
    <div class="data-card">
        <h4><i class="fas fa-file-invoice"></i> Documents</h4>
        <div class="document-box">
            <div class="doc-name">
                <i class="far fa-file-alt" style="color: #cbd5e1; font-size: 1.2rem;"></i> BirthCertificate.pdf
            </div>
            <i class="fas fa-download download-icon"></i>
        </div>
    </div>
    
    <!-- Hostel -->
    <div class="data-card">
        <h4><i class="fas fa-bed"></i> Hostel</h4>
        <div class="card-data-grid">
            <div class="card-data-col">
                <strong>Hostel</strong>
                <span>Boys Hostel 101</span>
            </div>
            <div class="card-data-col">
                <strong>Room No.</strong>
                <span>Room No.</span>
            </div>
            <div class="card-data-col">
                <strong>Room Type</strong>
                <span>One Bed</span>
            </div>
        </div>
    </div>
</div>

    <!-- Description -->
    <div class="data-card" style="margin-bottom: 40px;">
        <h4><i class="fas fa-align-left"></i> Description</h4>
        <p class="description-text">
            Known for their punctuality and positive attitude, [he/she/they] consistently demonstrates a strong commitment to academic excellence and co-curricular participation. [He/She/They] maintains good behavior, shows respect toward teachers and peers, and actively engages in classroom discussions and group activities.
        </p>
    </div>
</div> <!-- End tab-details -->

<!-- Attendance Tab Content -->
<div id="tab-attendance" class="tab-pane" style="display: none;">
    <div class="data-card" style="margin-bottom: 40px; text-align: center; padding: 60px 20px;">
        <i class="far fa-calendar-check" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 15px;"></i>
        <h3 style="margin: 0 0 10px; color: #1e293b;">Attendance Records</h3>
        <p style="color: #64748b;">No attendance records found for this student yet.</p>
    </div>
</div>

<!-- Leave Tab Content -->
<div id="tab-leave" class="tab-pane" style="display: none;">
    <div class="data-card" style="margin-bottom: 40px; text-align: center; padding: 60px 20px;">
        <i class="far fa-clock" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 15px;"></i>
        <h3 style="margin: 0 0 10px; color: #1e293b;">Leave History</h3>
        <p style="color: #64748b;">This student has not applied for any leave.</p>
    </div>
</div>

<!-- Fees Tab Content -->
<div id="tab-fees" class="tab-pane" style="display: none;">
    <div class="data-card" style="margin-bottom: 40px; text-align: center; padding: 60px 20px;">
        <i class="fas fa-file-invoice-dollar" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 15px;"></i>
        <h3 style="margin: 0 0 10px; color: #1e293b;">Fee Collection</h3>
        <p style="color: #64748b;">No fee records are available at this moment.</p>
    </div>
</div>

<!-- Exam Tab Content -->
<div id="tab-exam" class="tab-pane" style="display: none;">
    <div class="data-card" style="margin-bottom: 40px; text-align: center; padding: 60px 20px;">
        <i class="far fa-edit" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 15px;"></i>
        <h3 style="margin: 0 0 10px; color: #1e293b;">Exam Results</h3>
        <p style="color: #64748b;">Exam marks and report cards will appear here.</p>
    </div>
</div>

<!-- Library Tab Content -->
<div id="tab-library" class="tab-pane" style="display: none;">
    <div class="data-card" style="margin-bottom: 40px; text-align: center; padding: 60px 20px;">
        <i class="fas fa-book" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 15px;"></i>
        <h3 style="margin: 0 0 10px; color: #1e293b;">Library Books</h3>
        <p style="color: #64748b;">No books are currently issued to this student.</p>
    </div>
</div>

</div> <!-- End tab-content-container -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabPanes = document.querySelectorAll('.tab-pane');

    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active from all
            tabLinks.forEach(t => t.classList.remove('active'));
            tabPanes.forEach(p => p.style.display = 'none');
            
            // Add active to clicked
            this.classList.add('active');
            const targetId = this.getAttribute('data-target');
            const targetPane = document.getElementById(targetId);
            if (targetPane) {
                targetPane.style.display = 'block';
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
