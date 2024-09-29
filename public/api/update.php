<?php

require '../../vendor/autoload.php';

use App\Database;
use App\ProductRepository;

$db = Database::getConnection();
$productRepo = new ProductRepository($db);

$id = $_POST['id'] ?? 0;
$operation = $_POST['operation'] ?? '';

if ($id && ($operation === 'add' || $operation === 'clear')) {
    $productRepo->updateOrderAmount((int)$id, $operation);
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid operation']);
}
