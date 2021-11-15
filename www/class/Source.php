<?php

class Source {
    public $name;
    public $db;

    public function __construct(array $variables = []) {
        extract($variables);

        /**
         *  Instanciation d'une db car on peut avoir besoin de récupérer certaines infos en BDD
         */
        try {
            $this->db = new Database();
        } catch(Exception $e) {
            die('Erreur : '.$e->getMessage());
        }

        /* Nom */
        if (!empty($sourceName)) { $this->name = $sourceName; }
    }

    /**
     *  Ajouter un nouveau repo source
     */
    public function new(string $name, string $url) {
        global $OS_FAMILY;
        global $REPOMANAGER_YUM_DIR;
        global $WWW_HOSTNAME;
        global $RPM_GPG_DIR;
        global $TEMP_DIR;
        global $GPGHOME;
        global $DEBUG_MODE;

        /**
         *  On vérifie que le nom du repo source ne contient pas de caractères interdits
         */
        if (!is_alphanumdash($name)) {
            printAlert('Erreur : le nom du repo source ne peut pas contenir de caractères spéciaux hormis le tiret - et l\'underscore _', 'error');
            slidediv_byid('sourcesDiv');
            return;
        }

        /**
         *  Formattage de l'URL passée
         */
        $url = trim($url); // Suppression des espaces si il y en a (ça ne devrait pas)
        $url = stripslashes($url); // Suppression des anti-slash
        $url = strtolower($url); // converti tout en minuscules

        /**
         *  Si l'URL contient des caractères non-autorisés ou si elle ne commence pas par http(s) alors elle est invalide
         */
        if (!is_alphanumdash($url, array('=', ':', '/', '.', '?', '$', '&'))) {
            printAlert('Erreur : l\'URL du repo source est invalide', 'error');
            slidediv_byid('sourcesDiv');
            return;
        }
        if (!preg_match('#^https?://#', $url)) {
            printAlert('Erreur : l\'URL du repo source est invalide', 'error');
            slidediv_byid('sourcesDiv');
            return;
        }

        /*if (!is_alphanumdash($url, array(':', '/', '.', '?', '$', '&', '=')) OR !preg_match('#^https?://#', $url)) {
            printAlert('Erreur : l\'URL du repo source est invalide', 'error');
            slidediv_byid('sourcesDiv');
            return;
        }*/

        /**
         *  Sur Redhat/Centos, on crée un fichier dans /etc/yum.repos.d/repomanager/
         */
        if ($OS_FAMILY == "Redhat") {
            if (file_exists("${REPOMANAGER_YUM_DIR}/${name}.repo")) {
                printAlert("Un repo source <b>$name</b> existe déjà", 'error');
                slidediv_byid('sourcesDiv');
                return;
            }

            /**
             *  On récupère la clé GPG, il s'agit soit une clé existante, soit au format url, soit au format texte à importer. Si les deux sont renseignés on affiche une erreur (c'est l'un ou l'autre)
             */
            if (!empty($_POST['existingGpgKey']) AND !empty($_POST['gpgKeyURL']) AND !empty($_POST['gpgKeyText'])) {
                printAlert("Erreur : Vous ne pouvez pas renseigner plusieurs types de clé GPG à la fois", 'error');
                slidediv_byid('sourcesDiv');
                return;

            /**
             *  Cas où c'est une clé existante
             */
            } elseif (!empty($_POST['existingGpgKey'])) { // On recupère le nom de la clé existante
                $existingGpgKey = validateData($_POST['existingGpgKey']);

                /**
                 *  Si la clé renseignée n'existe pas, on quitte
                 */
                if (!file_exists("$RPM_GPG_DIR/$existingGpgKey")) {
                    printAlert('Erreur : la clé GPG renseignée n\'existe pas', 'error');
                    slidediv_byid('sourcesDiv');
                    return;
                }

            /**
             *  Cas où c'est une URL vers une clé GPG
             */
            } elseif (!empty($_POST['gpgKeyURL'])) { // On recupère l'url de la clé gpg
                $gpgKeyURL = validateData($_POST['gpgKeyURL']);

                /**
                 *  Formattage de l'URL
                 */
                $gpgKeyURL = trim($gpgKeyURL); // Suppression des espaces si il y en a (ça ne devrait pas)
                $gpgKeyURL = stripslashes($gpgKeyURL); // Suppression des anti-slash

                /**
                 *  Si l'URL contient des caractères invalide alors on quitte 
                 */
                if (!is_alphanumdash($gpgKeyURL, array(':', '/', '.'))) {
                    printAlert('Erreur : l\'URL de la clé GPG contient des caractères invalides', 'error');
                    slidediv_byid('sourcesDiv');
                    return;
                }

                /**
                 *  Si l'URL ne commence pas par http(s) ou par file:// (pour désigner un fichier sur le serveur) alors elle est invalide
                 */
                if (!preg_match('#^https?://#', $gpgKeyURL) AND !preg_match('/^file:\/\/\//', $gpgKeyURL)) {
                    printAlert('Erreur : l\'URL de la clé GPG est invalide', 'error');
                    slidediv_byid('sourcesDiv');
                    return;
                }

            /**
             *  Cas où on importe une clé au format texte ASCII
             */
            } elseif (!empty($_POST['gpgKeyText'])) { // On récupère la clé gpg au format texte
                $gpgKeyText = validateData($_POST['gpgKeyText']);

                /**
                 *  Si le 'pavé' de texte contient des caractères invalide alors on quitte 
                 */
                if (!is_alphanum($gpgKeyText, array('-', '=', '+', '/', ' ', ':', '.', '(', ')'))) { // on autorise tous les caractères qu'on peut possiblement retrouver dans une clé GPG au format ASCII
                    printAlert('Erreur : l\'URL de la clé GPG contient des caractères invalides', 'error');
                    slidediv_byid('sourcesDiv');
                    return;
                }

                /**
                 *  Si le contenu qu'on tente d'importer est un fichier sur le disque alors on quitte
                 */
                if (file_exists($gpgKeyText)) {
                    printAlert('Erreur : la clé GPG contient des caractères invalides', 'error');
                    slidediv_byid('sourcesDiv');
                    return;
                }

                /**
                 *  On importe la clé gpg au format texte dans le répertoire par défaut où rpm stocke ses clés gpg importées (et dans un sous-répertoire repomanager)
                 */
                $newGpgFile = "REPOMANAGER-RPM-GPG-KEY-${name}";
                if (file_exists("${RPM_GPG_DIR}/${newGpgFile}")) {
                    // Affichage d'un message et rechargement de la div
                    printAlert("Erreur : un fichier GPG du même nom existe déjà dans le trousseau de repomanager", 'error'); // on n'incrémente pas error ici car l'import de la clé peut se refaire à part ensuite
                    slidediv_byid('sourcesDiv');
                    return;
                } else {
                    file_put_contents("${RPM_GPG_DIR}/${newGpgFile}", $gpgKeyText | LOCK_EX); // ajout de la clé gpg à l'intérieur du fichier gpg
                }
            }

            /**
             *  Récupération du type d'URL
             */
            $addSourceUrlType = validateData($_POST['addSourceUrlType']);
            if ($addSourceUrlType != 'baseurl' AND $addSourceUrlType != 'mirrorlist' AND $addSourceUrlType != 'metalink') {
                printAlert('Erreur : le type d\'URL renseigné est invalide', 'error');
                return;
            }

            /**
             *  On génère la conf qu'on va injecter dans le fichier de repo
             */
            $newRepoFileConf  = "[$name]".PHP_EOL;
            $newRepoFileConf .= 'enabled=1'.PHP_EOL;
            $newRepoFileConf .= "name=Repo source $name sur $WWW_HOSTNAME".PHP_EOL;

            /**
             *  Forge l'url en fonction de son type (baseurl, mirrorlist...)
             */
            $newRepoFileConf .= "${addSourceUrlType}=${url}".PHP_EOL;

            /*if ($addSourceUrlType == "baseurl") {
                $newRepoFileConf .= "baseurl=${url}".PHP_EOL;
            }
            if ($addSourceUrlType == "mirrorlist") {
                $newRepoFileConf .= "mirrorlist=${url}".PHP_EOL;
            }
            if ($addSourceUrlType == "metalink") {
                $newRepoFileConf .= "metalink=${url}".PHP_EOL;
            }*/

            /**
             *  Si on a renseigné une clé GPG alors on active gpgcheck
             */
            if (!empty($existingGpgKey) OR !empty($gpgKeyURL) OR !empty($gpgKeyText)) {
                $newRepoFileConf .= "gpgcheck=1".PHP_EOL;
            }

            /**
             *  On indique le chemin vers la clé GPG existante si indiqué
             */
            if (!empty($existingGpgKey)) {
                $newRepoFileConf .= "gpgkey=file://${RPM_GPG_DIR}/${existingGpgKey}".PHP_EOL;
            }
            /**
             *  On indique l'url vers la clé GPG si indiqué
             */
            if (!empty($gpgKeyURL)) {
                $newRepoFileConf .= "gpgkey=${gpgKeyURL}".PHP_EOL;
            }
            /**
             *  On indique le chemin vers la clé GPG importée
             */
            if (!empty($gpgKeyText)) {
                $newRepoFileConf .= "gpgkey=file://${RPM_GPG_DIR}/${newGpgFile}".PHP_EOL;
            }

            /**
             *  Ecriture de la configuration dans le fichier de repo source
             */
            file_put_contents("${REPOMANAGER_YUM_DIR}/${name}.repo", $newRepoFileConf.PHP_EOL);
        }


        /**
         *  Sur Debian, on ajoute l'url en BDD
         */
        if ($OS_FAMILY == "Debian") {
            /**
             *  On vérifie qu'un repo source du même nom n'existe pas déjà en BDD
             */
            $stmt = $this->db->prepare("SELECT Name FROM Sources WHERE Name=:name");
            $stmt->bindValue(':name', $name);
            $result = $stmt->execute();
            $count = 0;
            while ($row = $result->fetchArray()) $count++;
            if ($count != 0) {
                printAlert("Erreur : un repo source <b>$name</b> existe déja", 'error');
                slidediv_byid('sourcesDiv');
                return;
            }

            /**
             *  Si une clé GPG a été transmise alors on l'importe
             */
            if (!empty($_POST['addSourceGpgKey'])) {
                $addSourceGpgKey = validateData($_POST['addSourceGpgKey']);
                $addSourceGpgKey = trim($addSourceGpgKey);

                /**
                 *  Si le 'pavé' de texte contient des caractères invalide alors on quitte 
                 */
                if (!is_alphanum($addSourceGpgKey, array('-', '=', '+', '/', ' ', ':', '.', '(', ')', "\n", "\r"))) { // on autorise tous les caractères qu'on peut possiblement retrouver dans une clé GPG au format ASCII
                    printAlert('Erreur : la clé GPG contient des caractères invalides', 'error');
                    slidediv_byid('sourcesDiv');
                    return;
                }

                /**
                 *  Si le contenu qu'on tente d'importer est un fichier sur le disque alors on quitte
                 */
                if (file_exists($addSourceGpgKey)) {
                    printAlert('Erreur : la clé GPG contient des caractères invalides', 'error');
                    slidediv_byid('sourcesDiv');
                    return;
                }

                /**
                 *  Création d'un fichier temporaire dans lequel on injecte la clé GPG à importer
                 */
                $gpgTempFile = "${TEMP_DIR}/repomanager_newgpgkey.tmp";
                file_put_contents($gpgTempFile, "$addSourceGpgKey");

                /**
                 *  Import du fichier temporaire dans le trousseau de repomanager
                 */
                exec("gpg --no-default-keyring --keyring ${GPGHOME}/trustedkeys.gpg --import $gpgTempFile", $output, $result);

                /**
                 *  Si erreur lors de l'import, on affiche un message d'erreur
                 */
                if ($result != 0) {
                    printAlert("Erreur lors de l'import de la clé GPG", 'error');
                    if ($DEBUG_MODE == "yes") print_r($output); // affichage du retour de la commande exec si DEBUG_MODE est activé
                    unlink($gpgTempFile); // suppression du fichier temporaire
                    return;
                }

                unlink($gpgTempFile); // suppression du fichier temporaire               
            }

            /*if (!empty($addSourceGpgKey)) {
                $this->db->prepare("INSERT INTO sources ('Name', 'Url', 'Gpgkey') VALUES ('$name', '$url', '')");
            } else {
                $this->db->prepare("INSERT INTO sources ('Name', 'Url') VALUES ('$name', '$url')");
            }*/

            $stmt = $this->db->prepare("INSERT INTO sources ('Name', 'Url') VALUES (:name, :url)");
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':url', $url);
            $stmt->execute();
            unset($stmt);
        }

        printAlert("Le repo source <b>$name</b> a été ajouté", 'success');
        slidediv_byid('sourcesDiv');
        showdiv_byid("sourceConfigurationDiv-${name}");
    }

    /**
     *  Supprimer un repo source
     */
    public function delete(string $name) {
        global $OS_FAMILY;
        global $REPOMANAGER_YUM_DIR;

        if ($OS_FAMILY == "Redhat") {
            if (file_exists("$REPOMANAGER_YUM_DIR/${name}.repo")) {
                if (!unlink("$REPOMANAGER_YUM_DIR/${name}.repo")) {
                    printAlert("Erreur lors de la suppression du repo source <b>$name</b>", 'error');
                    return;
                }
            }
        }
        if ($OS_FAMILY == "Debian") {
            $stmt = $this->db->prepare("DELETE FROM sources WHERE Name=:name");
            $stmt->bindValue(':name', $name);
            $stmt->execute();
            unset($stmt);
        }

        printAlert("Le repo source <b>$name</b> a été supprimé", 'success');
        slidediv_byid('sourcesDiv');
    }

    /**
     *  Renommer un repo source
     */
    public function rename(string $newName, string $newUrl = '') {
        global $OS_FAMILY;
        global $REPOMANAGER_YUM_DIR;

        /**
         *  On vérifie que le nouveau nom ne contient pas de caractères invalides
         */
        if (!is_alphanumdash($newName)) {
            printAlert('Erreur : le nom contient des caractères invalides', 'error');
            slidediv_byid('sourcesDiv');
            return;
        }

        /**
         *  Sur Redhat, le renommage consiste à changer le nom du fichier de repo source ainsi que le nom du repo à l'intérieur de ce fichier
         */
        if ($OS_FAMILY == "Redhat") {
            /**
             *  Si un fichier portant le même nom que $newName existe déjà alors on ne peut pas renommer le fichier
             */
            if (file_exists("$REPOMANAGER_YUM_DIR/${newName}.repo")) {
                printAlert("Erreur : un repo source <b>$newName<b> existe déjà", 'error');
                slidediv_byid('sourcesDiv');
                return;
            }

            /**
             *  Renommage
             */
            if (file_exists("$REPOMANAGER_YUM_DIR/{$this->name}.repo")) {
                rename("$REPOMANAGER_YUM_DIR/{$this->name}.repo", "$REPOMANAGER_YUM_DIR/${newName}.repo");
                $content = file_get_contents("$REPOMANAGER_YUM_DIR/${newName}.repo");
                $content = str_replace("[$this->name]", "[$newName]", $content);
                $content = str_replace("Repo source $this->name", "Repo source $newName", $content);
                file_put_contents("$REPOMANAGER_YUM_DIR/${newName}.repo", $content);
                unset($content);
            }
        }

        /**
         *  Sur Debian, les repos sources sont stockés en BDD
         */
        if ($OS_FAMILY == "Debian") {
            /**
             *  Formattage de l'URL passée
             */
            $newUrl = trim($newUrl); // Suppression des espaces si il y en a (ça ne devrait pas)
            $newUrl = stripslashes($newUrl); // Suppression des anti-slash
            $newUrl = strtolower($newUrl); // converti tout en minuscules

            /**
             *  Si l'URL contient des caractères non-autorisés ou si elle ne commence pas par http(s) alors elle est invalide
             */
            if (!is_alphanumdash($newUrl, array(':', '/', '.')) OR !preg_match('#^https?://#', $newUrl)) {
                printAlert('Erreur : l\'URL saisie est invalide', 'error');
                slidediv_byid('sourcesDiv');
                return;
            }

            $stmt = $this->db->prepare("UPDATE sources SET Name=:newname, Url=:url WHERE Name=:name");
            $stmt->bindValue(':newname', $newName);
            $stmt->bindValue(':url', $newUrl);
            $stmt->bindValue(':name', $this->name);
            $stmt->execute();
            unset($stmt);
        }
        
        printAlert('Modifications prises en compte', 'success');

        slidediv_byid('sourcesDiv');
        showdiv_byid("sourceConfigurationDiv-${newName}");
    }

    /**
     *  Modifier la configuration d'un repo source
     */
    public function configure(string $sourceName, array $option, string $comments) {
        global $REPOMANAGER_YUM_DIR;
        $generalError = 0;

        $sourceFile = "$REPOMANAGER_YUM_DIR/${sourceName}.repo"; // Le fichier dans lequel on va écrire
        $options = $_POST['option']; // Les options à inclure dans le fichier

        /**
         *  On initialise le contenu du fichier en mettant le nom du repo source en crochet (standard des fichiers .repo)
         */
        $content = "[${sourceName}]".PHP_EOL;

        foreach ($options as $option) {
            $optionError = 0;

            /**
             *  On vérifie que le nom de l'option est valide, càd qu'il ne contient pas de caractère spéciaux
             */
            $optionName = validateData($option['name']);
            if (!is_alphanumdash($optionName)) $optionError++;

            /**
             *  Pas de validateData sur la valeur car celle-ci peut contenir certains caractères légitimes (par exemple une url avec des slash)
             *  Certaines valeurs peuvent être vides (ex: les boutons slides renvoient une valeur null si décochés)
             */
            if (!empty($option['value'])) {

                /**
                 *  On récupère la valeur du paramètre si celle-ci est non-vide
                 */
                $optionValue = $option['value'];

            } else {
                /**
                 *  Si la valeur est vide et que le nom du paramètre est 'enabled' ou 'gpgcheck', alors il faut set une valeur de 0, sinon on set la valeur à ''
                 */
                if ($optionName == 'gpgcheck' OR $optionName == 'enabled') {
                    $optionValue = '0';
                } else {
                    $optionValue = ''; // au moins ça permettra d'afficher un input text vide dans lequel l'utilisateur pourra renseigner une valeur
                }
            }

            if (!empty($optionValue)) {
                /**
                 *  Dans le cas où le paramètre se nomme baseurl, mirrorlist ou metalink, on accepte + de caractères spéciaux car sa valeur est souvent une url pouvant comporter des slashs, des ? et des $
                 *  Note : ne pas autoriser les parenthèses pour éviter l'injection de code et la tentative d'utilisation de la fonction exec() par exemple. Si possible voir pour échapper le caractère $
                 */
                if ($optionName == 'baseurl' OR $optionName == 'mirrorlist' OR $optionName == 'metalink') {
                    $optionValue = trim($optionValue); // Suppression des espaces si il y en a (ça ne devrait pas)
                    $optionValue = stripslashes($optionValue); // Suppression des anti-slash
                    $optionValue = strtolower($optionValue); // convertit tout en minuscules
                    if (!is_alphanumdash($optionValue, array(':', '/', '.', '?', '$', '&', '='))) $optionError++;
                    if (!preg_match('#^https?://#', $optionValue)) $optionError++; // Si la valeur ne commence pas par http(s):// alors le paramètre est invalide

                /**
                 *  Paramètre gpgkey
                 */
                } elseif ($optionName == 'gpgkey') {
                    $optionValue = trim($optionValue); // Suppression des espaces si il y en a (ça ne devrait pas)
                    $optionValue = stripslashes($optionValue); // Suppression des anti-slash
                    if (!is_alphanumdash($optionValue, array(':', '/', '.'))) $optionError++; // la clé gpg peut être un fichier ou une url, donc on accepte certains caractères
                    if (!preg_match('#^https?://#', $optionValue) AND !preg_match('/^file:\/\/\//', $optionValue)) $optionError++; // Si la valeur ne commence pas par http(s):// ou par file:/// alors le paramètre est invalide

                /**
                 *  Tous les autres types paramètres
                 */
                } else {
                    if (!is_alphanumdash($optionValue, array('.', ' '))) $optionError++;
                    if (preg_match('#^/#', $optionValue)) $optionError++; // Si la valeur commence par un slash, ce n'est pas bon... cela pourrait être un chemin de fichier sur le système
                }

                /**
                 *  Si le nom du paramètre est 'gpgcheck' ou 'enabled' alors sa valeur ne peut être que '1' ou '0'
                 *  Si la valeur est non-vide et qu'elle vaut 'yes' alors on la set à '1' conformément à la syntaxe des fichiers .repo, sinon dans tous les autres cas on la set à '0' (fait plus haut)
                 */
                if ($optionName == 'gpgcheck' OR $optionName == 'enabled' AND !empty($optionValue) AND $optionValue == 'yes') {
                    $optionValue = '1';
                }

                /**
                 *  Autre vérifications : si la valeur est un chemin vers un fichier ou un répertoire du système alors on ne l'accepte pas (excepté si le chemin commence par file:// ce qui est un chemin legitime pour préciser le chemin vers la clé GPG par exemple)
                 *  On ignore cette vérification si il s'agit du paramètre gpgkey car celui-ci indique dans certains cas un fichier sur le système
                 */
                if ($optionName != 'gpgkey' AND file_exists($optionValue)) $optionError++;
            }

            /**
             *  Si il n'y a pas eu d'erreurs jusque là alors on forge la ligne du paramètre avec son nom et sa valeur, séparés par un égal '='
             *  Sinon on forge la même ligne mais en laissant la valeur vide afin que l'utilisateur puisse la resaisir
             */
            if ($optionError == 0) {
                $content .= $optionName . "=" . $optionValue . PHP_EOL;
            } else {
                $content .= $optionName . "=" . '' . PHP_EOL;
                ++$generalError;
            }
        }

        /**
         *  Si des commentaires ont été saisis dans le bloc de textarea 'Notes' alors on ajoute un dièse # avant chaque ligne afin de l'inclure en tant que commentaire dans le fichier
         */
        if (!empty($comments)) {
            $comments = explode(PHP_EOL, validateData($comments));
            foreach ($comments as $comment) {
                $content .= "#".$comment;
            }
        }

        file_put_contents("$REPOMANAGER_YUM_DIR/${sourceName}.repo", $content);

        if ($generalError == 0) printAlert('Modifications prises en compte', 'success');
        if ($generalError != 0) printAlert('Erreur : des caractères invalides ont été saisis', 'error');

        slidediv_byid('sourcesDiv');
        showdiv_byid("sourceConfigurationDiv-${sourceName}");

        unset($content);
    }


/**
 *  LISTER TOUS LES REPOS SOURCES
 */
    public function listAll() {
        $query = $this->db->query("SELECT * FROM sources");

        while ($datas = $query->fetchArray(SQLITE3_ASSOC)) $sources[] = $datas;

        /**
         *  Retourne un array avec les noms des groupes
         */
        if (!empty($sources)) return $sources;
    }
}
?>