<?php

namespace Luminous\Bridge\Exceptions;

use Exception;
use LogicException;

class MissingEntityException extends LogicException
{
    /**
     * Create a new exception instance.
     *
     * @param string $abstract
     * @param int $code
     * @param \Exception $previous
     * @return void
     */
    public function __construct($abstract, $code = 0, Exception $previous = null)
    {
        $message = sprintf("Entity class [%s] could not be found.", $abstract);
        parent::__construct($message, $code, $previous);
    }
}
