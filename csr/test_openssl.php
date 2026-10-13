<?php

echo '<pre>';

echo "PHP Version: " . PHP_VERSION . PHP_EOL;
echo "OpenSSL Extension: ";

if (extension_loaded('openssl')) {
    echo "ENABLED";
} else {
    echo "DISABLED";
}

echo PHP_EOL . PHP_EOL;

echo "OpenSSL Version: ";
echo OPENSSL_VERSION_TEXT ?? 'UNKNOWN';

echo PHP_EOL . PHP_EOL;

$config = [
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
    'private_key_bits' => 2048
];

$key = openssl_pkey_new($config);

if ($key === false) {
    echo "PRIVATE KEY TEST: FAILED" . PHP_EOL;
    echo "OpenSSL Errors:" . PHP_EOL;

    while ($error = openssl_error_string()) {
        echo $error . PHP_EOL;
    }
} else {
    echo "PRIVATE KEY TEST: OK" . PHP_EOL;
}

echo '</pre>';