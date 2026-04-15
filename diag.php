<?php
header('Content-Type: text/plain; charset=UTF-8');

echo "SIGMA diagnostic\n";
echo "PHP: " . PHP_VERSION . "\n";

echo "Host: " . ($_SERVER['HTTP_HOST'] ?? 'cli') . "\n";

date_default_timezone_set('Africa/Abidjan');

function envVarLocal($key, $default = '') {
    $v = getenv($key);
    if ($v === false && isset($_ENV[$key])) {
        $v = $_ENV[$key];
    }
    if ($v === false && isset($_SERVER[$key])) {
        $v = $_SERVER[$key];
    }
    return ($v === false || $v === '') ? $default : $v;
}

$isLocal = isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
$isInfinity = isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], '.rf.gd') !== false;

if ($isLocal) {
    $dbHost = envVarLocal('DB_HOST', 'localhost');
    $dbUser = envVarLocal('DB_USER', 'root');
    $dbPass = envVarLocal('DB_PASS', '');
    $dbName = envVarLocal('DB_NAME', 'laho');
} elseif ($isInfinity) {
    $dbHost = envVarLocal('DB_HOST', 'sql303.infinityfree.com');
    $dbUser = envVarLocal('DB_USER', 'if0_40826245');
    $dbPass = envVarLocal('DB_PASS', 'KOBAhariel16');
    $dbName = envVarLocal('DB_NAME', 'if0_40826245_sigma_db');
} else {
    $dbHost = envVarLocal('DB_HOST', 'localhost');
    $dbUser = envVarLocal('DB_USER', 'root');
    $dbPass = envVarLocal('DB_PASS', '');
    $dbName = envVarLocal('DB_NAME', 'laho');
}

if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

$conn = @new mysqli($dbHost, $dbUser, $dbPass, $dbName);

if (!$conn || $conn->connect_error) {
    echo "DB: FAIL\n";
    echo "Error: " . ($conn ? $conn->connect_error : 'mysqli init failed') . "\n";
    exit;
}

echo "DB: OK\n";

$rs = $conn->query("SELECT 1 as ok");
if ($rs) {
    $row = $rs->fetch_assoc();
    echo "Query test: OK (" . ($row['ok'] ?? 'n/a') . ")\n";
} else {
    echo "Query test: FAIL (" . $conn->error . ")\n";
}

$conn->close();
