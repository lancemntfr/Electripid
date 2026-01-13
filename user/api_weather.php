<?php
header('Content-Type: application/json');

$WEATHER_API_KEY = 'a4ad5de980d109abed0fec591eefd391';
$currentLocation = isset($_GET['city']) ? trim($_GET['city']) : 'Batangas';

// Basic validation and sanitization
$currentLocation = preg_replace('/[^a-zA-Z\s\-,\.]/', '', $currentLocation); // Remove special characters except common ones
$currentLocation = substr($currentLocation, 0, 50); // Limit length

// Fallback to Batangas if no valid city provided
if (empty($currentLocation) || strlen($currentLocation) < 2) {
    $currentLocation = 'Batangas';
}

// Helper function for weather icon color filtering
function getWeatherIconFilter($condition, $description) {
    $cond = strtolower($condition);
    $desc = strtolower($description);

    if (strpos($cond, 'cloud') !== false || strpos($desc, 'cloud') !== false) {
        return 'brightness(0) saturate(100%) invert(40%) sepia(100%) saturate(2000%) hue-rotate(200deg) brightness(0.9)'; // Blue
    } else if (strpos($cond, 'rain') !== false || strpos($desc, 'rain') !== false || strpos($desc, 'drizzle') !== false) {
        return 'brightness(0) saturate(100%) invert(40%) sepia(100%) saturate(2000%) hue-rotate(200deg) brightness(0.8)'; // Darker blue
    } else if (strpos($cond, 'clear') !== false || strpos($desc, 'clear') !== false || strpos($desc, 'sun') !== false) {
        return 'brightness(0) saturate(100%) invert(80%) sepia(100%) saturate(2000%) hue-rotate(0deg) brightness(1.1)'; // Yellow/Orange
    } else if (strpos($cond, 'snow') !== false) {
        return 'brightness(0) saturate(100%) invert(100%)'; // White
    } else if (strpos($cond, 'thunder') !== false || strpos($desc, 'thunder') !== false) {
        return 'brightness(0) saturate(100%) invert(20%) sepia(100%) saturate(2000%) hue-rotate(250deg)'; // Purple
    } else if (strpos($cond, 'mist') !== false || strpos($cond, 'fog') !== false || strpos($desc, 'mist') !== false || strpos($desc, 'fog') !== false) {
        return 'brightness(0) saturate(100%) invert(90%)'; // Light gray
    }
    return '';
}

try {
    // Fetch weather data from OpenWeatherMap
    $encodedLocation = urlencode($currentLocation);
    $url = "https://api.openweathermap.org/data/2.5/forecast?q={$encodedLocation}&appid={$WEATHER_API_KEY}&units=metric";
    $json = @file_get_contents($url);

    if ($json === false) {
        echo json_encode(['success' => false, 'error' => 'Unable to fetch weather data']);
        exit;
    }

    $data = json_decode($json, true);

    if (!$data || $data['cod'] !== '200') {
        echo json_encode(['success' => false, 'error' => 'Weather data unavailable']);
        exit;
    }

    // Process current weather
    $current = $data['list'][0];
    $currentWeather = [
        'temp' => round($current['main']['temp']) . '°C',
        'condition' => $current['weather'][0]['description'],
        'humidity' => $current['main']['humidity'],
        'wind' => $current['wind']['speed'],
        'icon' => "https://openweathermap.org/img/wn/{$current['weather'][0]['icon']}@2x.png",
        'filter' => getWeatherIconFilter($current['weather'][0]['main'], $current['weather'][0]['description'])
    ];

    // Process forecast data
    $forecastByDay = [];
    foreach ($data['list'] as $item) {
        $date = new DateTime($item['dt_txt']);
        $day = $date->format('D');
        $hour = (int)$date->format('H');

        if ($hour === 12 && !isset($forecastByDay[$day])) {
            $forecastByDay[$day] = $item;
        }
    }

    $forecast = [];
    foreach ($forecastByDay as $day => $item) {
        $forecast[] = [
            'day' => $day,
            'temp' => round($item['main']['temp']),
            'icon' => "https://openweathermap.org/img/wn/{$item['weather'][0]['icon']}@2x.png",
            'filter' => getWeatherIconFilter($item['weather'][0]['main'], $item['weather'][0]['description'])
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'current' => $currentWeather,
            'forecast' => $forecast
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Weather API error: ' . $e->getMessage()]);
}
?>