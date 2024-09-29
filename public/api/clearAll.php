<?php

require '../../vendor/autoload.php';

use App\Database;

$db = Database::getConnection();

// Reset orderamount to 0 for all products
$stmt = $db->prepare("UPDATE products SET orderamount = 0");
$stmt->execute();

// Return a success message
echo json_encode(['status' => 'success', 'message' => 'Order amount cleared for all products']);


