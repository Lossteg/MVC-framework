<?php

namespace App\Core\Exceptions;

class ViewNotFoundException extends \Exception
{
    /** @var string */
    protected $message = 'Views not found';
}
