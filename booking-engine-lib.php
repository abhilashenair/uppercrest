<?php

function booking_load_config() {
    $config = __DIR__ . '/booking-admin-config.php';
    if (is_file($config)) {
        require_once $config;
    }
}

function booking_configured() {
    booking_load_config();
    return defined('BOOKING_DB_HOST') && defined('BOOKING_DB_NAME') && defined('BOOKING_DB_USER') && defined('BOOKING_DB_PASS');
}

function booking_setting($name, $fallback) {
    booking_load_config();
    return defined($name) ? constant($name) : $fallback;
}

function booking_pdo() {
    static $pdo = null;
    if ($pdo) return $pdo;
    if (!booking_configured()) {
        throw new Exception('Booking engine database is not configured.');
    }
    $charset = booking_setting('BOOKING_DB_CHARSET', 'utf8mb4');
    $dsn = 'mysql:host=' . BOOKING_DB_HOST . ';dbname=' . BOOKING_DB_NAME . ';charset=' . $charset;
    $pdo = new PDO($dsn, BOOKING_DB_USER, BOOKING_DB_PASS, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ));
    return $pdo;
}

function booking_install_schema() {
    $pdo = booking_pdo();
    $sql = file_get_contents(__DIR__ . '/booking-engine-schema.sql');
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $statement) {
        if ($statement !== '') {
            $pdo->exec($statement);
        }
    }
}

function booking_e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function booking_json($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}

function booking_xml($xml, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/xml; charset=utf-8');
    header('Cache-Control: no-store');
    echo $xml;
    exit;
}

function booking_iso_date($value) {
    $value = trim((string) $value);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return $value;
    if (preg_match('/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})$/', $value, $m)) {
        return $m[3] . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT);
    }
    $time = strtotime($value);
    return $time ? date('Y-m-d', $time) : '';
}

function booking_date_range($checkIn, $checkOut) {
    $dates = array();
    $start = new DateTime($checkIn);
    $end = new DateTime($checkOut);
    while ($start < $end) {
        $dates[] = $start->format('Y-m-d');
        $start->modify('+1 day');
    }
    return $dates;
}

function booking_nights($checkIn, $checkOut) {
    $start = new DateTime($checkIn);
    $end = new DateTime($checkOut);
    return max(0, (int) $start->diff($end)->days);
}

function booking_get_inventory($from, $to) {
    $pdo = booking_pdo();
    $stmt = $pdo->prepare('SELECT * FROM booking_inventory WHERE inventory_date >= ? AND inventory_date < ? ORDER BY inventory_date');
    $stmt->execute(array($from, $to));
    $rows = array();
    foreach ($stmt->fetchAll() as $row) {
        $rows[$row['inventory_date']] = $row;
    }
    return $rows;
}

function booking_confirmed_overlaps($checkIn, $checkOut) {
    $pdo = booking_pdo();
    $stmt = $pdo->prepare("SELECT * FROM booking_reservations WHERE status = 'confirmed' AND check_in < ? AND check_out > ? ORDER BY check_in");
    $stmt->execute(array($checkOut, $checkIn));
    return $stmt->fetchAll();
}

function booking_availability($checkIn, $checkOut) {
    $checkIn = booking_iso_date($checkIn);
    $checkOut = booking_iso_date($checkOut);
    if (!$checkIn || !$checkOut || $checkOut <= $checkIn) {
        return array('ok' => false, 'available' => false, 'error' => 'Invalid check-in or check-out date.');
    }
    $nights = booking_nights($checkIn, $checkOut);
    $inventory = booking_get_inventory($checkIn, $checkOut);
    $closed = array();
    $notes = array();
    $total = 0.0;
    $defaultRate = (float) booking_setting('BOOKING_DEFAULT_RATE', 3500);
    foreach (booking_date_range($checkIn, $checkOut) as $date) {
        $row = isset($inventory[$date]) ? $inventory[$date] : null;
        $rate = $row ? (float) $row['rate'] : $defaultRate;
        $total += $rate;
        if ($row && $row['status'] === 'closed') {
            $closed[] = $date;
            if (!empty($row['note'])) $notes[] = $date . ': ' . $row['note'];
        }
    }
    $overlaps = booking_confirmed_overlaps($checkIn, $checkOut);
    foreach ($overlaps as $booking) {
        $closed[] = $booking['check_in'] . ' to ' . $booking['check_out'];
        $notes[] = 'Confirmed booking ' . $booking['booking_id'];
    }
    $available = count($closed) === 0;
    $taxRate = (float) booking_setting('BOOKING_TAX_RATE', 0);
    $taxes = round($total * $taxRate, 2);
    return array(
        'ok' => true,
        'available' => $available,
        'check_in' => $checkIn,
        'check_out' => $checkOut,
        'nights' => $nights,
        'currency' => booking_setting('BOOKING_CURRENCY', 'INR'),
        'base_rate' => round($nights ? $total / $nights : 0, 2),
        'subtotal' => round($total, 2),
        'taxes' => $taxes,
        'total' => round($total + $taxes, 2),
        'closed_dates' => array_values(array_unique($closed)),
        'notes' => $notes,
    );
}

function booking_upsert_inventory($date, $status, $rate, $minStay, $note) {
    $pdo = booking_pdo();
    $stmt = $pdo->prepare("INSERT INTO booking_inventory (inventory_date, status, rate, min_stay, note) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE status = VALUES(status), rate = VALUES(rate), min_stay = VALUES(min_stay), note = VALUES(note)");
    $stmt->execute(array($date, $status, $rate, $minStay, $note));
}

function booking_create_reservation($data) {
    $checkIn = booking_iso_date(isset($data['check_in']) ? $data['check_in'] : '');
    $checkOut = booking_iso_date(isset($data['check_out']) ? $data['check_out'] : '');
    if (!$checkIn || !$checkOut || $checkOut <= $checkIn) {
        throw new Exception('Invalid reservation dates.');
    }
    $bookingId = !empty($data['booking_id']) ? preg_replace('/[^A-Za-z0-9-]/', '', $data['booking_id']) : booking_new_id();
    $pdo = booking_pdo();
    $stmt = $pdo->prepare("INSERT INTO booking_reservations (booking_id, guest_name, phone, check_in, check_out, status, source, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE guest_name = VALUES(guest_name), phone = VALUES(phone), check_in = VALUES(check_in), check_out = VALUES(check_out), status = VALUES(status), note = VALUES(note)");
    $stmt->execute(array(
        $bookingId,
        isset($data['name']) ? trim($data['name']) : '',
        isset($data['phone']) ? trim($data['phone']) : '',
        $checkIn,
        $checkOut,
        booking_reservation_status(isset($data['status']) ? $data['status'] : 'pending'),
        isset($data['source']) ? trim($data['source']) : 'website',
        isset($data['note']) ? trim($data['note']) : '',
    ));
    return $bookingId;
}

function booking_update_reservation_status($bookingId, $status) {
    $bookingId = preg_replace('/[^A-Za-z0-9-]/', '', (string) $bookingId);
    if (!$bookingId) {
        throw new Exception('Booking ID is required.');
    }
    $status = booking_reservation_status($status);
    $pdo = booking_pdo();
    $stmt = $pdo->prepare('UPDATE booking_reservations SET status = ?, updated_at = NOW() WHERE booking_id = ?');
    $stmt->execute(array($status, $bookingId));
    return $stmt->rowCount() > 0;
}

function booking_new_id() {
    $bytes = function_exists('random_bytes') ? random_bytes(3) : openssl_random_pseudo_bytes(3);
    return 'UC-' . date('YmdHis') . '-' . strtoupper(substr(bin2hex($bytes), 0, 4));
}

function booking_reservation_status($status) {
    $status = strtolower(trim((string) $status));
    if ($status === 'confirmed') return 'confirmed';
    if ($status === 'cancelled' || $status === 'canceled') return 'cancelled';
    return 'pending';
}

function booking_admin_password_ok($password) {
    if (defined('BOOKING_ADMIN_PASSWORD_HASH') && BOOKING_ADMIN_PASSWORD_HASH) {
        return password_verify($password, BOOKING_ADMIN_PASSWORD_HASH);
    }
    if (defined('BOOKING_ADMIN_PASSWORD') && BOOKING_ADMIN_PASSWORD) {
        return hash_equals(BOOKING_ADMIN_PASSWORD, $password);
    }
    return false;
}

function booking_admin_configured() {
    booking_load_config();
    return (defined('BOOKING_ADMIN_PASSWORD_HASH') && BOOKING_ADMIN_PASSWORD_HASH) || (defined('BOOKING_ADMIN_PASSWORD') && BOOKING_ADMIN_PASSWORD);
}

function booking_csrf() {
    if (empty($_SESSION['booking_csrf'])) {
        $_SESSION['booking_csrf'] = function_exists('random_bytes') ? bin2hex(random_bytes(24)) : bin2hex(openssl_random_pseudo_bytes(24));
    }
    return $_SESSION['booking_csrf'];
}

function booking_check_csrf() {
    return isset($_POST['csrf']) && hash_equals(booking_csrf(), $_POST['csrf']);
}
