<?php
// admin/db_setup.php — first-time install lives on Super Admin login
header('Content-Type: application/json; charset=utf-8');
http_response_code(403);
echo json_encode([
    'ok' => false,
    'error' => 'Database setup is done from Super Admin login, not School Admin.',
]);
