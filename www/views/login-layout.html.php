<!DOCTYPE html>
<html>
<?php
if (!defined('ROOT')) {
    define('ROOT', '/var/www/repomanager');
}

require_once(ROOT . '/controllers/Autoloader.php');
new \Controllers\Autoloader('minimal');
include_once(ROOT . '/views/includes/head.inc.php');

$loginErrors = array();
$error = 0;

if (SSO_OIDC_ONLY == 'true' && OIDC_ENABLED == 'true') {
    ssoLogin();
    exit();
}

/**
 * Login request
 */
if (!empty($_POST['authType']) || isset($_GET['code'])) {

    /**
     * SSO Login
     */
    if (($_POST['authType'] == 'sso' || isset($_GET['code'])) && OIDC_ENABLED == 'true') {
        ssoLogin();
        exit();
    }

    /**
     *  If username and password have been sent
     */
    if (!empty($_POST['username']) and !empty($_POST['password'])) {
        /**
         *  Checking auth type (default is local for the moment)
         */
        if ($_POST['authType'] != 'local' and $_POST['authType'] != 'ldap') {
            $error++;
            $loginErrors[] = 'Specified connection type is invalid';
        }

        /**
         *  Continue if there is no error
         */
        if ($error == 0) {
            $username = \Controllers\Common::validateData($_POST['username']);
            $mylogin = new \Controllers\Login();
            $myhistory = new \Controllers\History();

            /**
             *  Case auth type is 'ldap'
             */
            if ($_POST['authType'] == 'ldap') {
                /**
                 *  To do
                 */

                $loginErrors[] = 'Invalid login and/or password';
            }

            /**
             *  Case auth type is 'local'
             */
            if ($_POST['authType'] == 'local') {
                /**
                 *  Checking in database that username/password couple is matching
                 */
                try {
                    $mylogin->checkUsernamePwd($username, $_POST['password']);

                    /**
                     *  Getting all user informations in datbase
                     */
                    $mylogin->getAll($username);

                    /**
                     *  Starting session
                     */
                    session_start();

                    /**
                     *  Saving user informations in session variable
                     */
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = $mylogin->getRole();
                    $_SESSION['first_name'] = $mylogin->getFirstName();
                    $_SESSION['last_name'] = $mylogin->getLastName();
                    $_SESSION['email'] = $mylogin->getEmail();
                    $_SESSION['type'] = 'local';

                    $myhistory->set($username, 'Authentication', 'success');

                    /**
                     *  If an 'origin' cookie exists then redirect to the specified URI
                     */
                    if (!empty($_COOKIE['origin'])) {
                        if ($_COOKIE['origin'] != '/logout') {
                            header('Location: ' . $_COOKIE['origin']);
                            exit();
                        }
                    }

                    /**
                     *  Else redirect to default page '/'
                     */
                    header('Location: /');
                    exit();
                } catch (Exception $e) {
                    $loginErrors[] = $e->getMessage();
                }
            }
        }
    }
}

function ssoLogin(): void
{
    require_once ROOT . '/libs/vendor/autoload.php';

    $oidc = new Jumbojett\OpenIDConnectClient(
        OIDC_PROVIDER_URL,
        OIDC_CLIENT_ID,
        OIDC_CLIENT_SECRET
    );

    $oidc->setHttpUpgradeInsecureRequests(false);

    if (!empty(OIDC_AUTHORIZATION_ENDPOINT)) {
        $oidc->providerConfigParam(['authorization_endpoint' => OIDC_AUTHORIZATION_ENDPOINT]);
    }

    if (!empty(OIDC_TOKEN_ENDPOINT)) {
        $oidc->providerConfigParam(['token_endpoint' => OIDC_TOKEN_ENDPOINT]);
    }

    if (!empty(OIDC_USERINFO_ENDPOINT)) {
        $oidc->providerConfigParam(['userinfo_endpoint' => OIDC_USERINFO_ENDPOINT]);
    }

    if (!empty(OIDC_SCOPES)) {
        $scopes = explode(',', OIDC_SCOPES);
        $oidc->addScope($scopes);
    }

    $oidc->authenticate();

    $username = $oidc->getVerifiedClaims(OIDC_USERNAME);

    $firstName = $oidc->requestUserInfo(OIDC_FIRST_NAME);
    $lastName = $oidc->requestUserInfo(OIDC_LAST_NAME);
    $email = $oidc->requestUserInfo(OIDC_EMAIL);

    $role = 'usage';
    $roles = $oidc->getVerifiedClaims(OIDC_GROUPS);

    if (is_array($roles)) {
        if (!empty(OIDC_GROUP_ADMINISTRATOR) && in_array(OIDC_GROUP_ADMINISTRATOR, $roles)) {
            $role = 'administrator';
        }
        if (!empty(OIDC_GROUP_SUPER_ADMINISTRATOR) && in_array(OIDC_GROUP_SUPER_ADMINISTRATOR, $roles)) {
            $role = 'super-administrator';
        }
    }

    /**
     * Add user
     */
    $mylogin = new \Controllers\Login();
    $mylogin->addUserSSO($username, $firstName, $lastName, $email, $role);

    /**
     *  Saving user informations in session variable
     */
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role;
    $_SESSION['first_name'] = $firstName;
    $_SESSION['last_name'] = $lastName;
    $_SESSION['email'] = $email;
    $_SESSION['type'] = 'sso';

    $myhistory = new \Controllers\History();
    $myhistory->set($username, 'Authentication', 'success');

    /**
     *  If an 'origin' cookie exists then redirect to the specified URI
     */
    if (!empty($_COOKIE['origin'])) {
        if ($_COOKIE['origin'] != '/logout') {
            header('Location: ' . $_COOKIE['origin']);
            exit();
        }
    }

    /**
     *  Else redirect to default page '/'
     */
    header('Location: /');
}?>
<head>
    <meta charset="utf-8">
    <!-- CSS -->
    <link rel="stylesheet" type="text/css" href="/resources/styles/main.css">

    <!-- Favicon -->
    <link rel="icon" href="/assets/favicon.ico" />
    <title>Login</title>
</head>

<body>
    <div id="loginDiv-container">
        <div id="loginDiv">
            <img src="/assets/icons/package.svg" class="margin-bottom-30 mediumopacity-cst" />

            <form action="/login" method="post" autocomplete="off">
                <input type="hidden" name="authType" value="local" />
                <!-- <div class="switch-field">
                    <input type="radio" id="authType_local" name="authType" value="local" checked />
                    <label for="authType_local">Local</label>
                    <input type="radio" id="authType_ldap" name="authType" value="ldap" />
                    <label for="authType_ldap">LDAP</label>
                </div>   
                <br> -->
                <input type="text" name="username" placeholder="Username" required />
                <br>
                <input type="password" name="password" placeholder="Password" required />
                <br>
                <button class="btn-large-green" type="submit">Login</button>
            </form>
            <br>

            <?php
            /**
             * Show sso login button
             */
            if (OIDC_ENABLED == 'true') : ?>
                <form action="/login" method="post">
                    <input type="hidden" name="authType" value="sso" />
                    <button class="btn-large-green" type="submit">SSO</button>
                </form>
                <?php
            endif ?>

            <?php
            /**
             *  Display authentication errors if any
             */
            if (!empty($loginErrors)) {
                foreach ($loginErrors as $loginError) {
                    echo '<p>' . $loginError . '</p>';
                }
            } ?>
        </div>
    </div>
</body>
</html>