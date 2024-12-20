<?php

namespace App\Core\Exceptions;

class ViewNotFoundException extends \Exception
{
    protected $message = 'View not found';
}