<?php
require_once __DIR__ . '/../../config/config.php';

$found = [];
$searchDirs = [
    dirname(__DIR__, 2), // root of wapi
    dirname(__DIR__, 3), // parent dir (e.g. public_html or domains)
    sys_get_temp_dir()
];

foreach ($searchDirs as $dir) {
    if (!is_dir($dir)) continue;
    try {
        $files = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                function ($file, $key, $iterator) {
                    if ($iterator->hasChildren() && in_array($file->getFilename(), ['.git', 'vendor', 'node_modules', 'sessions'])) {
                        return false;
                    }
                    return true;
                }
            )
        );
        foreach ($files as $file) {
            if (strpos($file->getFilename(), 'media_69') !== false || strpos($file->getFilename(), '177571') !== false) {
                $found[] = $file->getPathname();
                if (count($found) > 30) break 2;
            }
        }
    } catch (Exception $e) {}
}

header('Content-Type: application/json');
echo json_encode(['found_media' => $found], JSON_PRETTY_PRINT);
