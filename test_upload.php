<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

echo "<h2>Upload Test</h2>";
echo "<p>UPLOAD_DIR: " . UPLOAD_DIR . "</p>";
echo "<p>Directory exists: " . (is_dir(UPLOAD_DIR) ? 'YES' : 'NO') . "</p>";
echo "<p>Directory writable: " . (is_writable(UPLOAD_DIR) ? 'YES' : 'NO') . "</p>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    echo "<h3>Upload Result:</h3>";
    echo "<pre>";
    print_r($_FILES['test_file']);
    echo "</pre>";

    if ($_FILES['test_file']['error'] === UPLOAD_ERR_OK) {
        $result = uploadImage($_FILES['test_file'], 'test_');
        echo "<p>uploadImage returned: " . var_export($result, true) . "</p>";
        if ($result) {
            echo "<p>File URL: " . getImageUrl($result) . "</p>";
            echo "<img src='" . getImageUrl($result) . "' style='max-width:200px;'>";
        }
    } else {
        echo "<p>Upload error code: " . $_FILES['test_file']['error'] . "</p>";
    }
}
?>

<form method="POST" enctype="multipart/form-data">
    <input type="file" name="test_file" accept="image/*">
    <button type="submit">Upload Test</button>
</form>
