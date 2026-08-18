<?php
require_once __DIR__ . '/booking-engine-lib.php';
booking_load_config();
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (empty($_SESSION['booking_admin'])) {
    http_response_code(403);
    echo json_encode(array('ok' => false, 'error' => 'Admin login required.'));
    exit;
}

try {
    $pdo = booking_pdo();
    $stmt = $pdo->query("SELECT COUNT(*) AS pending_count FROM booking_reservations WHERE status = 'pending'");
    $pending = $stmt->fetch();

    $stmt = $pdo->query("SELECT booking_id, guest_name, check_in, check_out, status, created_at FROM booking_reservations ORDER BY created_at DESC LIMIT 1");
    $latest = $stmt->fetch();

    echo json_encode(array(
        'ok' => true,
        'pending_count' => (int) ($pending ? $pending['pending_count'] : 0),
        'latest' => $latest ?: null,
        'checked_at' => date('c')
    ), JSON_UNESCAPED_SLASHES);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array('ok' => false, 'error' => $e->getMessage()));
}
