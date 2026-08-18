<?php

namespace App\Exceptions;

use Exception;

/**
 * Erro de regra de negocio (ex.: vender mais do que ha em stock).
 * O handler da API traduz isto num 422 com mensagem legivel,
 * em vez do 500 generico que uma excepcao normal daria.
 */
class RegraNegocioException extends Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}