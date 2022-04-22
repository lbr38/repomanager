<!DOCTYPE html>
<html>
<?php
if (!defined('ROOT')) {
    define('ROOT', dirname(__FILE__, 2));
}
require_once(ROOT . '/controllers/Autoloader.php');
\Controllers\Autoloader::loadFromLogin();
include_once(ROOT . '/includes/head.inc.php');

$loginErrors = array();
$error = 0;

/**
 *  Tentative de connexion
 *  Vérification de username et du mot de passe
 */
if (!empty($_POST['username']) and !empty($_POST['password']) and !empty($_POST['authType'])) {
    /**
     *  Vérification du type de connexion sélectionné
     */
    if ($_POST['authType'] != 'local' and $_POST['authType'] != 'ldap') {
        $error++;
        $loginErrors[] = 'Le type de connexion sélectionné est invalide';
    }

    /**
     *  On continue si il n'y a pas eu d'erreur
     */
    if ($error == 0) {
        $username = \Models\Common::validateData($_POST['username']);

        $mylogin = new \Models\Login();

        /**
         *  Cas où la connexion est avec un compte LDAP
         */
        if ($_POST['authType'] == 'ldap') {
            // Commenté car pas encore au point :
            // if ($mylogin->connLdap($username, $_POST['password']) === true) {
            //     /**
            //      *  On récupère les informations concernant l'utilisateur en base de données
            //      */
            //     $mylogin->getAll($username);

            //     /**
            //      *  On ouvre la session
            //      */
            //     session_start();

            //     /**
            //      *  On enregistre les informations concernant l'utilisateur dans les variables de session
            //      */
            //     $_SESSION['username']   = $username;
            //     $_SESSION['role']       = $mylogin->getRole();
            //     $_SESSION['first_name'] = $mylogin->getName();
            //     $_SESSION['type']       = 'ldap';

            //     /**
            //      *  On redirige vers index.php
            //      */
            //     header('Location: index.php');
            //     exit();
            // }

            $loginErrors[] = 'Login et/ou mot de passe incorrect(s)';
        }


        /**
         *  Cas où la connexion est avec un compte local
         */
        if ($_POST['authType'] == 'local') {
            /**
             *  On vérifie en base de données que le couple username/passwd est valide
             */
            if ($mylogin->checkUsernamePwd($username, $_POST['password']) === true) {
                /**
                 *  On récupère les informations concernant l'utilisateur en base de données
                 */
                $mylogin->getAll($username);

                /**
                 *  On ouvre la session
                 */
                session_start();

                /**
                 *  On enregistre les informations concernant l'utilisateur dans les variables de session
                 */
                $_SESSION['username']   = $username;
                $_SESSION['role']       = $mylogin->getRole();
                $_SESSION['first_name'] = $mylogin->getFirstName();
                $_SESSION['last_name']  = $mylogin->getLastName();
                $_SESSION['email']      = $mylogin->getEmail();
                $_SESSION['type']       = 'local';

                /**
                 *  Si un cookie 'origin' existe alors celui-ci contient une URI vers laquelle on redirige l'utilisateur
                 */
                if (!empty($_COOKIE['origin'])) {
                    header('Location: ' . $_COOKIE['origin']);
                    exit();
                }

                /**
                 *  Sinon on redirige vers index.php
                 */
                header('Location: index.php');
                exit();
            }

            $loginErrors[] = 'Login et/ou mot de passe incorrect(s)';
        }
    }
} ?>
<head>
    <meta charset="utf-8">
    <!-- CSS -->
    <link rel="stylesheet" type="text/css" href="ressources/styles/main.css">

    <!-- Favicon -->
    <link rel="icon" href="favicon.ico" />
    <title>Login</title>
</head>

<body>
    <div id="loginDiv-container">
        <div id="loginDiv">
            <h3>CONNEXION</h3>
            <br>
            <form action="login.php" method="post" autocomplete="off">
                <input type="hidden" name="authType" value="local" />
                <!-- <div class="switch-field">
                    <input type="radio" id="authType_local" name="authType" value="local" checked />
                    <label for="authType_local">Local</label>
                    <input type="radio" id="authType_ldap" name="authType" value="ldap" />
                    <label for="authType_ldap">LDAP</label>
                </div>   
                <br> -->
                <input class="input-large" type="text" name="username" placeholder="Nom d'utilisateur" required />
                <br>
                <input class="input-large" type="password" name="password" placeholder="Mot de passe" required />
                <br>
                <button class="btn-large-blue" type="submit">Se connecter</button>
            </form>

            <?php
            if (!empty($loginErrors)) {
                foreach ($loginErrors as $loginError) {
                    echo '<p>' . $loginError . '</p>';
                }
            }
            ?>
        </div>
    </div>
</body>
</html>