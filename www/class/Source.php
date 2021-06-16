<?php

class Source {
    public $name;
    private $db;

    public function __construct(array $variables = []) {
        extract($variables);

        /**
         *  Instanciation d'une db car on paut avoir besoin de récupérer certaines infos en BDD
         */
        try {
            $this->db = new databaseConnection();
        } catch(Exception $e) {
            die('Erreur : '.$e->getMessage());
        }

        /* Nom */
        if (!empty($sourceName)) { $this->name = $sourceName; }
    }

    public function new(string $name, string $url) {
        global $OS_FAMILY;
        global $REPOMANAGER_YUM_DIR;
        global $WWW_HOSTNAME;
        global $RPM_GPG_DIR;

        /**
         *  Sur Redhat/Centos, on crée un fichier dans /etc/yum.repos.d/repomanager/
         */
        if ($OS_FAMILY == "Redhat") {
            if (file_exists("${REPOMANAGER_YUM_DIR}/${name}.repo")) {
                printAlert("Un repo source <b>$name</b> existe déjà");
                return;
            }

            /**
             *  On récupère la clé gpg, soit une clé existante, soit au format url, soit au format texte à importer. Si les deux sont renseignés on affiche une erreur (c'est l'un ou l'autre)
             */
            if (!empty($_POST['existingGpgKey']) AND !empty($_POST['gpgKeyURL']) AND !empty($_POST['gpgKeyText'])) {
                printAlert("Erreur : Vous ne pouvez pas renseigner plusieurs types de clé GPG à la fois");
                return;
            } elseif (!empty($_POST['existingGpgKey'])) { // On recupère le nom de la clé existante
                $existingGpgKey = validateData($_POST['existingGpgKey']);
            } elseif (!empty($_POST['gpgKeyURL'])) { // On recupère l'url de la clé gpg
                $gpgKeyURL = validateData($_POST['gpgKeyURL']);
            } elseif (!empty($_POST['gpgKeyText'])) { // On récupère la clé gpg au format texte
                $gpgKeyText = validateData($_POST['gpgKeyText']);
                // on importe la clé gpg au format texte dans le répertoire par défaut où rpm stocke ses clés gpg importées (et dans un sous-répertoire repomanager)
                $newGpgFile = "REPOMANAGER-RPM-GPG-KEY-${name}";
                if (file_exists("${RPM_GPG_DIR}/${newGpgFile}")) {
                    // Affichage d'un message et rechargement de la div
                    printAlert("Erreur : un fichier GPG du même nom existe déjà dans le trousseau de repomanager"); // on n'incrémente pas error ici car l'import de la clé peut se refaire à part ensuite
                } else {
                    file_put_contents("${RPM_GPG_DIR}/${newGpgFile}", $gpgKeyText, FILE_APPEND | LOCK_EX); // ajout de la clé gpg à l'intérieur du fichier gpg
                }
            }

            /**
             *  On génère la conf qu'on va injecter dans le fichier de repo
             */
            $addSourceUrlType = validateData($_POST['addSourceUrlType']);
            $newRepoFileConf  = "[$name]";
            $newRepoFileConf  = "${newRepoFileConf}\nenabled=1";
            $newRepoFileConf  = "${newRepoFileConf}\nname=Repo $name sur ${WWW_HOSTNAME}";
            // Forge l'url en fonction de son type (baseurl, mirrorlist...)
            if ($addSourceUrlType == "baseurl") {
                $newRepoFileConf = "${newRepoFileConf}\nbaseurl=${url}";
            }
            if ($addSourceUrlType == "mirrorlist") {
                $newRepoFileConf = "${newRepoFileConf}\nmirrorlist=${url}";
            }
            if ($addSourceUrlType == "metalink") {
                $newRepoFileConf = "${newRepoFileConf}\nmetalink=${url}";
            }
            // Si on a renseigné une clé gpg, on active gpgcheck
            if (!empty($existingGpgKey) OR !empty($gpgKeyURL) OR !empty($gpgKeyText)) {
                $newRepoFileConf = "${newRepoFileConf}\ngpgcheck=1";
            }
            // On indique le chemin vers la clé GPG existante
            if (!empty($existingGpgKey)) {
                $newRepoFileConf = "${newRepoFileConf}\ngpgkey=file://${RPM_GPG_DIR}/${existingGpgKey}";
            }
            // On indique l'url vers la clé gpg
            if (!empty($gpgKeyURL)) {
                $newRepoFileConf = "${newRepoFileConf}\ngpgkey=${gpgKeyURL}";
            }
            // On indique le chemin vers la clé gpg
            if (!empty($gpgKeyText)) {
                $newRepoFileConf = "${newRepoFileConf}\ngpgkey=file://${RPM_GPG_DIR}/${newGpgFile}";
            }
            file_put_contents("${REPOMANAGER_YUM_DIR}/${name}.repo", $newRepoFileConf.PHP_EOL);
        }

        /**
         *  Sur Debian, on ajoute l'url en BDD
         */
        if ($OS_FAMILY == "Debian") {
            $this->db->exec("INSERT INTO sources ('Name', 'Url') VALUES ('$name', '$url')");
        }

        printAlert("Le repo source <b>$name</b> a été ajouté");
        animatediv_byid('sourcesDiv');
    }

    public function delete(string $name) {
        global $OS_FAMILY;
        global $REPOMANAGER_YUM_DIR;

        if ($OS_FAMILY == "Redhat") {
            if (file_exists("$REPOMANAGER_YUM_DIR/${name}.repo")) {
                if (!unlink("$REPOMANAGER_YUM_DIR/${name}.repo")) {
                    printAlert("Erreur lors de la suppression du repo source <b>$name</b>");
                    return;
                }
            }
        }
        if ($OS_FAMILY == "Debian") {
            $this->db->exec("DELETE FROM sources WHERE Name = '$name'");
        }
        printAlert("Le repo source <b>$name</b> a été supprimé");
        animatediv_byid('sourcesDiv');
    }

    public function rename(string $newName, string $newUrl) {
        global $OS_FAMILY;

        if ($OS_FAMILY == "Redhat") {

        }
        if ($OS_FAMILY == "Debian") {
            $this->db->exec("UPDATE sources SET Name = '$newName', Url = '$newUrl' WHERE Name = '$this->name'");
        }
        printAlert("Modifications prises en compte");
        animatediv_byid('sourcesDiv');
    }

/**
 *  LISTER TOUS LES REPOS SOURCES
 */
    public function listAll() {
        $query = $this->db->query("SELECT * FROM sources");
        while ($datas = $query->fetchArray()) { 
            $sources[] = $datas;
        }
        /**
         *  Retourne un array avec les noms des groupes
         */
        if (!empty($sources)) {
            return $sources;
        }
    }
}
?>