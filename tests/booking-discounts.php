<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../booking-engine-lib.php';

function expect_discount($rates, $rule, $subtotal, $discount) {
    $result = booking_apply_discount($rates, $rule);
    if (abs($result['subtotal'] - $subtotal) > 0.001 || abs($result['discount_amount'] - $discount) > 0.001) {
        throw new Exception('Unexpected pricing: ' . json_encode($result));
    }
}

expect_discount(array(3500), array('discount_per_night' => 500), 3000, 500);
expect_discount(array(2500, 3500), array('discount_per_night' => 500), 5000, 1000);
expect_discount(array_fill(0, 7, 2500), array('discount_per_night' => 500), 14000, 3500);
expect_discount(array_fill(0, 30, 2500), array('discount_per_night' => 1000), 45000, 30000);
expect_discount(array(100, 1000), array('discount_per_night' => 500), 500, 600);
expect_discount(array(3500), array('discount_per_night' => 0, 'discount_percent' => 40), 3500, 0);
expect_discount(array(100.50, 200.25), array('discount_per_night' => 10.25), 280.25, 20.50);
expect_discount(array(2500, 3500), array('discount_per_night' => null, 'discount_percent' => 10), 5400, 600);
expect_discount(array(2500, 3500), array('discount_percent' => 10), 5400, 600);
expect_discount(array(0.05), array('discount_percent' => 10), 0.04, 0.01);
foreach (array(-1, 'invalid', '', 100000000, INF, array()) as $invalid) {
    try {
        booking_upsert_discount(1, $invalid, 'Test');
    } catch (Exception $e) {
        if ($e->getMessage() !== 'Enter a valid discount amount of zero or more.') throw $e;
        continue;
    }
    throw new Exception('Invalid amount accepted.');
}
echo "Discount pricing and validation checks passed.\n";
