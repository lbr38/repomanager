<!DOCTYPE html>
<html>
<?php
if (!defined('ROOT')) {
    define('ROOT', dirname(__FILE__, 2));
}

require_once(ROOT . '/controllers/Autoloader.php');
new \Controllers\Autoloader('minimal');
include_once(ROOT . '/views/includes/head.inc.php');

$loginErrors = array();
$error = 0;

/**
 *  If username and password have been sent
 */
if (!empty($_POST['username']) and !empty($_POST['password']) and !empty($_POST['authType'])) {
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
                $_SESSION['username']   = $username;
                $_SESSION['role']       = $mylogin->getRole();
                $_SESSION['first_name'] = $mylogin->getFirstName();
                $_SESSION['last_name']  = $mylogin->getLastName();
                $_SESSION['email']      = $mylogin->getEmail();
                $_SESSION['type']       = 'local';

                \Models\History::set($username, 'Authentication', 'success');

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
} ?>
<head>
    <meta charset="utf-8">
    <!-- CSS -->
    <link rel="stylesheet" type="text/css" href="resources/styles/main.css">

    <!-- Favicon -->
    <link rel="icon" href="assets/favicon.ico" />
    <title>Login</title>
</head>

<body>
    <div id="loginDiv-container">
        <div id="loginDiv">
            <h3>AUTHENTICATION</h3>
            <br>
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