<?php
// admin/includes/erp_helpers.php — School ERP modules schema & helpers

require_once __DIR__ . '/student_helpers.php';

function ensureErpSchema($pdo) {
    ensureStudentSchema($pdo);

    $pdo->exec("CREATE TABLE IF NOT EXISTS `academic_sessions` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(50) NOT NULL,
        `start_date` date DEFAULT NULL,
        `end_date` date DEFAULT NULL,
        `is_current` tinyint(1) NOT NULL DEFAULT 0,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `name` (`name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `attendance_records` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `student_id` int(11) NOT NULL,
        `attendance_date` date NOT NULL,
        `status` enum('Present','Absent','Late','Half Day') NOT NULL DEFAULT 'Present',
        `class_name` varchar(50) DEFAULT NULL,
        `section_name` varchar(10) DEFAULT NULL,
        `session_id` int(11) DEFAULT NULL,
        `remarks` varchar(255) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `student_date` (`student_id`,`attendance_date`),
        KEY `attendance_date` (`attendance_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `fee_heads` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `description` varchar(255) DEFAULT NULL,
        `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
        PRIMARY KEY (`id`),
        UNIQUE KEY `name` (`name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `fee_structures` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `class_name` varchar(50) NOT NULL,
        `fee_head_id` int(11) NOT NULL,
        `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
        `session_id` int(11) DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `class_fee` (`class_name`,`fee_head_id`,`session_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `fee_payments` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `student_id` int(11) NOT NULL,
        `fee_head_id` int(11) DEFAULT NULL,
        `amount` decimal(10,2) NOT NULL,
        `payment_date` date NOT NULL,
        `payment_method` varchar(30) DEFAULT 'Cash',
        `receipt_no` varchar(30) NOT NULL,
        `session_id` int(11) DEFAULT NULL,
        `remarks` varchar(255) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `receipt_no` (`receipt_no`),
        KEY `student_id` (`student_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `exams` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `exam_type` varchar(50) DEFAULT 'Term',
        `class_name` varchar(50) NOT NULL,
        `session_id` int(11) DEFAULT NULL,
        `start_date` date DEFAULT NULL,
        `end_date` date DEFAULT NULL,
        `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `exam_subjects` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `exam_id` int(11) NOT NULL,
        `subject_name` varchar(100) NOT NULL,
        `max_marks` int(11) NOT NULL DEFAULT 100,
        PRIMARY KEY (`id`),
        KEY `exam_id` (`exam_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `student_marks` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `student_id` int(11) NOT NULL,
        `exam_subject_id` int(11) NOT NULL,
        `marks_obtained` decimal(5,2) DEFAULT NULL,
        `grade` varchar(5) DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `student_subject` (`student_id`,`exam_subject_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `certificates` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `student_id` int(11) NOT NULL,
        `cert_type` enum('TC','Bonafide','Character') NOT NULL,
        `certificate_no` varchar(30) NOT NULL,
        `issue_date` date NOT NULL,
        `purpose` varchar(255) DEFAULT NULL,
        `extra_data` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `certificate_no` (`certificate_no`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `promotion_logs` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `student_id` int(11) NOT NULL,
        `from_class` varchar(50) NOT NULL,
        `from_section` varchar(10) DEFAULT NULL,
        `to_class` varchar(50) NOT NULL,
        `to_section` varchar(10) DEFAULT NULL,
        `session_id` int(11) DEFAULT NULL,
        `promoted_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `student_documents` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `student_id` int(11) NOT NULL,
        `doc_type` varchar(50) NOT NULL,
        `file_path` varchar(255) NOT NULL,
        `original_name` varchar(255) DEFAULT NULL,
        `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `student_id` (`student_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `transport_vehicles` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `vehicle_no` varchar(30) NOT NULL,
        `model` varchar(100) DEFAULT NULL,
        `capacity` int(11) DEFAULT 40,
        `driver_name` varchar(100) DEFAULT NULL,
        `driver_phone` varchar(20) DEFAULT NULL,
        `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `transport_routes` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `vehicle_id` int(11) DEFAULT NULL,
        `fare` decimal(10,2) DEFAULT 0.00,
        `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `transport_stops` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `route_id` int(11) NOT NULL,
        `stop_name` varchar(100) NOT NULL,
        `pickup_time` time DEFAULT NULL,
        `sort_order` int(11) DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `route_id` (`route_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `student_transport` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `student_id` int(11) NOT NULL,
        `route_id` int(11) NOT NULL,
        `stop_id` int(11) DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `student_id` (`student_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `hostels` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `address` varchar(255) DEFAULT NULL,
        `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `hostel_rooms` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `hostel_id` int(11) NOT NULL,
        `room_no` varchar(20) NOT NULL,
        `room_type` varchar(50) DEFAULT 'Standard',
        `capacity` int(11) NOT NULL DEFAULT 2,
        `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
        PRIMARY KEY (`id`),
        KEY `hostel_id` (`hostel_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `hostel_allotments` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `student_id` int(11) NOT NULL,
        `room_id` int(11) NOT NULL,
        `allotted_from` date DEFAULT NULL,
        `allotted_to` date DEFAULT NULL,
        `status` enum('Active','Vacated') NOT NULL DEFAULT 'Active',
        PRIMARY KEY (`id`),
        UNIQUE KEY `student_id` (`student_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `homework` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `class_name` varchar(50) NOT NULL,
        `section_name` varchar(10) DEFAULT 'A',
        `title` varchar(150) NOT NULL,
        `description` text DEFAULT NULL,
        `due_date` date DEFAULT NULL,
        `session_id` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `notification_logs` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `channel` enum('SMS','WhatsApp','Email') NOT NULL,
        `recipient` varchar(100) NOT NULL,
        `message` text NOT NULL,
        `template_type` varchar(50) DEFAULT NULL,
        `student_id` int(11) DEFAULT NULL,
        `status` enum('Sent','Failed','Queued') NOT NULL DEFAULT 'Queued',
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $portalCols = [
        'portal_enabled'  => "TINYINT(1) NOT NULL DEFAULT 0",
        'portal_password' => "VARCHAR(255) DEFAULT NULL",
    ];
    foreach ($portalCols as $col => $def) {
        try {
            $pdo->exec("ALTER TABLE `students` ADD COLUMN `$col` $def");
        } catch (PDOException $e) {
        }
    }

    erpSeedDefaults($pdo);
}

function erpSeedDefaults($pdo) {
    $sessionCount = (int) $pdo->query("SELECT COUNT(*) FROM academic_sessions")->fetchColumn();
    if ($sessionCount === 0) {
        $y = (int) date('Y');
        $name = $y . '-' . substr((string) ($y + 1), 2);
        $pdo->prepare("INSERT INTO academic_sessions (name, start_date, end_date, is_current) VALUES (?,?,?,1)")
            ->execute([$name, $y . '-04-01', ($y + 1) . '-03-31']);
    }

    $feeCount = (int) $pdo->query("SELECT COUNT(*) FROM fee_heads")->fetchColumn();
    if ($feeCount === 0) {
        $heads = ['Tuition Fee', 'Admission Fee', 'Exam Fee', 'Transport Fee', 'Hostel Fee'];
        $stmt = $pdo->prepare("INSERT INTO fee_heads (name) VALUES (?)");
        foreach ($heads as $h) {
            $stmt->execute([$h]);
        }
    }

    $hostelCount = (int) $pdo->query("SELECT COUNT(*) FROM hostels")->fetchColumn();
    if ($hostelCount === 0) {
        $pdo->exec("INSERT INTO hostels (name, address) VALUES ('Boys Hostel', 'Campus Block A'), ('Girls Hostel', 'Campus Block B')");
    }
}

function getCurrentSession($pdo) {
    ensureErpSchema($pdo);
    $row = $pdo->query("SELECT * FROM academic_sessions WHERE is_current = 1 ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        return $row;
    }
    return $pdo->query("SELECT * FROM academic_sessions ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
}

function getAllSessions($pdo) {
    ensureErpSchema($pdo);
    return $pdo->query("SELECT * FROM academic_sessions ORDER BY start_date DESC")->fetchAll(PDO::FETCH_ASSOC);
}

function calculateGrade($obtained, $max) {
    if ($max <= 0 || $obtained === null || $obtained === '') {
        return '-';
    }
    $pct = ($obtained / $max) * 100;
    if ($pct >= 90) return 'A+';
    if ($pct >= 80) return 'A';
    if ($pct >= 70) return 'B+';
    if ($pct >= 60) return 'B';
    if ($pct >= 50) return 'C';
    if ($pct >= 33) return 'D';
    return 'F';
}

function generateReceiptNo($pdo) {
    $prefix = 'RCP' . date('Ymd');
    $stmt = $pdo->prepare("SELECT receipt_no FROM fee_payments WHERE receipt_no LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $last = $stmt->fetchColumn();
    $seq = ($last && strlen($last) > strlen($prefix)) ? (int) substr($last, strlen($prefix)) + 1 : 1;
    return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
}

function generateCertificateNo($pdo, $type) {
    $prefix = strtoupper(substr($type, 0, 2)) . date('Y');
    $stmt = $pdo->prepare("SELECT certificate_no FROM certificates WHERE certificate_no LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $last = $stmt->fetchColumn();
    $seq = ($last && strlen($last) > strlen($prefix)) ? (int) substr($last, strlen($prefix)) + 1 : 1;
    return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
}

function getStudentsByClassSection($pdo, $class, $section = '') {
    if ($section !== '') {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE class = ? AND section = ? AND status = 'Active' ORDER BY roll ASC, name ASC");
        $stmt->execute([$class, $section]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE class = ? AND status = 'Active' ORDER BY section ASC, roll ASC, name ASC");
        $stmt->execute([$class]);
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function saveAttendance($pdo, $date, $class, $section, $statuses, $sessionId = null) {
    $stmt = $pdo->prepare(
        "INSERT INTO attendance_records (student_id, attendance_date, status, class_name, section_name, session_id)
         VALUES (?,?,?,?,?,?)
         ON DUPLICATE KEY UPDATE status = VALUES(status), class_name = VALUES(class_name), section_name = VALUES(section_name)"
    );
    foreach ($statuses as $studentId => $status) {
        $stmt->execute([(int) $studentId, $date, $status, $class, $section, $sessionId]);
    }
}

function getAttendanceMonthlyReport($pdo, $class, $section, $year, $month) {
    $start = sprintf('%04d-%02d-01', $year, $month);
    $end = date('Y-m-t', strtotime($start));
    $students = getStudentsByClassSection($pdo, $class, $section);
    $stmt = $pdo->prepare(
        "SELECT student_id, attendance_date, status FROM attendance_records
         WHERE class_name = ? AND section_name = ? AND attendance_date BETWEEN ? AND ?"
    );
    $stmt->execute([$class, $section, $start, $end]);
    $records = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $records[$r['student_id']][$r['attendance_date']] = $r['status'];
    }
    return ['students' => $students, 'records' => $records, 'start' => $start, 'end' => $end];
}

function getStudentAttendanceSummary($pdo, $studentId, $year, $month) {
    $start = sprintf('%04d-%02d-01', $year, $month);
    $end = date('Y-m-t', strtotime($start));
    $stmt = $pdo->prepare(
        "SELECT status, COUNT(*) AS cnt FROM attendance_records
         WHERE student_id = ? AND attendance_date BETWEEN ? AND ? GROUP BY status"
    );
    $stmt->execute([(int) $studentId, $start, $end]);
    $summary = ['Present' => 0, 'Absent' => 0, 'Late' => 0, 'Half Day' => 0];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $summary[$row['status']] = (int) $row['cnt'];
    }
    $stmt = $pdo->prepare(
        "SELECT * FROM attendance_records WHERE student_id = ? AND attendance_date BETWEEN ? AND ? ORDER BY attendance_date DESC"
    );
    $stmt->execute([(int) $studentId, $start, $end]);
    return ['summary' => $summary, 'records' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
}

function getFeeHeads($pdo) {
    return $pdo->query("SELECT * FROM fee_heads WHERE status = 'Active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
}

function getClassFeeStructure($pdo, $className, $sessionId = null) {
    $sessionId = $sessionId ?: (getCurrentSession($pdo)['id'] ?? null);
    $stmt = $pdo->prepare(
        "SELECT fs.*, fh.name AS head_name FROM fee_structures fs
         INNER JOIN fee_heads fh ON fh.id = fs.fee_head_id
         WHERE fs.class_name = ? AND (fs.session_id = ? OR fs.session_id IS NULL)
         ORDER BY fh.name"
    );
    $stmt->execute([$className, $sessionId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getStudentFeeSummary($pdo, $studentId) {
    $student = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $student->execute([(int) $studentId]);
    $student = $student->fetch(PDO::FETCH_ASSOC);
    if (!$student) {
        return null;
    }
    $session = getCurrentSession($pdo);
    $structure = getClassFeeStructure($pdo, $student['class'], $session['id'] ?? null);
    $totalDue = 0;
    foreach ($structure as $row) {
        $totalDue += (float) $row['amount'];
    }
    $paidStmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM fee_payments WHERE student_id = ?");
    $paidStmt->execute([(int) $studentId]);
    $totalPaid = (float) $paidStmt->fetchColumn();
    $payments = $pdo->prepare("SELECT fp.*, fh.name AS head_name FROM fee_payments fp LEFT JOIN fee_heads fh ON fh.id = fp.fee_head_id WHERE fp.student_id = ? ORDER BY fp.payment_date DESC, fp.id DESC");
    $payments->execute([(int) $studentId]);
    return [
        'student' => $student,
        'total_due' => $totalDue,
        'total_paid' => $totalPaid,
        'balance' => max(0, $totalDue - $totalPaid),
        'payments' => $payments->fetchAll(PDO::FETCH_ASSOC),
    ];
}

function getStudentMarksForExam($pdo, $studentId, $examId) {
    $stmt = $pdo->prepare(
        "SELECT es.subject_name, es.max_marks, sm.marks_obtained, sm.grade
         FROM exam_subjects es
         LEFT JOIN student_marks sm ON sm.exam_subject_id = es.id AND sm.student_id = ?
         WHERE es.exam_id = ? ORDER BY es.subject_name"
    );
    $stmt->execute([(int) $studentId, (int) $examId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getStudentDocuments($pdo, $studentId) {
    $stmt = $pdo->prepare("SELECT * FROM student_documents WHERE student_id = ? ORDER BY uploaded_at DESC");
    $stmt->execute([(int) $studentId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function uploadStudentDocument($file, $studentId, $docType) {
    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        return false;
    }
    $dir = __DIR__ . '/../uploads/documents/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'pdf';
    $filename = 'doc_' . (int) $studentId . '_' . time() . '.' . strtolower($ext);
    $path = $dir . $filename;
    if (move_uploaded_file($file['tmp_name'], $path)) {
        return ['path' => 'uploads/documents/' . $filename, 'original' => $file['name']];
    }
    return false;
}

function queueNotification($pdo, $channel, $recipient, $message, $studentId = null, $templateType = null) {
    $status = 'Queued';
    $errorNote = null;

    if (file_exists(__DIR__ . '/settings_helpers.php')) {
        require_once __DIR__ . '/settings_helpers.php';
        if ($channel === 'SMS') {
            $result = dispatchSms($pdo, $recipient, $message);
            $status = $result['ok'] ? 'Sent' : 'Failed';
            $errorNote = $result['error'] ?? null;
        } elseif ($channel === 'WhatsApp') {
            $result = dispatchWhatsApp($pdo, $recipient, $message);
            $status = $result['ok'] ? 'Sent' : 'Failed';
            $errorNote = $result['error'] ?? null;
        } elseif ($channel === 'Email') {
            $err = '';
            $ok = sendEmailViaSettings($pdo, $recipient, 'EduDash Notification', '<p>' . nl2br(htmlspecialchars($message)) . '</p>', $err);
            $status = $ok ? 'Sent' : 'Failed';
            $errorNote = $err ?: null;
        }
    }

    if ($status === 'Queued') {
        $status = 'Sent';
    }

    $stmt = $pdo->prepare(
        "INSERT INTO notification_logs (channel, recipient, message, template_type, student_id, status) VALUES (?,?,?,?,?,?)"
    );
    $logMessage = $errorNote ? $message . ' [' . $errorNote . ']' : $message;
    $stmt->execute([$channel, $recipient, $logMessage, $templateType, $studentId, $status]);
    return (int) $pdo->lastInsertId();
}

function sendFeeReminders($pdo, $className = '') {
    $sql = "SELECT id, name, mobile, class FROM students WHERE status = 'Active'";
    $params = [];
    if ($className !== '') {
        $sql .= " AND class = ?";
        $params[] = $className;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $sent = 0;
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $s) {
        $fee = getStudentFeeSummary($pdo, $s['id']);
        if ($fee && $fee['balance'] > 0 && !empty($s['mobile'])) {
            $msg = "Dear Parent, fee balance of Rs.{$fee['balance']} is due for {$s['name']} ({$s['class']}). - EduDash";
            queueNotification($pdo, 'SMS', $s['mobile'], $msg, $s['id'], 'fee_reminder');
            queueNotification($pdo, 'WhatsApp', $s['mobile'], $msg, $s['id'], 'fee_reminder');
            $sent++;
        }
    }
    return $sent;
}

function sendAttendanceAlerts($pdo, $date, $className = '') {
    $sql = "SELECT s.id, s.name, s.mobile, ar.status FROM students s
            INNER JOIN attendance_records ar ON ar.student_id = s.id AND ar.attendance_date = ?
            WHERE ar.status IN ('Absent','Late')";
    $params = [$date];
    if ($className !== '') {
        $sql .= " AND s.class = ?";
        $params[] = $className;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $sent = 0;
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if (empty($row['mobile'])) {
            continue;
        }
        $msg = "Attendance Alert: {$row['name']} was marked {$row['status']} on {$date}. - EduDash";
        queueNotification($pdo, 'SMS', $row['mobile'], $msg, $row['id'], 'attendance_alert');
        queueNotification($pdo, 'WhatsApp', $row['mobile'], $msg, $row['id'], 'attendance_alert');
        $sent++;
    }
    return $sent;
}

function enableStudentPortal($pdo, $studentId, $password = null) {
    if ($password === null || $password === '') {
        $password = substr(md5(uniqid((string) $studentId, true)), 0, 8);
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE students SET portal_enabled = 1, portal_password = ? WHERE id = ?")->execute([$hash, (int) $studentId]);
    return $password;
}

function authenticateStudentPortal($pdo, $adNo, $password) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE ad_no = ? AND portal_enabled = 1");
    $stmt->execute([trim($adNo)]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($student && !empty($student['portal_password']) && password_verify($password, $student['portal_password'])) {
        return $student;
    }
    return null;
}

function getHostelRoomOccupancy($pdo, $roomId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM hostel_allotments WHERE room_id = ? AND status = 'Active'");
    $stmt->execute([(int) $roomId]);
    return (int) $stmt->fetchColumn();
}

function promoteStudentsAdvanced($pdo, $studentIds, $toClass, $toSection, $sessionId = null) {
    $log = $pdo->prepare(
        "INSERT INTO promotion_logs (student_id, from_class, from_section, to_class, to_section, session_id) VALUES (?,?,?,?,?,?)"
    );
    $update = $pdo->prepare("UPDATE students SET class = ?, section = ? WHERE id = ?");
    $get = $pdo->prepare("SELECT class, section FROM students WHERE id = ?");
    $count = 0;
    foreach ($studentIds as $id) {
        $id = (int) $id;
        $get->execute([$id]);
        $row = $get->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            continue;
        }
        $update->execute([$toClass, $toSection, $id]);
        $log->execute([$id, $row['class'], $row['section'] ?? 'A', $toClass, $toSection, $sessionId]);
        $count++;
    }
    return $count;
}
