<?php
require_once __DIR__ . '/booking-engine-lib.php';
booking_load_config();
session_start();

$message = '';
$error = '';

if (isset($_GET['logout'])) {
    unset($_SESSION['booking_admin']);
    header('Location: booking-admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (booking_admin_password_ok(isset($_POST['password']) ? $_POST['password'] : '')) {
        $_SESSION['booking_admin'] = true;
        header('Location: booking-admin.php');
        exit;
    }
    $error = 'Invalid password.';
}

$loggedIn = !empty($_SESSION['booking_admin']);

if ($loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install_schema'])) {
    if (!booking_check_csrf()) {
        $error = 'Session expired. Please try again.';
    } else {
        try {
            booking_install_schema();
            $message = 'Booking engine tables are ready.';
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

if ($loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_inventory'])) {
    if (!booking_check_csrf()) {
        $error = 'Session expired. Please try again.';
    } else {
        try {
            $from = booking_iso_date($_POST['from_date']);
            $to = booking_iso_date($_POST['to_date']);
            if (!$from || !$to || $to < $from) {
                throw new Exception('Please enter a valid inventory date range.');
            }
            $status = $_POST['status'] === 'closed' ? 'closed' : 'open';
            $rate = max(0, (float) $_POST['rate']);
            $minStay = max(1, (int) $_POST['min_stay']);
            $note = trim($_POST['note']);
            foreach (booking_date_range($from, date('Y-m-d', strtotime($to . ' +1 day'))) as $date) {
                booking_upsert_inventory($date, $status, $rate, $minStay, $note);
            }
            $message = 'Inventory updated from ' . $from . ' to ' . $to . '.';
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

if ($loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_reservation'])) {
    if (!booking_check_csrf()) {
        $error = 'Session expired. Please try again.';
    } else {
        try {
            $bookingId = booking_create_reservation(array(
                'booking_id' => $_POST['booking_id'],
                'name' => $_POST['name'],
                'phone' => $_POST['phone'],
                'check_in' => $_POST['check_in'],
                'check_out' => $_POST['check_out'],
                'status' => $_POST['reservation_status'],
                'source' => 'admin',
                'note' => $_POST['reservation_note'],
            ));
            $message = 'Reservation saved: ' . $bookingId;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$csrf = $loggedIn ? booking_csrf() : '';
$month = isset($_GET['month']) ? booking_iso_date($_GET['month'] . '-01') : date('Y-m-01');
if (!$month) $month = date('Y-m-01');
$monthEnd = date('Y-m-t', strtotime($month));
$nextDay = date('Y-m-d', strtotime($monthEnd . ' +1 day'));
$inventory = array();
$reservations = array();
if ($loggedIn && booking_configured()) {
    try {
        $inventory = booking_get_inventory($month, $nextDay);
        $pdo = booking_pdo();
        $stmt = $pdo->prepare('SELECT * FROM booking_reservations WHERE check_in < ? AND check_out > ? ORDER BY check_in DESC LIMIT 50');
        $stmt->execute(array(date('Y-m-d', strtotime($nextDay . ' +60 days')), date('Y-m-d', strtotime($month . ' -60 days'))));
        $reservations = $stmt->fetchAll();
    } catch (Exception $e) {
        $error = $error ?: $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Booking Engine Admin | Upper Crest Homestay</title>
  <meta name="robots" content="noindex, nofollow" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" />
  <link rel="stylesheet" href="css/style.css?v=20260728-booking-engine">
</head>
<body class="blog-admin-page">
  <main class="blog-admin-wrap booking-admin-wrap">
    <div class="blog-admin-shell">
      <div class="blog-admin-head">
        <div>
          <div class="eyebrow">Upper Crest Homestay</div>
          <h1>Booking Engine</h1>
        </div>
        <?php if ($loggedIn): ?><a class="btn btn-light" href="booking-admin.php?logout=1">Logout</a><?php endif; ?>
      </div>

      <?php if (!booking_admin_configured() || !booking_configured()): ?>
        <div class="blog-admin-alert error">Booking engine is not configured. Copy <strong>booking-admin-config.example.php</strong> to <strong>booking-admin-config.php</strong> and add MySQL/admin credentials.</div>
      <?php elseif (!$loggedIn): ?>
        <form class="blog-admin-card blog-login" method="post">
          <h2>Sign In</h2>
          <?php if ($error): ?><div class="blog-admin-alert error"><?php echo booking_e($error); ?></div><?php endif; ?>
          <label>Password</label>
          <input type="password" name="password" required autofocus>
          <button class="btn btn-primary" type="submit" name="login" value="1">Open Dashboard</button>
        </form>
      <?php else: ?>
        <?php if ($message): ?><div class="blog-admin-alert success"><?php echo booking_e($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="blog-admin-alert error"><?php echo booking_e($error); ?></div><?php endif; ?>

        <div class="booking-admin-actions-top">
          <form method="post">
            <input type="hidden" name="csrf" value="<?php echo booking_e($csrf); ?>">
            <button class="btn btn-primary" type="submit" name="install_schema" value="1">Create / Update MySQL Tables</button>
          </form>
          <a class="btn btn-light" href="booking-engine-api.php?action=hotel-ads&check_in=<?php echo date('Y-m-d', strtotime('+1 day')); ?>&nights=1" target="_blank" rel="noopener">Test Hotel Ads API</a>
        </div>

        <div class="booking-admin-grid">
          <section class="blog-admin-card">
            <h2>Open / Close Dates</h2>
            <form method="post" class="booking-inventory-form">
              <input type="hidden" name="csrf" value="<?php echo booking_e($csrf); ?>">
              <div class="blog-admin-row">
                <div><label>From Date</label><input type="date" name="from_date" required value="<?php echo date('Y-m-d'); ?>"></div>
                <div><label>To Date</label><input type="date" name="to_date" required value="<?php echo date('Y-m-d'); ?>"></div>
              </div>
              <div class="blog-admin-row">
                <div><label>Status</label><select name="status"><option value="open">Open</option><option value="closed">Closed</option></select></div>
                <div><label>Nightly Rate</label><input type="number" name="rate" min="0" step="1" value="<?php echo booking_e(booking_setting('BOOKING_DEFAULT_RATE', 3500)); ?>"></div>
              </div>
              <label>Minimum Stay</label>
              <input type="number" name="min_stay" min="1" value="1">
              <label>Narration / Note</label>
              <textarea name="note" rows="3" placeholder="Owner blocked, maintenance, confirmed phone booking..."></textarea>
              <button class="btn btn-primary" type="submit" name="save_inventory" value="1">Save Inventory</button>
            </form>
          </section>

          <section class="blog-admin-card">
            <h2>Add / Update Reservation</h2>
            <form method="post">
              <input type="hidden" name="csrf" value="<?php echo booking_e($csrf); ?>">
              <label>Booking ID</label><input type="text" name="booking_id" placeholder="Leave blank for auto ID">
              <div class="blog-admin-row">
                <div><label>Name</label><input type="text" name="name"></div>
                <div><label>Phone</label><input type="text" name="phone"></div>
              </div>
              <div class="blog-admin-row">
                <div><label>Check-in</label><input type="date" name="check_in" required></div>
                <div><label>Check-out</label><input type="date" name="check_out" required></div>
              </div>
              <label>Status</label>
              <select name="reservation_status"><option value="pending">Pending</option><option value="confirmed">Confirmed</option><option value="cancelled">Cancelled</option></select>
              <label>Narration / Note</label><textarea name="reservation_note" rows="3"></textarea>
              <button class="btn btn-primary" type="submit" name="save_reservation" value="1">Save Reservation</button>
            </form>
          </section>
        </div>

        <section class="blog-admin-card booking-calendar-card">
          <div class="booking-month-nav">
            <a href="booking-admin.php?month=<?php echo date('Y-m', strtotime($month . ' -1 month')); ?>">&larr; Previous</a>
            <h2><?php echo date('F Y', strtotime($month)); ?></h2>
            <a href="booking-admin.php?month=<?php echo date('Y-m', strtotime($month . ' +1 month')); ?>">Next &rarr;</a>
          </div>
          <div class="booking-calendar-grid">
            <?php foreach (array('Mon','Tue','Wed','Thu','Fri','Sat','Sun') as $day): ?><div class="booking-day-head"><?php echo $day; ?></div><?php endforeach; ?>
            <?php
              $firstDow = (int) date('N', strtotime($month));
              for ($i = 1; $i < $firstDow; $i++) echo '<div class="booking-day muted"></div>';
              for ($day = 1; $day <= (int) date('t', strtotime($month)); $day++):
                $date = date('Y-m-d', strtotime($month . ' +' . ($day - 1) . ' days'));
                $row = isset($inventory[$date]) ? $inventory[$date] : null;
                $status = $row ? $row['status'] : 'open';
            ?>
              <div class="booking-day <?php echo $status === 'closed' ? 'closed' : 'open'; ?>">
                <strong><?php echo $day; ?></strong>
                <span><?php echo strtoupper($status); ?></span>
                <?php if ($row && $row['note']): ?><small><?php echo booking_e($row['note']); ?></small><?php endif; ?>
              </div>
            <?php endfor; ?>
          </div>
        </section>

        <section class="blog-admin-card">
          <h2>Recent Reservations</h2>
          <div class="booking-reservation-list">
            <?php if (!$reservations): ?><p class="blog-muted">No reservations yet.</p><?php endif; ?>
            <?php foreach ($reservations as $reservation): ?>
              <div class="booking-reservation-row">
                <strong><?php echo booking_e($reservation['booking_id']); ?></strong>
                <span><?php echo booking_e($reservation['guest_name']); ?> · <?php echo booking_e($reservation['check_in']); ?> to <?php echo booking_e($reservation['check_out']); ?></span>
                <em><?php echo booking_e($reservation['status']); ?></em>
              </div>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endif; ?>
    </div>
  </main>
</body>
</html>
