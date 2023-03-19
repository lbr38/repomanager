<?php

/**
 *  Edit personnal informations
 */
if (
    $_POST['action'] == "edit"
    and !empty($_POST['username'])
    and isset($_POST['firstName'])
    and isset($_POST['lastName'])
    and isset($_POST['email'])
) {
    $mylogin = new \Controllers\Login();

    try {
        $mylogin->edit($_POST['username'], $_POST['firstName'], $_POST['lastName'], $_POST['email']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Personnal informations saved');
}

/**
 *  Change password
 */
if (
    $_POST['action'] == "changePassword"
    and !empty($_POST['username'])
    and !empty($_POST['actualPassword'])
    and !empty($_POST['newPassword'])
    and !empty($_POST['newPasswordConfirm'])
) {
    $mylogin = new \Controllers\Login();

    try {
        $mylogin->changePassword($_POST['username'], $_POST['actualPassword'], $_POST['newPassword'], $_POST['newPasswordConfirm']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'New password saved');
}

response(HTTP_BAD_REQUEST, 'Invalid action');
