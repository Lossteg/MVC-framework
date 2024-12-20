<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Contracts\ProductServiceInterface;

class ProductService implements ProductServiceInterface
{
    private array $products = [];

    public function getAllProducts(): array
    {
        return $this->products;
    }

    public function createProduct(array $data): array
    {
        $newProduct = [
            'id' => count($this->products) + 1,
            'name' => $data['name'],
            'price' => (float)$data['price']
        ];
        $this->products[] = $newProduct;
        return $newProduct;
    }

    public function updateProduct(int $id, array $data): ?array
    {
        foreach ($this->products as &$product) {
            if ($product['id'] === $id) {
                $product['name'] = $data['name'] ?? $product['name'];
                $product['price'] = isset($data['price']) ? (float)$data['price'] : $product['price'];
                return $product;
            }
        }
        return null;
    }
}
