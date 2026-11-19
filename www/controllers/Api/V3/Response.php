<?php

namespace Controllers\Api\V3;

use Controllers\Api\Serializer;
use Controllers\Api\HttpException;

/**
 *  API v3 response
 *
 *  Success: {"data": ..., "meta": {...}}
 *  - data: the requested resource(s), null when there is none
 *  - meta: optional information: message, pagination
 *
 *  Error: Problem Details object (RFC 9457), with the application/problem+json content type
 *  {"type": ..., "title": ..., "status": ..., "detail": ..., "code": ..., "errors": [...]}
 *  - code: stable error code (extension member), clients can rely on it rather than on the messages
 *  - errors: parameters in error (extension member), each one with a detail and a pointer (body) or a parameter (query)
 *
 *  All keys are lowercase: every response goes through the serializer
 */
class Response
{
    // URL of the errors documentation, each error code has its anchor
    public const ERRORS_DOC_URL = 'https://docs.repomanager.net/reference/api/';

    // Title of each error code
    private const TITLES = [
        'invalid_request'    => 'Invalid request',
        'invalid_parameter'  => 'Invalid parameter',
        'missing_parameter'  => 'Missing parameter',
        'operation_refused'  => 'Operation refused',
        'unauthorized'       => 'Authentication required',
        'forbidden'          => 'Forbidden',
        'not_found'          => 'Not found',
        'method_not_allowed' => 'Method not allowed',
        'unavailable'        => 'Service unavailable',
        'internal_error'     => 'Internal error'
    ];

    private int $status;
    private array $body;
    private array $headers;
    private string $contentType = 'application/json';

    private function __construct(int $status, array $body, array $headers = [])
    {
        $this->status = $status;
        $this->body = $body;
        $this->headers = $headers;
    }

    /**
     *  Successful response
     */
    public static function ok(mixed $data, string $message = ''): self
    {
        return new self(200, self::success($data, $message));
    }

    /**
     *  Successful response without data (e.g. an action on a task)
     */
    public static function message(string $message): self
    {
        return new self(200, self::success(null, $message));
    }

    /**
     *  Page of a collection, with the pagination in meta and the links to the other pages (Link header, RFC 8288)
     */
    public static function page(Request $request, array $items, string $message = ''): self
    {
        [$limit, $offset] = $request->pagination();

        $total = count($items);
        $body = self::success(array_values(array_slice($items, $offset, $limit)), $message);
        $body['meta']['pagination'] = ['total' => $total, 'limit' => $limit, 'offset' => $offset];

        $links = [];

        if ($offset + $limit < $total) {
            $links[] = '<' . $request->uriWith(['limit' => $limit, 'offset' => $offset + $limit]) . '>; rel="next"';
        }

        if ($offset > 0) {
            $links[] = '<' . $request->uriWith(['limit' => $limit, 'offset' => max(0, $offset - $limit)]) . '>; rel="prev"';
        }

        return new self(200, $body, empty($links) ? [] : ['Link' => implode(', ', $links)]);
    }

    /**
     *  Resource created, located at the specified URL (Location header)
     */
    public static function created(mixed $data, string $location, string $message = ''): self
    {
        return new self(201, self::success($data, $message), ['Location' => $location]);
    }

    /**
     *  Task started or scheduled: the operation is executed in background, the task is returned
     *  and its URL is in the Location header, to follow its progress
     *  When several tasks are created at once, they are grouped under a parent task, which is returned
     */
    public static function task(array $task, string $message): self
    {
        return new self(202, self::success($task, $message), ['Location' => Request::ROOT . '/tasks/' . $task['id']]);
    }

    /**
     *  Error response (Problem Details, RFC 9457)
     */
    public static function error(HttpException $exception): self
    {
        $code = $exception->getErrorCode();

        $body = [
            'type'   => self::ERRORS_DOC_URL . '#' . str_replace('_', '-', $code),
            'title'  => self::TITLES[$code] ?? 'Error',
            'status' => $exception->getStatus(),
            'detail' => implode(PHP_EOL, self::plainText($exception->getMessages())),
            'code'   => $code
        ];

        if (!empty($exception->getErrors())) {
            $body['errors'] = $exception->getErrors();

            foreach ($body['errors'] as $i => $error) {
                $body['errors'][$i]['detail'] = implode(PHP_EOL, self::plainText([$error['detail'] ?? '']));
            }
        }

        $response = new self($exception->getStatus(), $body);
        $response->contentType = 'application/problem+json';

        return $response;
    }

    /**
     *  Send the response and stop the execution
     */
    public function send(): never
    {
        http_response_code($this->status);
        header('Content-Type: ' . $this->contentType . '; charset=UTF-8');

        // The status code is specified again, as PHP changes it to 302 when a Location header is sent (except for 201 and 3xx)
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value, true, $this->status);
        }

        echo json_encode(Serializer::normalize($this->body), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

        exit($this->status < 400 ? 0 : 1);
    }

    /**
     *  Return the body of a successful response
     */
    private static function success(mixed $data, string $message): array
    {
        $body = ['data' => $data];

        $message = self::plainText([$message]);

        if (!empty($message)) {
            $body['meta']['message'] = $message[0];
        }

        return $body;
    }

    /**
     *  Return messages as plain text
     *  Messages may come from code shared with the web interface and contain HTML formatting (labels, bold...)
     */
    private static function plainText(array $messages): array
    {
        $list = [];

        foreach ($messages as $message) {
            if (!is_string($message)) {
                continue;
            }

            $message = trim(html_entity_decode(strip_tags($message), ENT_QUOTES));

            if ($message !== '') {
                $list[] = $message;
            }
        }

        return $list;
    }
}
