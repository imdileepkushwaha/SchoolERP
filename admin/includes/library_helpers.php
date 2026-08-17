<?php
// admin/includes/library_helpers.php

function ensureLibrarySchema($pdo): void {
    static $done = false;
    if ($done) {
        return;
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS `library_categories` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `name` (`name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `library_books` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `category_id` int(11) DEFAULT NULL,
        `title` varchar(200) NOT NULL,
        `author` varchar(120) DEFAULT NULL,
        `isbn` varchar(40) DEFAULT NULL,
        `publisher` varchar(120) DEFAULT NULL,
        `copies` int(11) NOT NULL DEFAULT 1,
        `available` int(11) NOT NULL DEFAULT 1,
        `shelf` varchar(40) DEFAULT NULL,
        `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `category_id` (`category_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `library_issues` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `book_id` int(11) NOT NULL,
        `student_id` int(11) NOT NULL,
        `issue_date` date NOT NULL,
        `due_date` date DEFAULT NULL,
        `return_date` date DEFAULT NULL,
        `status` enum('Issued','Returned') NOT NULL DEFAULT 'Issued',
        `fine` decimal(10,2) NOT NULL DEFAULT 0.00,
        `remarks` varchar(255) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `book_id` (`book_id`),
        KEY `student_id` (`student_id`),
        KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $catCount = (int) $pdo->query('SELECT COUNT(*) FROM library_categories')->fetchColumn();
    if ($catCount === 0) {
        $stmt = $pdo->prepare('INSERT INTO library_categories (name) VALUES (?)');
        foreach (['Textbook', 'Reference', 'Fiction', 'Magazine'] as $name) {
            $stmt->execute([$name]);
        }
    }

    $done = true;
}

function getLibraryStats($pdo): array {
    ensureLibrarySchema($pdo);
    return [
        'books' => (int) $pdo->query("SELECT COUNT(*) FROM library_books WHERE status='Active'")->fetchColumn(),
        'copies' => (int) $pdo->query("SELECT COALESCE(SUM(copies),0) FROM library_books WHERE status='Active'")->fetchColumn(),
        'available' => (int) $pdo->query("SELECT COALESCE(SUM(available),0) FROM library_books WHERE status='Active'")->fetchColumn(),
        'issued' => (int) $pdo->query("SELECT COUNT(*) FROM library_issues WHERE status='Issued'")->fetchColumn(),
        'overdue' => (int) $pdo->query("SELECT COUNT(*) FROM library_issues WHERE status='Issued' AND due_date IS NOT NULL AND due_date < CURDATE()")->fetchColumn(),
    ];
}
