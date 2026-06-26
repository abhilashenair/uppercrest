<?php
$SHEET_URL = "https://script.google.com/macros/s/AKfycbxZ0GDPcMmRdq2duuhd6wiW2PjP_A7LpaDXTWrtOJVGKCBMMyp99syPhcQyD2ia1V_QMQ/exec";
$response = @file_get_contents($SHEET_URL);
$data = json_decode($response, true);
$bookings = isset($data['bookedDates']) ? $data['bookedDates'] : [];
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: inline; filename="uppercrest.ics"');
header('Cache-Control: no-cache, must-revalidate');
$out = "BEGIN:VCALENDAR\r\n";
$out .= "VERSION:2.0\r\n";
$out .= "PRODID:-//Upper Crest Homestay//Booking Calendar//EN\r\n";
$out .= "CALSCALE:GREGORIAN\r\n";
$out .= "METHOD:PUBLISH\r\n";
$out .= "X-WR-CALNAME:Upper Crest Homestay - Bookings\r\n";
$out .= "X-WR-TIMEZONE:Asia/Kolkata\r\n";
foreach ($bookings as $booking) {
    $ci = isset($booking['check_in'])  ? str_replace('-', '', $booking['check_in'])  : '';
    $co = isset($booking['check_out']) ? str_replace('-', '', $booking['check_out']) : '';
    if (!$ci || !$co) continue;
    $uid = $ci.'-'.$co.'-'.md5($ci.$co).'@theuppercrest.in';
    $now = gmdate('Ymd\THis\Z');
    $out .= "BEGIN:VEVENT\r\n";
    $out .= "UID:".$uid."\r\n";
    $out .= "DTSTAMP:".$now."\r\n";
    $out .= "DTSTART;VALUE=DATE:".$ci."\r\n";
    $out .= "DTEND;VALUE=DATE:".$co."\r\n";
    $out .= "SUMMARY:BOOKED - Upper Crest Homestay\r\n";
    $out .= "STATUS:CONFIRMED\r\n";
    $out .= "TRANSP:OPAQUE\r\n";
    $out .= "END:VEVENT\r\n";
}
$out .= "END:VCALENDAR\r\n";
echo $out;
?>
