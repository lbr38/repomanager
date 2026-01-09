<!DOCTYPE html>
<html>
<?php
include_once(ROOT . '/views/includes/head.inc.php');

try {
    $userLoginController = new \Controllers\User\Login();

    if (!empty($_POST['authType']) and $_POST['authType'] == 'local' and SSO_OIDC_ONLY == 'true') {
        throw new Exception('Local account login is disabled');
    }

    /**
     *  If SSO only (local account disabled), login using SSO
     */
    if (SSO_OIDC_ONLY == 'true' && OIDC_ENABLED == 'true') {
        $userLoginController->ssoLogin();
        exit();
    }

    /**
     *  Login request (user clicked on one of the login buttons)
     */
    if (!empty($_POST['authType']) || isset($_GET['code'])) {
        /**
         *  Checking if auth type is valid (local or sso)
         */
        if (!empty($_POST['authType']) and !in_array($_POST['authType'], ['local', 'sso'])) {
            throw new Exception('Specified connection type is invalid');
        }

        /**
         *  Local account login, if username and password have been sent
         */
        if (!empty($_POST['authType']) and $_POST['authType'] == 'local' and !empty($_POST['username']) and !empty($_POST['password'])) {
            $userLoginController->login($_POST['username'], $_POST['password']);
        }

        /**
         *  SSO Login
         */
        if (((!empty($_POST['authType']) and $_POST['authType'] == 'sso') || isset($_GET['code'])) && OIDC_ENABLED == 'true') {
            $userLoginController->ssoLogin();
        }

        exit();
    }
} catch (Exception $e) {
    $loginError = $e->getMessage();
} ?>

<head>
    <meta charset="utf-8">
    <!-- CSS -->
    <link rel="stylesheet" type="text/css" href="/resources/styles/common.css">
    <link rel="stylesheet" type="text/css" href="/resources/styles/main.css">
    <link rel="stylesheet" type="text/css" href="/resources/styles/login.css">

    <title>Login</title>
</head>

<body>
    <div id="login-container">
        <div id="login">
            <img src="/assets/icons/package.svg" class="margin-bottom-30 mediumopacity-cst" />

            <?php
            /** Show system use notification */
            if (!empty(SYSTEM_USE_NOTIFICATION)) {
                echo '<p>' . SYSTEM_USE_NOTIFICATION . '</p>';
            } ?>

            <form action="/login" method="post" autocomplete="off">
                <input type="hidden" name="authType" value="local" />
                <input type="text" name="username" placeholder="Username" required />
                <br>
                <input type="password" name="password" placeholder="Password" required />
                <br>
                <button class="btn-large-green" type="submit">Login</button>
            </form>
            <br>

            <?php
            /**
             * Show SSO login button
             */
            if (OIDC_ENABLED == 'true') : ?>
                <form action="/login" method="post">
                    <input type="hidden" name="authType" value="sso" />
                    <button class="btn-large-green" type="submit">SSO</button>
                </form>
                <?php
            endif; ?>
        </div>

        <div id="login-error">
            <?php
            /**
             *  Display authentication errors if any
             */
            if (!empty($loginError)) {
                echo '<p>' . $loginError . '</p>';
            } ?>
        </div>
    </div>
</body>
</html>