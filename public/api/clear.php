<?php

require '../../vendor/autoload.php';

use App\Database;
use App\ProductRepository;

$db = Database::getConnection();
$productRepo = new ProductRepository($db);

// Get the product ID from the request
$id = $_POST['id'] ?? 0;

if ($id) {
    // Clear the order amount for the specific product
    $productRepo->updateOrderAmount((int)$id, 'clear');
    echo json_encode(['status' => 'success', 'message' => 'Order amount cleared for product ID ' . $id]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Product ID not provided']);
}
