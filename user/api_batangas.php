<?php
    header('Content-Type: application/json');

    $provinceCode = "0401";

    function array_find($array, $callback) {
        foreach ($array as $item) {
            if ($callback($item)) {
                return $item;
            }
        }
        return null;
    }

    if (isset($_GET['city'])) {
        $cityParam = $_GET['city'];

        if (is_numeric($cityParam)) {
            $cityCode = $cityParam;
        } else {
            $cities = json_decode(@file_get_contents("https://psgc.cloud/api/cities"), true) ?? [];
            $municipalities = json_decode(@file_get_contents("https://psgc.cloud/api/municipalities"), true) ?? [];

            $batangasCities = array_filter($cities, fn($c) => substr($c['code'], 0, 4) === $provinceCode);
            $batangasMunicipalities = array_filter($municipalities, fn($m) => substr($m['code'], 0, 4) === $provinceCode);

            $all = array_merge($batangasCities, $batangasMunicipalities);

            $cityData = array_find($all, fn($c) => $c['name'] === $cityParam);
            $cityCode = $cityData ? $cityData['code'] : null;
        }

        if (!$cityCode) {
            echo json_encode([]);
            exit;
        }

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