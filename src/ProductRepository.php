<?php
namespace App;

use PDO;

class ProductRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function upsertProduct(array $productData): void
    {
        $sql = "
        INSERT INTO products (number, name, bottlesize, price, priceGBP, timestamp)
        VALUES (:number, :name, :bottlesize, :price, :priceGBP, CURRENT_TIMESTAMP)
        ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            bottlesize = VALUES(bottlesize),
            price = VALUES(price),
            priceGBP = VALUES(priceGBP),
            timestamp = CURRENT_TIMESTAMP
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':number' => $productData['number'],
            ':name' => $productData['name'],
            ':bottlesize' => $productData['bottlesize'],
            ':price' => $productData['price'],
            ':priceGBP' => $productData['priceGBP'],
        ]);
    }


    public function fetchAllProducts(): array
    {
        $stmt = $this->db->query("SELECT * FROM products");
        return $stmt->fetchAll();
    }

    public function updateOrderAmount(int $id, string $action): void
    {
        if ($action === 'add') {
            $stmt = $this->db->prepare("UPDATE products SET orderamount = orderamount + 1 WHERE number = :id");
        } elseif ($action === 'clear') {
            $stmt = $this->db->prepare("UPDATE products SET orderamount = 0 WHERE number = :id");
        }
        $stmt->execute(['id' => $id]);
    }
}
