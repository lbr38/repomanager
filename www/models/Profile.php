<?php

class Profile extends Model {
    private $id;
    private $name;
    private $newName;

    public function __construct(array $variables = []) {
        extract($variables);

        /**
         *  Ouverture d'une connexion à la base de données
         */
        $this->getConnection('main', 'rw');

        /* Id */
        if (!empty($profileId)) $this->id = $profileId;
        /* Nom */
        if (!empty($profileName)) $this->name = $profileName;
        /* Nouveau nom */
        if (!empty($newProfileName)) $this->newName = $newProfileName;
    }


    /**
     * 	Création d'un nouveau profil
     */
    function new() {
        /**
         *  1. On vérifie que le nom du profil ne contient pas des caractères interdits
         */
        if (!is_alphanumdash($this->name)) return;

        /**
         * 	2. On vérifie qu'un profil du même nom n'existe pas déjà
         */
        if (file_exists(PROFILES_MAIN_DIR."/{$this->name}")) {
            printAlert("Erreur : un profil du même nom (<b>$this->name</b>) existe déjà", 'error');
            return;
        }

        /**
         * 	3. Si pas d'erreur alors on peut créer le répertoire de profil
         */
        if (!is_dir(PROFILES_MAIN_DIR."/{$this->name}")) { 
            if (!mkdir(PROFILES_MAIN_DIR."/{$this->name}", 0775, true)) {
                printAlert("Erreur lors de la création du profil <b>$this->name</b>", 'error');
                return;
            }
        }

        /**
         * 	4. Créer le fichier de config
         */
        if (!file_exists(PROFILES_MAIN_DIR."/{$this->name}/config")) {
            if (!touch(PROFILES_MAIN_DIR."/{$this->name}/config")) {
                printAlert("Erreur lors de l'initialisation du profil <b>$this->name</b>", 'error');
                return;
            }
        }

        /**
         * 	5. Créer le fichier de config du profil avec des valeurs vides ou par défaut
         */
        if (!file_put_contents(PROFILES_MAIN_DIR."/{$this->name}/config", "EXCLUDE_MAJOR=\"\"\nEXCLUDE=\"\"\nNEED_RESTART=\"\"\nKEEP_CRON=\"no\"\nALLOW_OVERWRITE=\"yes\"\nALLOW_REPOSFILES_OVERWRITE=\"yes\"")) {
            printAlert("Erreur lors de l'initialisation du profil <b>$this->name</b>", 'error');
            return;
        }
        
        /**
         * 	Affichage d'un message
         */
        printAlert("Le profil <b>{$this->name}</b> a été créé", 'success');
    }


    /**
     * 	Renommage d'un profil
     */
    function rename() {
        /**
         *  1. On vérifie que le nom du profil ne contient pas des caractères interdits
         */
        if (!is_alphanumdash($this->name) OR !is_alphanumdash($this->newName)) return;

        /**
         *  2. On vérifie qu'un profil du même nom n'existe pas déjà. Si c'est le cas on affiche un message d'erreur
         */
        if (is_dir(PROFILES_MAIN_DIR."/{$this->newName}")) {
            printAlert("Erreur : un profil du même nom (<b>{$this->newName}</b>) existe déjà", 'error');
            return false;
        }

        /**
         *  3. Si pas d'erreur alors on peut renommer le répertoire de profil
         */
        if (!rename(PROFILES_MAIN_DIR."/{$this->name}", PROFILES_MAIN_DIR."/{$this->newName}")) {
            printAlert("Erreur lors du renommage du profil <b>{$this->name}</b>", 'error');
            return;
        }

        /**
         *  4. Affichage d'un message
         */
        printAlert("Le profil <b>{$this->name}</b> a été renommé en <b>{$this->newName}</b>", 'success');
    }


    /**
     *  Supliquer un profil et sa configuration
     */
    public function duplicate() {
        /**
         *  1. On génère un nouveau nom de profil basé sur le nom du profil dupliqué + suivi d'un nombre aléatoire
         */
        $newProfileName = $this->name.'-'.mt_rand(100000,200000);
        
        /**
         *  2. On vérifie que le nouveau nom n'existe pas déjà sait-on jamais
         */
        if (file_exists(PROFILES_MAIN_DIR."/${newProfileName}")) {
            printAlert("Erreur : un profil du même nom (<b>$newProfileName</b>) existe déjà", 'error');
            return;
        }

        /**
         *  3. Création du répertoire du nouveau profil
         */
        if (!file_exists(PROFILES_MAIN_DIR."/${newProfileName}")) mkdir(PROFILES_MAIN_DIR."/${newProfileName}", 0775, true);

        /**
         *  4. Copie du contenu du répertoire du profil dupliqué afin de copier sa config et ses fichiers de repo
         */
        exec("cp -rP ".PROFILES_MAIN_DIR."/{$this->name}/* ".PROFILES_MAIN_DIR."/${newProfileName}/");

        printAlert("Le profil <b>$newProfileName</b> a été créé", 'success');
    }


    /**
     *  Supprimer un profil
     */
    public function delete() {
        /**
         *  1. On vérifie que le nom du profil ne contient pas des caractères interdits
         */
        if (!is_alphanumdash($this->name)) return;

        /**
         * 	2. Suppression du répertoire du profil
         */
        exec("rm -fr ".PROFILES_MAIN_DIR."/{$this->name}/", $output, $return);
        if ($return == 0) {
            printAlert("Le profil <b>{$this->name}</b> a été supprimé", 'success');
        } else {
            printAlert("Erreur lors de la suppression du profil <b>{$this->name}</b>", 'error');
        }
    }

    /**
     *  Configuration d'un profil
     *  Gestion des repos du profil
     *  Gestion des paquets à exclure
     */
    public function configure() {
        $error = 0;

        /**
         *  1.1. Gestion des repos/sections du profil
         *  Les repos peuvent être vides (si on a décidé de supprimer tous les repos d'un profil par exemple).
         *  Donc il est tout à fait possible que $_POST['profileRepos'] ne soit pas définie, si c'est le cas alors on set $profileRepos à vide
         */
        if (empty($_POST['profileRepos']))
            $profileRepos = '';
        else
            $profileRepos = $_POST['profileRepos']; // validateData fait plus bas

        /**
         *  1.2. On vérifie que le nom du profil ne contient pas des caractères interdits
         */
        if (!is_alphanumdash($this->name)) return;
        
        /**
         * 	1.3. D'abord on supprime tous les repos présents dans le répertoire du profil, avant de rajouter seulement ceux qui ont été sélectionnés dans la liste
         */
        if (is_dir(PROFILES_MAIN_DIR."/{$this->name}/")) {
            if (OS_FAMILY == "Redhat") exec("rm ".PROFILES_MAIN_DIR."/{$this->name}/*.repo -f");
            if (OS_FAMILY == "Debian") exec("rm ".PROFILES_MAIN_DIR."/{$this->name}/*.list -f");
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
                $addProfileRepo = validateData($profileRepo);
        
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
                if (!is_alphanumdash($addProfileRepo, array('.'))) return;
                if (OS_FAMILY == "Debian") {
                    // Certaines nom de distribution peuvent contenir des slashs, donc ici on autorise l'utilisation d'un slash
                    if (!is_alphanumdash($addProfileRepoDist, array('/')) OR !is_alphanumdash($addProfileRepoSection)) return;
                }

                if (OS_FAMILY == "Redhat") {
                    $myRepo = new Repo(array('repoName' => $addProfileRepo));

                    /**
                     *  On vérifie que le repo existe, sinon on passe au suivant
                     */
                    if ($myRepo->exists($myRepo->name) === false) {
                        printAlert("Le repo <b>$myRepo->name</b> n'existe pas", 'error');
                        continue;
                    }
        
                    exec("cd ".PROFILES_MAIN_DIR."/{$this->name}/ && ln -sfn ".REPOS_PROFILES_CONF_DIR."/".REPO_CONF_FILES_PREFIX."{$myRepo->name}.repo");
                }
        
                if (OS_FAMILY == "Debian" AND !empty($addProfileRepoDist) AND !empty($addProfileRepoSection)) {
                    $myRepo = new Repo(array('repoName' => $addProfileRepo, 'repoDist' => $addProfileRepoDist, 'repoSection' => $addProfileRepoSection));

                    /**
                     * 	On vérifie que la section repo existe, sinon on passe au suivant
                     */
                    if ($myRepo->section_exists($myRepo->name, $myRepo->dist, $myRepo->section) === false) {
                        printAlert("La section <b>$myRepo->section</b> du repo <b>$myRepo->name</b> n'existe pas", 'error');
                        continue;
                    }
        
                    /**
                     * 	Si le nom de la distribution contient un slash, c'est le cas par exemple avec debian-security (buster/updates), alors il faudra remplacer ce slash par --slash-- dans le nom du fichier .list
                     */
                    //$checkIfDistContainsSlash = exec("echo $myRepo->dist | grep '/'");
                    //if (!empty($checkIfDistContainsSlash)) {
                    if (preg_match('#/#', $myRepo->dist)) {
                        $myRepo->dist = str_replace("/", "--slash--","$myRepo->dist");
                    }
                
                    exec("cd ".PROFILES_MAIN_DIR."/{$this->name}/ && ln -sfn ".REPOS_PROFILES_CONF_DIR."/".REPO_CONF_FILES_PREFIX."{$myRepo->name}_{$myRepo->dist}_{$myRepo->section}.list");
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

        if (!empty($_POST['profileConf_excludeMajor'])) {
            foreach ($_POST['profileConf_excludeMajor'] as $packageName) {
                $packageName = validateData($packageName);

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

        if (!empty($_POST['profileConf_exclude'])) {
            foreach ($_POST['profileConf_exclude'] as $packageName) {
                $packageName = validateData($packageName);

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

        if (!empty($_POST['profileConf_needRestart'])) {
            foreach ($_POST['profileConf_needRestart'] as $serviceName) {
                $serviceName = validateData($serviceName);

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
         *  Boutons radio : si non-vide alors on récupère sa valeur, sinon on set à 'no'
         */
        if (!empty($_POST['profileConf_keepCron']) AND validateData($_POST['profileConf_keepCron']) === "yes")
            $profileConf_keepCron = 'yes';
        else
            $profileConf_keepCron = 'no';
        
        if (!empty($_POST['profileConf_allowOverwrite']) AND validateData($_POST['profileConf_allowOverwrite']) === "yes")
            $profileConf_allowOverwrite = 'yes';
        else
            $profileConf_allowOverwrite = 'no';

        if (!empty($_POST['profileConf_allowReposFilesOverwrite']) AND validateData($_POST['profileConf_allowReposFilesOverwrite']) === "yes")
            $profileConf_allowReposFilesOverwrite = 'yes';
        else
            $profileConf_allowReposFilesOverwrite = 'no';

        /**
         *  2.2. On écrit dans le fichier de conf les paramètres précédemment récupérées
         */
        $profileConfiguration = "EXCLUDE_MAJOR=\"${profileConf_excludeMajor}\"";
        $profileConfiguration = "${profileConfiguration}\nEXCLUDE=\"${profileConf_exclude}\"";
        $profileConfiguration = "${profileConfiguration}\nNEED_RESTART=\"${profileConf_needRestart}\"";
        $profileConfiguration = "${profileConfiguration}\nKEEP_CRON=\"${profileConf_keepCron}\"";
        $profileConfiguration = "${profileConfiguration}\nALLOW_OVERWRITE=\"${profileConf_allowOverwrite}\"";
        $profileConfiguration = "${profileConfiguration}\nALLOW_REPOSFILES_OVERWRITE=\"${profileConf_allowReposFilesOverwrite}\"";
        file_put_contents(PROFILES_MAIN_DIR."/{$this->name}/config", $profileConfiguration);

        /**
         *  3. Affichage d'un message, si tout s'est bien passé
         */
        if ($error == 0) printAlert("Configuration du profil <b>$this->name</b> enregistrée", 'success');

        /**
         *  4. Ré-affichage de la configuration du profil
         */
        showdiv_byid("profileConfigurationDiv-{$this->name}");

        unset($profileConfiguration, $profileConf_excludeMajor, $profileConf_exclude, $profileConf_needRestart, $profileConf_keepCron, $profileConf_allowOverwrite, $profileConf_allowReposFilesOverwrite);
    }

    /**
     *  Vérifier qu'un nom de package est présent dans la table profile_package
     */
    private function db_packageExists(string $package) {
        $stmt = $this->db->prepare("SELECT * FROM profile_package WHERE Name=:name");
        $stmt->bindValue(':name', $package);
        $result = $stmt->execute();

        /**
         *  Si le résultat obtenu est vide alors le package n'existe pas, on renvoie false
         */
        if ($this->db->isempty($result)) return false;

        return true;
    }

    /**
     *  Ajout d'un nouveau nom de paquet dans la table profile_package
     */
    private function db_addPackage(string $package) {
        /**
         *  On vérifie que le nom du paquet ne contient pas de caractères interdits sinon on renvoie false
         */
        if (!is_alphanumdash($package)) return false;

        $stmt = $this->db->prepare("INSERT INTO profile_package (Name) VALUES (:name)");
        $stmt->bindValue(':name', $package);
        $stmt->execute();

        return true;
    }

    /**
     *  Récupère la liste des paquets dans la table profile_package
     */
    public function db_getPackages() {
        $result = $this->db->query("SELECT Name FROM profile_package");
        
        while ($datas = $result->fetchArray(SQLITE3_ASSOC)) $packages[] = $datas['Name'];

        if (!empty($packages))
            return $packages;
        else
            return '';
    }

    /**
     *  Vérifier qu'un nom de service est présent dans la table profile_service
     */
    private function db_serviceExists(string $service) {
        $stmt = $this->db->prepare("SELECT * FROM profile_service WHERE Name=:name");
        $stmt->bindValue(':name', $service);
        $result = $stmt->execute();

        /**
         *  Si le résultat obtenu est vide alors le service n'existe pas, on renvoie false
         */
        if ($this->db->isempty($result)) return false;

        return true;
    }

    /**
     *  Ajout d'un nouveau nom de service dans la table profile_service
     */
    private function db_addService(string $service) {
        /**
         *  On vérifie que le nom du service ne contient pas de caractères interdits sinon on renvoie false
         */
        if (!is_alphanumdash($service)) return false;

        $stmt = $this->db->prepare("INSERT INTO profile_service (Name) VALUES (:name)");
        $stmt->bindValue(':name', $service);
        $stmt->execute();

        return true;
    }

    /**
     *  Récupère la liste des services dans la table profile_service
     */
    public function db_getServices() {
        $result = $this->db->query("SELECT Name FROM profile_service");
        
        while ($datas = $result->fetchArray(SQLITE3_ASSOC)) $services[] = $datas['Name'];

        if (!empty($services)) return $services;
        
        return '';
    }
}
?>