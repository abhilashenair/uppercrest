<?php
require_once __DIR__ . '/booking-engine-lib.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }
        $bookingId = booking_create_reservation($payload);
        booking_json(array('ok' => true, 'booking_id' => $bookingId));
    }

    $action = isset($_GET['action']) ? $_GET['action'] : 'availability';

    if ($action === 'availability') {
        $checkIn = isset($_GET['check_in']) ? $_GET['check_in'] : '';
        $checkOut = isset($_GET['check_out']) ? $_GET['check_out'] : '';
        booking_json(booking_availability($checkIn, $checkOut));
    }

    if ($action === 'rate-plan') {
        $start = isset($_GET['start']) ? booking_iso_date($_GET['start']) : booking_today();
        if (!$start || $start < booking_today()) $start = booking_today();
        $plans = array();
        foreach (array(1, 2, 7) as $minimumNights) {
            $checkOut = date('Y-m-d', strtotime($start . ' +' . $minimumNights . ' days'));
            $result = booking_availability($start, $checkOut);
            $plans[] = array(
                'minimum_nights' => (int) $minimumNights,
                'nightly_rate' => (float) $result['base_rate'],
                'currency' => $result['currency'],
                'total' => (float) $result['subtotal'],
                'available' => (bool) $result['available'],
                'check_in' => $result['check_in'],
                'check_out' => $result['check_out'],
                'rate_plan' => $result['rate_plan'],
            );
        }
        booking_json(array(
            'ok' => true,
            'currency' => booking_setting('BOOKING_CURRENCY', 'INR'),
            'rack_rate' => (float) booking_setting('BOOKING_RACK_RATE', 4500),
            'start_date' => $start,
            'plans' => $plans,
        ));
    }

    if ($action === 'booked-dates') {
        $from = isset($_GET['from']) ? booking_iso_date($_GET['from']) : booking_today();
        if (!$from) $from = booking_today();
        $days = isset($_GET['days']) ? max(1, min(730, (int) $_GET['days'])) : 365;
        $to = date('Y-m-d', strtotime($from . ' +' . $days . ' days'));
        $inventory = booking_get_inventory($from, $to);
        $booked = array();
        foreach (booking_date_range($from, min($to, booking_today())) as $date) {
            $booked[] = array(
                'check_in' => $date,
                'check_out' => date('Y-m-d', strtotime($date . ' +1 day')),
                'status' => 'confirmed',
                'reason' => 'past'
            );
        }
        foreach ($inventory as $date => $row) {
            if ($row['status'] === 'closed') {
                $booked[] = array(
                    'check_in' => $date,
                    'check_out' => date('Y-m-d', strtotime($date . ' +1 day')),
                    'status' => 'confirmed',
                    'reason' => $row['note'] ?: 'closed'
                );
            }
        }
        foreach (booking_confirmed_overlaps($from, $to) as $reservation) {
            $booked[] = array(
                'check_in' => max($from, $reservation['check_in']),
                'check_out' => min($to, $reservation['check_out']),
                'status' => 'confirmed',
                'booking_id' => $reservation['booking_id']
            );
        }
        booking_json(array('ok' => true, 'bookedDates' => $booked, 'bookings' => $booked));
    }

    if ($action === 'hotel-ads') {
        $checkIn = isset($_GET['check_in']) ? booking_iso_date($_GET['check_in']) : '';
        if (!$checkIn) booking_json(array('ok' => false, 'error' => 'check_in is required.'), 400);
        $nights = isset($_GET['nights']) ? max(1, (int) $_GET['nights']) : 1;
        $checkOut = date('Y-m-d', strtotime($checkIn . ' +' . $nights . ' days'));
        $result = booking_availability($checkIn, $checkOut);
        $result['hotel_id'] = booking_setting('BOOKING_PROPERTY_ID', 'uppercrest');
        $result['hotel_name'] = booking_setting('BOOKING_PROPERTY_NAME', 'The Upper Crest');
        $result['landing_page'] = 'https://theuppercrest.in/?check_in=' . urlencode($result['check_in']) . '&check_out=' . urlencode($result['check_out']) . '#booking';
        booking_json($result);
    }

    if ($action === 'hotel-ads-xml') {
        $checkIn = isset($_GET['check_in']) ? booking_iso_date($_GET['check_in']) : '';
        if (!$checkIn) booking_json(array('ok' => false, 'error' => 'check_in is required.'), 400);
        $nights = isset($_GET['nights']) ? max(1, (int) $_GET['nights']) : 1;
        $checkOut = date('Y-m-d', strtotime($checkIn . ' +' . $nights . ' days'));
        $result = booking_availability($checkIn, $checkOut);
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<UpperCrestAvailability>';
        $xml .= '<HotelId>' . booking_e(booking_setting('BOOKING_PROPERTY_ID', 'uppercrest')) . '</HotelId>';
        $xml .= '<CheckIn>' . booking_e($result['check_in']) . '</CheckIn>';
        $xml .= '<Nights>' . (int) $result['nights'] . '</Nights>';
        $xml .= '<Available>' . ($result['available'] ? 'true' : 'false') . '</Available>';
        $xml .= '<Currency>' . booking_e($result['currency']) . '</Currency>';
        $xml .= '<Total>' . booking_e($result['total']) . '</Total>';
        $xml .= '<LandingPage>https://theuppercrest.in/</LandingPage>';
        $xml .= '</UpperCrestAvailability>';
        booking_xml($xml);
    }

    booking_json(array('ok' => false, 'error' => 'Unknown action.'), 404);
} catch (Exception $e) {
    booking_json(array('ok' => false, 'error' => $e->getMessage()), 500);
}
