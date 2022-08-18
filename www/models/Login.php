<?php

namespace Models;

use Exception;

class Login extends Model
{
    public $db;
    protected $username;
    protected $password;
    protected $first_name;
    protected $last_name;
    protected $email;
    protected $role;

    public function __construct()
    {
        /**
         *  Ouverture de la base de données
         */
        $this->getConnection('main');
    }

    private function setUsername(string $username)
    {
        $this->username = \Controllers\Common::validateData($username);
    }

    private function setPassword(string $password)
    {
        $this->password = \Controllers\Common::validateData($password);
    }

    private function setFirstName(string $first_name = null)
    {
        $this->first_name = \Controllers\Common::validateData($first_name);
    }

    private function setLastName(string $last_name = null)
    {
        $this->last_name = \Controllers\Common::validateData($last_name);
    }

    private function setEmail(string $email = null)
    {
        $this->email = \Controllers\Common::validateData($email);
    }

    private function setRole(string $role)
    {
        $this->role = \Controllers\Common::validateData($role);
    }

    public function getFirstName()
    {
        return $this->first_name;
    }

    public function getLastName()
    {
        return $this->last_name;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getRole()
    {
        return $this->role;
    }

    private function getHashedPasswordFromDb(string $username)
    {
        try {
            $stmt = $this->db->prepare("SELECT Password FROM users WHERE username = :username and State = 'active'");
            $stmt->bindValue(':username', $username);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $password = $row['Password'];
        }

        return $password;
    }

    private function generateRandomPassword()
    {
        $combinaison = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@$+-=%|{}[]&";
        $shuffle = str_shuffle($combinaison);

        return substr($shuffle, 0, 16);
    }

    /**
     *  Récupère toutes les informations d'un utilisateur en base de données
     */
    public function getAll(string $username)
    {
        try {
            $stmt = $this->db->prepare("SELECT users.Username, users.First_name, users.Last_name, users.Email, user_role.Name as Role_name FROM users JOIN user_role ON users.Role = user_role.Id WHERE Username = :username and State = 'active'");
            $stmt->bindValue(':username', \Controllers\Common::validateData($username));
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $this->setFirstName($row['First_name']);
            $this->setLastName($row['Last_name']);
            $this->setRole($row['Role_name']);
            $this->setEmail($row['Email']);
        }

        return true;
    }

    /**
     *  Renvoie la liste des utilisateurs en base de données
     */
    public function getUsers()
    {
        try {
            $result = $this->db->query("SELECT users.Id, users.Username, users.First_name, users.Last_name, users.Email, users.Type, user_role.Name as Role_name FROM users JOIN user_role ON users.Role = user_role.Id WHERE State = 'active' ORDER BY Username ASC");
        } catch (\Exception $e) {
            \Controllers\Common::printAlert('An error occured while executing request in database', 'error');
            return;
        }

        $datas = array();
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $datas[] = $row;
        }

        return $datas;
    }

    /**
     *  Ajoute un nouvel utilisateur en base de données
     */
    public function addUser(string $username, string $role)
    {
        $username = \Controllers\Common::validateData($username);
        $role = \Controllers\Common::validateData($role);

        /**
         *  On vérifie que le nom d'utilisateur ne contient pas de caractères spéciaux
         */
        if (\Controllers\Common::isAlphanumDash($username) === false) {
            \Controllers\Common::printAlert('Username cannot contain special characters except hyphen and underscore', 'error');
            return false;
        }

        /**
         *  On vérifie que le role est valide
         */
        if ($role != "usage" and $role != "administrator") {
            \Controllers\Common::printAlert('Selected role is invalid', 'error');
            return false;
        }

        /**
         *  On vérifie que le nom d'utilisateur n'est pas déjà utilisé
         */
        try {
            $stmt = $this->db->prepare("SELECT Id FROM users WHERE Username = :username and State = 'active'");
            $stmt->bindValue(':username', $username);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        if ($this->db->isempty($result) === false) {
            \Controllers\Common::printAlert("Username <b>$username</b> is already used", 'error');
            return false;
        }

        /**
         *  Génération d'un nouveau mot de passe aléatoire
         */
        $password = $this->generateRandomPassword();

        /**
         *  Hashage du mot de passe avec un salt généré automatiquement
         */
        $password_hashed = password_hash($password, PASSWORD_BCRYPT);
        if ($password_hashed === false) {
            \Controllers\Common::printAlert("Error while creating user", 'error');
            return false;
        }

        /**
         *  Conversion du role
         */
        if ($role == "administrator") {
            $role = 2;
        }
        if ($role == "usage") {
            $role = 3;
        }

        /**
         *  Insertion de l'username, du mdp hashé et son salt en base de données
         */
        try {
            $stmt = $this->db->prepare("INSERT INTO users ('Username', 'Password', 'First_name', 'Role', 'State', 'Type') VALUES (:username, :password, :first_name, :role, 'active', 'local')");
            $stmt->bindValue(':username', $username);
            $stmt->bindValue(':password', $password_hashed);
            $stmt->bindValue(':first_name', $username);
            $stmt->bindValue(':role', $role);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        \Controllers\Common::printAlert("User <b>$username</b> has been created", 'success');

        History::set($_SESSION['username'], "Create user: <b>$username</b>", 'success');

        /**
         *  On retourne le mot de passe temporaire généré afin que l'utilisateur puisse le récupérer
         */
        return $password;
    }

    /**
     *  Vérification en base de données que le couple username / password renseigné est valide (existe en base de données)
     */
    public function checkUsernamePwd(string $username, string $password)
    {
        $username = \Controllers\Common::validateData($username);

        /**
         *  On récupère le username et le mot de passe haché en base de données correspondant à l'username fourni
         */
        try {
            $stmt = $this->db->prepare("SELECT Username, Password FROM users WHERE Username = :username and State = 'active' and Type = 'local'");
            $stmt->bindValue(':username', $username);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        /**
         *  Si le résultat est vide, cela signifie que l'username n'existe pas en BDD
         */
        if ($this->db->isempty($result)) {
            return false;
        }

        /**
         *  Si le résultat est non-vide alors on vérifie que le mot de passe fourni correspond au hash en base de données
         */
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $password_hashed = $row['Password'];
        }

        /**
         *  Si les mots de passe ne correspondent pas on retourne false
         */
        if (!password_verify($password, $password_hashed)) {
            History::set($username, 'Authentification', 'error');
            return false;
        }

        History::set($username, 'Authentification', 'success');

        return true;
    }

    /**
     *  Vérification auprès du serveur LDAP que le couple username / password renseigné est valide
     */
    public function connLdap(string $username, string $password)
    {
        /**
         *  Si aucun serveur ldap n'est configuré alors on quitte
         */
        if (!defined('LDAP_SERVER')) {
            return false;
        }

        // Eléments d'authentification LDAP
        $ldaprdn  = 'uname';     // DN ou RDN LDAP
        $ldappass = 'password';  // Mot de passe associé

        // Connexion au serveur LDAP
        $ldapconn = ldap_connect("ldap://ldap.example.com")
            or die("Cannot connect to LDAP server.");

        if ($ldapconn) {
            // Connexion au serveur LDAP
            $ldapbind = ldap_bind($ldapconn, $ldaprdn, $ldappass);

            // Vérification de l'authentification
            if ($ldapbind) {
                echo "Connexion LDAP réussie...";
            } else {
                echo "Connexion LDAP échouée...";
            }
        }

        return true;
    }

    /**
     *  Modification des informations personnelles d'un utilisateur
     */
    public function edit(string $username, string $first_name = null, string $last_name = null, string $email = null)
    {
        /**
         *  Vérification des données renseignées
         */
        $username = \Controllers\Common::validateData($username);
        if (!empty($first_name)) {
            $first_name = \Controllers\Common::validateData($first_name);
        }
        if (!empty($last_name)) {
            $last_name = \Controllers\Common::validateData($last_name);
        }
        if (!empty($email)) {
            if (\Controllers\Common::validateMail($email) === false) {
                \Controllers\Common::printAlert("Email address is invalid", 'error');
                return;
            }
        }

        /**
         *  Mise à jour en base de données
         */
        try {
            $stmt = $this->db->prepare("UPDATE users SET First_name = :first_name, Last_name = :last_name, Email = :email WHERE Username = :username and State = 'active'");
            $stmt->bindValue(':username', $username);
            $stmt->bindValue(':first_name', $first_name);
            $stmt->bindValue(':last_name', $last_name);
            $stmt->bindValue(':email', $email);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        /**
         *  On modifie les valeurs en session par les valeurs qui ont été renseignées
         */
        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name']  = $last_name;
        $_SESSION['email']      = $email;

        History::set($_SESSION['username'], "Personal informations modification", 'success');

        \Controllers\Common::printAlert('Changes have been taken into account', 'success');
    }

    /**
     *  Modification du mot de passe d'un utilisateur
     */
    public function changePassword(string $username, string $actual_password, string $new_password, string $new_password2)
    {
        $username        = \Controllers\Common::validateData($username);
        $actual_password = \Controllers\Common::validateData($actual_password);
        $new_password    = $new_password;
        $new_password2   = $new_password2;

        /**
         *  On vérifie que le mot de passe actuel saisi correspond au mot de passe actuel en base de données
         */
        $actual_password_hashed = $this->getHashedPasswordFromDb($username);

        /**
         *  Si le hash récupéré est vide alors il y a une erreur, on quitte
         */
        if (empty($actual_password_hashed)) {
            return;
        }

        /**
         *  On vérifie que le nouveau mot de passe renseigné et sa re-saisie sont les mêmes
         */
        if ($new_password !== $new_password2) {
            \Controllers\Common::printAlert('New password and password re-type are different', 'error');
            return;
        }

        /**
         *  On vérifie que le nouveau mot de passe renseigné et l'ancien (hashé en bdd) sont différents
         */
        if (password_verify($new_password, $actual_password_hashed)) {
            \Controllers\Common::printAlert('New password is the same as the old password', 'error');
            return;
        }

        /**
         *  On hash le nouveau mot de passe renseigné
         */
        $new_password_hashed = password_hash($new_password, PASSWORD_BCRYPT);

        /**
         *  On modifie le mot de passe en base de données
         */
        try {
            $stmt = $this->db->prepare("UPDATE users SET Password = :new_password WHERE username = :username and State = 'active' and Type = 'local'");
            $stmt->bindValue(':new_password', $new_password_hashed);
            $stmt->bindValue(':username', $username);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        History::set($_SESSION['username'], "Password modification", 'success');

        \Controllers\Common::printAlert('Password has been changed', 'success');
    }

    /**
     *  Réinitialisation du mot de passe d'un utilisateur
     */
    public function resetPassword(string $username)
    {
        $username = \Controllers\Common::validateData($username);

        /**
         *  Vérification de l'existance de l'utilisateur en base de données
         */
        try {
            $stmt = $this->db->prepare("SELECT Id FROM users WHERE Username = :username and State = 'active' and Type = 'local'");
            $stmt->bindValue(':username', $username);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        if ($this->db->isempty($result) === true) {
            \Controllers\Common::printAlert('User <b>$username</b> does not exist', 'error');
            return false;
        }

        /**
         *  Génération d'un nouveau mot de passe
         */
        $password = $this->generateRandomPassword();

        /**
         *  Hashage du mot de passe avec un salt généré automatiquement
         */
        $password_hashed = password_hash($password, PASSWORD_BCRYPT);
        if ($password_hashed === false) {
            \Controllers\Common::printAlert("Error while creating the user <b>$username</b>", 'error');
            return false;
        }

        /**
         *  Ajout du nouveau mot de passe hashé en base de données
         */
        try {
            $stmt = $this->db->prepare("UPDATE users SET Password = :password WHERE Username = :username and State = 'active' and Type = 'local'");
            $stmt->bindValue(':username', $username);
            $stmt->bindValue(':password', $password_hashed);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        History::set($_SESSION['username'], "Reset password of user <b>$username</b>", 'success');

        \Controllers\Common::printAlert('Password has been regenerated', 'success');

        return $password;
    }

    /**
     *  Suppression d'un utilisateur
     */
    public function deleteUser(string $username)
    {
        $username = \Controllers\Common::validateData($username);

        /**
         *  On vérifie que l'utilisateur mentionné existe en base de données
         */
        try {
            $stmt = $this->db->prepare("SELECT Id FROM users WHERE Username = :username and State = 'active' and Type = 'local'");
            $stmt->bindValue(':username', $username);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        if ($this->db->isempty($result) === true) {
            \Controllers\Common::printAlert('User <b>$username</b> does not exist', 'error');
            return;
        }

        /**
         *  Suppression de l'utilisateur en base de données
         *  On conserve l'utilisateur pour des raisons d'historique mais on passe son status en 'deleted' et devient alors inutilisable
         *  On vide le Password de l'utilisateur pour ne plus le stocker en base
         */
        try {
            $stmt = $this->db->prepare("UPDATE users SET State = 'deleted', Password = null WHERE Username = :username and Type = 'local'");
            $stmt->bindValue(':username', $username);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        History::set($_SESSION['username'], "Delete user <b>$username</b>", 'success');

        \Controllers\Common::printAlert("User <b>$username</b> has been deleted", 'success');
    }
}
