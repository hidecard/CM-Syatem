<?php
$zipname = 'cms_backup_' . date('Ymd_His') . '.zip';
$zip = new ZipArchive;
if ($zip->open($zipname, ZipArchive::CREATE) === TRUE) {
    // Add database
    if (file_exists('database.db')) {
        $zip->addFile('database.db', 'database.db');
    }
    // Add uploads folder recursively
    $dir = 'uploads/';
    if (is_dir($dir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen(getcwd()) + 1); // keep uploads/ prefix
                $zip->addFile($filePath, $relativePath);
            }
        }
    }
    $zip->close();
    // Download zip
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename=' . $zipname);
    header('Content-Length: ' . filesize($zipname));
    readfile($zipname);
    // Delete zip after download
    unlink($zipname);
    exit;
} else {
    echo 'Failed to create backup zip file.';
} 