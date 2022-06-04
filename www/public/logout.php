<?php

/**
 *  Destruction de la session en cours et redirection vers la page de login
 */

/**
 *  On démarre la session
 */
session_start();

// Réinitialisation du tableau de session
// On le vide intégralement
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destruction de la session
session_destroy();

// Destruction du tableau de session
unset($_SESSION);

/**
 *  On redirige vers le fichier login.php
 */
header('Location: login.php');
exit();
