<?php

declare(strict_types=1);

namespace App\Core\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class AuthorizedAccess
{
}
