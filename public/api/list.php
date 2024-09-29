<?php

require '../../vendor/autoload.php';


use App\Database;
use App\ProductRepository;

$db = Database::getConnection();
$productRepo = new ProductRepository($db);

// Fetch all products from the database
$products = $productRepo->fetchAllProducts();

// Return products as JSON
header('Content-Type: application/json');
echo json_encode($products);
