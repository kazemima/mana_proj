<?php

session_start();

$id   = $_GET['id'] ?? '';
$file = $_GET['file'] ?? '';

if (!preg_match('/^[a-f0-9]{32}$/', $id)) {
    http_response_code(400);
    exit('Invalid request');
}


$files = [

    'private' => [
        'name' => 'private.key',
        'download' => 'private.key'
    ],

    'public' => [
        'name' => 'public.key',
        'download' => 'public.key'
    ],

    'csr' => [
        'name' => 'certificate.csr',
        'download' => 'certificate.csr'
    ]

];


if (!isset($files[$file])) {
    http_response_code(400);
    exit('Invalid file');
}


/*
|--------------------------------------------------------------------------
| Session ownership check
|--------------------------------------------------------------------------
| Only the session that generated the CSR may download its files.
| Guessing someone else's id via URL is not enough anymore.
*/

$sessionFiles = $_SESSION['csr_files'] ?? null;

if (
    !is_array($sessionFiles) ||
    !isset($sessionFiles['id']) ||
    !hash_equals((string) $sessionFiles['id'], $id)
) {
    http_response_code(403);
    exit('این فایل متعلق به نشست شما نیست یا منقضی شده است.');
}

/*
|--------------------------------------------------------------------------
| One-time download enforcement (per file)
|--------------------------------------------------------------------------
| Each file can be downloaded exactly once. The session record keeps
| track of which files were already fetched; replaying a link that was
| already used is rejected.
*/

$downloaded = isset($sessionFiles['downloaded']) && is_array($sessionFiles['downloaded'])
    ? $sessionFiles['downloaded']
    : [];

if (in_array($file, $downloaded, true)) {
    http_response_code(403);
    exit('این فایل قبلاً دانلود شده است. لطفاً مجدداً کلید و CSR تولید کنید.');
}


$baseDir = __DIR__ .
           DIRECTORY_SEPARATOR .
           'temp' .
           DIRECTORY_SEPARATOR .
           $id;


$path = $baseDir .
        DIRECTORY_SEPARATOR .
        $files[$file]['name'];


if (!is_file($path)) {
    http_response_code(404);
    exit('File not found');
}


header('Content-Type: application/octet-stream');

header(
    'Content-Disposition: attachment; filename="' .
    $files[$file]['download'] .
    '"'
);

header('Content-Length: ' . filesize($path));

header('Cache-Control: no-store');

readfile($path);


/*
|--------------------------------------------------------------------------
| حذف فایل بعد از دانلود
|--------------------------------------------------------------------------
*/

@unlink($path);


/*
|--------------------------------------------------------------------------
| ثبت دانلود در نشست
|--------------------------------------------------------------------------
*/

$downloaded[] = $file;

$_SESSION['csr_files']['downloaded'] = $downloaded;


/*
|--------------------------------------------------------------------------
| اگر هر سه فایل دانلود شد، پوشه و رکورد نشست پاک شود
|--------------------------------------------------------------------------
*/

if (count($downloaded) >= count($files)) {

    if (is_dir($baseDir)) {

        $remaining = scandir($baseDir);

        if ($remaining !== false && count($remaining) <= 2) {
            @rmdir($baseDir);
        }

    }

    unset($_SESSION['csr_files']);

}
