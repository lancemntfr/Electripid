<?php
// user/api_weather.php
// Weather API proxy with caching and fallback support

session_start();

// ==============================
// CONFIGURATION
// ==============================
define('OPENWEATHER_API_KEY', 'a4ad5de980d109abed0fec591eefd391'); // MOVE to env in production
define('CACHE_TTL', 1800); // 30 minutes

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// ==============================
// INPUT
// ==============================
$city = isset($_GET['city']) && trim($_GET['city']) !== ''
    ? trim($_GET['city'])
    : 'Batangas';

// ==============================
// CACHE SETUP
// ==============================
$cacheDir  = __DIR__ . '/../cache';
$cacheKey  = 'weather_' . md5(strtolower($city));
$cacheFile = $cacheDir . '/' . $cacheKey . '.json';

if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

// ==============================
// SERVE CACHE IF VALID
// ==============================
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < CACHE_TTL) {
    echo json_encode([
        'success' => true,
        'cached'  => true,
        'data'    => json_decode(file_get_contents($cacheFile), true)
    ]);
    exit;
}

// ==============================
// STEP 1: GEOCODING (CITY → LAT/LON)
// ==============================
$geoUrl = sprintf(
    'https://api.openweathermap.org/geo/1.0/direct?q=%s,PH&limit=1&appid=%s',
    urlencode($city),
    OPENWEATHER_API_KEY
);

$geoResponse = curlRequest($geoUrl);
$geoData     = json_decode($geoResponse, true);

// Default: Batangas
$lat = 13.7565;
$lon = 121.0583;
$locationName = 'Batangas, PH';

if (is_array($geoData) && isset($geoData[0])) {
    $lat = $geoData[0]['lat'];
    $lon = $geoData[0]['lon'];
    $locationName = $geoData[0]['name'] . ', ' . $geoData[0]['country'];
}

// ==============================
// STEP 2: ONE CALL API 3.0
// ==============================
$weatherUrl = sprintf(
    'https://api.openweathermap.org/data/3.0/onecall?lat=%s&lon=%s&units=metric&exclude=minutely,alerts&appid=%s',
    $lat,
    $lon,
    OPENWEATHER_API_KEY
);

$weatherResponse = curlRequest($weatherUrl);
$weatherData     = json_decode($weatherResponse, true);

// ==============================
// VALIDATE RESPONSE
// ==============================
if (
    !is_array($weatherData) ||
    !isset($weatherData['current']) ||
    !isset($weatherData['daily'])
) {
    $fallback = getFallbackWeather($locationName);
    file_put_contents($cacheFile, json_encode($fallback));

    echo json_encode([
        'success' => true,
        'cached'  => true,
        'data'    => $fallback
    ]);
    exit;
}

// ==============================
// FORMAT DATA FOR FRONTEND
// ==============================
$formatted = [
    'current' => [
        'location'  => $locationName,
        'temp'      => round($weatherData['current']['temp']) . '°C',
        'condition' => ucfirst($weatherData['current']['weather'][0]['description']),
        'humidity'  => $weatherData['current']['humidity'] . '%',
        'wind'      => round($weatherData['current']['wind_speed'] * 3.6, 1) . ' km/h',
        'icon'      => 'https://openweathermap.org/img/wn/' .
                        $weatherData['current']['weather'][0]['icon'] . '@2x.png'
    ],
    'forecast' => []
];

// Next 5 days forecast
for ($i = 1; $i <= 5; $i++) {
    if (!isset($weatherData['daily'][$i])) continue;

    $day = $weatherData['daily'][$i];
    $formatted['forecast'][] = [
        'day'       => date('D', $day['dt']),
        'temp_max'  => round($day['temp']['max']),
        'temp_min'  => round($day['temp']['min']),
        'condition' => ucfirst($day['weather'][0]['description']),
        'icon'      => 'https://openweathermap.org/img/wn/' .
                        $day['weather'][0]['icon'] . '@2x.png'
    ];
}

// ==============================
// CACHE & RETURN
// ==============================
file_put_contents($cacheFile, json_encode($formatted));

echo json_encode([
    'success' => true,
    'cached'  => false,
    'data'    => $formatted
]);

// ==============================
// FUNCTIONS
// ==============================
function curlRequest(string $url): string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_FOLLOWLOCATION => true
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response ?: '';
}

function getFallbackWeather(string $location): array
{
    return [
        'current' => [
            'location'  => $location,
            'temp'      => '28°C',
            'condition' => 'Partly Cloudy',
            'humidity'  => '65%',
            'wind'      => '12.5 km/h',
            'icon'      => 'https://openweathermap.org/img/wn/02d@2x.png'
        ],
        'forecast' => [
            ['day' => date('D', strtotime('+1 day')), 'temp_max' => 32, 'temp_min' => 25, 'condition' => 'Sunny', 'icon' => 'https://openweathermap.org/img/wn/01d@2x.png'],
            ['day' => date('D', strtotime('+2 day')), 'temp_max' => 31, 'temp_min' => 26, 'condition' => 'Partly Cloudy', 'icon' => 'https://openweathermap.org/img/wn/02d@2x.png'],
            ['day' => date('D', strtotime('+3 day')), 'temp_max' => 30, 'temp_min' => 26, 'condition' => 'Cloudy', 'icon' => 'https://openweathermap.org/img/wn/03d@2x.png'],
            ['day' => date('D', strtotime('+4 day')), 'temp_max' => 29, 'temp_min' => 25, 'condition' => 'Cloudy', 'icon' => 'https://openweathermap.org/img/wn/04d@2x.png'],
            ['day' => date('D', strtotime('+5 day')), 'temp_max' => 28, 'temp_min' => 24, 'condition' => 'Showers', 'icon' => 'https://openweathermap.org/img/wn/09d@2x.png']
        ]
    ];
}
