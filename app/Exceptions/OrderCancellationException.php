<?php

namespace App\Exceptions;

use Exception;

class OrderCancellationException extends Exception
{
    protected $message = 'Невозможно отменить заказ';
}
