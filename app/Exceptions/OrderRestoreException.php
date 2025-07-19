<?php

namespace App\Exceptions;

use Exception;

class OrderRestoreException extends Exception
{
    protected $message = 'Невозможно возобновить заказ';
}
