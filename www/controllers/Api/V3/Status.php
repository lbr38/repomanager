<?php

namespace Controllers\Api\V3;

/**
 *  /status: check that the API is available and that the authentication is valid
 */
class Status extends Controller
{
    public function get(): Response
    {
        return Response::ok(['status' => 'OK', 'version' => VERSION]);
    }
}
