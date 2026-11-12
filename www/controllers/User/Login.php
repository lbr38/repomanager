<?php
namespace Controllers\User;

use Controllers\App\Session;

/**
 *  Composer autoload
 */
require ROOT . '/libs/vendor/autoload.php';

use Exception;
use Jumbojett\OpenIDConnectClient;
use Controllers\App\DebugMode;
use Controllers\History\Save as History;
use Controllers\History\History as HistoryController;
use Controllers\Utils\Validate;

class Login extends User
{
    /**
     *  Maximum number of failed local authentications for a username from an IP address, during the specified period
     */
    private const MAX_FAILED_LOGINS = 10;
    private const FAILED_LOGINS_PERIOD = 15; // minutes

    /**
     *  Login local user
     */
    public function login(string $username, string $password) : void
    {
        try {
            $username = Validate::string($username);

            // Refuse authentication if too many failed attempts have been made recently (prevents brute force)
            $historyController = new HistoryController();

            if ($historyController->countFailedLogins($username, self::FAILED_LOGINS_PERIOD) >= self::MAX_FAILED_LOGINS) {
                $tooManyAttempts = true;
                throw new Exception('Too many failed attempts');
            }

            // Get user Id from username
            $id = $this->getIdByUsername($username, 'local');

            // If no matching user has been found, throw an exception
            if (empty($id)) {
                throw new Exception('Unknown login');
            }

            // Checking in database that username/password couple is matching
            $this->checkUsernamePwd($id, $password);

            // Getting all user informations in database
            $informations = $this->get($id);

            // Generate a new session Id now that the user is authenticated (prevents session fixation)
            Session::regenerate();

            // Saving user informations in session variables
            Session::set([
                'username'   => $username,
                'id'         => $informations['userId'],
                'role'       => $informations['Role_name'],
                'first_name' => $informations['First_name'],
                'last_name'  => $informations['Last_name'],
                'email'      => $informations['Email'],
                'type'       => 'local',
            ]);

            // Add history
            History::set('Authentication (local account)', category: 'authentication');

            // If an 'origin' cookie exists then redirect the user to the specified URI (only a local path)
            if (!empty($_COOKIE['origin']) and self::localPath($_COOKIE['origin'])) {
                if ($_COOKIE['origin'] != '/logout') {
                    header('Location: ' . $_COOKIE['origin']);
                    exit();
                }
            }

            // Else redirect to default page '/'
            header('Location: /');

            exit();
        } catch (Exception $e) {
            // Add history
            History::set('Authentication failed for <code>' . $username . '</code> (local account): ' . $e->getMessage(), 'error', $username, category: 'authentication');

            if (isset($tooManyAttempts)) {
                throw new Exception('Too many failed login attempts, please try again in ' . self::FAILED_LOGINS_PERIOD . ' minutes');
            }

            // Throw back an exception with generic message to display on login page
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

            // Initialize OpenID Connect client
            $oidc = new OpenIDConnectClient(
                OIDC_PROVIDER_URL,
                OIDC_CLIENT_ID,
                OIDC_CLIENT_SECRET
            );

            // Disable https upgrade: useful for local/dev environment with no https
            $oidc->setHttpUpgradeInsecureRequests(false);

            // Use OIDC_AUTHORIZATION_ENDPOINT as authorization_endpoint if defined
            if (!empty(OIDC_AUTHORIZATION_ENDPOINT)) {
                $oidc->providerConfigParam(['authorization_endpoint' => OIDC_AUTHORIZATION_ENDPOINT]);
            }

            // Use OIDC_TOKEN_ENDPOINT as token_endpoint if defined
            if (!empty(OIDC_TOKEN_ENDPOINT)) {
                $oidc->providerConfigParam(['token_endpoint' => OIDC_TOKEN_ENDPOINT]);
            }

            // Use OIDC_USERINFO_ENDPOINT as userinfo_endpoint if defined
            if (!empty(OIDC_USERINFO_ENDPOINT)) {
                $oidc->providerConfigParam(['userinfo_endpoint' => OIDC_USERINFO_ENDPOINT]);
            }

            // Use OIDC_SCOPES as scopes if defined
            if (!empty(OIDC_SCOPES)) {
                // Convert OIDC_SCOPES string to array
                $scopes = explode(',', OIDC_SCOPES);
                $oidc->addScope($scopes);
            }

            // Use OIDC_HTTP_PROXY as httpProxy if defined
            if (!empty(OIDC_HTTP_PROXY)) {
                $oidc->setHttpProxy(OIDC_HTTP_PROXY);
            }

            // Use OIDC_CERT_PATH as certPath if defined
            if (!empty(OIDC_CERT_PATH)) {
                $oidc->setCertPath(OIDC_CERT_PATH);
            }

            // Try to authenticate user
            $oidc->authenticate();

            // Get user informations
            $roles     = $oidc->getVerifiedClaims(OIDC_GROUPS);
            $username  = $oidc->getVerifiedClaims(OIDC_USERNAME);
            $firstName = $oidc->requestUserInfo(OIDC_FIRST_NAME);
            $lastName  = $oidc->requestUserInfo(OIDC_LAST_NAME);
            $email     = $oidc->requestUserInfo(OIDC_EMAIL);

            if (empty($username)) {
                throw new Exception('No username found in SSO response');
            }

            // Define user role based on OIDC_GROUPS
            if (is_array($roles)) {
                if (!empty(OIDC_GROUP_ADMINISTRATOR) && in_array(OIDC_GROUP_ADMINISTRATOR, $roles)) {
                    $role = 'administrator';
                }
                // if (!empty(OIDC_GROUP_SUPER_ADMINISTRATOR) && in_array(OIDC_GROUP_SUPER_ADMINISTRATOR, $roles)) {
                //     $role = 'super-administrator';
                // }
            }

            // Generate a new session Id now that the user is authenticated (prevents session fixation)
            Session::regenerate();

            // Saving user informations in session variable
            Session::set([
                'username'   => $username,
                'role'       => $role,
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'email'      => $email,
                'type'       => 'sso',
            ]);

            // Create user in database
            $userCreateController->createSSO($username, $firstName, $lastName, $email, $role);

            // Also save user Id in session variable now that the user exists in database
            Session::set([
                'id' => $this->getIdByUsername($username, 'sso'),
            ]);

            // Add history
            History::set('Authentication (SSO account)', category: 'authentication');

            // If an 'origin' cookie exists then redirect the user to the specified URI (only a local path)
            if (!empty($_COOKIE['origin']) and self::localPath($_COOKIE['origin'])) {
                if ($_COOKIE['origin'] != '/logout') {
                    header('Location: ' . $_COOKIE['origin']);
                    exit();
                }
            }

            // Else redirect to default page '/'
            header('Location: /');

            exit();
        } catch (Exception $e) {
            /**
             *  Add history
             *  Specify the username if it has been found
             */
            History::set('Authentication failed (SSO account): ' . htmlspecialchars($e->getMessage()), 'error', $username ?? 'unknown', category: 'authentication');

            /**
             *  If debug mode is enabled, display error message and try to get verified claims and user info for debugging
             */
            if (DebugMode::enabled()) {
                $error = 'Could not connect through SSO: ' . htmlspecialchars($e->getMessage());

                // Try to get verified claims and user info if defined
                if (isset($oidc)) {
                    try {
                        $verifiedClaims = $oidc->getVerifiedClaims();
                        $requestUserInfo = $oidc->requestUserInfo();
                        $error .= '<br><h5>DEBUG</h5><p>Verified claims:</p>';
                        $error .= '<pre class="codeblock">' . htmlspecialchars(print_r($verifiedClaims, true)) . '</pre>';
                        $error .= '<br><p>Request user info:</p>';
                        $error .= '<pre class="codeblock">' . htmlspecialchars(print_r($requestUserInfo, true)) . '</pre>';
                    // It's not necessary to print the error if it fails, so just catch the exception and do nothing
                    } catch (Exception $e) {
                    }
                }

                // Throw exception to display error message on login page
                throw new Exception($error);

            // If debug mode is disabled, just display the error message
            } else {
                // Throw exception to display error message on login page
                throw new Exception('Could not connect through SSO: ' . htmlspecialchars($e->getMessage()));
            }
        }
    }

    /**
     *  Return true if the specified URI is a local path of the application
     *  Prevents redirecting the user to another site (e.g. '//evil.com', '/\\evil.com')
     */
    private static function localPath(string $uri): bool
    {
        return (bool) preg_match('#^/(?![/\\\\])[a-zA-Z0-9/_.~%-]*$#', $uri);
    }
}
