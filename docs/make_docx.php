<?php
$src = __DIR__ . '/docx_build';
$out = __DIR__ . '/Dokumentasi_Modul_CRUD.docx';
if (file_exists($out)) {
    unlink($out);
}

$zip = new ZipArchive();
if ($zip->open($out, ZipArchive::CREATE) !== true) {
    fwrite(STDERR, "cannot create docx\n");
    exit(1);
}

$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS)
);
foreach ($it as $file) {
    if ($file->isFile()) {
        $full = $file->getPathname();
        $local = substr($full, strlen($src) + 1);
        $local = str_replace('\\', '/', $local);
        $zip->addFile($full, $local);
    }
}

$zip->close();
echo "OK\n";
