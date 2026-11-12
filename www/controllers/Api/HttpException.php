<?php

namespace Controllers\Api;

use Exception;

/**
 *  Error returned to an API client, with the HTTP status code of the response
 *  API v3 returns it as a Problem Details object (RFC 9457), with a stable error code
 *  and, for invalid parameters, the parameters in error
 */
class HttpException extends Exception
{
    // Default error code of each HTTP status code
    private const CODES = [
        400 => 'invalid_request',
        401 => 'unauthorized',
        403 => 'forbidden',
        404 => 'not_found',
        405 => 'method_not_allowed',
        500 => 'internal_error',
        503 => 'unavailable'
    ];

    private array $messages;
    private string $errorCode;
    private array $errors;

    /**
     *  $errors: parameters in error, each one as ['detail' => ..., 'pointer' => ...] for a body parameter (JSON Pointer, RFC 6901)
     *  or ['detail' => ..., 'parameter' => ...] for a query parameter
     */
    public function __construct(int $status, string|array $messages, string|null $errorCode = null, array $errors = [])
    {
        $this->messages = array_values((array) $messages);
        $this->errorCode = $errorCode ?? self::CODES[$status] ?? 'invalid_request';
        $this->errors = $errors;

        parent::__construct(implode(PHP_EOL, $this->messages), $status);
    }

    /**
     *  A body parameter is missing
     */
    public static function missingParameter(string $name, string $pointer): self
    {
        return new self(400, 'Missing parameter: ' . $name, 'missing_parameter', [
            ['detail' => 'Missing parameter', 'pointer' => $pointer]
        ]);
    }

    /**
     *  A body parameter has an invalid value or is not expected
     */
    public static function invalidParameter(string $detail, string $pointer): self
    {
        return new self(400, $detail, 'invalid_parameter', [
            ['detail' => $detail, 'pointer' => $pointer]
        ]);
    }

    /**
     *  A query parameter has an invalid value
     */
    public static function invalidQueryParameter(string $detail, string $parameter): self
    {
        return new self(400, $detail, 'invalid_parameter', [
            ['detail' => $detail, 'parameter' => $parameter]
        ]);
    }

    /**
     *  Return the HTTP status code of the response
     */
    public function getStatus(): int
    {
        return $this->getCode();
    }

    /**
     *  Return the error messages
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     *  Return the stable error code (e.g. 'not_found'), clients can rely on it rather than on the messages
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     *  Return the parameters in error
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
