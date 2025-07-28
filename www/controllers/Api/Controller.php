<?php

namespace Controllers\Api;

use Exception;

class Controller
{
    protected $method;
    protected $uri;
    protected $data;
    protected $hostId;

    public function __construct(string $method, array $uri)
    {
        $this->method = $method;
        $this->uri = $uri;
    }

    /**
     *  Set retrieved JSON data from request
     */
    public function setJsonData(object $data)
    {
        $this->data = $data;
    }
}
