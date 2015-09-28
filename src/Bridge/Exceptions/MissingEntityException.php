<?php

namespace Luminous\Bridge\Exceptions;

use Luminous\Bridge\Exceptions\Exception as BaseException;

class MissingEntityException extends BaseException
{
    /**
     * The message template.
     *
     * @var string
     */
    protected $messageTemplate = 'Entity class [%s] could not be found.';
}
