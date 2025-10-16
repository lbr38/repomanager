<?php

namespace Controllers\Layout;

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

        if (class_exists($class)) {
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
        // Start the session
        session_start();

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
        include_once(ROOT . '/public/custom_errors/custom_404.html');
    }
}
