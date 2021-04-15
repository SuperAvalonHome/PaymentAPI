<?php

namespace SuperAvalon\Payment\Exceptions;

/**
 * UnexpectedValueException
 *
 * PHP 7.3 compatibility interface
 *
 * @package	    SuperAvalon
 * @subpackage	Payment
 * @category	Payment Libraries
 * @Framework   Lumen/Laravel
 * @author	    Eric <think2017@gmail.com>
 * @Github	    https://github.com/SuperAvalon/Payment/
 * @Composer	https://packagist.org/packages/superavalon/payment
 */
class InvalidArgumentException extends \Exception
{
    public function __construct($message, $raw = [])
    {
        parent::__construct('INVALID_ARGUMENT:' . $message, $raw, self::INVALID_ARGUMENT);
    }
}
