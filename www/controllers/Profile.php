<?php

namespace Controllers;

use Exception;
use Datetime;

class Profile
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\Profile();
    }

    /**
     *  Retourne la liste des paquets dans la table profile_package
     */
    public function getPackages()
    {
        return $this->model->getPackages();
    }

    /**
     *  Retourne la liste des services dans la table profile_service
     */
    public function getServices()
    {
        return $this->model->getServices();
    }

    /**
     *  Retourne un array contenant la configuration générale d'un profil (paquets à exclure...)
     */
    public function getProfileConfiguration(string $profile)
    {
        /**
         *  D'abord on vérifie que le profil spécifié existe en base de données
         */
        if ($this->model->exists($profile) === false) {
            throw new Exception("Profile <b>$profile</b> does not exist");
        }

        /**
         *  On récupère l'id du profil spécifié en base de données
         */
        $profileId = $this->model->getIdByName($profile);

        /**
         *  Récupération de la configuration générale (exclusions de paquets...)
         */
        return $this->model->getProfileConfiguration($profileId);
    }

    /**
     *  Retourne un array contenant la liste des repos membres d'un profil
     */
    public function getReposMembersList(string $profile)
    {
        /**
         *  D'abord on vérifie que le profil spécifié existe en base de données
         */
        if ($this->model->exists($profile) === false) {
            throw new Exception("Profile <b>$profile</b> does not exist");
        }

        /**
         *  On récupère l'id du profil spécifié en base de données
         */
        $profileId = $this->model->getIdByName($profile);

        /**
         *  Récupération de la configuration des repos
         */
        $repos = $this->model->reposMembersList($profileId);

        /**
         *  Formattage des fichiers de configuration .repo ou .list avant de envoyer au format JSON
         */
        $globalArray = array();

        foreach ($repos as $repo) {
            $name = $repo['Name'];
            $dist = $repo['Dist'];
            $section = $repo['Section'];
            $packageType = $repo['Package_type'];

            if ($packageType == 'rpm') {
                $repoArray =
                array(
                    'filename' => REPO_CONF_FILES_PREFIX . $name . '.repo',
                    'description' => 'Repo ' . $name . ' sur ' . __SERVER_URL__,
                    'content' => '[' . REPO_CONF_FILES_PREFIX . $name . '___ENV__]' . PHP_EOL . 'name=Repo ' . $name . ' sur ' . WWW_HOSTNAME . PHP_EOL . 'comment=Repo ' . $name . ' sur ' . WWW_HOSTNAME . PHP_EOL . 'baseurl=' . __SERVER_URL__ . '/repo/' . $name . '___ENV__' . PHP_EOL . 'enabled=1' . PHP_EOL . 'gpgkey=' . __SERVER_URL__ . '/repo/gpgkeys/' . WWW_HOSTNAME . '.pub' . PHP_EOL . 'gpgcheck=1'
                );
            }

            if ($packageType == 'deb') {
                $repoArray =
                array(
                    'filename' => REPO_CONF_FILES_PREFIX . $name . '.list',
                    'description' => 'Repo ' . $name . ' sur ' . __SERVER_URL__,
                    'content' => 'deb ' . __SERVER_URL__ . '/repo/' . $name . '/' . $dist . '/' . $section . '___ENV__ ' . $dist . ' ' . $section
                );
            }

            $globalArray[] = $repoArray;
        }

        return $globalArray;
    }

    /**
     *  Retourne la configuration générale du serveur pour la gestion des profils
     */
    public function getServerConfiguration()
    {
        return $this->model->getServerConfiguration();
    }

    /**
     *  Modifie la configuration générale du serveur pour la gestion des profils
     */
    public function setServerConfiguration(string $serverPackageType, string $serverManageClientConf, string $serverManageClientRepos)
    {
        $serverPackageType = \Controllers\Common::validateData($serverPackageType);

        if ($serverManageClientConf != 'yes' && $serverManageClientConf != 'no') {
            throw new Exception("Parameter 'Manage profiles packages configuration");
        }
        if ($serverManageClientRepos != 'yes' && $serverManageClientRepos != 'no') {
            throw new Exception("Parameter 'Manage profiles repos configuration' is invalid");
        }

        $this->model->setServerConfiguration($serverPackageType, $serverManageClientConf, $serverManageClientRepos);
    }

    /**
     *  Retourne true ou false suivant si le profil existe en base de données
     */
    public function exists(string $name)
    {
        if ($this->model->exists($name) === false) {
            return false;
        }

        return true;
    }

    /**
     *  Retourne la liste des profils en base de données
     */
    public function list()
    {
        return $this->model->list();
    }

    /**
     *  Retourne le nombre d'hôtes utilisant le profil spécifié
     */
    public function countHosts(string $profile)
    {
        return $this->model->countHosts($profile);
    }

    /**
     *  Création d'un nouveau profil
     */
    public function new(string $name)
    {
        $name = \Controllers\Common::validateData($name);

        /**
         *  1. On vérifie que le nom du profil ne contient pas des caractères interdits
         */
        if (\Controllers\Common::isAlphanumDash($name) === false) {
            throw new Exception("Profile <b>$name</b> contains invalid characters");
        }

        /**
         *  2. On vérifie qu'un profil du même nom n'existe pas déjà
         */
        if ($this->model->exists($name) === true) {
            throw new Exception("Profile <b>$name</b> already exist");
        }

        /**
         *  3. Si pas d'erreur alors on peut créer le répertoire en base de données
         */
        $this->model->add($name);

        \Models\History::set($_SESSION['username'], "Create a new profile: $name", 'success');
    }

    /**
     *  Renommage d'un profil
     */
    public function rename(string $name, string $newName)
    {
        $name = \Controllers\Common::validateData($name);
        $newName = \Controllers\Common::validateData($newName);

        /**
         *  1. On vérifie que le nom du profil ne contient pas des caractères interdits
         */
        if (\Controllers\Common::isAlphanumDash($name) === false) {
            throw new Exception("Profile name <b>$name</b> contains invalid characters");
        }

        if (\Controllers\Common::isAlphanumDash($newName) === false) {
            throw new Exception("Profile name <b>$newName</b> contains invalid characters");
        }

        /**
         *  2. On vérifie qu'un profil du même nom n'existe pas déjà. Si c'est le cas on affiche un message d'erreur
         */
        if ($this->model->exists($newName) === true) {
            throw new Exception("Profile <b>$newName</b> already exist");
        }

        /**
         *  3. Si pas d'erreur alors on peut renommer le profil en base de données
         */
        $this->model->rename($name, $newName);

        \Models\History::set($_SESSION['username'], "Profile <b>$name</b> renamed to <b>$newName</b>", 'success');
    }

    /**
     *  Dupliquer un profil et sa configuration
     */
    public function duplicate(string $name)
    {
        $name = \Controllers\Common::validateData($name);

        /**
         *  Récupéraiton de l'Id du profil source
         */
        $profileId = $this->model->getIdByName($name);

        /**
         *  On génère un nouveau nom de profil basé sur le nom du profil dupliqué + suivi d'un nombre aléatoire
         */
        $newName = $name . '-' . mt_rand(100000, 200000);

        /**
         *  On vérifie que le nouveau nom n'existe pas déjà
         */
        while ($this->model->exists($newName) === true) {
            // Re-génération d'un nom si celui-ci est déjà prit
            $newName = $name . '-' . mt_rand(100000, 200000);
        }

        /**
         *  Création du du nouveau profil en base de données
         */
        $this->model->add($newName);

        /**
         *  Récupération de l'Id du profil créé
         */
        $newProfileId = $this->model->getLastInsertRowID();

        /**
         *  Récupération de la configuration générale actuelle du profil source
         */
        $profileConf = $this->model->getProfileConfiguration($profileId);

        /**
         *  Copie de la configuration du profil dupliqué vers le nouveau profil
         */
        $this->model->configure($newProfileId, $profileConf['Package_exclude'], $profileConf['Package_exclude_major'], $profileConf['Service_restart'], $profileConf['Allow_overwrite'], $profileConf['Allow_repos_overwrite']);

        /**
         *  Récupération des repos membres du profil source
         */
        $reposMembersId = $this->model->reposMembersIdList($profileId);

        /**
         *  On ajoute chaque repos au nouveau profil
         */
        foreach ($reposMembersId as $repoMemberId) {
            $this->model->addRepoToProfile($newProfileId, $repoMemberId);
        }

        \Models\History::set($_SESSION['username'], "Duplicate profile <b>$name</b> to <b>$newName</b>", 'success');
    }

    /**
     *  Supprimer un profil
     */
    public function delete(string $name)
    {
        $name = \Controllers\Common::validateData($name);

        /**
         *  1. On vérifie que le nom du profil ne contient pas des caractères interdits
         */
        if (\Controllers\Common::isAlphanumDash($name) === false) {
            throw new Exception("Profile name <b>$name</b> contains invalid characters");
        }

        /**
         *  2. Suppression du profil en base de données
         */
        $this->model->delete($name);

        \Models\History::set($_SESSION['username'], "Delete profile <b>$name</b>", 'success');
    }

    /**
     *  Configuration d'un profil
     *  Gestion des repos du profil
     *  Gestion des paquets à exclure
     */
    public function configure(string $name, array $reposIds = null, array $packagesExcluded = null, array $packagesMajorExcluded = null, array $serviceNeedRestart = null, string $allowOverwrite, string $allowReposOverwrite, string $notes)
    {
        $name = \Controllers\Common::validateData($name);

        $error = 0;

        /**
         *  1. On vérifie que le nom du profil ne contient pas des caractères interdits
         */
        if (\Controllers\Common::isAlphanumDash($name) === false) {
            throw new Exception("Profile name <b>$name</b> contains invalid characters");
        }

        /**
         *  2. On vérifie que le profil existe en base de données
         */
        if ($this->model->exists($name) === false) {
            throw new Exception("Profile <b>$name</b> does not exist");
        }

        /**
         *  3. Récupération de l'Id du profil en base de données
         */
        $profileId = $this->model->getIdByName($name);

        /**
         *  4. D'abord on vide tous les repos membres du profil avant d'ajouter ensuite uniquement ceux qui ont été spécifiés
         */
        $this->model->cleanProfileRepoMembers($profileId);

        /**
         *  5. Si l'array $reposIds est vide alors on s'arrête là, le profil restera sans repo configuré. Sinon on continue.
         *  Ce n'est pas une erreur alors on retourne true
         */
        if (!empty($reposIds)) {

            /**
             *  On ajoute chaque Id de repo dans la table profile_repo_members
             */
            foreach ($reposIds as $repoId) {
                $this->model->addRepoToProfile($profileId, $repoId);
            }
        }

        /**
         *  6. Gestion des exclusions et autres paramètres...
         *
         *  Pour chaque paramètre ci-dessous,
         *  Si non-vide alors on implode l'array en string en séparant chaque valeurs par une virgule car c'est comme ça qu'elles seront renseignées en base de données
         *  Si vide, alors on set une valeur vide
         */

        /**
         *  Vérification des paquets à exclure (toute version)
         */
        if (!empty($packagesMajorExcluded)) {
            foreach ($packagesMajorExcluded as $packageName) {
                $packageName = \Controllers\Common::validateData($packageName);

                /**
                 *  Pour chaque paquet, on vérifie sa syntaxe puis on l'ajoute en base de données si il n'existe pas
                 *
                 *  Si le package possède un wildcard .* alors on retire d'abord ce wildcard avant d'effectuer le test
                 */
                if (substr($packageName, -2) == ".*") {
                    $packageNameFormatted = rtrim($packageName, ".*");
                } else {
                    $packageNameFormatted = $packageName;
                }

                /**
                 *  On vérifie que le nom du paquet ne contient pas de caractères interdits
                 */
                \Controllers\Common::isAlphanumDash($packageNameFormatted);

                /**
                 *  Ajout du paquet dans la table profile_package si il n'existe pas déjà.
                 */
                $this->model->addPackage($packageNameFormatted);
            }
        }

        /**
         *  Vérification des paquets à exclure
         */
        if (!empty($packagesExcluded)) {
            foreach ($packagesExcluded as $packageName) {
                $packageName = \Controllers\Common::validateData($packageName);

                /**
                 *  Pour chaque paquet, on vérifie sa syntaxe puis on l'ajoute en base de données si il n'existe pas
                 *
                 *  Si le package possède un wildcard .* alors on retire d'abord ce wildcard avant d'effectuer le test
                 */
                if (substr($packageName, -2) == ".*") {
                    $packageNameFormatted = rtrim($packageName, ".*");
                } else {
                    $packageNameFormatted = $packageName;
                }

                /**
                 *  On vérifie que le nom du paquet ne contient pas de caractères interdits
                 */
                \Controllers\Common::isAlphanumDash($packageNameFormatted);

                /**
                 *  Ajout du paquet dans la table profile_package si il n'existe pas déjà.
                 */
                $this->model->addPackage($packageNameFormatted);
            }
        }

        /**
         *  Vérification des services à redémarrer
         */
        if (!empty($serviceNeedRestart)) {
            foreach ($serviceNeedRestart as $serviceName) {
                $serviceName = \Controllers\Common::validateData($serviceName);

                /**
                 *  On vérifie que le nom du service ne contient pas de caractères interdits
                 */
                \Controllers\Common::isAlphanumDash($serviceName);

                /**
                 *  Ajout du paquet dans la table profile_package si il n'existe pas déjà.
                 */
                $this->model->addService($serviceName);
            }
        }

        /**
         *  Si on tente de passer une autre valeur de paramètre differente de 'yes' ou 'no' alors on ne fait rien
         */
        if ($allowOverwrite != 'yes' and $allowOverwrite != 'no') {
            return;
        }
        if ($allowReposOverwrite != 'yes' and $allowReposOverwrite != 'no') {
            return;
        }

        /**
         *  Si toutes les vérifications sont passées alors on peut insérer les données en base
         *  On implode tous les arrays transmis en string avec les valeurs séparées par une virgule
         */
        $packagesExcludedExploded = implode(',', $packagesExcluded);
        $packagesMajorExcludedExploded = implode(',', $packagesMajorExcluded);
        $serviceNeedRestartExploded = implode(',', $serviceNeedRestart);

        /**
         *  Vérification des notes
         */
        if (!empty($notes)) {
            $notes = \Controllers\Common::validateData($notes);
        }

        /**
         *  Insertion de la nouvelle configuration en base de données
         */
        $this->model->configure($profileId, $packagesExcludedExploded, $packagesMajorExcludedExploded, $serviceNeedRestartExploded, $allowOverwrite, $allowReposOverwrite, $notes);

        \Models\History::set($_SESSION['username'], "Modification of <b>$name</b> profile configuration", 'success');
    }

    /**
     *  Retourne un array contenant les Id de repos membres d'un profil
     */
    public function reposMembersIdList(string $profileId)
    {
        return $this->model->reposMembersIdList($profileId);
    }
}
