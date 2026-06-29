<?php
$CSV_URL = "https://docs.google.com/spreadsheets/d/1h9MvSrn-GdQD_z4Jw2QJfUHelJSjGukHSCuZcG3QxHA/export?format=csv&gid=0";
$response = @file_get_contents($CSV_URL);
$bookings = [];
if ($response) {
    $rows = array_map('str_getcsv', explode("\n", trim($response)));
    array_shift($rows);
    foreach ($rows as $row) {
        if (count($row) < 6) continue;
        $status = strtolower(trim($row[5]));
        if ($status !== 'confirmed') continue;
        $ci_parts = explode('-', trim($row[3]));
        $co_parts = explode('-', trim($row[4]));
        if (count($ci_parts) !== 3 || count($co_parts) !== 3) continue;
        $ci = $ci_parts[2] . $ci_parts[1] . $ci_parts[0];
        $co = $co_parts[2] . $co_parts[1] . $co_parts[0];
        if ($ci && $co) $bookings[] = ['check_in' => $ci, 'check_out' => $co];
    }
}
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: inline; filename="uppercrest.ics"');
header('Cache-Control: no-cache, must-revalidate');
$out  = "BEGIN:VCALENDAR\r\n";
$out .= "VERSION:2.0\r\n";
$out .= "PRODID:-//Upper Crest Homestay//Booking Calendar//EN\r\n";
$out .= "CALSCALE:GREGORIAN\r\n";
$out .= "METHOD:PUBLISH\r\n";
$out .= "X-WR-CALNAME:Upper Crest Homestay - Bookings\r\n";
$out .= "X-WR-TIMEZONE:Asia/Kolkata\r\n";
foreach ($bookings as $b) {
    $uid = $b['check_in'].'-'.$b['check_out'].'-'.md5($b['check_in'].$b['check_out']).'@theuppercrest.in';
    $now = gmdate('Ymd\THis\Z');
    $out .= "BEGIN:VEVENT\r\n";
    $out .= "UID:".$uid."\r\n";
    $out .= "DTSTAMP:".$now."\r\n";
    $out .= "DTSTART;VALUE=DATE:".$b['check_in']."\r\n";
    $out .= "DTEND;VALUE=DATE:".$b['check_out']."\r\n";
    $out .= "SUMMARY:BOOKED - Upper Crest Homestay\r\n";
    $out .= "STATUS:CONFIRMED\r\n";
    $out .= "TRANSP:OPAQUE\r\n";
    $out .= "END:VEVENT\r\n";
}
$out .= "END:VCALENDAR\r\n";
echo $out;
?>
