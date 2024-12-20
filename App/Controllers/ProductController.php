<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Attributes\Get;
use App\Services\Contracts\ProductServiceInterface;
use App\Core\View;

class ProductController
{
    public function __construct(private ProductServiceInterface $productService)
    {}

    #[Get('/product')]
    public function index(): void
    {
        $products = $this->productService->getAllProducts();
        echo View::make('product', compact($products)) . "<p>Working fine!</p>";
    }

    public function store(array $data): void
    {
        $errors = $this->validateProductData($data);

        if (!empty($errors)) {
            echo View::make('product', [
                'products' => $this->productService->getAllProducts(),
                'errors' => $errors,
            ]);
            return;
        }

        $this->productService->createProduct($data);
        header('Location: /product');
    }

    private function validateProductData(array $data): array
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors[] = 'Название продукта обязательно.';
        }

        if (empty($data['price']) || !is_numeric($data['price']) || $data['price'] <= 0) {
            $errors[] = 'Цена должна быть положительным числом.';
        }

        return $errors;
    }
}
