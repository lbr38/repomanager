<?php

class Profile extends Model {
    private $id;
    private $name;
    private $newName;

    public function __construct() {
        /**
         *  Ouverture d'une connexion à la base de données
         */
        $this->getConnection('main');
    }

    /**
     * 	Création d'un nouveau profil
     */
    function new(string $name)
    {
        $name = Common::validateData($name);

        /**
         *  1. On vérifie que le nom du profil ne contient pas des caractères interdits
         */
        if (Common::is_alphanumdash($name) === false) {
            throw new Exception("Le profil <b>$name</b> contient des caractères invalides");
        }

        /**
         * 	2. On vérifie qu'un profil du même nom n'existe pas déjà
         */
        if (file_exists(PROFILES_MAIN_DIR."/${name}")) {
            throw new Exception("Le profil <b>$name</b> existe déjà");
        }

        /**
         * 	3. Si pas d'erreur alors on peut créer le répertoire de profil
         */
        if (!is_dir(PROFILES_MAIN_DIR."/${name}")) { 
            if (!mkdir(PROFILES_MAIN_DIR."/${name}", 0775, true)) {
                throw new Exception("Impossible de créer le répertoire du profil <b>$name</b>");
            }
        }

        /**
         * 	4. Créer le fichier de config
         */
        if (!file_exists(PROFILES_MAIN_DIR."/${name}/config")) {
            if (!touch(PROFILES_MAIN_DIR."/${name}/config")) {
                throw new Exception("Impossible d'initialiser la configuration du profil <b>$name</b>");
            }
        }

        /**
         * 	5. Créer le fichier de config du profil avec des valeurs vides ou par défaut
         */
        if (!file_put_contents(PROFILES_MAIN_DIR."/${name}/config", "EXCLUDE_MAJOR=\"\"\nEXCLUDE=\"\"\nNEED_RESTART=\"\"\nKEEP_CRON=\"no\"\nALLOW_OVERWRITE=\"yes\"\nALLOW_REPOSFILES_OVERWRITE=\"yes\"")) {
            throw new Exception("Impossible de configurer le profil <b>$name</b>");
        }

        History::set($_SESSION['username'], "Création d'un nouveau profil : $name", 'success');
    }

    /**
     * 	Renommage d'un profil
     */
    function rename(string $name, string $newName)
    {
        $name = Common::validateData($name);
        $newName = Common::validateData($newName);

        /**
         *  1. On vérifie que le nom du profil ne contient pas des caractères interdits
         */
        if (Common::is_alphanumdash($name) === false) {
            throw new Exception("Le nom de profil <b>$name</b> contient des caractères invalides");
        }

        if (Common::is_alphanumdash($newName) === false) {
            throw new Exception("Le nom de profil <b>$newName</b> contient des caractères invalides");
        }

        /**
         *  2. On vérifie qu'un profil du même nom n'existe pas déjà. Si c'est le cas on affiche un message d'erreur
         */
        if (is_dir(PROFILES_MAIN_DIR."/${newName}")) {
            throw new Exception("Un profil du même nom (<b>{$newName}</b>) existe déjà");
        }

        /**
         *  3. Si pas d'erreur alors on peut renommer le répertoire de profil
         */
        if (!rename(PROFILES_MAIN_DIR."/${name}", PROFILES_MAIN_DIR."/${newName}")) {
            throw new Exception("Impossible de finaliser le renommage du profil <b>$name</b>");
        }

        History::set($_SESSION['username'], "Renommage du profil $name en $newName", 'success');
    }

    /**
     *  Dupliquer un profil et sa configuration
     */
    public function duplicate(string $name)
    {
        $name = Common::validateData($name);

        /**
         *  1. On génère un nouveau nom de profil basé sur le nom du profil dupliqué + suivi d'un nombre aléatoire
         */
        $newProfileName = $name.'-'.mt_rand(100000,200000);
        
        /**
         *  2. On vérifie que le nouveau nom n'existe pas déjà sait-on jamais
         */
        if (file_exists(PROFILES_MAIN_DIR."/${newProfileName}")) {
            throw new Exception("Un profil du même nom (<b>$newProfileName</b>) existe déjà");
        }

        /**
         *  3. Création du répertoire du nouveau profil
         */
        if (!file_exists(PROFILES_MAIN_DIR."/${newProfileName}")) {
            mkdir(PROFILES_MAIN_DIR."/${newProfileName}", 0775, true);
        }

        /**
         *  4. Copie du contenu du répertoire du profil dupliqué afin de copier sa config et ses fichiers de repo
         */
        exec("cp -rP ".PROFILES_MAIN_DIR."/${name}/* ".PROFILES_MAIN_DIR."/${newProfileName}/", $output, $result);
        if ($result != 0) {
            throw new Exception("Erreur lors de la duplication de <b>$name</b>");
        }

        History::set($_SESSION['username'], "Duplication d'u profil $name en $newProfileName", 'success');
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
        if (Common::is_alphanumdash($name) === false) {
            throw new Exception("Le nom de profil <b>$name</b> contient des caractères invalides");
        }

        /**
         * 	2. Suppression du répertoire du profil
         */
        exec("rm -fr ".PROFILES_MAIN_DIR."/${name}/", $output, $return);
        if ($return != 0) {
            throw new Exception("Impossible de supprimer le profil <b>$name</b>");
        }

        History::set($_SESSION['username'], "Suppression du profil $name", 'success');
    }

    /**
     *  Configuration d'un profil
     *  Gestion des repos du profil
     *  Gestion des paquets à exclure
     */
    public function configure(string $name, $profileRepos = null, $packagesMajorExcluded = null, $packagesExcluded = null, $serviceNeedRestart = null, string $keepCron, string $allowOverwrite, string $allowReposFilesOverwrite)
    {
        $name = Common::validateData($name);

        $error = 0;

        /**
         *  1.2. On vérifie que le nom du profil ne contient pas des caractères interdits
         */
        if (Common::is_alphanumdash($name) === false) {
            throw new Exception("Le nom du profil <b>$name</b> contient des caractères invalides");
        }
        
        /**
         * 	1.3. D'abord on supprime tous les repos présents dans le répertoire du profil, avant de rajouter seulement ceux qui ont été sélectionnés dans la liste
         */
        if (is_dir(PROFILES_MAIN_DIR."/${name}/")) {
            if (OS_FAMILY == "Redhat") exec("rm ".PROFILES_MAIN_DIR."/${name}/*.repo -f");
            if (OS_FAMILY == "Debian") exec("rm ".PROFILES_MAIN_DIR."/${name}/*.list -f");
        }
    
        /**
         * 	1.4. Si l'array $profileRepos est vide alors on s'arrête là, le profil restera sans repo configuré. Sinon on continue.
         * 	Ce n'est pas une erreur alors on retourne true
         */
        if (!empty($profileRepos)) {
            /**
             * 	On traite chaque repo sélectionné
             */
            foreach ($profileRepos as $profileRepo) {
                $addProfileRepo = Common::validateData($profileRepo);
        
                if (OS_FAMILY == "Debian") {
                    $addProfileRepoExplode = explode('|', $addProfileRepo);
                    $addProfileRepo = $addProfileRepoExplode[0];
                    $addProfileRepoDist = $addProfileRepoExplode[1];
                    $addProfileRepoSection = $addProfileRepoExplode[2];
                }
        
                /**
                 *  On vérifie que le nom du repo ne contient pas des caractères interdits. Ici la fonction is_alphanumdash autorise de base les tirets et underscore ainsi que le point (qu'on a indiqué)
                 *  Pour Debian, on vérifie également que la distribution et la section ne contiennent pas de caractères interdits
                 */
                if (Common::is_alphanumdash($addProfileRepo, array('.')) === false) {
                    throw new Exception("Un ou plusieurs repo(s) sélectionné(s) contient des caractères invalides");
                }

                /**
                 *  Certains nom de distribution peuvent contenir des slashs, donc ici on autorise l'utilisation d'un slash
                 */
                if (OS_FAMILY == "Debian") {
                    if (Common::is_alphanumdash($addProfileRepoDist, array('/')) === false OR Common::is_alphanumdash($addProfileRepoSection) === false) {
                        throw new Exception("Une ou plusieurs distribution(s) de repo sélectionnée(s) contient des caractères invalides");
                    }
                }

                $myRepo = new Repo();

                if (OS_FAMILY == "Redhat") {
                    /**
                     *  On vérifie que le repo existe, sinon on passe au suivant
                     */
                    if ($myRepo->exists($addProfileRepo) === false) {
                        continue;
                    }
        
                    exec("cd ".PROFILES_MAIN_DIR."/${name}/ && ln -sfn ".REPOS_PROFILES_CONF_DIR."/".REPO_CONF_FILES_PREFIX."${addProfileRepo}.repo");
                }
        
                if (OS_FAMILY == "Debian" AND !empty($addProfileRepoDist) AND !empty($addProfileRepoSection)) {
                    /**
                     * 	On vérifie que la section repo existe, sinon on passe au suivant
                     */
                    if ($myRepo->section_exists($addProfileRepo, $addProfileRepoDist, $addProfileRepoSection) === false) {
                        continue;
                    }
        
                    /**
                     * 	Si le nom de la distribution contient un slash, c'est le cas par exemple avec debian-security (buster/updates), alors il faudra remplacer ce slash par --slash-- dans le nom du fichier .list
                     */
                    if (preg_match('#/#', $addProfileRepoDist)) {
                        $addProfileRepoDist = str_replace("/", "--slash--","$addProfileRepoDist");
                    }
                
                    exec("cd ".PROFILES_MAIN_DIR."/${name}/ && ln -sfn ".REPOS_PROFILES_CONF_DIR."/".REPO_CONF_FILES_PREFIX."${addProfileRepo}_${addProfileRepoDist}_${addProfileRepoSection}.list");
                }
            }
        }

        /**
         *  2. Gestion des exclusions, tâche cron et autres paramètres...
         *
         *  2.1. Pour chaque paramètre ci-dessous,
         *  Si non-vide alors on implode l'array en string en séparant chaque valeurs par une virgule (car c'est comme ça qu'elles seront renseignées dans le fichier de conf) 
         *  Si vide, alors on set une valeur vide
         */

        /**
         *  Remise à zero du paramètre, il est ensuite peuplé par les options sélectionnées ou laissé vide si aucune n'a été sélectionnée
         */
        $profileConf_excludeMajor = '';

        if (!empty($packagesMajorExcluded)) {
            foreach ($packagesMajorExcluded as $packageName) {
                $packageName = Common::validateData($packageName);

                /**
                 *  Pour chaque packageName sélectionnées dans la liste déroulante, on vérifie que le paquet existe en BDD,
                 *  Si ce n'est pas le cas on l'ajoute à condition qu'il respecte un certain format (pas de caractères spéciaux...)
                 * 
                 *  Si le package possède un wildcard .* alors on retire d'abord ce wildcard avant d'effectuer le test
                 */
                if (substr($packageName, -2) == ".*")
                    $packageNameFormatted = rtrim($packageName, ".*");
                else
                    $packageNameFormatted = $packageName;

                /**
                 *  Vérif en BDD puis ajout en BDD si n'existe pas et si le format du nom est valide
                 */
                if ($this->db_packageExists($packageNameFormatted) === false) {
                    /**
                     *  Si l'ajout en BDD a échouée (à cause d'un caractère spécial par exemple) alors on passe au paquet suivant
                     */
                    if ($this->db_addPackage($packageNameFormatted) === false) continue;
                }

                /**
                 *  Si le paquet est déjà connu ou qu'il a été ajouté en BDD sans erreur alors on l'ajoute à la liste des paquets à inclure dans le fichier de conf
                 */
                $profileConf_excludeMajor .= "${packageName},";
            }

            /**
             *  Suppression de la dernière virgule de la liste
             */
            $profileConf_excludeMajor = rtrim($profileConf_excludeMajor, ",");
        }

        /**
         *  Remise à zero du paramètre, il est ensuite peuplé par les options sélectionnées ou laissé vide si aucune n'a été sélectionnée
         */
        $profileConf_exclude = '';

        if (!empty($packagesExcluded)) {
            foreach ($packagesExcluded as $packageName) {
                $packageName = Common::validateData($packageName);

                /**
                 *  Pour chaque packageName sélectionnées dans la liste déroulante, on vérifie que le paquet existe en BDD,
                 *  Si ce n'est pas le cas on l'ajoute à condition qu'il respecte un certain format (pas de caractères spéciaux...)
                 * 
                 *  Si le package possède un wildcard .* alors on retire d'abord ce wildcard avant d'effectuer le test
                 */
                if (substr($packageName, -2) == ".*")
                    $packageNameFormatted = rtrim($packageName, ".*");
                else
                    $packageNameFormatted = $packageName;

                /**
                 *  Vérif en BDD puis ajout en BDD si n'existe pas et si le format du nom est valide
                 */
                if ($this->db_packageExists($packageNameFormatted) === false) {
                    /**
                     *  Si l'ajout en BDD a échouée (à cause d'un caractère spécial par exemple) alors on passe au paquet suivant
                     */
                    if ($this->db_addPackage($packageNameFormatted) === false) continue;
                }

                /**
                 *  Si le paquet est déjà connu ou qu'il a été ajouté en BDD sans erreur alors on l'ajoute à la liste des paquets à inclure dans le fichier de conf
                 */
                $profileConf_exclude .= "${packageName},";
            }

            /**
             *  Suppression de la dernière virgule de la liste
             */
            $profileConf_exclude = rtrim($profileConf_exclude, ",");
        }
        
        /**
         *  Remise à zero du paramètre, il est ensuite peuplé par les options sélectionnées ou laissé vide si aucune n'a été sélectionnée
         */
        $profileConf_needRestart = '';

        if (!empty($serviceNeedRestart)) {
            foreach ($serviceNeedRestart as $serviceName) {
                $serviceName = Common::validateData($serviceName);

                /**
                 *  Pour chaque serviceName sélectionnées dans la liste déroulante, on vérifie que le service existe en BDD,
                 *  Si ce n'est pas le cas on l'ajoute à condition qu'il respecte un certain format (pas de caractères spéciaux...)
                 */

                /**
                 *  Vérif en BDD puis ajout en BDD si n'existe pas et si le format du nom est valide
                 */
                if ($this->db_serviceExists($serviceName) === false) {
                    /**
                     *  Si l'ajout en BDD a échouée (à cause d'un caractère spécial par exemple) alors on passe au service suivant
                     */
                    if ($this->db_addService($serviceName) === false) continue;
                }

                /**
                 *  Si le service est déjà connu ou qu'il a été ajouté en BDD sans erreur alors on l'ajoute à la liste des services à inclure dans le fichier de conf
                 */
                $profileConf_needRestart .= "${serviceName},";
            }

            /**
             *  Suppression de la dernière virgule de la liste
             */
            $profileConf_needRestart = rtrim($profileConf_needRestart, ",");
        }

        /**
         *  2.2. On écrit dans le fichier de conf les paramètres précédemment récupérées
         */
        $profileConfiguration = "EXCLUDE_MAJOR=\"${profileConf_excludeMajor}\"";
        $profileConfiguration = "${profileConfiguration}\nEXCLUDE=\"${profileConf_exclude}\"";
        $profileConfiguration = "${profileConfiguration}\nNEED_RESTART=\"${profileConf_needRestart}\"";
        $profileConfiguration = "${profileConfiguration}\nKEEP_CRON=\"${keepCron}\"";
        $profileConfiguration = "${profileConfiguration}\nALLOW_OVERWRITE=\"${allowOverwrite}\"";
        $profileConfiguration = "${profileConfiguration}\nALLOW_REPOSFILES_OVERWRITE=\"${allowReposFilesOverwrite}\"";
        file_put_contents(PROFILES_MAIN_DIR."/${name}/config", $profileConfiguration);

        History::set($_SESSION['username'], "Modification de la configuration du profil $name", 'success');
    }

    /**
     *  Vérifier qu'un nom de package est présent dans la table profile_package
     */
    private function db_packageExists(string $package)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM profile_package WHERE Name=:name");
            $stmt->bindValue(':name', $package);
            $result = $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        /**
         *  Si le résultat obtenu est vide alors le package n'existe pas, on renvoie false
         */
        if ($this->db->isempty($result)) return false;

        return true;
    }

    /**
     *  Vérifier qu'un nom de service est présent dans la table profile_service
     */
    private function db_serviceExists(string $service)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM profile_service WHERE Name=:name");
            $stmt->bindValue(':name', $service);
            $result = $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        /**
         *  Si le résultat obtenu est vide alors le service n'existe pas, on renvoie false
         */
        if ($this->db->isempty($result)) return false;

        return true;
    }

    /**
     *  Ajout d'un nouveau nom de paquet dans la table profile_package
     */
    private function db_addPackage(string $package)
    {
        /**
         *  On vérifie que le nom du paquet ne contient pas de caractères interdits sinon on renvoie false
         */
        if (!Common::is_alphanumdash($package)) return false;

        try {
            $stmt = $this->db->prepare("INSERT INTO profile_package (Name) VALUES (:name)");
            $stmt->bindValue(':name', $package);
            $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        return true;
    }

    /**
     *  Ajout d'un nouveau nom de service dans la table profile_service
     */
    private function db_addService(string $service)
    {
        /**
         *  On vérifie que le nom du service ne contient pas de caractères interdits sinon on renvoie false
         */
        if (!Common::is_alphanumdash($service)) return false;

        try {
            $stmt = $this->db->prepare("INSERT INTO profile_service (Name) VALUES (:name)");
            $stmt->bindValue(':name', $service);
            $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        return true;
    }

    /**
     *  Récupère la liste des paquets dans la table profile_package
     */
    public function db_getPackages()
    {
        $result = $this->db->query("SELECT Name FROM profile_package");
        
        while ($datas = $result->fetchArray(SQLITE3_ASSOC)) $packages[] = $datas['Name'];

        if (!empty($packages))
            return $packages;
        else
            return '';
    }

    /**
     *  Récupère la liste des services dans la table profile_service
     */
    public function db_getServices()
    {
        $result = $this->db->query("SELECT Name FROM profile_service");
        
        while ($datas = $result->fetchArray(SQLITE3_ASSOC)) $services[] = $datas['Name'];

        if (!empty($services)) return $services;
        
        return '';
    }
}
?>