<?php

declare(strict_types=1);

namespace App\Core\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class RouteGroup
{
    public function __construct(
        public string $prefix,
    ) {
    }
}
