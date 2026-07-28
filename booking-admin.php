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

$adminPages = array(
    'open-close-date' => 'Open or Close Date',
    'add-update-reservation' => 'Add / Update Reservation',
    'calendar' => 'Calendar',
    'recent-reservations' => 'Recent Reservations',
    'hotel-apis' => 'Hotel APIs',
    'mysql-setup' => 'Create / Update MySQL',
);
$activePage = isset($_GET['page']) ? $_GET['page'] : 'calendar';
if (!isset($adminPages[$activePage])) $activePage = 'calendar';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = isset($_POST['username']) ? $_POST['username'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    if (booking_admin_username_ok($username) && booking_admin_password_ok($password)) {
        $_SESSION['booking_admin'] = true;
        header('Location: booking-admin.php?page=calendar');
        exit;
    }
    $error = 'Invalid username or password.';
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
            if ($to < booking_today()) {
                throw new Exception('Past dates are already blocked.');
            }
            if ($from < booking_today()) {
                $from = booking_today();
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

if ($loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_day_inventory'])) {
    if (!booking_check_csrf()) {
        $error = 'Session expired. Please try again.';
    } else {
        try {
            $date = booking_iso_date($_POST['inventory_date']);
            if (!$date) {
                throw new Exception('Please enter a valid inventory date.');
            }
            if ($date < booking_today()) {
                throw new Exception('Past dates are already blocked.');
            }
            $status = $_POST['status'] === 'closed' ? 'closed' : 'open';
            $rate = max(0, (float) $_POST['rate']);
            $minStay = max(1, (int) $_POST['min_stay']);
            $note = trim($_POST['note']);
            booking_upsert_inventory($date, $status, $rate, $minStay, $note);
            $message = 'Inventory updated for ' . $date . '.';
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

if ($loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_reservation_status'])) {
    if (!booking_check_csrf()) {
        $error = 'Session expired. Please try again.';
    } else {
        try {
            $updated = booking_update_reservation_status(
                isset($_POST['booking_id']) ? $_POST['booking_id'] : '',
                isset($_POST['reservation_status']) ? $_POST['reservation_status'] : 'pending'
            );
            $message = $updated ? 'Reservation status updated.' : 'Reservation not found or already had that status.';
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$csrf = $loggedIn ? booking_csrf() : '';
$month = isset($_GET['month']) ? booking_iso_date($_GET['month'] . '-01') : date('Y-m-01', strtotime(booking_today()));
if (!$month) $month = date('Y-m-01', strtotime(booking_today()));
$monthEnd = date('Y-m-t', strtotime($month));
$nextDay = date('Y-m-d', strtotime($monthEnd . ' +1 day'));
$inventory = array();
$reservations = array();
$reservationBlocks = array();
if ($loggedIn && booking_configured()) {
    try {
        $inventory = booking_get_inventory($month, $nextDay);
        $pdo = booking_pdo();
        $stmt = $pdo->prepare('SELECT * FROM booking_reservations WHERE check_in < ? AND check_out > ? ORDER BY check_in DESC LIMIT 50');
        $stmt->execute(array(date('Y-m-d', strtotime($nextDay . ' +60 days')), date('Y-m-d', strtotime($month . ' -60 days'))));
        $reservations = $stmt->fetchAll();
        foreach ($reservations as $reservation) {
            if ($reservation['status'] !== 'confirmed') continue;
            $blockStart = max($month, $reservation['check_in']);
            $blockEnd = min($nextDay, $reservation['check_out']);
            if ($blockEnd <= $blockStart) continue;
            foreach (booking_date_range($blockStart, $blockEnd) as $date) {
                if (!isset($reservationBlocks[$date])) $reservationBlocks[$date] = array();
                $reservationBlocks[$date][] = $reservation;
            }
        }
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
  <link rel="stylesheet" href="css/style.css?v=20260728-booking-engine-5">
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
          <label>Username</label>
          <input type="text" name="username" required autofocus value="admin">
          <label>Password</label>
          <input type="password" name="password" required>
          <button class="btn btn-primary" type="submit" name="login" value="1">Open Dashboard</button>
        </form>
      <?php else: ?>
        <?php if ($message): ?><div class="blog-admin-alert success"><?php echo booking_e($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="blog-admin-alert error"><?php echo booking_e($error); ?></div><?php endif; ?>

        <nav class="booking-admin-menu" aria-label="Booking admin menu">
          <a class="<?php echo $activePage === 'open-close-date' ? 'active' : ''; ?>" href="booking-admin.php?page=open-close-date">1. Open or Close Date</a>
          <a class="<?php echo $activePage === 'add-update-reservation' ? 'active' : ''; ?>" href="booking-admin.php?page=add-update-reservation">2. Add / Update Reservation</a>
          <a class="<?php echo $activePage === 'calendar' ? 'active' : ''; ?>" href="booking-admin.php?page=calendar">3. Calendar</a>
          <a class="<?php echo $activePage === 'recent-reservations' ? 'active' : ''; ?>" href="booking-admin.php?page=recent-reservations">4. Recent Reservations</a>
          <a class="<?php echo $activePage === 'hotel-apis' ? 'active' : ''; ?>" href="booking-admin.php?page=hotel-apis">5. Hotel APIs</a>
          <a class="<?php echo $activePage === 'mysql-setup' ? 'active' : ''; ?>" href="booking-admin.php?page=mysql-setup">6. Create / Update MySQL</a>
        </nav>

        <h2 class="booking-admin-page-title"><?php echo booking_e($adminPages[$activePage]); ?></h2>

        <?php if ($activePage === 'open-close-date' || $activePage === 'add-update-reservation'): ?>
        <div class="booking-admin-grid">
          <?php if ($activePage === 'open-close-date'): ?>
          <section class="blog-admin-card" id="open-close-date">
            <h2>Open / Close Dates</h2>
            <form method="post" class="booking-inventory-form">
              <input type="hidden" name="csrf" value="<?php echo booking_e($csrf); ?>">
              <div class="blog-admin-row">
                <div><label>From Date</label><input type="date" name="from_date" required value="<?php echo booking_e(booking_today()); ?>"></div>
                <div><label>To Date</label><input type="date" name="to_date" required value="<?php echo booking_e(booking_today()); ?>"></div>
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
          <?php endif; ?>

          <?php if ($activePage === 'add-update-reservation'): ?>
          <section class="blog-admin-card" id="add-update-reservation">
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
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($activePage === 'calendar'): ?>
        <section class="blog-admin-card booking-calendar-card" id="calendar">
          <div class="booking-month-nav">
            <a href="booking-admin.php?page=calendar&month=<?php echo date('Y-m', strtotime($month . ' -1 month')); ?>">&larr; Previous</a>
            <h2><?php echo date('F Y', strtotime($month)); ?></h2>
            <a href="booking-admin.php?page=calendar&month=<?php echo date('Y-m', strtotime($month . ' +1 month')); ?>">Next &rarr;</a>
          </div>
          <div class="booking-calendar-grid">
            <?php foreach (array('Mon','Tue','Wed','Thu','Fri','Sat','Sun') as $day): ?><div class="booking-day-head"><?php echo $day; ?></div><?php endforeach; ?>
            <?php
              $firstDow = (int) date('N', strtotime($month));
              for ($i = 1; $i < $firstDow; $i++) echo '<div class="booking-day muted"></div>';
              for ($day = 1; $day <= (int) date('t', strtotime($month)); $day++):
                $date = date('Y-m-d', strtotime($month . ' +' . ($day - 1) . ' days'));
                $row = isset($inventory[$date]) ? $inventory[$date] : null;
                $inventoryStatus = $row ? $row['status'] : 'open';
                $dayRate = $row ? (float) $row['rate'] : (float) booking_setting('BOOKING_DEFAULT_RATE', 3500);
                $minStay = $row ? (int) $row['min_stay'] : 1;
                $note = $row ? $row['note'] : '';
                $bookings = isset($reservationBlocks[$date]) ? $reservationBlocks[$date] : array();
                $isBooked = count($bookings) > 0;
                $isPast = $date < booking_today();
                $statusClass = $isPast ? 'past' : ($isBooked ? 'booked' : ($inventoryStatus === 'closed' ? 'closed' : 'open'));
                $statusLabel = $isPast ? 'blocked' : ($isBooked ? 'booked' : $inventoryStatus);
            ?>
              <div class="booking-day <?php echo booking_e($statusClass); ?>">
                <div class="booking-day-top">
                  <strong><?php echo $day; ?></strong>
                  <b>INR <?php echo booking_e(number_format($dayRate, 0)); ?></b>
                </div>
                <span><?php echo strtoupper($statusLabel); ?></span>
                <?php foreach ($bookings as $booking): ?>
                  <small><?php echo booking_e($booking['guest_name'] ?: $booking['booking_id']); ?></small>
                <?php endforeach; ?>
                <?php if ($row && $row['note']): ?><small><?php echo booking_e($row['note']); ?></small><?php endif; ?>
                <?php if ($isPast): ?><small>Past date</small><?php endif; ?>
                <?php if (!$isPast): ?>
                <form method="post" class="booking-day-edit">
                  <input type="hidden" name="csrf" value="<?php echo booking_e($csrf); ?>">
                  <input type="hidden" name="inventory_date" value="<?php echo booking_e($date); ?>">
                  <input type="hidden" name="min_stay" value="<?php echo booking_e($minStay); ?>">
                  <input type="hidden" name="note" value="<?php echo booking_e($note); ?>">
                  <select name="status" aria-label="Day status">
                    <option value="open" <?php echo $inventoryStatus === 'open' ? 'selected' : ''; ?>>Open</option>
                    <option value="closed" <?php echo $inventoryStatus === 'closed' ? 'selected' : ''; ?>>Closed</option>
                  </select>
                  <input type="number" name="rate" min="0" step="1" value="<?php echo booking_e((int) $dayRate); ?>" aria-label="Nightly rate">
                  <button type="submit" name="save_day_inventory" value="1">Save</button>
                </form>
                <?php endif; ?>
              </div>
            <?php endfor; ?>
          </div>
        </section>
        <?php endif; ?>

        <?php if ($activePage === 'recent-reservations'): ?>
        <section class="blog-admin-card" id="recent-reservations">
          <h2>Recent Reservations</h2>
          <div class="booking-reservation-list">
            <?php if (!$reservations): ?><p class="blog-muted">No reservations yet.</p><?php endif; ?>
            <?php foreach ($reservations as $reservation): ?>
              <div class="booking-reservation-row">
                <strong><?php echo booking_e($reservation['booking_id']); ?></strong>
                <span><?php echo booking_e($reservation['guest_name']); ?> · <?php echo booking_e($reservation['check_in']); ?> to <?php echo booking_e($reservation['check_out']); ?></span>
                <form method="post" class="booking-reservation-status-form">
                  <input type="hidden" name="csrf" value="<?php echo booking_e($csrf); ?>">
                  <input type="hidden" name="booking_id" value="<?php echo booking_e($reservation['booking_id']); ?>">
                  <select name="reservation_status" aria-label="Reservation status">
                    <?php foreach (array('pending', 'confirmed', 'cancelled') as $status): ?>
                      <option value="<?php echo $status; ?>" <?php echo $reservation['status'] === $status ? 'selected' : ''; ?>><?php echo ucfirst($status); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" name="update_reservation_status" value="1">Update</button>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        </section>
        <?php endif; ?>

        <?php if ($activePage === 'hotel-apis' || $activePage === 'mysql-setup'): ?>
        <div class="booking-admin-grid booking-admin-bottom-grid">
          <?php if ($activePage === 'hotel-apis'): ?>
          <section class="blog-admin-card" id="hotel-apis">
            <h2>Hotel APIs</h2>
            <p class="blog-muted">Use these endpoints for testing availability and Google Hotel Ads integration.</p>
            <div class="booking-api-list">
              <a class="btn btn-light" href="booking-engine-api.php?action=availability&check_in=<?php echo date('Y-m-d', strtotime(booking_today() . ' +1 day')); ?>&check_out=<?php echo date('Y-m-d', strtotime(booking_today() . ' +2 days')); ?>" target="_blank" rel="noopener">Test Availability API</a>
              <a class="btn btn-light" href="booking-engine-api.php?action=hotel-ads&check_in=<?php echo date('Y-m-d', strtotime(booking_today() . ' +1 day')); ?>&nights=1" target="_blank" rel="noopener">Test Hotel Ads API</a>
            </div>
            <code class="booking-api-code">booking-engine-api.php?action=availability&amp;check_in=2026-08-01&amp;check_out=2026-08-02</code>
            <code class="booking-api-code">booking-engine-api.php?action=hotel-ads&amp;check_in=2026-08-01&amp;nights=1</code>
          </section>
          <?php endif; ?>

          <?php if ($activePage === 'mysql-setup'): ?>
          <section class="blog-admin-card" id="mysql-setup">
            <h2>Create / Update MySQL</h2>
            <p class="blog-muted">Run this after first deployment or whenever the schema file is updated.</p>
            <form method="post" class="booking-schema-form">
              <input type="hidden" name="csrf" value="<?php echo booking_e($csrf); ?>">
              <button class="btn btn-primary" type="submit" name="install_schema" value="1">Create / Update MySQL Tables</button>
            </form>
          </section>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </main>
</body>
</html>
