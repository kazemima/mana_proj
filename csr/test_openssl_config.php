<?php

echo '<pre>';

$configFile = 'C:/xampp/php/extras/ssl/openssl.cnf';

echo "PHP Version: " . PHP_VERSION . PHP_EOL;
echo "OpenSSL Extension: " . (extension_loaded('openssl') ? 'ENABLED' : 'DISABLED') . PHP_EOL;
echo "OpenSSL Version: " . OPENSSL_VERSION_TEXT . PHP_EOL;

echo PHP_EOL;
echo "=== OpenSSL Config ===" . PHP_EOL;
echo "Config File: " . $configFile . PHP_EOL;
echo "Config Exists: " . (file_exists($configFile) ? 'YES' : 'NO') . PHP_EOL;

echo PHP_EOL;
echo "=== openssl_pkey_new() ===" . PHP_EOL;

$config = [
    'config' => $configFile,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
    'private_key_bits' => 2048
];

$key = openssl_pkey_new($config);

if ($key === false) {

    echo "PRIVATE KEY TEST: FAILED" . PHP_EOL;

    echo PHP_EOL;
    echo "OpenSSL Errors:" . PHP_EOL;

    while ($error = openssl_error_string()) {
        echo $error . PHP_EOL;
    }

} else {

    echo "PRIVATE KEY TEST: OK" . PHP_EOL;

    $details = openssl_pkey_get_details($key);

    echo "Key Type: " . ($details['type'] ?? 'UNKNOWN') . PHP_EOL;
    echo "Key Bits: " . ($details['bits'] ?? 'UNKNOWN') . PHP_EOL;

    openssl_pkey_free($key);
}

echo '</pre>';