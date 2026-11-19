<?php

namespace Controllers\Api\V3;

use Controllers\Api\Bootstrap;
use Controllers\Api\HttpException;

/**
 *  API v3 request: route parameters, query parameters and body, with typed accessors
 *  The body is the JSON body of the request, or the fields of a multipart request (packages upload, template import)
 *  Accessors throw a 400 error when a required value is missing or when a value has an invalid type,
 *  with the JSON Pointer (RFC 6901) of the parameter in error
 */
class Request
{
    // API root path, the route is the path that follows
    public const ROOT = '/api/v3';
    // Maximum number of items of a collection page
    public const MAX_LIMIT = 1000;

    private string $method;
    private array $segments;
    private array $params = [];
    private array $query;
    private array $body;
    private array $files;
    // Path of the request, used to build the pagination links
    private string $uri;
    // Path of the body of a sub-request in the request body (e.g. ['schedule']), for error messages and pointers
    private array $path = [];

    public function __construct(string $method, string $path, array $query = [], array $body = [], array $files = [])
    {
        $this->method = $method;
        $this->query = $query;
        $this->body = $body;
        $this->files = $files;
        $this->uri = $path;

        // Remove the API root from the path, then split the route into segments (trailing slash is ignored)
        if (str_starts_with($path, self::ROOT)) {
            $path = substr($path, strlen(self::ROOT));
        }

        $this->segments = array_values(array_map('rawurldecode', array_filter(explode('/', $path), function ($segment) {
            return $segment !== '';
        })));
    }

    /**
     *  Build the request from PHP globals
     */
    public static function fromGlobals(): self
    {
        $body = Bootstrap::body();

        // Multipart requests have no JSON body, their fields are in $_POST
        if (empty($body) and !empty($_POST)) {
            $body = $_POST;
        }

        return new self($_SERVER['REQUEST_METHOD'], (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), $_GET, $body, $_FILES);
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getSegments(): array
    {
        return $this->segments;
    }

    /**
     *  Set the parameters extracted from the route (e.g. repository type and Id)
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     *  Return a route parameter
     */
    public function param(string $name): string
    {
        if (!isset($this->params[$name])) {
            throw new HttpException(500, 'Missing route parameter: ' . $name);
        }

        return $this->params[$name];
    }

    /**
     *  Return a route parameter, or null if the route has no such parameter
     */
    public function optionalParam(string $name): string|null
    {
        return $this->params[$name] ?? null;
    }

    /**
     *  Return a numeric route parameter (e.g. an Id), the route only matches digits
     */
    public function id(string $name = 'id'): int
    {
        return (int) $this->param($name);
    }

    /**
     *  Return the whole body
     */
    public function body(): array
    {
        return $this->body;
    }

    /**
     *  Return the uploaded files
     */
    public function files(): array
    {
        return $this->files;
    }

    /**
     *  Return true if the body contains the specified key (even if its value is null)
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->body);
    }

    /**
     *  Return true if the body contains at least one of the specified keys
     */
    public function hasAny(array $keys): bool
    {
        foreach ($keys as $key) {
            if ($this->has($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     *  Throw a 400 error if the body contains keys that are not expected, to report misspelled parameters
     *  instead of silently ignoring them
     */
    public function allow(array $keys): void
    {
        $unknown = array_diff(array_keys($this->body), $keys);

        if (empty($unknown)) {
            return;
        }

        $errors = [];

        foreach ($unknown as $key) {
            $errors[] = ['detail' => 'Unknown parameter', 'pointer' => $this->pointer((string) $key)];
        }

        throw new HttpException(400, 'Unknown parameter(s): ' . implode(', ', array_map(fn ($key) => $this->name((string) $key), $unknown)) . '. Valid parameters are: ' . (empty($keys) ? 'none' : implode(', ', array_map(fn ($key) => $this->name($key), $keys))), 'invalid_parameter', $errors);
    }

    /**
     *  Return a string value from the body
     *  Numbers are accepted and converted, as some values can be numeric (e.g. a release version)
     */
    public function string(string $key, string|null $default = null): string
    {
        if (!isset($this->body[$key])) {
            return $default ?? throw $this->missing($key);
        }

        $value = $this->body[$key];

        if (is_int($value) or is_float($value)) {
            return (string) $value;
        }

        if (!is_string($value)) {
            throw $this->invalid($key, 'a string');
        }

        return $value;
    }

    /**
     *  Return a boolean value from the body
     *  'true' / 'false' strings are accepted, as multipart fields are always strings
     */
    public function bool(string $key, bool|null $default = null): bool
    {
        if (!isset($this->body[$key])) {
            return $default ?? throw $this->missing($key);
        }

        $value = $this->body[$key];

        if (is_string($value)) {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        if (!is_bool($value)) {
            throw $this->invalid($key, 'a boolean');
        }

        return $value;
    }

    /**
     *  Return a 'true' / 'false' string from a boolean body value, the format expected by tasks parameters
     */
    public function boolString(string $key, bool|null $default = null): string
    {
        return $this->bool($key, $default) ? 'true' : 'false';
    }

    /**
     *  Return an integer value from the body, or null if the value is null and null is allowed
     */
    public function int(string $key, int|null $default = null, bool $nullable = false): int|null
    {
        if (!isset($this->body[$key])) {
            if ($nullable and $this->has($key)) {
                return null;
            }

            return $default ?? throw $this->missing($key);
        }

        $value = $this->body[$key];

        if (is_string($value) and preg_match('/^-?[0-9]+$/', $value)) {
            $value = (int) $value;
        }

        if (!is_int($value)) {
            throw $this->invalid($key, 'an integer');
        }

        return $value;
    }

    /**
     *  Return a list of strings from the body
     *  A single string is accepted and converted to a list of one value
     */
    public function list(string $key, array|null $default = null): array
    {
        if (!isset($this->body[$key])) {
            return $default ?? throw $this->missing($key);
        }

        $value = $this->body[$key];

        if (is_string($value) or is_int($value)) {
            $value = [$value];
        }

        if (!is_array($value) or !array_is_list($value)) {
            throw $this->invalid($key, 'a list');
        }

        foreach ($value as $item) {
            if (!is_string($item) and !is_int($item)) {
                throw $this->invalid($key, 'a list of strings');
            }
        }

        return array_map('strval', $value);
    }

    /**
     *  Return an array value from the body (list or object), without checking its content
     *  Its content must be checked by the code using it
     */
    public function array(string $key, array|null $default = null): array
    {
        if (!isset($this->body[$key])) {
            return $default ?? throw $this->missing($key);
        }

        if (!is_array($this->body[$key])) {
            throw $this->invalid($key, 'an array');
        }

        return $this->body[$key];
    }

    /**
     *  Return an object value from the body, as an associative array
     */
    public function object(string $key, array|null $default = null): array
    {
        if (!isset($this->body[$key])) {
            return $default ?? throw $this->missing($key);
        }

        $value = $this->body[$key];

        if (!is_array($value) or (!empty($value) and array_is_list($value))) {
            throw $this->invalid($key, 'an object');
        }

        return $value;
    }

    /**
     *  Return a sub-request built from an object value of the body, to read its values with the same accessors
     *  e.g. $request->sub('schedule')->string('type')
     */
    public function sub(string $key): self
    {
        $request = new self($this->method, '', [], $this->object($key, []));
        $request->path = array_merge($this->path, [$key]);

        return $request;
    }

    /**
     *  Return the name of a body parameter as displayed in error messages, e.g. 'schedule.type'
     */
    private function name(string $key): string
    {
        return implode('.', array_merge($this->path, [$key]));
    }

    /**
     *  Return the JSON Pointer (RFC 6901) of a body parameter, e.g. '#/schedule/type'
     */
    private function pointer(string $key): string
    {
        $segments = array_map(function ($segment) {
            return str_replace(['~', '/'], ['~0', '~1'], (string) $segment);
        }, array_merge($this->path, [$key]));

        return '#/' . implode('/', $segments);
    }

    /**
     *  Return the error of a missing body parameter
     */
    private function missing(string $key): HttpException
    {
        return HttpException::missingParameter($this->name($key), $this->pointer($key));
    }

    /**
     *  Return the error of a body parameter of an invalid type
     */
    private function invalid(string $key, string $expected): HttpException
    {
        return HttpException::invalidParameter('Parameter ' . $this->name($key) . ' must be ' . $expected, $this->pointer($key));
    }

    /**
     *  Return a query parameter, or the default value if it is not set
     */
    public function query(string $key, string|null $default = null): string|null
    {
        if (!isset($this->query[$key])) {
            return $default;
        }

        if (!is_string($this->query[$key])) {
            throw HttpException::invalidQueryParameter('Query parameter ' . $key . ' must be a string', $key);
        }

        return $this->query[$key];
    }

    /**
     *  Return the pagination of a collection: ?limit=<1-1000, default 100>&offset=<default 0>
     *  Return [limit, offset]
     */
    public function pagination(): array
    {
        $limit = $this->query('limit', '100');
        $offset = $this->query('offset', '0');

        if (!ctype_digit($limit) or (int) $limit < 1 or (int) $limit > self::MAX_LIMIT) {
            throw HttpException::invalidQueryParameter('Query parameter limit must be an integer between 1 and ' . self::MAX_LIMIT, 'limit');
        }

        if (!ctype_digit($offset)) {
            throw HttpException::invalidQueryParameter('Query parameter offset must be a positive integer', 'offset');
        }

        return [(int) $limit, (int) $offset];
    }

    /**
     *  Return the URI of the request with other query parameters, e.g. to link to another page of a collection
     */
    public function uriWith(array $query): string
    {
        return $this->uri . '?' . http_build_query(array_merge($this->query, $query), '', '&', PHP_QUERY_RFC3986);
    }

    /**
     *  Return true if a boolean query parameter is set: ?packages, ?packages=true or ?packages=1
     */
    public function queryFlag(string $key): bool
    {
        if (!isset($this->query[$key])) {
            return false;
        }

        if ($this->query[$key] === '') {
            return true;
        }

        $value = is_string($this->query[$key]) ? filter_var($this->query[$key], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;

        if ($value === null) {
            throw HttpException::invalidQueryParameter('Query parameter ' . $key . ' must be a boolean', $key);
        }

        return $value;
    }
}
