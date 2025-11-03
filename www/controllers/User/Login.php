<?php
namespace Controllers\User;

/**
 *  Composer autoload
 */
require ROOT . '/libs/vendor/autoload.php';

use Exception;
use Jumbojett\OpenIDConnectClient;
use \Controllers\App\DebugMode;
use \Controllers\History\Save as History;
use \Controllers\Utils\Validate;

class Login extends User
{
    /**
     *  Login local user
     */
    public function login(string $username, string $password) : void
    {
        try {
            $username = Validate::string($username);

            /**
             *  Get user Id from username
             */
            $id = $this->getIdByUsername($username, 'local');

            /**
             *  If no matching user has been found, throw an exception
             */
            if (empty($id)) {
                throw new Exception('Unknown login');
            }

            /**
             *  Checking in database that username/password couple is matching
             */
            $this->checkUsernamePwd($id, $password);

            /**
             *  Getting all user informations in datbase
             */
            $informations = $this->get($id);

            /**
             *  Starting session
             */
            session_start([
                'cookie_secure'   => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'),
                'cookie_httponly' => true,
            ]);

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
             *  Add history
             */
            History::set('Authentication (local account)');

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
            /**
             *  Add history
             */
            History::set('Authentication failed for <code>' . $username . '</code> (local account): ' . $e->getMessage(), 'error', $username);

            /**
             *  Throw back an exception with generic message to display on login page
             */
            throw new Exception('Invalid login and/or password');
        }
    }

    /**
     *  Login SSO user
     */
    public function ssoLogin(): void
    {
        try {
            $userCreateController = new \Controllers\User\Create();
            $username = '';
            $firstName = '';
            $lastName = '';
            $email = '';
            $role = 'usage';

            /**
             *  Initialize OpenID Connect client
             */
            $oidc = new OpenIDConnectClient(
                OIDC_PROVIDER_URL,
                OIDC_CLIENT_ID,
                OIDC_CLIENT_SECRET
            );

            /**
             *  Disable https upgrade: useful for local/dev environment with no https
             */
            $oidc->setHttpUpgradeInsecureRequests(false);

            /**
             *  Use OIDC_AUTHORIZATION_ENDPOINT as authorization_endpoint if defined
             */
            if (!empty(OIDC_AUTHORIZATION_ENDPOINT)) {
                $oidc->providerConfigParam(['authorization_endpoint' => OIDC_AUTHORIZATION_ENDPOINT]);
            }

            /**
             *  Use OIDC_TOKEN_ENDPOINT as token_endpoint if defined
             */
            if (!empty(OIDC_TOKEN_ENDPOINT)) {
                $oidc->providerConfigParam(['token_endpoint' => OIDC_TOKEN_ENDPOINT]);
            }

            /**
             *  Use OIDC_USERINFO_ENDPOINT as userinfo_endpoint if defined
             */
            if (!empty(OIDC_USERINFO_ENDPOINT)) {
                $oidc->providerConfigParam(['userinfo_endpoint' => OIDC_USERINFO_ENDPOINT]);
            }

            /**
             *  Use OIDC_SCOPES as scopes if defined
             */
            if (!empty(OIDC_SCOPES)) {
                // Convert OIDC_SCOPES string to array
                $scopes = explode(',', OIDC_SCOPES);
                $oidc->addScope($scopes);
            }

            /**
             *  Use OIDC_HTTP_PROXY as httpProxy if defined
             */
            if (!empty(OIDC_HTTP_PROXY)) {
                $oidc->setHttpProxy(OIDC_HTTP_PROXY);
            }

            /**
             *  Use OIDC_CERT_PATH as certPath if defined
             */
            if (!empty(OIDC_CERT_PATH)) {
                $oidc->setCertPath(OIDC_CERT_PATH);
            }

            /**
             *  Try to authenticate user
             */
            $oidc->authenticate();

            /**
             *  Get user informations
             */
            $roles     = $oidc->getVerifiedClaims(OIDC_GROUPS);
            $username  = $oidc->getVerifiedClaims(OIDC_USERNAME);
            $firstName = $oidc->requestUserInfo(OIDC_FIRST_NAME);
            $lastName  = $oidc->requestUserInfo(OIDC_LAST_NAME);
            $email     = $oidc->requestUserInfo(OIDC_EMAIL);

            if (empty($username)) {
                throw new Exception('No username found in SSO response');
            }

            /**
             *  Define user role based on OIDC_GROUPS
             */
            if (is_array($roles)) {
                if (!empty(OIDC_GROUP_ADMINISTRATOR) && in_array(OIDC_GROUP_ADMINISTRATOR, $roles)) {
                    $role = 'administrator';
                }
                // if (!empty(OIDC_GROUP_SUPER_ADMINISTRATOR) && in_array(OIDC_GROUP_SUPER_ADMINISTRATOR, $roles)) {
                //     $role = 'super-administrator';
                // }
            }

            /**
             *  Saving user informations in session variable
             */
            $_SESSION['username']   = $username;
            $_SESSION['role']       = $role;
            $_SESSION['first_name'] = $firstName;
            $_SESSION['last_name']  = $lastName;
            $_SESSION['email']      = $email;
            $_SESSION['type']       = 'sso';

            /**
             *  Create user in database
             */
            $userCreateController->createSSO($username, $firstName, $lastName, $email, $role);

            /**
             *  Also save user Id in session variable now that the user exists in database
             */
            $_SESSION['id'] = $this->getIdByUsername($username, 'sso');

            /**
             *  Add history
             */
            History::set('Authentication (SSO account)');

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
            /**
             *  Add history
             *  Specify the username if it has been found
             */
            History::set('Authentication failed (SSO account): ' . $e->getMessage(), 'error', $username ?? 'unknown');

            /**
             *  If debug mode is enabled, display error message and try to get verified claims and user info for debugging
             */
            if (DebugMode::enabled()) {
                $error = 'Could not connect through SSO: ' . $e->getMessage();

                // Try to get verified claims and user info if defined
                if (isset($oidc)) {
                    try {
                        $verifiedClaims = $oidc->getVerifiedClaims();
                        $requestUserInfo = $oidc->requestUserInfo();
                        $error .= '<br><h5>DEBUG</h5><p>Verified claims:</p><pre class="codeblock">' . print_r($verifiedClaims, true) . '</pre><br><p>Request user info:</p><pre class="codeblock">' . print_r($requestUserInfo, true) . '</pre>';
                    // It's not necessary to print the error if it fails, so just catch the exception and do nothing
                    } catch (Exception $e) {
                    }
                }

                // Throw exception to display error message on login page
                throw new Exception($error);

            /**
             *  If debug mode is disabled, just display the error message
             */
            } else {
                // Throw exception to display error message on login page
                throw new Exception('Could not connect through SSO: ' . $e->getMessage());
            }
        }
    }
}
