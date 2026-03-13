<?php

namespace Controllers\Exception;

use Exception;

class AppException extends Exception
{
    private array $details = [];

    /**
     *  Accept a string or an array as the message
     */
    public function __construct(string|array $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        if (is_array($message)) {
            $this->details = $message;
            parent::__construct('', $code, $previous);
        } else {
            parent::__construct($message, $code, $previous);
        }
    }

    /**
     *  Return the details array
     */
    public function getDetails(): array
    {
        return $this->details;
    }
}
