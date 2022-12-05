<?php

namespace Controllers\Api;

use Exception;

class Api
{
    /**
     *  Check that specified host id and token are valid
     */
    public static function checkHostAuthentication(string $id, string $token)
    {
        $myhost = new \Controllers\Host();
        $myhost->setAuthId($id);
        $myhost->setToken($token);

        if (!$myhost->checkIdToken()) {
            return false;
        }

        return true;
    }

    /**
     *  Return 201 with specified results
     */
    public static function returnSuccess(array $results)
    {
        $returnArray = array("return" => "201");
        $returnArray = array_merge($returnArray, $results);

        echo json_encode($returnArray);
        exit;
    }

    /**
     *  Return 401 Authentication required
     */
    public static function returnAuthenticationRequired()
    {
        http_response_code(401);
        echo json_encode(['return' => '401', 'message_error' => array('Authentication required.')]);
        exit;
    }

    /**
     *  Return 401 Bad credentials
     */
    public static function returnBadCredentials()
    {
        http_response_code(401);
        echo json_encode(['return' => '401', 'message_error' => array('Bad credentials.')]);
        exit;
    }
}
