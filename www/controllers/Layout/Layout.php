<?php

namespace Controllers\Layout;

use Controllers\App\Session;

class Layout
{
    public function render(string $tab)
    {
        if ($tab == 'login') {
            $this->login();
        }

        if ($tab == 'logout') {
            $this->logout();
        }

        $class = '\Controllers\Layout\Tab' . '\\' . ucfirst($tab);

        /**
         *  Check that the tab exists before loading its class
         *  The autoloader throws an exception when a class file does not exist, unknown tabs must display the 404 page
         */
        $tabExists = (preg_match('/^[a-z0-9-]+$/', $tab) and file_exists(ROOT . '/controllers/Layout/Tab/' . ucfirst($tab) . '.php'));

        if ($tabExists and class_exists($class)) {
            ob_start();

            $class::render();

            $content = ob_get_clean();

            include_once(ROOT . '/views/layout.html.php');
        } else {
            $this->notFound();
        }
    }

    /**
     *  Render login page
     */
    private function login()
    {
        include_once(ROOT . '/views/login-layout.html.php');

        exit();
    }

    /**
     *  Logout
     *  Destroy current session and redirect to login page
     */
    private function logout()
    {
        // Start the session if not already active
        Session::start();

        // Reinitialize the session array, empty it
        $_SESSION = [];

        // If it's desired to kill the session, also delete the session cookie.
        // Note: This will destroy the session, and not just the session data!
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }

        // Destroy the session
        session_destroy();

        // Destroy the session array
        unset($_SESSION);

        // Redirect to login
        header('Location: /login');

        exit();
    }

    /**
     *  Render Not found page
     */
    private function notFound()
    {
        http_response_code(404);

        include_once(ROOT . '/public/custom_errors/custom_404.html');
    }
}
