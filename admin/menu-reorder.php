<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action == 'reorder_json') {
    $orderData = json_decode($_POST['order_data'] ?? '[]', true);
    if (!empty($orderData)) {
        $pdo = $db->getConnection();
        $sortStmt = $pdo->prepare("UPDATE menu_items SET sort_order = ?, parent_id = ? WHERE id = ?");
        foreach ($orderData as $index => $item) {
            $sortStmt->execute([$index, (int)($item['parent_id'] ?? 0), (int)$item['id']]);
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
}

header('Content-Type: application/json');
echo json_encode(['success' => false, 'error' => 'Invalid action']);
