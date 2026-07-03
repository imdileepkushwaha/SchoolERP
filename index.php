<?php
session_start();
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/admin/includes/erp_helpers.php';
require_once __DIR__ . '/admin/includes/settings_helpers.php';

ensureErpSchema($pdo);
ensureSettingsSchema($pdo);
$school = getSchoolProfile($pdo);
$logoUrl = schoolBrandingUrl($school['logo'] ?? '', 'portal');
$faviconUrl = schoolBrandingUrl($school['favicon'] ?? '', 'portal');
$brandName = $school['name'] ?: 'SchoolERP';
$brandTag = $school['tagline'] ?: 'Complete school management platform';
$brandPhone = $school['phone'] ?: '+91 98765 43210';
$brandEmail = $school['email'] ?: 'info@schoolerp.com';
$brandAddress = $school['address'] ?: 'Your city, India';

$leadSuccess = false;
$leadError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_demo'])) {
    $name = trim($_POST['student_name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $classSought = trim($_POST['class_sought'] ?? '');
    $message = trim($_POST['message'] ?? '');
    if ($name === '' || $mobile === '') {
        $leadError = 'Please share your name and mobile so we can reach you.';
    } else {
        try {
            $pdo->prepare(
                "INSERT INTO admission_enquiries (student_name, mobile, email, class_sought, message, status) VALUES (?,?,?,?,?, 'New')"
            )->execute([$name, $mobile, $email ?: null, $classSought ?: null, $message ?: null]);
            $leadSuccess = true;
        } catch (PDOException $e) {
            $leadError = 'Something went wrong. Please try again.';
        }
    }
}

$modules = [
    ['icon' => 'fa-user-plus', 'tone' => 'blue', 'title' => 'Admissions & Enquiries', 'text' => 'Manage new admissions, walk-in enquiries, document verification and follow-ups from one place.'],
    ['icon' => 'fa-file-invoice-dollar', 'tone' => 'green', 'title' => 'Fees & Online Payments', 'text' => 'Class-wise fee structure, receipts, defaulters report and instant online collection with reminders.'],
    ['icon' => 'fa-user-check', 'tone' => 'teal', 'title' => 'Smart Attendance', 'text' => 'Daily student and teacher attendance with monthly reports and parent notifications.'],
    ['icon' => 'fa-graduation-cap', 'tone' => 'purple', 'title' => 'Exams & Report Cards', 'text' => 'Create exams, enter marks, auto-grade and generate printable report cards for every board.'],
    ['icon' => 'fa-bullhorn', 'tone' => 'orange', 'title' => 'Notices & Communication', 'text' => 'Publish notices, homework, results and alerts to students, teachers and parents instantly.'],
    ['icon' => 'fa-calendar-week', 'tone' => 'indigo', 'title' => 'Class Timetable', 'text' => 'Build class timetables, allocate teachers and share weekly schedules with everyone.'],
    ['icon' => 'fa-chalkboard-teacher', 'tone' => 'rose', 'title' => 'Teachers & HR', 'text' => 'Teacher profiles, subject allocation, HR attendance, leaves and portal accounts.'],
    ['icon' => 'fa-bus', 'tone' => 'amber', 'title' => 'Transport & Hostel', 'text' => 'Routes, stops, vehicles, hostel rooms and student allotments — all in one dashboard.'],
];

$painPoints = [
    ['icon' => 'fa-hourglass-half', 'title' => 'Fee collection every month is chaotic', 'text' => 'Chasing dues on calls, writing manual receipts and no clear picture of pending balances.'],
    ['icon' => 'fa-book', 'title' => 'Attendance stuck in paper registers', 'text' => 'Teachers waste class time on registers and parents never know when kids are absent.'],
    ['icon' => 'fa-phone-volume', 'title' => 'Parents call office for everything', 'text' => 'Fee status, homework, results, notices — every small update becomes a phone call.'],
    ['icon' => 'fa-file-alt', 'title' => 'Admissions data lost in Excel & WhatsApp', 'text' => 'Enquiries scattered across sheets and chats, no follow-up, lost admissions every season.'],
    ['icon' => 'fa-chart-line', 'title' => 'No real-time data to take decisions', 'text' => 'Fee recovery, teacher attendance, exam trends — no report available at a click.'],
    ['icon' => 'fa-puzzle-piece', 'title' => 'Too many disconnected tools', 'text' => 'Separate apps for SMS, fees, timetable — none of them talk to each other properly.'],
];

$benefits = [
    ['icon' => 'fa-flag', 'title' => 'Built for Indian schools', 'text' => 'CBSE, ICSE and State board grading, report cards and compliance ready out of the box.'],
    ['icon' => 'fa-wifi', 'title' => 'Works on any device', 'text' => 'Cloud-hosted and lightweight — usable from a basic Android phone or old desktop.'],
    ['icon' => 'fa-shield-alt', 'title' => 'Role-based secure access', 'text' => 'Admin, teacher, student and parent portals with granular permissions and audit logs.'],
    ['icon' => 'fa-headset', 'title' => 'Free onboarding & support', 'text' => 'Data migration, staff training and ongoing support — no IT team needed at your school.'],
];

$process = [
    ['num' => '01', 'title' => 'Book a free walkthrough', 'text' => 'Share a few details and our team will set up a personalised demo for your school.'],
    ['num' => '02', 'title' => 'We import your data', 'text' => 'Students, classes, staff and fee structure — we migrate from Excel or your current tool.'],
    ['num' => '03', 'title' => 'Train staff in one session', 'text' => 'Simple, hands-on training so admins and teachers start using it from day one.'],
    ['num' => '04', 'title' => 'Go live with confidence', 'text' => 'Continued support, quick fixes and periodic health checks after go-live.'],
];

$faqs = [
    ['q' => 'How long does setup take?', 'a' => 'Most schools go live within a week. We handle data migration, portal setup and staff training end-to-end.'],
    ['q' => 'Does it support CBSE, ICSE and State boards?', 'a' => 'Yes. Report cards, grading systems and compliance formats are pre-built for all major Indian boards.'],
    ['q' => 'Can parents and teachers get their own portal?', 'a' => 'Yes. Students, parents and teachers each get a dedicated portal with role-based access from any device.'],
    ['q' => 'Is online fee collection included?', 'a' => 'Yes. Class-wise fee structures, online payments, GST-ready receipts and defaulters reports are built in.'],
    ['q' => 'Can we migrate from Excel or another ERP?', 'a' => 'Absolutely. Free data migration from Excel, Tally or any other school management tool.'],
    ['q' => 'How secure is the data?', 'a' => 'Encrypted database, role-based access and regular backups — your school data stays safe and available only to authorised users.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($brandName); ?> — <?php echo htmlspecialchars($brandTag); ?></title>
    <?php if ($faviconUrl): ?><link rel="icon" href="<?php echo htmlspecialchars($faviconUrl); ?>"><?php endif; ?>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/landing.css">
</head>
<body class="lp-body">

<header class="lp-nav">
    <div class="lp-container lp-nav-inner">
        <a href="index.php" class="lp-brand">
            <div class="lp-brand-icon<?php echo $logoUrl ? ' has-logo' : ''; ?>">
                <?php if ($logoUrl): ?>
                <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="<?php echo htmlspecialchars($brandName); ?>">
                <?php else: ?>
                <i class="fas fa-graduation-cap"></i>
                <?php endif; ?>
            </div>
            <div class="lp-brand-text">
                <strong><?php echo htmlspecialchars($brandName); ?></strong>
                <span>School Management ERP</span>
            </div>
        </a>

        <nav class="lp-nav-links">
            <a href="#about">About</a>
            <a href="#modules">Modules</a>
            <a href="#why">Why Us</a>
            <a href="#process">Process</a>
            <a href="#faq">FAQ</a>
            <a href="#contact">Contact</a>
        </nav>

        <div class="lp-nav-actions">
            <div class="lp-login-menu">
                <button type="button" class="lp-btn-outline lp-login-trigger" id="lpLoginTrigger">
                    <i class="fas fa-sign-in-alt"></i> Login
                    <i class="fas fa-chevron-down lp-chev"></i>
                </button>
                <div class="lp-login-dropdown" id="lpLoginDropdown">
                    <a href="admin/index.php"><i class="fas fa-user-shield"></i> <div><strong>Admin</strong><span>Manage school</span></div></a>
                    <a href="teacher/index.php"><i class="fas fa-chalkboard-teacher"></i> <div><strong>Teacher</strong><span>Faculty portal</span></div></a>
                    <a href="portal/index.php"><i class="fas fa-user-graduate"></i> <div><strong>Student</strong><span>Student portal</span></div></a>
                </div>
            </div>
            <a href="#lead" class="lp-btn-primary"><i class="fas fa-rocket"></i> Get Started</a>
        </div>

        <button type="button" class="lp-mobile-toggle" id="lpMobileToggle"><i class="fas fa-bars"></i></button>
    </div>
</header>

<section class="lp-hero">
    <div class="lp-hero-bg">
        <div class="lp-hero-grid"></div>
        <span class="lp-orb lp-orb-1"></span>
        <span class="lp-orb lp-orb-2"></span>
        <span class="lp-orb lp-orb-3"></span>
    </div>
    <div class="lp-container lp-hero-inner">
        <div class="lp-hero-left">
            <div class="lp-hero-badge">
                <span class="lp-hero-badge-dot"></span>
                <span>India's smart school ERP platform</span>
            </div>

            <h1>
                Run your entire school from
                <span class="lp-hero-gradient">one powerful dashboard</span>
            </h1>

            <p class="lp-hero-sub">
                <?php echo htmlspecialchars($brandName); ?> brings admissions, fees, attendance, exams and parent communication together — built for CBSE, ICSE and State board schools across India.
            </p>

            <div class="lp-hero-chips">
                <div class="lp-hero-chip">
                    <span class="lp-chip-icon tone-green"><i class="fas fa-indian-rupee-sign"></i></span>
                    <div><strong>Online fees</strong><small>UPI &amp; receipts</small></div>
                </div>
                <div class="lp-hero-chip">
                    <span class="lp-chip-icon tone-blue"><i class="fas fa-user-check"></i></span>
                    <div><strong>Attendance</strong><small>Real-time alerts</small></div>
                </div>
                <div class="lp-hero-chip">
                    <span class="lp-chip-icon tone-purple"><i class="fas fa-chart-bar"></i></span>
                    <div><strong>Report cards</strong><small>Board-ready</small></div>
                </div>
                <div class="lp-hero-chip">
                    <span class="lp-chip-icon tone-orange"><i class="fas fa-mobile-alt"></i></span>
                    <div><strong>Mobile ready</strong><small>Any device</small></div>
                </div>
            </div>

            <div class="lp-hero-cta">
                <a href="#lead" class="lp-btn-primary lp-btn-lg"><i class="fas fa-rocket"></i> Start Free Trial</a>
                <a href="admin/index.php" class="lp-btn-ghost lp-btn-lg"><i class="fas fa-play-circle"></i> Watch Demo</a>
            </div>

            <div class="lp-hero-trust">
                <div class="lp-trust-card">
                    <div class="lp-trust-icon"><i class="fas fa-users"></i></div>
                    <div><strong>1000+</strong><span>Students managed</span></div>
                </div>
                <div class="lp-trust-card">
                    <div class="lp-trust-icon"><i class="fas fa-cloud"></i></div>
                    <div><strong>100%</strong><span>Cloud hosted</span></div>
                </div>
                <div class="lp-trust-card">
                    <div class="lp-trust-icon"><i class="fas fa-headset"></i></div>
                    <div><strong>24×7</strong><span>Hindi &amp; English support</span></div>
                </div>
            </div>
        </div>

        <div class="lp-hero-right" id="lead">
            <div class="lp-lead-card">
                <div class="lp-lead-accent"></div>
                <div class="lp-lead-head">
                    <span class="lp-lead-pill"><i class="fas fa-gift"></i> 7-day free trial</span>
                    <h3>Get a personalised demo</h3>
                    <p>Share your details — we set everything up for your school.</p>
                </div>

                <?php if ($leadSuccess): ?>
                <div class="lp-lead-success">
                    <div class="lp-lead-success-icon"><i class="fas fa-check"></i></div>
                    <strong>Thank you!</strong>
                    <p>Your request is received. Our team will contact you within a few hours.</p>
                    <a href="index.php" class="lp-btn-outline lp-btn-sm">Submit another</a>
                </div>
                <?php else: ?>
                <?php if ($leadError): ?><div class="lp-lead-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($leadError); ?></div><?php endif; ?>
                <form method="POST" class="lp-lead-form">
                    <input type="hidden" name="request_demo" value="1">
                    <div class="lp-field">
                        <label>Full Name <em>*</em></label>
                        <div class="lp-input-wrap"><i class="fas fa-user"></i><input type="text" name="student_name" required placeholder="Your name"></div>
                    </div>
                    <div class="lp-field">
                        <label>Mobile <em>*</em></label>
                        <div class="lp-input-wrap"><i class="fas fa-phone"></i><input type="tel" name="mobile" required placeholder="10-digit mobile"></div>
                    </div>
                    <div class="lp-field">
                        <label>Email</label>
                        <div class="lp-input-wrap"><i class="fas fa-envelope"></i><input type="email" name="email" placeholder="you@school.com"></div>
                    </div>
                    <div class="lp-field">
                        <label>School Name / Class</label>
                        <div class="lp-input-wrap"><i class="fas fa-school"></i><input type="text" name="class_sought" placeholder="e.g. Sunrise Public School"></div>
                    </div>
                    <div class="lp-field lp-field-full">
                        <label>Message (optional)</label>
                        <textarea name="message" rows="2" placeholder="Tell us about your school — number of students, current tools, etc."></textarea>
                    </div>
                    <button type="submit" class="lp-btn-primary lp-btn-block"><i class="fas fa-paper-plane"></i> Activate My Free Trial</button>
                    <p class="lp-lead-note">No spam · No credit card · We reply within 2 hours</p>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="lp-trust-strip">
    <div class="lp-container">
        <div class="lp-trust-grid">
            <div><strong>₹0</strong><span>Setup fee<br>No server cost</span></div>
            <div><strong>100%</strong><span>Cloud-based<br>Any device</span></div>
            <div><strong>1000+</strong><span>Students managed<br>Across India</span></div>
            <div><strong>7 Days</strong><span>Average go-live<br>Guaranteed</span></div>
        </div>
    </div>
</section>

<section id="about" class="lp-section lp-about">
    <div class="lp-container lp-about-inner">
        <div class="lp-about-visual">
            <div class="lp-hero-preview" aria-hidden="true">
                <div class="lp-preview-glow"></div>
                <div class="lp-preview-window">
                    <div class="lp-preview-topbar">
                        <span class="lp-preview-dot red"></span>
                        <span class="lp-preview-dot yellow"></span>
                        <span class="lp-preview-dot green"></span>
                        <span class="lp-preview-title"><?php echo htmlspecialchars($brandName); ?> — Admin</span>
                    </div>
                    <div class="lp-preview-body">
                        <div class="lp-preview-sidebar">
                            <span></span><span></span><span></span><span class="active"></span><span></span>
                        </div>
                        <div class="lp-preview-main">
                            <div class="lp-preview-stats">
                                <div class="lp-pstat"><small>Fee collected</small><strong>₹4.2L</strong><em class="up">+18%</em></div>
                                <div class="lp-pstat"><small>Present today</small><strong>94%</strong><em class="up">+2%</em></div>
                                <div class="lp-pstat"><small>Admissions</small><strong>128</strong><em class="up">+12</em></div>
                            </div>
                            <div class="lp-preview-chart">
                                <div class="lp-bar" style="height:42%"></div>
                                <div class="lp-bar" style="height:68%"></div>
                                <div class="lp-bar" style="height:55%"></div>
                                <div class="lp-bar" style="height:82%"></div>
                                <div class="lp-bar" style="height:61%"></div>
                                <div class="lp-bar" style="height:90%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="lp-float-badge lp-float-fee">
                    <i class="fas fa-check-circle"></i>
                    <div><strong>Fee received</strong><small>₹12,500 · Receipt sent</small></div>
                </div>
                <div class="lp-float-badge lp-float-att">
                    <i class="fas fa-bell"></i>
                    <div><strong>Attendance alert</strong><small>Class 8-A · 2 absent</small></div>
                </div>
            </div>
        </div>

        <div class="lp-about-copy">
            <span class="lp-eyebrow">About the platform</span>
            <h2>One live dashboard for your entire school</h2>
            <p>
                <?php echo htmlspecialchars($brandName); ?> replaces scattered registers, spreadsheets and phone calls with a single connected system. See fee collection, attendance and admissions update in real time — and take decisions with data instead of guesswork.
            </p>

            <ul class="lp-about-points">
                <li><i class="fas fa-circle-check"></i> <div><strong>Real-time insights</strong><span>Fees, attendance and admissions on one screen.</span></div></li>
                <li><i class="fas fa-circle-check"></i> <div><strong>Role-based access</strong><span>Separate portals for admin, teachers and students.</span></div></li>
                <li><i class="fas fa-circle-check"></i> <div><strong>Works everywhere</strong><span>Cloud-hosted and fast on any device or connection.</span></div></li>
            </ul>

            <div class="lp-about-cta">
                <a href="#lead" class="lp-btn-primary"><i class="fas fa-rocket"></i> Start Free Trial</a>
                <a href="#modules" class="lp-btn-ghost"><i class="fas fa-grip"></i> Explore Modules</a>
            </div>
        </div>
    </div>
</section>

<section class="lp-section lp-pain">
    <div class="lp-container">
        <div class="lp-section-head">
            <span class="lp-eyebrow">Sound familiar?</span>
            <h2>Why manual school management slows you down</h2>
            <p>Paperwork, phone calls, and scattered tools drain your team’s time. A single ERP fixes it all.</p>
        </div>
        <div class="lp-pain-grid">
            <?php foreach ($painPoints as $p): ?>
            <div class="lp-pain-card">
                <div class="lp-pain-icon"><i class="fas <?php echo $p['icon']; ?>"></i></div>
                <strong><?php echo htmlspecialchars($p['title']); ?></strong>
                <p><?php echo htmlspecialchars($p['text']); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section id="modules" class="lp-section lp-modules-section">
    <div class="lp-container">
        <div class="lp-section-head">
            <span class="lp-eyebrow">Complete School ERP</span>
            <h2>From admissions to attendance — manage everything in one place</h2>
            <p>Eight tightly integrated modules built for real Indian school workflows.</p>
        </div>
        <div class="lp-module-grid">
            <?php foreach ($modules as $m): ?>
            <div class="lp-module-card tone-<?php echo $m['tone']; ?>">
                <div class="lp-module-icon"><i class="fas <?php echo $m['icon']; ?>"></i></div>
                <h4><?php echo htmlspecialchars($m['title']); ?></h4>
                <p><?php echo htmlspecialchars($m['text']); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section id="why" class="lp-section lp-why">
    <div class="lp-container">
        <div class="lp-section-head">
            <span class="lp-eyebrow">Why <?php echo htmlspecialchars($brandName); ?></span>
            <h2>Built for Indian schools — not adapted from foreign software</h2>
            <p>Everything is designed around how Indian schools actually run — from board formats to fee cycles.</p>
        </div>

        <div class="lp-why-grid">
            <?php foreach ($benefits as $b): ?>
            <div class="lp-why-card">
                <div class="lp-why-icon"><i class="fas <?php echo $b['icon']; ?>"></i></div>
                <div>
                    <strong><?php echo htmlspecialchars($b['title']); ?></strong>
                    <p><?php echo htmlspecialchars($b['text']); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="lp-stat-strip">
            <div><strong>40%</strong><span>Faster fee collection in the first quarter</span></div>
            <div><strong>3 hrs</strong><span>Saved per teacher per week on paperwork</span></div>
            <div><strong>67%</strong><span>Reduction in admin follow-up calls</span></div>
            <div><strong>98%</strong><span>Schools renew year after year</span></div>
        </div>
    </div>
</section>

<section class="lp-section lp-testimonials">
    <div class="lp-container">
        <div class="lp-section-head">
            <span class="lp-eyebrow">Loved by school teams</span>
            <h2>Real results, real principals</h2>
        </div>
        <div class="lp-testimonial-grid">
            <div class="lp-testimonial">
                <div class="lp-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                <p>“Our fee collection jumped from 72% to 94% in a single quarter. Auto-reminders and online payments changed everything for us.”</p>
                <div class="lp-tst-author">
                    <span class="lp-tst-avatar">RS</span>
                    <div>
                        <strong>Rajesh Sharma</strong>
                        <small>Principal, Sunrise Public School</small>
                    </div>
                </div>
            </div>
            <div class="lp-testimonial">
                <div class="lp-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                <p>“We were using three different tools. Now everything — fees, attendance, results — is in one place. Staff was comfortable within two days.”</p>
                <div class="lp-tst-author">
                    <span class="lp-tst-avatar">PG</span>
                    <div>
                        <strong>Priya Gupta</strong>
                        <small>Administrator, Greenwood Academy</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="process" class="lp-section lp-process">
    <div class="lp-container">
        <div class="lp-section-head">
            <span class="lp-eyebrow">How it works</span>
            <h2>Four simple steps to go live</h2>
            <p>No complicated setup, no expensive hardware. Our team handles everything for you.</p>
        </div>
        <div class="lp-process-grid">
            <?php foreach ($process as $i => $p): ?>
            <div class="lp-process-step">
                <div class="lp-step-num"><?php echo $p['num']; ?></div>
                <strong><?php echo htmlspecialchars($p['title']); ?></strong>
                <p><?php echo htmlspecialchars($p['text']); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section id="faq" class="lp-section lp-faq">
    <div class="lp-container">
        <div class="lp-section-head">
            <span class="lp-eyebrow">Common questions</span>
            <h2>Frequently asked questions</h2>
        </div>
        <div class="lp-faq-list">
            <?php foreach ($faqs as $idx => $f): ?>
            <details class="lp-faq-item"<?php echo $idx === 0 ? ' open' : ''; ?>>
                <summary>
                    <span><?php echo htmlspecialchars($f['q']); ?></span>
                    <i class="fas fa-plus"></i>
                </summary>
                <p><?php echo htmlspecialchars($f['a']); ?></p>
            </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section id="contact" class="lp-section lp-cta">
    <div class="lp-container">
        <div class="lp-cta-card">
            <div class="lp-cta-left">
                <h2>Ready to make the switch?</h2>
                <p>Full access for 7 days — all modules, no restrictions. We configure everything for your school.</p>
                <div class="lp-cta-actions">
                    <a href="#lead" class="lp-btn-primary lp-btn-lg"><i class="fas fa-rocket"></i> Start Free Trial</a>
                    <a href="tel:<?php echo htmlspecialchars(preg_replace('/\s+/', '', $brandPhone)); ?>" class="lp-btn-ghost-light"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($brandPhone); ?></a>
                </div>
            </div>
            <div class="lp-cta-right">
                <div class="lp-cta-check"><i class="fas fa-check-circle"></i> 7-day full access</div>
                <div class="lp-cta-check"><i class="fas fa-check-circle"></i> No credit card required</div>
                <div class="lp-cta-check"><i class="fas fa-check-circle"></i> Free data migration</div>
                <div class="lp-cta-check"><i class="fas fa-check-circle"></i> Setup done for you</div>
            </div>
        </div>
    </div>
</section>

<footer class="lp-footer">
    <div class="lp-container lp-footer-inner">
        <div class="lp-footer-brand">
            <div class="lp-brand-icon<?php echo $logoUrl ? ' has-logo' : ''; ?>">
                <?php if ($logoUrl): ?>
                <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="<?php echo htmlspecialchars($brandName); ?>">
                <?php else: ?>
                <i class="fas fa-graduation-cap"></i>
                <?php endif; ?>
            </div>
            <div>
                <strong><?php echo htmlspecialchars($brandName); ?></strong>
                <p><?php echo htmlspecialchars($brandTag); ?></p>
            </div>
        </div>
        <div class="lp-footer-cols">
            <div>
                <h5>Product</h5>
                <a href="#modules">Modules</a>
                <a href="#why">Why us</a>
                <a href="#process">Process</a>
                <a href="#faq">FAQ</a>
            </div>
            <div>
                <h5>Access</h5>
                <a href="admin/index.php">Admin login</a>
                <a href="teacher/index.php">Teacher portal</a>
                <a href="portal/index.php">Student portal</a>
            </div>
            <div>
                <h5>Contact</h5>
                <a href="tel:<?php echo htmlspecialchars(preg_replace('/\s+/', '', $brandPhone)); ?>"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($brandPhone); ?></a>
                <a href="mailto:<?php echo htmlspecialchars($brandEmail); ?>"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($brandEmail); ?></a>
                <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($brandAddress); ?></span>
            </div>
        </div>
    </div>
    <div class="lp-footer-bottom">
        <div class="lp-container">
            <span>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($brandName); ?>. All rights reserved.</span>
            <div class="lp-footer-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Use</a>
            </div>
        </div>
    </div>
</footer>

<a href="tel:<?php echo htmlspecialchars(preg_replace('/\s+/', '', $brandPhone)); ?>" class="lp-fab-call" aria-label="Call us">
    <i class="fas fa-phone-alt"></i>
</a>

<script>
(function () {
    var trigger = document.getElementById('lpLoginTrigger');
    var dropdown = document.getElementById('lpLoginDropdown');
    if (trigger && dropdown) {
        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            dropdown.classList.toggle('open');
        });
        document.addEventListener('click', function (e) {
            if (!dropdown.contains(e.target) && !trigger.contains(e.target)) {
                dropdown.classList.remove('open');
            }
        });
    }

    var mobileToggle = document.getElementById('lpMobileToggle');
    var navLinks = document.querySelector('.lp-nav-links');
    if (mobileToggle && navLinks) {
        mobileToggle.addEventListener('click', function () {
            navLinks.classList.toggle('open');
        });
        navLinks.querySelectorAll('a').forEach(function (a) {
            a.addEventListener('click', function () {
                navLinks.classList.remove('open');
            });
        });
    }

    document.querySelectorAll('a[href^="#"]').forEach(function (a) {
        a.addEventListener('click', function (e) {
            var href = a.getAttribute('href');
            if (href.length > 1) {
                var target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });
    });
})();
</script>

</body>
</html>
