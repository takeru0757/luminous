<?php

namespace Luminous\Bridge\Exceptions;

use Luminous\Bridge\Exceptions\Exception as BaseException;

class TypeNotExistException extends BaseException
{
    /**
     * The message template.
     *
     * @var string
     */
    protected $messageTemplate = 'Type [%s] does not exist in %s.';

    /**
     * Create a new Exception instance.
     *
     * @param string|array $message
     * @param int $code
     * @param \Exception $previous
     * @return void
     */
    public function __construct($message, $code = 404, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
