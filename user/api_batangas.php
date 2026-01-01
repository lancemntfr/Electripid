<?php
    header('Content-Type: application/json');

    $provinceCode = "0401";

    if (isset($_GET['city'])) {
        $cityCode = $_GET['city'];

        $url = "https://psgc.cloud/api/cities/$cityCode/barangays";
        $json = @file_get_contents($url);

        if ($json === false) {
            $url = "https://psgc.cloud/api/municipalities/$cityCode/barangays";
            $json = @file_get_contents($url);
        }

        echo $json ?: json_encode([]);
        exit;
    }

    $cities = json_decode(@file_get_contents("https://psgc.cloud/api/cities"), true) ?? [];
    $municipalities = json_decode(@file_get_contents("https://psgc.cloud/api/municipalities"), true) ?? [];

    $batangasCities = array_filter($cities, fn($c) =>
        substr($c['code'], 0, 4) === $provinceCode
    );

    $batangasMunicipalities = array_filter($municipalities, fn($m) =>
        substr($m['code'], 0, 4) === $provinceCode
    );

    $all = array_merge($batangasCities, $batangasMunicipalities);

    usort($all, fn($a, $b) => strcmp($a['name'], $b['name']));

    echo json_encode(array_values($all));
?>