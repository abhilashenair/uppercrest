<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=1800');

$config = __DIR__ . '/google-reviews-config.php';
if (is_file($config)) {
    require $config;
}

$apiKey = defined('GOOGLE_MAPS_API_KEY') ? GOOGLE_MAPS_API_KEY : (getenv('GOOGLE_MAPS_API_KEY') ?: ($_SERVER['GOOGLE_MAPS_API_KEY'] ?? ''));
$placeId = defined('GOOGLE_PLACE_ID') ? GOOGLE_PLACE_ID : (getenv('GOOGLE_PLACE_ID') ?: ($_SERVER['GOOGLE_PLACE_ID'] ?? ''));
$placeQuery = defined('GOOGLE_PLACE_QUERY') ? GOOGLE_PLACE_QUERY : (getenv('GOOGLE_PLACE_QUERY') ?: 'The Upper Crest Poikattusserry Aluva Kerala');

if (!$apiKey) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Google Maps API key is not configured.']);
    exit;
}

function google_reviews_get_json($url) {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false || $code >= 400) {
            return null;
        }
        return json_decode($body, true);
    }

    $context = stream_context_create(['http' => ['timeout' => 12]]);
    $body = @file_get_contents($url, false, $context);
    return $body ? json_decode($body, true) : null;
}

if (!$placeId) {
    $findUrl = 'https://maps.googleapis.com/maps/api/place/findplacefromtext/json?' . http_build_query([
        'input' => $placeQuery,
        'inputtype' => 'textquery',
        'fields' => 'place_id',
        'key' => $apiKey,
    ]);
    $findData = google_reviews_get_json($findUrl);
    if (!isset($findData['candidates'][0]['place_id'])) {
        http_response_code(502);
        echo json_encode(['ok' => false, 'error' => 'Google Place ID could not be found.']);
        exit;
    }
    $placeId = $findData['candidates'][0]['place_id'];
}

$detailsUrl = 'https://maps.googleapis.com/maps/api/place/details/json?' . http_build_query([
    'place_id' => $placeId,
    'fields' => 'name,rating,user_ratings_total,reviews,url',
    'reviews_sort' => 'newest',
    'key' => $apiKey,
]);
$detailsData = google_reviews_get_json($detailsUrl);

if (!isset($detailsData['status']) || $detailsData['status'] !== 'OK') {
    http_response_code(502);
    echo json_encode([
        'ok' => false,
        'error' => $detailsData['error_message'] ?? ($detailsData['status'] ?? 'Google Places request failed.'),
    ]);
    exit;
}

$result = $detailsData['result'];
$reviews = array_values(array_filter($result['reviews'] ?? [], function ($review) {
    return isset($review['rating']) && (int) $review['rating'] === 5;
}));

echo json_encode([
    'ok' => true,
    'placeId' => $placeId,
    'name' => $result['name'] ?? 'The Upper Crest',
    'rating' => $result['rating'] ?? null,
    'userRatingCount' => $result['user_ratings_total'] ?? null,
    'googleMapsUrl' => $result['url'] ?? 'https://www.google.com/search?q=The+Upper+Crest+Poikattusserry',
    'reviews' => $reviews,
], JSON_UNESCAPED_SLASHES);
