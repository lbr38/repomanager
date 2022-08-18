<?php

namespace Models;

use Exception;

class Source extends Model
{
    public $name;

    public function __construct()
    {
        /**
         *  Open a new database connection
         */
        $this->getConnection('main');
    }

    /**
     *  Add a new source repo
     */
    public function new(string $repoType, string $name, string $urlType = null, string $url, string $existingGpgKey = null, string $gpgKeyURL = null, string $gpgKeyText = null)
    {
        $name = \Controllers\Common::validateData($name);

        /**
         *  On vérifie que le nom du repo source ne contient pas de caractères invalides
         */
        if (!\Controllers\Common::isAlphanumDash($name)) {
            throw new Exception('Repo source name cannot contain special characters except hyphen and underscore');
        }

        /**
         *  Formattage de l'URL passée
         */
        $url = trim($url); // Suppression des espaces si il y en a (ça ne devrait pas)
        $url = stripslashes($url); // Suppression des anti-slash

        /**
         *  Si l'URL contient des caractères non-autorisés ou si elle ne commence pas par http(s) alors elle est invalide
         */
        if (!\Controllers\Common::isAlphanumDash($url, array('=', ':', '/', '.', '?', '$', '&', ','))) {
            throw new Exception('Repo source URL contains invalid characters');
        }
        if (!preg_match('#^https?://#', $url)) {
            throw new Exception('Repo source URL must start with <b>http(s)://</b>');
        }

        /**
         *  Sur Redhat/Centos, on crée un fichier dans /etc/yum.repos.d/repomanager/
         */
        if ($repoType == 'rpm') {
            if (file_exists(REPOMANAGER_YUM_DIR . '/' . $name . '.repo')) {
                throw new Exception("Repo source <b>$name</b> already exists");
            }

            /**
             *  On récupère la clé GPG, il s'agit soit une clé existante, soit au format url, soit au format texte à importer. Si les deux sont renseignés on affiche une erreur (c'est l'un ou l'autre)
             */
            if (!empty($existingGpgKey) and !empty($gpgKeyURL) and !empty($gpgKeyText)) {
                throw new Exception('You cannot specify more than one type of GPG key');

            /**
             *  Cas où c'est une clé existante
             */
            } elseif (!empty($existingGpgKey)) { // On recupère le nom de la clé existante
                $existingGpgKey = \Controllers\Common::validateData($existingGpgKey);

                /**
                 *  Si la clé renseignée n'existe pas, on quitte
                 */
                if (!file_exists(RPM_GPG_DIR . "/$existingGpgKey")) {
                    throw new Exception('Specified GPG key does not exist');
                }

            /**
             *  Cas où c'est une URL vers une clé GPG
             */
            } elseif (!empty($gpgKeyURL)) { // On recupère l'url de la clé gpg
                $gpgKeyURL = \Controllers\Common::validateData($gpgKeyURL);

                /**
                 *  Formattage de l'URL
                 */
                $gpgKeyURL = trim($gpgKeyURL); // Suppression des espaces si il y en a (ça ne devrait pas)
                $gpgKeyURL = stripslashes($gpgKeyURL); // Suppression des anti-slash

                /**
                 *  Si l'URL contient des caractères invalide alors on quitte
                 */
                if (!\Controllers\Common::isAlphanumDash($gpgKeyURL, array(':', '/', '.'))) {
                    throw new Exception('GPG key URL contains invalid characters');
                }

                /**
                 *  Si l'URL ne commence pas par http(s) ou par file:// (pour désigner un fichier sur le serveur) alors elle est invalide
                 */
                if (!preg_match('#^https?://#', $gpgKeyURL) and !preg_match('/^file:\/\/\//', $gpgKeyURL)) {
                    throw new Exception('GPG key URL is invalid');
                }

            /**
             *  Cas où on importe une clé au format texte ASCII
             */
            } elseif (!empty($gpgKeyText)) { // On récupère la clé gpg au format texte
                $gpgKeyText = \Controllers\Common::validateData($gpgKeyText);

                /**
                 *  Si le 'pavé' de texte ASCII contient des caractères invalide alors on quitte
                 *  Ici on autorise tous les caractères qu'on peut possiblement retrouver dans une clé GPG au format ASCII
                 */
                if (!\Controllers\Common::isAlphanum($gpgKeyText, array('-', '=', '+', '/', ' ', ':', '.', '(', ')', "\n", "\r"))) {
                    throw new Exception('ASCII GPG key contains invalid characters');
                }

                /**
                 *  Si le contenu qu'on tente d'importer est un fichier sur le disque alors on quitte
                 */
                if (file_exists($gpgKeyText)) {
                    throw new Exception('GPG key must be specified in ASCII text format');
                }

                /**
                 *  On importe la clé gpg au format texte dans le répertoire par défaut où rpm stocke ses clés gpg importées (et dans un sous-répertoire repomanager)
                 */
                $newGpgFile = "REPOMANAGER-RPM-GPG-KEY-${name}";
                if (file_exists(RPM_GPG_DIR . "/${newGpgFile}")) {
                    throw new Exception('A GPG file with the same name already exists');
                } else {
                    file_put_contents(RPM_GPG_DIR . "/${newGpgFile}", $gpgKeyText); // ajout de la clé gpg à l'intérieur du fichier gpg
                }
            }

            /**
             *  Récupération du type d'URL
             */
            $urlType = \Controllers\Common::validateData($urlType);
            if ($urlType != 'baseurl' and $urlType != 'mirrorlist' and $urlType != 'metalink') {
                throw new Exception('Specified URL type is invalid');
            }

            /**
             *  On génère la conf qu'on va injecter dans le fichier de repo
             */
            $newRepoFileConf  = "[$name]" . PHP_EOL;
            $newRepoFileConf .= 'enabled=1' . PHP_EOL;
            $newRepoFileConf .= "name=Repo source $name sur " . WWW_HOSTNAME . PHP_EOL;

            /**
             *  Forge l'url en fonction de son type (baseurl, mirrorlist...)
             */
            $newRepoFileConf .= "${urlType}=${url}" . PHP_EOL;

            /**
             *  Si on a renseigné une clé GPG alors on active gpgcheck
             */
            if (!empty($existingGpgKey) or !empty($gpgKeyURL) or !empty($gpgKeyText)) {
                $newRepoFileConf .= "gpgcheck=1" . PHP_EOL;
            }

            /**
             *  On indique le chemin vers la clé GPG existante si indiqué
             */
            if (!empty($existingGpgKey)) {
                $newRepoFileConf .= "gpgkey=file://" . RPM_GPG_DIR . "/${existingGpgKey}" . PHP_EOL;
            }
            /**
             *  On indique l'url vers la clé GPG si indiqué
             */
            if (!empty($gpgKeyURL)) {
                $newRepoFileConf .= "gpgkey=${gpgKeyURL}" . PHP_EOL;
            }
            /**
             *  On indique le chemin vers la clé GPG importée
             */
            if (!empty($gpgKeyText)) {
                $newRepoFileConf .= "gpgkey=file://" . RPM_GPG_DIR . "/${newGpgFile}" . PHP_EOL;
            }

            /**
             *  Ecriture de la configuration dans le fichier de repo source
             */
            file_put_contents(REPOMANAGER_YUM_DIR . "/${name}.repo", $newRepoFileConf . PHP_EOL);
        }


        /**
         *  Sur Debian, on ajoute l'URL en BDD
         */
        if ($repoType == "deb") {
            /**
             *  On vérifie qu'un repo source du même nom n'existe pas déjà en BDD
             */
            try {
                $stmt = $this->db->prepare("SELECT Name FROM Sources WHERE Name=:name");
                $stmt->bindValue(':name', $name);
                $result = $stmt->execute();
            } catch (\Exception $e) {
                \Controllers\Common::dbError($e);
            }

            /**
             *  Si le résultat n'est pas vide alors un repo existe déjà
             */
            if ($this->db->isempty($result) === false) {
                throw new Exception("A source repo <b>$name</b> already exists");
            }

            /**
             *  Si une clé GPG a été transmise alors on l'importe
             */
            if (!empty($gpgKeyText)) {
                $gpgKeyText = \Controllers\Common::validateData($gpgKeyText);
                $gpgKeyText = trim($gpgKeyText);

                /**
                 *  Si le 'pavé' de texte ASCII contient des caractères invalides alors on quitte
                 *  Ici on autorise tous les caractères qu'on peut possiblement retrouver dans une clé GPG au format ASCII
                 */
                if (!\Controllers\Common::isAlphanum($gpgKeyText, array('-', '=', '+', '/', ' ', ':', '.', '(', ')', "\n", "\r"))) {
                    throw new Exception('ASCII GPG key contains invalid characters');
                }

                /**
                 *  Si le contenu qu'on tente d'importer est un fichier sur le disque alors on quitte
                 */
                if (file_exists($gpgKeyText)) {
                    throw new Exception('GPG key must be specified in ASCII text format');
                }

                /**
                 *  Création d'un fichier temporaire dans lequel on injecte la clé GPG à importer
                 */
                $gpgTempFile = TEMP_DIR . "/repomanager_newgpgkey.tmp";
                file_put_contents($gpgTempFile, $gpgKeyText);

                /**
                 *  Import du fichier temporaire dans le trousseau de repomanager
                 */
                exec("gpg --no-default-keyring --keyring " . GPGHOME . "/trustedkeys.gpg --import $gpgTempFile", $output, $result);

                /**
                 *  Suppression du fichier temporaire
                 */
                unlink($gpgTempFile);

                /**
                 *  Si erreur lors de l'import, on affiche un message d'erreur
                 */
                if ($result != 0) {
                    throw new Exception('Error while importing GPG key');
                }
            }

            try {
                $stmt = $this->db->prepare("INSERT INTO sources ('Name', 'Url') VALUES (:name, :url)");
                $stmt->bindValue(':name', $name);
                $stmt->bindValue(':url', $url);
                $stmt->execute();
            } catch (\Exception $e) {
                \Controllers\Common::dbError($e);
            }
        }
    }

    /**
     *  Remove a source repo
     */
    public function delete(string $repoType, string $name)
    {
        if ($repoType != 'rpm' and $repoType != 'deb') {
            throw new Exception('Repo type is invalid');
        }

        $name = \Controllers\Common::validateData($name);

        if ($repoType == "rpm") {
            if (file_exists(REPOMANAGER_YUM_DIR . '/' . $name . '.repo')) {
                if (!unlink(REPOMANAGER_YUM_DIR . '/' . $name . '.repo')) {
                    throw new Exception("Error while deleting source repo <b>$name</b>");
                }
            }
        }
        if ($repoType == "deb") {
            try {
                $stmt = $this->db->prepare("DELETE FROM sources WHERE Name = :name");
                $stmt->bindValue(':name', $name);
                $stmt->execute();
            } catch (\Exception $e) {
                \Controllers\Common::dbError($e);
            }
        }
    }

    /**
     *  Rename a source repo
     */
    public function rename(string $repoType, string $name, string $newName)
    {
        if ($repoType != 'rpm' and $repoType != 'deb') {
            throw new Exception('Repo type is invalid');
        }

        $name = \Controllers\Common::validateData($name);
        $newName = \Controllers\Common::validateData($newName);

        /**
         *  Si le nom actuel et le nouveau nom sont les mêmes, on ne fait rien
         */
        if ($name == $newName) {
            throw new Exception('You must specify a different name from the actual');
        }

        /**
         *  On vérifie que le nom ainsi que le nouveau nom ne contiennent pas de caractères invalides
         */
        if (\Controllers\Common::isAlphanumDash($name) === false) {
            throw new Exception('Repo name contains invalid characters');
        }
        if (\Controllers\Common::isAlphanumDash($newName) === false) {
            throw new Exception('Repo new name contains invalid characters');
        }

        /**
         *  Sur Redhat, le renommage consiste à changer le nom du fichier de repo source ainsi que le nom du repo à l'intérieur de ce fichier
         */
        if ($repoType == "rpm") {
            /**
             *  Si un fichier portant le même nom que $newName existe déjà alors on ne peut pas renommer le fichier
             */
            if (file_exists(REPOMANAGER_YUM_DIR . '/' . $newName . '.repo')) {
                throw new Exception("A repo source with the same name <b>$newName<b> already exists");
            }

            /**
             *  Renommage
             */
            if (file_exists(REPOMANAGER_YUM_DIR . '/' . $name . '.repo')) {
                if (!rename(REPOMANAGER_YUM_DIR . '/' . $name . '.repo', REPOMANAGER_YUM_DIR . '/' . $newName . '.repo')) {
                    throw new Exception('Cannot rename source repo <b>' . $name . '</b>');
                }
                $content = file_get_contents(REPOMANAGER_YUM_DIR . "/${newName}.repo");
                $content = str_replace("[$name]", "[$newName]", $content);
                $content = str_replace("Source repo $name", "Source repo $newName", $content);

                file_put_contents(REPOMANAGER_YUM_DIR . "/${newName}.repo", $content);
                unset($content);
            }
        }

        /**
         *  Sur Debian, les repos sources sont stockés en BDD
         */
        if ($repoType == "deb") {
            /**
             *  On vérifie si un repo source du même nom existe déjà
             */
            try {
                $stmt = $this->db->prepare("SELECT Name FROM sources WHERE Name = :newname");
                $stmt->bindValue(':newname', $newName);
                $result = $stmt->execute();
            } catch (\Exception $e) {
                \Controllers\Common::dbError($e);
            }
            if ($this->db->isempty($result) === false) {
                throw new Exception("Source repo <b>$newName</b> already exists");
            }

            try {
                $stmt = $this->db->prepare("UPDATE sources SET Name = :newname WHERE Name = :name");
                $stmt->bindValue(':newname', $newName);
                $stmt->bindValue(':name', $name);
                $stmt->execute();
            } catch (\Exception $e) {
                \Controllers\Common::dbError($e);
            }
        }
    }

    /**
     *  Edit source repo URL (repo source de type deb uniquement)
     */
    public function editUrl(string $sourceName, string $url)
    {
        $sourceName = \Controllers\Common::validateData($sourceName);

        /**
         *  Formattage de l'URL passée
         */
        $url = trim($url); // Suppression des espaces si il y en a (ça ne devrait pas)
        $url = stripslashes($url); // Suppression des anti-slash
        $url = strtolower($url); // converti tout en minuscules

        /**
         *  On vérifie que l'url ne contient pas de caractères invalides
         */
        if (\Controllers\Common::isAlphanumDash($url, array(':', '/', '.', '?', '&')) === false) {
            throw new Exception("Specified URL contains invalid characters");
        }

        /**
         *  On vérifie que l'url commence par http(s)://
         */
        if (!preg_match('#^https?://#', $url)) {
            throw new Exception("Specified URL must start with http(s)://");
        }

        try {
            $stmt = $this->db->prepare("UPDATE sources SET Url = :url WHERE Name = :name");
            $stmt->bindValue(':url', $url);
            $stmt->bindValue(':name', $sourceName);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Edit source repo(s configuration (Redhat only)
     */
    public function configureSource(string $sourceName, array $options, string $comments = null)
    {
        $sourceName = \Controllers\Common::validateData($sourceName);
        $sourceFile = REPOMANAGER_YUM_DIR . "/${sourceName}.repo"; // Le fichier dans lequel on va écrire

        /**
         *  On initialise le contenu du fichier en mettant le nom du repo source en crochet (standard des fichiers .repo)
         */
        $content = "[${sourceName}]" . PHP_EOL;

        foreach ($options as $option) {
            $optionName = \Controllers\Common::validateData($option['name']);
            $optionValue = $option['value'];

            /**
             *  On vérifie que le nom de l'option est valide, càd qu'il ne contient pas de caractère spéciaux
             */
            if (\Controllers\Common::isAlphanumDash($optionName) === false) {
                throw new Exception("Parameter <b>$optionName</b> contains invalid characters");
            }

            if (empty($optionValue)) {
                /**
                 *  Si le nom du paramètre est 'gpgcheck' ou 'enabled' ou pkg_gpgcheck ou etc... alors il faut set une valeur de 0, sinon on set la valeur à ''
                 */
                if (
                    $optionName == 'gpgcheck' or
                    $optionName == 'enabled' or
                    $optionName == 'pkg_gpgcheck' or
                    $optionName == 'sslverify' or
                    $optionName == 'repo_gpgcheck' or
                    $optionName == 'countme'
                ) {
                    $optionValue = '0';
                }

                if ($optionName == 'autorefresh') {
                    $optionValue = 'no';
                }
            } elseif (!empty($optionValue)) {
                /**
                 *  Si le nom du paramètre est 'gpgcheck' ou 'enabled' ou pkg_gpgcheck ou etc... alors sa valeur ne peut être que '1' ou '0'
                 *  Si la valeur est non-vide et qu'elle vaut 'yes' alors on la set à '1' conformément à la syntaxe des fichiers .repo, sinon dans tous les autres cas on la set à '0' (fait plus haut)
                 */
                if (
                    $optionName == 'gpgcheck' or
                    $optionName == 'enabled' or
                    $optionName == 'pkg_gpgcheck' or
                    $optionName == 'sslverify' or
                    $optionName == 'repo_gpgcheck' or
                    $optionName == 'countme'
                ) {
                    $optionValue = '1';
                }

                /**
                 *  Dans le cas où le paramètre se nomme baseurl, mirrorlist ou metalink, on accepte + de caractères spéciaux car sa valeur est souvent une url pouvant comporter des slashs, des ? et des $
                 *  Note : ne pas autoriser les parenthèses pour éviter l'injection de code et la tentative d'utilisation de la fonction exec() par exemple. Si possible voir pour échapper le caractère $
                 */
                if ($optionName == 'baseurl' or $optionName == 'mirrorlist' or $optionName == 'metalink') {
                    $optionValue = trim($optionValue);          // Suppression des espaces si il y en a (ça ne devrait pas)
                    $optionValue = stripslashes($optionValue);  // Suppression des anti-slash
                    if (\Controllers\Common::isAlphanumDash($optionValue, array(':', '/', '.', '?', '$', '&', '=', ',')) === false) {
                        throw new Exception("Parameter value <b>$optionName</b> contains invalid characters");
                    }
                    /**
                     *  Si la valeur ne commence pas par http(s):// alors le paramètre est invalide
                     */
                    if (!preg_match('#^https?://#', $optionValue)) {
                        throw new Exception("Parameter value <b>$optionName</b> must start with http(s)://");
                    }

                /**
                 *  Paramètre gpgkey
                 */
                } elseif ($optionName == 'gpgkey') {
                    $optionValue = trim($optionValue);         // Suppression des espaces si il y en a (ça ne devrait pas)
                    $optionValue = stripslashes($optionValue); // Suppression des anti-slash
                    /**
                     *  La clé gpg peut être un fichier ou une url, donc on accepte certains caractères
                     */
                    if (\Controllers\Common::isAlphanumDash($optionValue, array(':', '/', '.')) === false) {
                        throw new Exception("Parameter value <b>$optionName</b> contains invalid characters");
                    }
                    /**
                     *  Si la valeur ne commence pas par http(s):// ou par file:/// alors le paramètre est invalide
                     */
                    if (!preg_match('#^https?://#', $optionValue) and !preg_match('/^file:\/\/\//', $optionValue)) {
                        throw new Exception("Parameter value <b>$optionName</b> must start with http(s):// ou file:///");
                    }

                /**
                 *  Paramètre metadata_expire
                 */
                } elseif ($optionName == 'metadata_expire') {
                    if (!is_numeric($optionValue)) {
                        throw new Exception("Parameter value <b>$optionName</b> must be numeric");
                    }

                /**
                 *  Paramètres sslcacert, sslclientcert, sslclientkey
                 *  Le paramètre doit être un chemin vers un fichier
                 */
                } elseif ($optionName == 'sslcacert' or $optionName == 'sslclientcert' or $optionName == 'sslclientkey') {
                    /**
                     *  Vérifie que le fichier existe
                     */
                    if (!file_exists($optionValue)) {
                        throw new Exception("File <b>$optionValue</b> of parameter <b>$optionName</b> soes not exist");
                    }

                    /**
                     *  Vérifie que le fichier est accessible en lecture
                     */
                    if (!is_readable($optionValue)) {
                        throw new Exception("File <b>$optionValue</b> of parameter <b>$optionName</b> is not readable");
                    }

                /**
                 *  Paramètre autorefresh
                 */
                } elseif ($optionName == 'autorefresh') {
                    $optionValue = 'yes';

                /**
                 *  Tous les autres types paramètres
                 */
                } else {
                    if (\Controllers\Common::isAlphanumDash($optionValue, array('.', ' ', ':', '/', '&', '?', '=')) === false) {
                        throw new Exception("Parameter value <b>$optionName</b> contains invalid characters");
                    }
                    /**
                     *  Si la valeur commence par un slash, ce n'est pas bon... cela pourrait être un chemin de fichier sur le système
                     */
                    if (preg_match('#^/#', $optionValue)) {
                        throw new Exception("Parameter value <b>$optionName</b> is invalid");
                    }
                }
            }

            /**
             *  Si il n'y a pas eu d'erreurs jusque là alors on forge la ligne du paramètre avec son nom et sa valeur, séparés par un égal '='
             *  Sinon on forge la même ligne mais en laissant la valeur vide afin que l'utilisateur puisse la resaisir
             */
            $content .= $optionName . "=" . $optionValue . PHP_EOL;
        }

        /**
         *  Si des commentaires ont été saisis dans le bloc de textarea 'Notes' alors on ajoute un dièse # avant chaque ligne afin de l'inclure en tant que commentaire dans le fichier
         */
        if (!empty($comments)) {
            $comments = explode(PHP_EOL, \Controllers\Common::validateData($comments));
            foreach ($comments as $comment) {
                $content .= "#" . $comment . PHP_EOL;
            }
        }

        /**
         *  Enfin, on écrit le contenu dans le fichier .repo
         */
        file_put_contents(REPOMANAGER_YUM_DIR . "/${sourceName}.repo", $content);

        unset($content);
    }

    /**
     *  Add a new GPG key
     */
    // public function addGpgKey(string $gpgKey, string $type)
    // {
    //     // WIP
    // }

    /**
     *  Delete a GPG key
     */
    public function removeGpgKey(string $repoType, string $gpgkey)
    {
        if ($repoType != 'rpm' and $repoType != 'deb') {
            throw new Exception('Repo type is invalid');
        }

        $gpgkey = \Controllers\Common::validateData($gpgkey);

        /**
         *  Cas Redhat
         *  La clé GPG est située un fichier dans /etc/pki/rpm-gpg/repomanager/
         */
        if ($repoType == "rpm") {
            if (!file_exists('/etc/pki/rpm-gpg/repomanager/' . $gpgkey)) {
                throw new Exception("GPG key <b>" . $gpgkey . "</b> does not exist");
            }

            if (!unlink('/etc/pki/rpm-gpg/repomanager/' . $gpgkey)) {
                throw new Exception("Cannot delete GPG key <b>" . $gpgkey . "</b>");
            }
        }

        /**
         *  Cas Debian
         *  La clé GPG est présente dans le trousseau gpg
         */
        if ($repoType == "deb") {
            /**
             *  On supprime la clé du trousseau, à partir de son ID
             */
            exec("gpg --no-default-keyring --keyring " . GPGHOME . "/trustedkeys.gpg --no-greeting --delete-key --batch --yes $gpgkey", $output, $result);

            if ($result != 0) {
                throw new Exception("Error while deleting GPG key <b>$gpgkey</b>");
            }
        }
    }

    /**
     *  Check if source repo (name) exists in database
     */
    public function exists(string $source)
    {
        $source = \Controllers\Common::validateData($source);

        try {
            $stmt = $this->db->prepare("SELECT Id FROM sources WHERE Name = :name");
            $stmt->bindValue(':name', $source);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        if ($this->db->isempty($result)) {
            return false;
        }

        return true;
    }

    /**
     *  List all source repos
     */
    public function listAll()
    {
        $sources = array();

        $query = $this->db->query("SELECT * FROM sources");

        while ($datas = $query->fetchArray(SQLITE3_ASSOC)) {
            $sources[] = $datas;
        }

        return $sources;
    }
}
