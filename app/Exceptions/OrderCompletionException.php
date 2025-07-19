<?php

namespace App\Exceptions;

use Exception;

class OrderCompletionException extends Exception
{
    protected $message = 'Невозможно завершить заказ';
}
