<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface ProductServiceInterface
{
    public function getAllProducts(): array;
    public function createProduct(array $data): array;
    public function updateProduct(int $id, array $data): ?array;
}