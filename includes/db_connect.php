<?php
// includes/db_connect.php

$host = 'localhost';
$db_name = 'SchoolERP_db';
$username = 'root'; // default XAMPP/WAMP username
$password = ''; // default XAMPP/WAMP password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}

date_default_timezone_set('Asia/Kolkata');
?>
