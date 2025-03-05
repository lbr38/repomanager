<?php
namespace Controllers\User;

/**
 *  Composer autoload
 */
require ROOT . '/libs/vendor/autoload.php';

use Exception;
use Jumbojett\OpenIDConnectClient;

class Login extends User
{
    /**
     *  Login local user
     */
    public function login(string $username, string $password) : void
    {
        $username = \Controllers\Common::validateData($username);

        /**
         *  Get user Id from username
         */
        $id = $this->getIdByUsername($username);

        /**
         *  If no matching user has been found, throw an exception
         */
        if (empty($id)) {
            throw new Exception('Invalid login and/or password');
        }

        /**
         *  Checking in database that username/password couple is matching
         */
        $this->checkUsernamePwd($id, $_POST['password']);

        /**
         *  Getting all user informations in datbase
         */
        $informations = $this->get($id);

        /**
         *  Starting session
         */
        session_start();

        /**
         *  Saving user informations in session variables
         */
        $_SESSION['username']   = $username;
        $_SESSION['id']         = $informations['userId'];
        $_SESSION['role']       = $informations['Role_name'];
        $_SESSION['first_name'] = $informations['First_name'];
        $_SESSION['last_name']  = $informations['Last_name'];
        $_SESSION['email']      = $informations['Email'];
        $_SESSION['type']       = 'local';

        /**
         *  If an 'origin' cookie exists then redirect the user to the specified URI
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
    }

    /**
     *  Login SSO user
     */
    public function ssoLogin(): void
    {
        try {
            $oidc = new OpenIDConnectClient(
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
             *  Add user
             */
            $mylogin = new \Controllers\Login();
            $mylogin->addUserSSO($username, $firstName, $lastName, $email, $role);

            /**
             *  Saving user informations in session variable
             */
            $_SESSION['username']   = $username;
            $_SESSION['role']       = $role;
            $_SESSION['first_name'] = $firstName;
            $_SESSION['last_name']  = $lastName;
            $_SESSION['email']      = $email;
            $_SESSION['type']       = 'sso';

            $myhistory = new \Controllers\History();
            $myhistory->set($username, 'Authentication', 'success');

            /**
             *  If an 'origin' cookie exists then redirect the user to the specified URI
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
            throw new Exception('Could not connect through SSO: ' . $e->getMessage());
        }
    }
}
