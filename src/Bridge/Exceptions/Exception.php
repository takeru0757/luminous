<?php

namespace Luminous\Bridge\Exceptions;

use RuntimeException;

abstract class Exception extends RuntimeException
{
    /**
     * The message template.
     *
     * @var string
     */
    protected $messageTemplate = '';

    /**
     * Create a new Exception instance.
     *
     * @param string|array $message
     * @param int $code
     * @param \Exception $previous
     * @return void
     */
    public function __construct($message, $code = 500, \Exception $previous = null)
    {
        if (is_array($message)) {
            $message = vsprintf($this->messageTemplate, $message);
        }
        parent::__construct($message, $code, $previous);
    }
}
