<?php

session_start();

// For production hosting, hide errors. Uncomment below for debugging:
// ini_set('display_errors', '1');
// ini_set('display_startup_errors', '1');
// error_reporting(E_ALL);
// ============================================================
// Moadian / GICA CSR Generator
// Based on the official GICA certificate profile guide:
//   CN = <name> [Stamp]
//   serialNumber = national id (legal) / national code (real)
//   O = Governmental | Non-Governmental  (legal person only)
//   OU = org unit                        (legal person only)
//   C = IR
// ============================================================

header('Content-Type: text/html; charset=utf-8');

// ------------------------------------------------------------
// Settings
// ------------------------------------------------------------

$tempDir = __DIR__ . '/temp';

// ------------------------------------------------------------
// Check OpenSSL extension FIRST (fail() uses openssl_error_string)
// ------------------------------------------------------------

if (!extension_loaded('openssl')) {
    fail('افزونه OpenSSL در PHP فعال نیست.');
}

// Auto-detect openssl.cnf
$opensslConfig = null;
$possiblePaths = [
    'C:/xampp/php/extras/ssl/openssl.cnf',
    '/etc/ssl/openssl.cnf',
    '/etc/pki/tls/openssl.cnf',
    '/usr/local/ssl/openssl.cnf',
    '/usr/local/etc/openssl/openssl.cnf',
    '/opt/openssl/openssl.cnf',
];

foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        $opensslConfig = $path;
        break;
    }
}

// Also try to find via openssl_get_cert_locations
if ($opensslConfig === null) {
    $locations = openssl_get_cert_locations();
    if (is_array($locations) && !empty($locations['default_cert_file'])) {
        $dir = dirname($locations['default_cert_file']);
        $candidate = $dir . '/openssl.cnf';
        if (file_exists($candidate)) {
            $opensslConfig = $candidate;
        }
    }
}

// --------------------------------------------------------
// Housekeeping helper (defined before first use)
// Removes temp/{id} directories older than $maxAge seconds
// and legacy flat temp/id_*.{csr,key} files, plus stale
// openssl_moadian_*.cnf config leftovers.
// --------------------------------------------------------

function cleanupTempDir(string $tempDir, int $maxAge): void
{
    $now = time();

    $entries = @scandir($tempDir);
    if ($entries === false) {
        return;
    }

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        $full = $tempDir . DIRECTORY_SEPARATOR . $entry;

        // Legacy flat files: temp/<id>.csr, temp/<id>_private.key, ...
        if (is_file($full)) {
            $mtime = @filemtime($full);
            if ($mtime !== false && ($now - $mtime) > $maxAge) {
                @unlink($full);
            }
            continue;
        }

        // Per-request subdirectories: temp/<32-hex-id>/
        if (is_dir($full) && preg_match('/^[a-f0-9]{32}$/', $entry)) {
            $mtime = @filemtime($full);
            if ($mtime !== false && ($now - $mtime) > $maxAge) {
                foreach (['private.key', 'public.key', 'certificate.csr'] as $name) {
                    @unlink($full . DIRECTORY_SEPARATOR . $name);
                }
                // Also remove any stale openssl_moadian_*.cnf inside
                $inner = @scandir($full);
                if (is_array($inner)) {
                    foreach ($inner as $innerEntry) {
                        if (strpos($innerEntry, 'openssl_moadian_') === 0) {
                            @unlink($full . DIRECTORY_SEPARATOR . $innerEntry);
                        }
                    }
                }
                @rmdir($full);
            }
        }
    }
}

// ------------------------------------------------------------
// Helper
// ------------------------------------------------------------

function fail($message)
{
    echo '<div style="
        direction:rtl;
        font-family:Tahoma;
        max-width:800px;
        margin:40px auto;
        padding:20px;
        border:1px solid #d00;
        background:#fff5f5;
        color:#900;
    ">';

    echo '<h3>خطا</h3>';
    echo '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';

    if (extension_loaded('openssl')) {
        $errors = [];
        while ($error = openssl_error_string()) {
            $errors[] = $error;
        }
        if (!empty($errors)) {
            echo '<pre style="direction:ltr;text-align:left;">';
            echo htmlspecialchars(implode("\n", $errors), ENT_QUOTES, 'UTF-8');
            echo '</pre>';
        }
    }

    echo '</div>';
    exit;
}

// ------------------------------------------------------------
// Check OpenSSL configuration
// ------------------------------------------------------------

if ($opensslConfig === null || !file_exists($opensslConfig)) {
    fail('فایل openssl.cnf پیدا نشد. افزونه OpenSSL فعال است ولی فایل پیکربندی یافت نشد.');
}

// ------------------------------------------------------------
// Housekeeping: remove leftover key material older than 1 hour
//
// Users who never click the download links must not leave their
// private keys on the server forever.
// ------------------------------------------------------------

cleanupTempDir($tempDir, 3600);

// ------------------------------------------------------------
// Create temp directory
// ------------------------------------------------------------

if (!is_dir($tempDir)) {
    if (!mkdir($tempDir, 0777, true)) {
        fail('امکان ایجاد پوشه temp وجود ندارد.');
    }
}

// ------------------------------------------------------------
// Read form
// ------------------------------------------------------------

$personType    = trim($_POST['person_type'] ?? 'real'); // real | legal
$province      = trim($_POST['province'] ?? '');
$city          = trim($_POST['city'] ?? '');
$entityType    = trim($_POST['entity_type'] ?? '');
$commonName    = trim($_POST['common_name'] ?? '');
$nationalId    = trim($_POST['national_id'] ?? '');
$nationalCode  = trim($_POST['national_code'] ?? '');
$email         = trim($_POST['email'] ?? '');
$companyTitle  = trim($_POST['company_title'] ?? '');
$businessTitle = trim($_POST['business_title'] ?? '');

if ($personType !== 'real' && $personType !== 'legal') {
    $personType = 'real';
}

// ------------------------------------------------------------
// Basic validation
// ------------------------------------------------------------

// CSRF protection: the form must originate from our own site
if (
    empty($_POST['csrf_token']) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], (string) ($_POST['csrf_token'] ?? ''))
) {
    http_response_code(403);
    fail('نشست شما منقضی شده است. لطفاً به صفحه اصلی برگردید و دوباره تلاش کنید.');
}

if ($province === '') {
    fail('استان وارد نشده است.');
}

if ($city === '') {
    fail('شهر وارد نشده است.');
}

if ($commonName === '' || !preg_match('/^[A-Za-z0-9._-]+$/', $commonName) || strpos($commonName, ' ') !== false) {
    fail('نام عمومی باید فقط شامل حروف انگلیسی، اعداد، نقطه و خط زیر باشد و بدون فاصله باشد.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fail('پست الکترونیکی وارد نشده یا فرمت آن صحیح نیست.');
}

// ------------------------------------------------------------
// serialNumber per GICA profile:
//   - Legal person:   شناسه ملی ۱۱ رقمی (اجباری)
//   - Real person:    کد ملی ۱۰ رقمی (اجباری)
// ------------------------------------------------------------

$serialNumber = '';

if ($personType === 'legal') {
    if ($nationalId === '') {
        fail('برای اشخاص حقوقی، شناسه ملی وارد نشده است.');
    }
    if (!preg_match('/^\d{11}$/', $nationalId)) {
        fail('شناسه ملی باید دقیقاً ۱۱ رقم باشد.');
    }
    $serialNumber = $nationalId;
} else {
    if ($nationalCode === '') {
        fail('برای اشخاص حقیقی، کد ملی وارد نشده است.');
    }
    if (!preg_match('/^\d{10}$/', $nationalCode)) {
        fail('کد ملی باید دقیقاً ۱۰ رقم باشد.');
    }
    $serialNumber = $nationalCode;
}

// ------------------------------------------------------------
// Organization / OU per GICA profile (legal person only)
//
// Official profile for organizational stamp certificate:
//   O  = Governmental | Non-Governmental
//   OU = organizational unit (e.g. company title)
// ------------------------------------------------------------

$organization       = '';
$organizationalUnit = '';

if ($personType === 'legal') {
    // OU = company title, fallback to business title
    $organizationalUnit = $companyTitle !== '' ? $companyTitle : $businessTitle;

    if ($organizationalUnit === '') {
        fail('عنوان شرکت یا عنوان واحد صنفی وارد نشده است.');
    }

    switch (trim($entityType)) {
        case 'دولتی':
            $organization = 'Governmental';
            break;
        case 'غیردولتی':
            $organization = 'Non-Governmental';
            break;
        default:
            // If the form sends Persian text directly
            if (mb_strpos($entityType, 'دول') !== false && mb_strpos($entityType, 'غیر') === false) {
                $organization = 'Governmental';
            } else {
                $organization = 'Non-Governmental';
            }
            break;
    }
}

// ------------------------------------------------------------
// Common Name per GICA profile: CN = <name> [Stamp]
// ------------------------------------------------------------

$certificateCommonName = $commonName . ' [Stamp]';

// ------------------------------------------------------------
// Distinguished Name
//
// Field order follows the official GICA fa.cnf sample:
//   CN, serialNumber, O, OU, C  (+ L, ST, emailAddress)
// ------------------------------------------------------------

$dn = [
    'commonName'   => $certificateCommonName,
    'serialNumber' => $serialNumber,
];

if ($personType === 'legal') {
    $dn['organizationName']       = $organization;
    $dn['organizationalUnitName'] = $organizationalUnit;
}

$dn['countryName']  = 'IR';
$dn['emailAddress'] = $email;
$dn['localityName'] = $city;

// ST (state/province) is not part of the GICA profile; kept for completeness
// of address info but can be removed if the CA rejects unknown attributes.

$dn['stateOrProvinceName'] = $province;

// ------------------------------------------------------------
// Create temporary OpenSSL configuration
//
// Standalone minimal config (NOT based on the host's openssl.cnf):
// this avoids leaking default values such as
//   0.organizationName_default = Internet Widgits Pty Ltd
// from XAMPP's stock config into the CSR subject.
// ------------------------------------------------------------

$customConfig = "";

$customConfig .= <<<CONF
# Minimal standalone config for Moadian CSR.
# NOTE: PHP's openssl_csr_new reads the DEFAULT [ req ] section (not a custom one),
# so the section below must be named [ req ]. Keeping it empty of *_default keys
# prevents host config values (e.g. Internet Widgits) leaking into the subject.

[ req ]

default_bits = 2048
distinguished_name = req_distinguished_name_moadian
prompt = no

[ req_distinguished_name_moadian ]

[ v3_req_moadian ]

keyUsage = critical, digitalSignature, nonRepudiation, keyEncipherment
extendedKeyUsage = serverAuth

CONF;

$customConfigFile = $tempDir . '/openssl_moadian_' . uniqid() . '.cnf';

if (file_put_contents($customConfigFile, $customConfig) === false) {
    fail('امکان ایجاد فایل تنظیمات موقت OpenSSL وجود ندارد.');
}

// ------------------------------------------------------------
// Generate RSA 2048 Private Key
// ------------------------------------------------------------

$keyConfig = [
    'config'           => $customConfigFile,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
    'private_key_bits' => 2048,
];

$privateKey = openssl_pkey_new($keyConfig);

if ($privateKey === false) {
    @unlink($customConfigFile);
    fail('خطا در تولید Private Key.');
}

// ------------------------------------------------------------
// Generate CSR
// ------------------------------------------------------------

$csrConfig = [
    'config'         => $customConfigFile,
    'req_extensions' => 'v3_req_moadian',
    'digest_alg'     => 'sha256',
];

$csr = openssl_csr_new(
    $dn,
    $privateKey,
    $csrConfig
);

if ($csr === false) {
    @unlink($customConfigFile);
    fail('خطا در تولید CSR.');
}

// ------------------------------------------------------------
// Export Private Key
// ------------------------------------------------------------

$privateKeyText = '';

if (!openssl_pkey_export(
    $privateKey,
    $privateKeyText,
    null,
    [
        'config' => $customConfigFile
    ]
)) {
    @unlink($customConfigFile);
    fail('خطا در استخراج Private Key.');
}

// ------------------------------------------------------------
// Export Public Key
// ------------------------------------------------------------

$keyDetails = openssl_pkey_get_details($privateKey);

if ($keyDetails === false || empty($keyDetails['key'])) {
    @unlink($customConfigFile);
    fail('خطا در استخراج Public Key.');
}

$publicKeyText = $keyDetails['key'];

// ------------------------------------------------------------
// Export CSR
// ------------------------------------------------------------

$csrText = '';

if (!openssl_csr_export($csr, $csrText)) {
    @unlink($customConfigFile);
    fail('خطا در استخراج CSR.');
}

// ------------------------------------------------------------
// Generate unique ID
// ------------------------------------------------------------

$id = bin2hex(random_bytes(16));

// ------------------------------------------------------------
// File names (inside temp/{id}/ subdirectory)
// ------------------------------------------------------------

$idDir = $tempDir . '/' . $id;

if (!is_dir($idDir)) {
    if (!mkdir($idDir, 0777, true)) {
        fail('امکان ایجاد پوشه موقت وجود ندارد.');
    }
}

$privateFile = $idDir . '/private.key';
$publicFile  = $idDir . '/public.key';
$csrFile     = $idDir . '/certificate.csr';

// ------------------------------------------------------------
// Save files
// ------------------------------------------------------------

if (
    file_put_contents($privateFile, $privateKeyText) === false ||
    file_put_contents($publicFile,  $publicKeyText)  === false ||
    file_put_contents($csrFile,     $csrText)        === false
) {
    @unlink($privateFile);
    @unlink($publicFile);
    @unlink($csrFile);
    @unlink($customConfigFile);

    fail('خطا در ذخیره فایل‌های خروجی.');
}

// ------------------------------------------------------------
// Restrict private key permissions (POSIX only)
// ------------------------------------------------------------

if (function_exists('chmod')) {
    @chmod($privateFile, 0600);
}

// ------------------------------------------------------------
// Delete temporary OpenSSL configuration
// ------------------------------------------------------------

@unlink($customConfigFile);

// ------------------------------------------------------------
// Store ID in session (used by download.php)
// ------------------------------------------------------------

$_SESSION['csr_files'] = [
    'id'      => $id,
    'private' => 'private.key',
    'public'  => 'public.key',
    'csr'     => 'certificate.csr'
];

// ------------------------------------------------------------
// Result
// ------------------------------------------------------------

$personTypeLabel = ($personType === 'legal') ? 'شخص حقوقی' : 'شخص حقیقی';

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تولید CSR</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>

        body {
            font-family: 'Vazirmatn', Tahoma, Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 40px 15px;
            color: #333;
            line-height: 1.8;
        }

        .container {
            max-width: 800px;
            margin: auto;
            background: white;
            padding: 35px 40px;
            border-radius: 8px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.08);
        }

        h2 {
            margin-top: 0;
            font-weight: 800;
            font-size: 1.5rem;
            color: #333;
        }

        .success {
            background: rgba(109,192,81,0.1);
            border: 1px solid rgba(109,192,81,0.3);
            color: #5aa842;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
        }

        .info {
            background: #f5f5f5;
            border: 1px solid #e0e0e0;
            padding: 18px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 0.95rem;
        }

        .btn {
            display: block;
            padding: 14px 18px;
            margin: 10px 0;
            text-decoration: none;
            border-radius: 8px;
            background: #6dc051;
            color: white;
            text-align: center;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .btn:hover {
            background: #5aa842;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(109,192,81,0.3);
        }

        .btn-private {
            background: #8b0000;
        }

        .btn-private:hover {
            background: #a00000;
        }

        .btn-public {
            background: #286090;
        }

        .btn-public:hover {
            background: #1e4a6e;
        }

        .btn-csr {
            background: #6dc051;
        }

        .details {
            line-height: 2.2;
        }

        .details strong {
            color: #333;
        }

        @media (max-width: 600px) {
            .container {
                padding: 20px;
            }
        }

    </style>

</head>

<body>

<div class="container">

    <h2>تولید CSR با موفقیت انجام شد</h2>

    <div class="success">
        فایل‌های Private Key، Public Key و CSR با موفقیت تولید شدند.
    </div>

    <div class="info details">

        <strong>اطلاعات CSR:</strong><br>

        نوع شخص:
        <?= htmlspecialchars($personTypeLabel, ENT_QUOTES, 'UTF-8') ?>
        <br>

        CN:
        <?= htmlspecialchars($certificateCommonName, ENT_QUOTES, 'UTF-8') ?>
        <br>

        Serial Number:
        <?= htmlspecialchars($serialNumber, ENT_QUOTES, 'UTF-8') ?>
        <br>

        <?php if ($personType === 'legal'): ?>
        Organization:
        <?= htmlspecialchars($organization, ENT_QUOTES, 'UTF-8') ?>
        <br>

        Organization Unit:
        <?= htmlspecialchars($organizationalUnit, ENT_QUOTES, 'UTF-8') ?>
        <br>
        <?php endif; ?>

        Country:
        IR
        <br>

        Email:
        <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>

    </div>

    <div class="info details">

        <strong>توجه:</strong> هر لینک فقط یک بار قابل استفاده است. لطفاً هر سه فایل را دریافت و در جای امن نگهداری کنید. کلید خصوصی قابل بازیابی نیست.

    </div>

    <a class="btn btn-private"
       href="download.php?id=<?= urlencode($id) ?>&file=private">
        دریافت Private Key
    </a>

    <a class="btn btn-public"
       href="download.php?id=<?= urlencode($id) ?>&file=public">
        دریافت Public Key
    </a>

    <a class="btn btn-csr"
       href="download.php?id=<?= urlencode($id) ?>&file=csr">
        دریافت CSR
    </a>

</div>

</body>

</html>
