<?php
$page_title = 'My Profile';
$page_subtitle = 'Your personal & professional information';
require_once 'includes/init.php';
require_once 'includes/layout_header.php';

$class_assigned = $teacher['class_assigned']
    ? htmlspecialchars($teacher['class_assigned'] . ' (' . ($teacher['section_assigned'] ?: 'A') . ')')
    : 'Not assigned';
?>

<div class="tp-profile-header">
    <img src="<?php echo htmlspecialchars($tp_photo); ?>" alt="<?php echo $tp_name; ?>">
    <div>
        <h2><?php echo $tp_name; ?></h2>
        <p style="margin:0;color:var(--tp-muted)"><?php echo htmlspecialchars($teacher['subject']); ?> Teacher · <?php echo htmlspecialchars($teacher['employee_id']); ?></p>
        <div class="tp-profile-meta">
            <span class="tp-meta-chip"><i class="fas fa-circle" style="font-size:6px;vertical-align:middle"></i> <?php echo htmlspecialchars($teacher['status']); ?></span>
            <?php if ($teacher['qualification']): ?><span class="tp-meta-chip"><i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($teacher['qualification']); ?></span><?php endif; ?>
            <?php if ($teacher['experience_years']): ?><span class="tp-meta-chip"><i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($teacher['experience_years']); ?></span><?php endif; ?>
        </div>
    </div>
</div>

<div class="tp-grid-2">
    <div class="tp-card">
        <div class="tp-card-head"><h3><i class="fas fa-user"></i> Personal Details</h3></div>
        <div class="tp-detail-grid">
            <div class="tp-detail-item"><label>Full Name</label><span><?php echo $tp_name; ?></span></div>
            <div class="tp-detail-item"><label>Gender</label><span><?php echo displayVal($teacher['gender']); ?></span></div>
            <div class="tp-detail-item"><label>Date of Birth</label><span><?php echo $teacher['dob'] ? date('d M Y', strtotime($teacher['dob'])) : '—'; ?></span></div>
            <div class="tp-detail-item"><label>Join Date</label><span><?php echo $teacher['join_date'] ? date('d M Y', strtotime($teacher['join_date'])) : '—'; ?></span></div>
            <div class="tp-detail-item"><label>Mobile</label><span><?php echo htmlspecialchars($teacher['phone']); ?></span></div>
            <div class="tp-detail-item"><label>Email</label><span><?php echo displayVal($teacher['email']); ?></span></div>
        </div>
    </div>
    <div class="tp-card">
        <div class="tp-card-head"><h3><i class="fas fa-chalkboard"></i> Professional</h3></div>
        <div class="tp-detail-grid">
            <div class="tp-detail-item"><label>Primary Subject</label><span><?php echo htmlspecialchars($teacher['subject']); ?></span></div>
            <div class="tp-detail-item"><label>Qualification</label><span><?php echo displayVal($teacher['qualification']); ?></span></div>
            <div class="tp-detail-item"><label>Experience</label><span><?php echo displayVal($teacher['experience_years']); ?></span></div>
            <div class="tp-detail-item"><label>Class Assigned</label><span><?php echo $class_assigned; ?></span></div>
        </div>
    </div>
</div>

<div class="tp-grid-2">
    <div class="tp-card">
        <div class="tp-card-head"><h3><i class="fas fa-map-marker-alt"></i> Address</h3></div>
        <div class="tp-detail-grid">
            <div class="tp-detail-item tp-field-full" style="grid-column:1/-1"><label>Street</label><span><?php echo displayVal($teacher['address']); ?></span></div>
            <div class="tp-detail-item"><label>City</label><span><?php echo displayVal($teacher['city']); ?></span></div>
            <div class="tp-detail-item"><label>State</label><span><?php echo displayVal($teacher['state']); ?></span></div>
            <div class="tp-detail-item"><label>Pincode</label><span><?php echo displayVal($teacher['pincode']); ?></span></div>
        </div>
    </div>
    <div class="tp-card">
        <div class="tp-card-head"><h3><i class="fas fa-university"></i> Bank Details</h3></div>
        <div class="tp-detail-grid">
            <div class="tp-detail-item"><label>Bank Name</label><span><?php echo displayVal($teacher['bank_name']); ?></span></div>
            <div class="tp-detail-item"><label>Account No.</label><span><?php echo displayVal($teacher['bank_account']); ?></span></div>
            <div class="tp-detail-item"><label>IFSC</label><span><?php echo displayVal($teacher['ifsc_code']); ?></span></div>
            <div class="tp-detail-item"><label>Salary</label><span><?php echo $teacher['salary'] ? 'Rs. ' . number_format($teacher['salary'], 2) : '—'; ?></span></div>
        </div>
    </div>
</div>

<?php if ($teacher['description']): ?>
<div class="tp-card">
    <div class="tp-card-head"><h3><i class="fas fa-align-left"></i> Remarks</h3></div>
    <p style="margin:0;line-height:1.6;color:#475569"><?php echo nl2br(htmlspecialchars($teacher['description'])); ?></p>
</div>
<?php endif; ?>

<?php require_once 'includes/layout_footer.php'; ?>
