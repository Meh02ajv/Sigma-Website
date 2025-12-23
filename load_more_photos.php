<?php
require 'config.php';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 0;
$bac_year = isset($_GET['bac_year']) ? sanitize($_GET['bac_year']) : '';
$limit = 12;
$offset = $page * $limit;

$uploads_dir = 'uploads/';
$photo_extensions = ['jpg', 'jpeg', 'png'];
$photos = [];

if ($bac_year) {
    $year_dir = $uploads_dir . $bac_year . '_pic/';
    $files = glob($year_dir . '*.{jpg,jpeg,png}', GLOB_BRACE);
    foreach ($files as $index => $file) {
        if ($index < $offset) continue;
        if (count($photos) >= $limit) break;
        $photos[] = [
            'id' => $index + 1,
            'photo_path' => $file,
            'bac_year' => $bac_year
        ];
    }
} else {
    $year_folders = glob($uploads_dir . '*_pic', GLOB_ONLYDIR);
    $years = array_map(function($folder) {
        return preg_replace('/.*\/(\d{4})_pic$/', '$1', $folder);
    }, $year_folders);
    rsort($years);
    foreach ($years as $year) {
        $year_dir = $uploads_dir . $year . '_pic/';
        $files = glob($year_dir . '*.{jpg,jpeg,png}', GLOB_BRACE);
        foreach ($files as $index => $file) {
            if ($index < $offset) continue;
            if (count($photos) >= $limit) break;
            $photos[] = [
                'id' => $index + 1,
                'photo_path' => $file,
                'bac_year' => $year
            ];
        }
    }
}

header('Content-Type: application/json');
echo json_encode(['photos' => $photos]);
?>