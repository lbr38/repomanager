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
     *  Retourne la configuration générale du serveur pour la gestion des profils
     */
    public function getServerConfiguration()
    {
        return $this->model->getServerConfiguration();
    }

    /**
     *  Get profile full configuration from database
     */
    public function getProfileFullConfiguration(string $profile)
    {
        /**
         *  Check that profile exists
         */
        if ($this->model->exists($profile) === false) {
            throw new Exception("$profile profile does not exist");
        }

        /**
         *  Get profile Id from database
         */
        $profileId = $this->model->getIdByName($profile);

        /**
         *  Get profile full configuration
         */
        return $this->model->getProfileFullConfiguration($profileId);
    }

    /**
     *  Get profile configuration
     */
    public function getProfileConfiguration(string $profile)
    {
        $configuration = array();

        /**
         *  Get profile full configuration
         */
        $fullConfiguration = $this->getProfileFullConfiguration($profile);

        /**
         *  Return only main configuration
         */
        $configuration['Linupdate_get_pkg_conf'] = $fullConfiguration['Linupdate_get_pkg_conf'];
        $configuration['Linupdate_get_repos_conf'] = $fullConfiguration['Linupdate_get_repos_conf'];

        return $configuration;
    }

    /**
     *  Get profile packages excludes configuration
     */
    public function getProfilePackagesConfiguration(string $profile)
    {
        $configuration = array();

        /**
         *  Get profile full configuration
         */
        $fullConfiguration = $this->getProfileFullConfiguration($profile);

        /**
         *  Return only packages configuration
         */
        $configuration['Package_exclude'] = $fullConfiguration['Package_exclude'];
        $configuration['Package_exclude_major'] = $fullConfiguration['Package_exclude_major'];
        $configuration['Service_restart'] = $fullConfiguration['Service_restart'];

        return $configuration;
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
            throw new Exception("$profile profile does not exist");
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
                    'description' => $name . ' repo on ' . __SERVER_URL__,
                    'content' => '[' . REPO_CONF_FILES_PREFIX . $name . '___ENV__]' . PHP_EOL . 'name=' . $name . ' repo on ' . WWW_HOSTNAME . PHP_EOL . 'comment=' . $name . ' repo on ' . WWW_HOSTNAME . PHP_EOL . 'baseurl=' . __SERVER_URL__ . '/repo/' . $name . '___ENV__' . PHP_EOL . 'enabled=1' . PHP_EOL . 'gpgkey=' . __SERVER_URL__ . '/repo/gpgkeys/' . WWW_HOSTNAME . '.pub' . PHP_EOL . 'gpgcheck=1'
                );
            }

            if ($packageType == 'deb') {
                $repoArray =
                array(
                    'filename' => REPO_CONF_FILES_PREFIX . $name . '.list',
                    'description' => $name . ' repo on ' . __SERVER_URL__,
                    'content' => 'deb ' . __SERVER_URL__ . '/repo/' . $name . '/' . $dist . '/' . $section . '___ENV__ ' . $dist . ' ' . $section
                );
            }

            $globalArray[] = $repoArray;
        }

        return $globalArray;
    }

    /**
     *  Modifie la configuration générale du serveur pour la gestion des profils
     */
    public function setServerConfiguration(string $serverManageClientConf, string $serverManageClientRepos)
    {
        if (DEB_REPO == 'true' && RPM_REPO == 'true') {
            $serverPackageType = 'deb,rpm';
        } elseif (DEB_REPO == 'true') {
            $serverPackageType = 'deb';
        } elseif (RPM_REPO == 'true') {
            $serverPackageType = 'rpm';
        }

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
     *  Return a list of all profiles names
     */
    public function listName()
    {
        return $this->model->listName();
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
        $name = Common::validateData($name);

        /**
         *  1. On vérifie que le nom du profil ne contient pas des caractères interdits
         */
        if (Common::isAlphanumDash($name) === false) {
            throw new Exception("<b>$name</b> profile contains invalid characters");
        }

        /**
         *  2. On vérifie qu'un profil du même nom n'existe pas déjà
         */
        if ($this->model->exists($name) === true) {
            throw new Exception("<b>$name</b> profile already exists");
        }

        /**
         *  3. Si pas d'erreur alors on peut créer le répertoire en base de données
         */
        $this->model->add($name);

        $myhistory = new \Controllers\History();
        $myhistory->set($_SESSION['username'], "Create a new profile: $name", 'success');
    }

    /**
     *  Renommage d'un profil
     */
    public function rename(string $name, string $newName)
    {
        $name = Common::validateData($name);
        $newName = Common::validateData($newName);

        /**
         *  1. On vérifie que le nom du profil ne contient pas des caractères interdits
         */
        if (Common::isAlphanumDash($name) === false) {
            throw new Exception("<b>$name</b> profile name contains invalid characters");
        }

        if (Common::isAlphanumDash($newName) === false) {
            throw new Exception("<b>$newName</b> profile name contains invalid characters");
        }

        /**
         *  2. On vérifie qu'un profil du même nom n'existe pas déjà. Si c'est le cas on affiche un message d'erreur
         */
        if ($this->model->exists($newName) === true) {
            throw new Exception("<b>$newName</b> profile already exists");
        }

        /**
         *  3. Si pas d'erreur alors on peut renommer le profil en base de données
         */
        $this->model->rename($name, $newName);

        $myhistory = new \Controllers\History();
        $myhistory->set($_SESSION['username'], "<b>$name</b> profile renamed to <b>$newName</b>", 'success');
    }

    /**
     *  Dupliquer un profil et sa configuration
     */
    public function duplicate(string $name)
    {
        $name = Common::validateData($name);

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
            /**
             *  Re-génération d'un nom si celui-ci est déjà prit
             */
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
        $profileConf = $this->model->getProfileFullConfiguration($profileId);

        /**
         *  Copie de la configuration du profil dupliqué vers le nouveau profil
         */
        $this->model->configure($newProfileId, $profileConf['Package_exclude'], $profileConf['Package_exclude_major'], $profileConf['Service_restart'], $profileConf['Linupdate_get_pkg_conf'], $profileConf['Linupdate_get_repos_conf'], $profileConf['Notes']);

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

        $myhistory = new \Controllers\History();
        $myhistory->set($_SESSION['username'], "Duplicate profile <b>$name</b> to <b>$newName</b>", 'success');
    }

    /**
     *  Supprimer un profil
     */
    public function delete(string $name)
    {
        $name = Common::validateData($name);

        /**
         *  1. On vérifie que le nom du profil ne contient pas des caractères interdits
         */
        if (Common::isAlphanumDash($name) === false) {
            throw new Exception("<b>$name</b> profile name contains invalid characters");
        }

        /**
         *  2. Suppression du profil en base de données
         */
        $this->model->delete($name);

        $myhistory = new \Controllers\History();
        $myhistory->set($_SESSION['username'], "Delete <b>$name</b> profile", 'success');
    }

    /**
     *  Configuration d'un profil
     *  Gestion des repos du profil
     *  Gestion des paquets à exclure
     */
    public function configure(string $name, array $reposIds = null, array $packagesExcluded = null, array $packagesMajorExcluded = null, array $serviceNeedRestart = null, string $linupdateGetPkgConf, string $linupdateGetReposConf, string $notes)
    {
        $name = Common::validateData($name);

        $error = 0;

        /**
         *  1. On vérifie que le nom du profil ne contient pas des caractères interdits
         */
        if (Common::isAlphanumDash($name) === false) {
            throw new Exception("<b>$name</b> profile name contains invalid characters");
        }

        /**
         *  2. On vérifie que le profil existe en base de données
         */
        if ($this->model->exists($name) === false) {
            throw new Exception("$name profile does not exist");
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
                $packageName = Common::validateData($packageName);

                if (!Common::isAlphanumDash($packageName, array('.*'))) {
                    throw new Exception('Package ' . $packageName . ' contains invalid characters');
                }

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
                $packageName = Common::validateData($packageName);

                if (!Common::isAlphanumDash($packageName, array('.*'))) {
                    throw new Exception('Package ' . $packageName . ' contains invalid characters');
                }

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
                $serviceName = Common::validateData($serviceName);

                /**
                 *  On vérifie que le nom du service ne contient pas de caractères interdits
                 */
                if (!Common::isAlphanumDash($serviceName, array('@'))) {
                    throw new Exception('Service ' . $serviceName . ' contains invalid characters');
                }

                /**
                 *  Ajout du paquet dans la table profile_package si il n'existe pas déjà.
                 */
                $this->model->addService($serviceName);
            }
        }

        /**
         *  Si on tente de passer une autre valeur de paramètre differente de 'yes' ou 'no' alors on ne fait rien
         */
        if ($linupdateGetPkgConf != 'true' and $linupdateGetPkgConf != 'false') {
            return;
        }
        if ($linupdateGetReposConf != 'true' and $linupdateGetReposConf != 'false') {
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
            $notes = Common::validateData($notes);
        }

        /**
         *  Insertion de la nouvelle configuration en base de données
         */
        $this->model->configure($profileId, $packagesExcludedExploded, $packagesMajorExcludedExploded, $serviceNeedRestartExploded, $linupdateGetPkgConf, $linupdateGetReposConf, $notes);

        $myhistory = new \Controllers\History();
        $myhistory->set($_SESSION['username'], "Modification of <b>$name</b> profile configuration", 'success');
    }

    /**
     *  Retourne un array contenant les Id de repos membres d'un profil
     */
    public function reposMembersIdList(string $profileId)
    {
        return $this->model->reposMembersIdList($profileId);
    }

    /**
     *  Remove unused repos from profiles (repos that have no active snapshot)
     */
    public function cleanProfiles()
    {
        /**
         *  Get unused repos Id (repos that have no active snapshot and so are not visible from web UI)
         */
        $myrepo = new \Controllers\Repo\Repo();
        $unusedRepos = $myrepo->getUnusedRepos();

        /**
         *  Remove those repos Id from profiles
         */
        if (!empty($unusedRepos)) {
            foreach ($unusedRepos as $unusedRepo) {
                $this->removeRepoMemberId($unusedRepo['Id']);
            }
        }

        unset($myrepo);
    }

    /**
     *  Remove repo Id from profile members
     */
    public function removeRepoMemberId(int $id)
    {
        $this->model->removeRepoMemberId($id);
    }
}
