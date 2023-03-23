<?php

namespace Controllers;

use Exception;

/**
 *  Classe regroupant quelques fonctions communes / génériques
 */

class Common
{
    private $validColors;

    /**
     *  Return an array with all editors GPG pub keys that were imported into repomanager keyring
     */
    public static function getGpgTrustedKeys()
    {
        $knownGpgKeys = array();

        $myprocess = new Process("/usr/bin/gpg --homedir " . GPGHOME . " --no-default-keyring --keyring " . GPGHOME . "/trustedkeys.gpg --list-key --fixed-list-mode --with-colons --with-fingerprint | sed 's/^pub/\\npub/g' | grep -v '^tru:'");
        $myprocess->execute();
        $content = $myprocess->getOutput();
        $myprocess->close();

        /**
         *  Parsing retrieved content
         */
        if (!empty($content)) {
            $gpgKeys = explode(PHP_EOL.PHP_EOL, $content);

            foreach ($gpgKeys as $gpgKey) {
                $gpgKeyId = '';
                $gpgKeyName = '';
                $gpgKey = explode(PHP_EOL, $gpgKey);

                foreach ($gpgKey as $gpgKeyRow) {
                    /**
                     *  Get GPG key Id from fpr row
                     */
                    if (preg_match('/^fpr:/', $gpgKeyRow)) {
                        $gpgKeyId = preg_split('/:/', $gpgKeyRow);
                        $gpgKeyId = trim($gpgKeyId[9]);
                    }

                    /**
                     *  Retrieve GPG key name from uid row
                     */
                    if (preg_match('/^uid:/', $gpgKeyRow)) {
                        $gpgKeyName = preg_split('/:/', $gpgKeyRow);
                        $gpgKeyName = trim($gpgKeyName[9]);
                    }

                    /**
                     *  If both name and Id have been found, had them to the global array
                     */
                    if (!empty($gpgKeyId) and !empty($gpgKeyName)) {
                        $knownGpgKeys[] = array('id' => $gpgKeyId, 'name' => $gpgKeyName);

                        /**
                         *  Only reset Id because a key can have one name (uid) and multiple Id, so do not reset the name until the next key
                         */
                        $gpgKeyId = '';
                    }
                }
            }
        }

        unset($content, $gpgKeys);

        return $knownGpgKeys;
    }

    /**
     *  Get content between two patterns strings
     */
    public static function getContentBetween(string $content, string $start, string $end)
    {
        $n = explode($start, $content);
        $result = array();

        foreach ($n as $val) {
            $pos = strpos($val, $end);
            if ($pos !== false) {
                $result[] = substr($val, 0, $pos);
            }
        }

        return $result;
    }

    /**
     *  Fonction de vérification / conversion des données envoyées par formulaire
     */
    public static function validateData($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    /**
     *  Fonction de vérification du format d'une adresse email
     */
    public static function validateMail(string $mail)
    {
        $mail = trim($mail);

        if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            return true;
        }

        return false;
    }

    /**
     *  Vérifie que la chaine passée ne contient que des chiffres ou des lettres
     */
    public static function isAlphanum(string $data, array $additionnalValidCaracters = [])
    {
        /**
         *  Si on a passé en argument des caractères supplémentaires à autoriser alors on les ignore dans le test en les remplacant temporairement par du vide
         */
        if (!empty($additionnalValidCaracters)) {
            if (!ctype_alnum(str_replace($additionnalValidCaracters, '', $data))) {
                return false;
            }

        /**
         *  Si on n'a pas passé de caractères supplémentaires alors on teste simplement la chaine avec ctype_alnum
         */
        } else {
            if (!ctype_alnum($data)) {
                return false;
            }
        }

        return true;
    }

    /**
     *  Vérifie que la chaine passée ne contient que des chiffres ou des lettres, un underscore ou un tiret
     *  Retire temporairement les tirets et underscore de la chaine passée afin qu'elle soit ensuite testée par la fonction PHP ctype_alnum
     */
    public static function isAlphanumDash(string $data, array $additionnalValidCaracters = [])
    {
        /**
         *  Si une chaine vide a été transmise alors c'est valide
         */
        if (empty($data)) {
            return true;
        }

        /**
         *  array contenant quelques exceptions de caractères valides
         */
        $validCaracters = array('-', '_');

        /**
         *  Si on a passé en argument des caractères supplémentaires à autoriser alors on les ajoute à l'array $validCaracters
         */
        if (!empty($additionnalValidCaracters)) {
            $validCaracters = array_merge($validCaracters, $additionnalValidCaracters);
        }

        if (!ctype_alnum(str_replace($validCaracters, '', $data))) {
            return false;
        }

        return true;
    }

    /**
     *  Suppression du "faux" cache ram du serveur
     *  2 cas possibles :
     *  il s'agit d'un répertoire classique sur le disque
     *  ou il s'agit d'un lien symbolique vers /dev/smh (en ram)
     */
    public static function clearCache()
    {
        /**
         *  Suppression de tous les fichiers commencant par 'repomanager-repos-list' dans le répertoire de cache
         */
        $files = glob(WWW_CACHE . '/repomanager-repos-*');

        if (!empty($files)) {
            foreach ($files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }

    public static function generateCache(string $role)
    {
        ob_start();
        include(ROOT . '/views/includes/repos-list.inc.php');

        $content = ob_get_clean();
        file_put_contents(WWW_CACHE . '/repomanager-repos-list-' . $role . '.html', $content);
    }

    /**
     *  Fonction permettant d'afficher une bulle d'alerte en bas de l'écran
     */
    public static function printAlert(string $message, string $alertType = null)
    {
        if ($alertType == "error") {
            echo '<div class="alert-error">';
        }
        if ($alertType == "success") {
            echo '<div class="alert-success">';
        }
        if (empty($alertType)) {
            echo '<div class="alert">';
        }

        echo '<span>' . $message . '</span>';
        echo '</div>';

        echo '<script type="text/javascript">';
        echo '$(document).ready(function () {';
        echo 'window.setTimeout(function() {';
        if ($alertType == "error" or $alertType == "success") {
            echo "$('.alert-${alertType}').fadeTo(1500, 0).slideUp(1000, function(){";
        } else {
            echo "$('.alert').fadeTo(1500, 0).slideUp(1000, function(){";
        }
        echo '$(this).remove();';
        echo '});';
        echo '}, 2500);';
        echo '});';
        echo '</script>';
    }

    /**
     *  Fonction affichant un message de confirmation avant de supprimer
     *  $message = le message à afficher
     *  $url = lien GET vers la page de suppression
     *  $divID = un id unique du div caché contenant le message et les bouton supprimer ou annuler
     *  $aID = une class avec un ID unique du bouton cliquable permettant d'afficher/fermer la div caché. Attention le bouton d'affichage doit être avant l'appel de cette fonction.
     */
    public static function deleteConfirm(string $message, string $url, $divID, $aID)
    {
        echo "<div id=\"$divID\" class=\"hide confirmAlert\">";
            echo "<span class=\"confirmAlert-message\">$message</span>";
            echo '<div class="confirmAlert-buttons-container">';
                echo "<a href=\"$url\"><span class=\"btn-doConfirm\">Delete</span></a>";
                echo "<span class=\"$aID btn-doCancel pointer\">Cancel</span>";
            echo '</div>';

        echo "<script>";
        echo "$(document).ready(function(){";
        echo "$(\".$aID\").click(function(){";
        echo "$(\"div#$divID\").slideToggle(150);";
        echo '$(this).toggleClass("open");';
        echo "});";
        echo "});";
        echo "</script>";
        echo '</div>';
        unset($message, $url, $divID, $aID);
    }

    /**
     *  Affiche une erreur générique ou personnalisée lorsqu'il y a eu une erreur d'exécution d'une requête dans la base de données
     *  Ajoute une copie de l'erreur dans le fichier de logs 'exceptions'
     */
    public static function dbError(string $exception = null)
    {
        /**
         *  Date et heure de l'évènement
         */
        $content = PHP_EOL . date("Y-m-d H:i:s") . PHP_EOL;

        /**
         *  Récupération du nom du fichier ayant fait appel à cette fonction
         */
        $content .= 'File : ' . debug_backtrace()[0]['file'] . PHP_EOL;

        /**
         *  Si une exception a été catchée, on va l'ajouter au contenu
         */
        if (!empty($exception)) {
            $content .= 'Error catched ¬' . PHP_EOL . $exception . PHP_EOL;
        }

        /**
         *  Ajout du contenu au fichier de log
         */
        $content .= '___________________________' . PHP_EOL;
        file_put_contents(EXCEPTIONS_LOG, $content, FILE_APPEND);

        /**
         *  Lancement d'une exception qui sera catchée par printAlert
         *  Si le mode debug est activé alors on affiche l'exception dans le message d'erreur
         */
        if (!empty($exception) and DEBUG_MODE == 'true') {
            throw new Exception('An error occured while executing request in database <br>' . $exception . '<br>');
        } else {
            throw new Exception('An error occured while executing request in database');
        }
    }

    /**
     *  Colore l'environnement d'une étiquette rouge ou blanche
     */
    public static function envtag($env, $css = null)
    {
        if ($env == LAST_ENV) {
            $class = 'last-env';
        } else {
            $class = 'env';
        }

        if ($css == 'fit') {
            $class .= '-fit';
        }

        return '<span class="' . $class . '">' . $env . '</span>';
    }

    public static function writeToIni($file, $array = [])
    {
        // check first argument is string
        if (!is_string($file)) {
            throw new \InvalidArgumentException('Function argument 1 must be a string.');
        }

        // check second argument is array
        if (!is_array($array)) {
            throw new \InvalidArgumentException('Function argument 2 must be an array.');
        }

        // process array
        $data = array();
        foreach ($array as $key => $val) {
            if (is_array($val)) {
                $data[] = "[$key]";
                foreach ($val as $skey => $sval) {
                    if (is_array($sval)) {
                        foreach ($sval as $_skey => $_sval) {
                            if (is_numeric($_skey)) {
                                $data[] = $skey . '[] = ' . (is_numeric($_sval) ? $_sval : (ctype_upper($_sval) ? $_sval : '"' . $_sval . '"'));
                            } else {
                                $data[] = $skey . '[' . $_skey . '] = ' . (is_numeric($_sval) ? $_sval : (ctype_upper($_sval) ? $_sval : '"' . $_sval . '"'));
                            }
                        }
                    } else {
                        $data[] = $skey . ' = ' . (is_numeric($sval) ? $sval : (ctype_upper($sval) ? $sval : '"' . $sval . '"'));
                    }
                }
            } else {
                $data[] = $key . ' = ' . (is_numeric($val) ? $val : (ctype_upper($val) ? $val : '"' . $val . '"'));
            }
            // empty line
            $data[] = null;
        }

        // open file pointer, init flock options
        $fp = fopen($file, 'w');
        $retries = 0;
        $max_retries = 100;
        if (!$fp) {
            return false;
        }

        // loop until get lock, or reach max retries
        do {
            if ($retries > 0) {
                usleep(rand(1, 5000));
            }
            $retries += 1;
        } while (!flock($fp, LOCK_EX) && $retries <= $max_retries);

        // couldn't get the lock
        if ($retries == $max_retries) {
            return false;
        }

        // got lock, write data
        fwrite($fp, implode(PHP_EOL, $data) . PHP_EOL);

        // release lock
        flock($fp, LOCK_UN);
        fclose($fp);
        return true;
    }

    /**
     *  Indique si un répertoire est vide ou non
     */
    public static function dirIsEmpty($dir)
    {
        $handle = opendir($dir);
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                closedir($handle);
                return false;
            }
        }

        closedir($handle);
        return true;
    }

    /**
     *  Renvoi true si le role spécifié ou la session utilisateur en cours est administrateur
     */
    public static function isadmin()
    {
        if (defined('GENERATE_CACHE_ROLE')) {
            if (GENERATE_CACHE_ROLE === 'super-administrator' or GENERATE_CACHE_ROLE === 'administrator') {
                return true;
            }

            return false;
        }

        if ($_SESSION['role'] === 'super-administrator' or $_SESSION['role'] === 'administrator') {
            return true;
        }

        return false;
    }

    /**
     *  Génère un nombre aléatoire en 10000 et 99999
     */
    public static function generateRandom()
    {
        return mt_rand(10000, 99999);
    }

    /**
     *  Génère une chaine de caractères aléatoires
     */
    public static function randomString(int $length)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyz';
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $randomString .= $characters[$index];
        }

        return $randomString;
    }

    /**
     *  Generate random strong string
     */
    public static function randomStrongString(int $length)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*%-_{}()';
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $randomString .= $characters[$index];
        }

        return $randomString;
    }

    /**
     *  Get a random color from a valid hex colors list
     */
    public function randomColor()
    {
        /**
         *  Refill available color list if there are no more available
         */
        if (empty($this->validColors)) {
            $this->validColors = array('rgb(75, 192, 192)', '#5993ec', '#e0b05f', '#24d794', '#EFBDEB', '#F85A3E', '#8EB1C7', '#1AC8ED', '#E9D758');
        }

        $randomColorId = array_rand($this->validColors, 1);
        $randomColor = $this->validColors[$randomColorId];
        unset($this->validColors[$randomColorId]);

        return $randomColor;
    }

    /**
     *  Convertit une durée microtime au format HHhMMmSSs
     */
    public static function convertMicrotime(string $duration)
    {
        $hours = (int)($duration/60/60);
        $minutes = (int)($duration/60)-$hours*60;
        $seconds = (int)$duration-$hours*60*60-$minutes*60;

        $time = '';

        if (!empty($hours)) {
            $time = strval($hours) . 'h';
        }
        if (!empty($minutes)) {
            $time .= strval($minutes) . 'm';
        }
        if (!empty($seconds)) {
            $time .= $seconds . 's';
        }

        return $time;
    }

    /**
     *  Modification des paramètres d'affichage de la liste des repos
     */
    public static function configureReposListDisplay(string $printRepoSize, string $printRepoType, string $printRepoSignature, string $cacheReposList)
    {
        /**
         *  On vérifie que la valeur des paramètres est 'yes' ou 'no'
         */
        if ($printRepoSize != 'yes' and $printRepoSize != 'no') {
            throw new Exception("Repo size display param is invalid");
        }

        if ($printRepoType != 'yes' and $printRepoType != 'no') {
            throw new Exception("Repo type display param is invalid");
        }

        if ($printRepoSignature != 'yes' and $printRepoSignature != 'no') {
            throw new Exception("Repo signature display param is invalid");
        }

        if ($cacheReposList != 'yes' and $cacheReposList != 'no') {
            throw new Exception('Caching param is invalid');
        }

        /**
         *  Ouverture d'une connexion à la base de données
         */
        $myconn = new \Models\Connection('main');

        /**
         *  Modification des paramètres en base de données
         */
        try {
            $stmt = $myconn->prepare("UPDATE repos_list_settings SET print_repo_size = :printRepoSize, print_repo_type = :printRepoType, print_repo_signature = :printRepoSignature, cache_repos_list = :cacheReposList");
            $stmt->bindValue(':printRepoSize', $printRepoSize);
            $stmt->bindValue(':printRepoType', $printRepoType);
            $stmt->bindValue(':printRepoSignature', $printRepoSignature);
            $stmt->bindValue(':cacheReposList', $cacheReposList);
            $stmt->execute();
        } catch (\Exception $e) {
            Common::dbError($e);
        }

        $myconn->close();

        /**
         *  On supprime le cache
         */
        Common::clearCache();

        return true;
    }

    /**
     *  Tri un array par la valeur de clé spécifiée
     */
    public static function groupBy($key, $data)
    {
        $result = array();

        foreach ($data as $val) {
            if (array_key_exists($key, $val)) {
                $result[$val[$key]][] = $val;
            } else {
                $result[""][] = $val;
            }
        }

        return $result;
    }

    /**
     *  Return an array with the list of files founded in specified directory
     */
    public static function findRecursive(string $directoryPath, string $fileExtension = null, bool $returnFullPath = true)
    {
        $foundedFiles = array();

        $dir = new \RecursiveDirectoryIterator($directoryPath . '/');
        $files = new \RecursiveIteratorIterator($dir);

        /**
         *  Find files with specified extension
         */
        if (!empty($files)) {
            foreach ($files as $file) {
                /**
                 *  If an extension has been specified, then check that the file has correct extension
                 */
                if (!empty($fileExtension)) {
                    /**
                     *  If extension is incorrect, then ignore the current file and process the next one
                     */
                    if ($file->getExtension() != $fileExtension) {
                        continue;
                    }
                }

                /**
                 *  By default, return file's fullpath
                 */
                if ($returnFullPath === true) {
                    $foundedFiles[] = $file->getPathname();
                /**
                 *  Else only return filename
                 */
                } else {
                    $foundedFiles[] = $file->getFilename();
                }
            }
        }

        return $foundedFiles;
    }

    /**
     *  Find files and copy them to the specified target directory
     */
    public static function findAndCopyRecursive(string $directoryPath, string $targetDirectoryPath, string $fileExtension = null)
    {
        $foundedFiles = Common::findRecursive($directoryPath, $fileExtension, true);

        /**
         *  Copy files if founded
         */
        if (!empty($foundedFiles)) {
            foreach ($foundedFiles as $foundedFile) {
                $filename = preg_split('#/#', $foundedFile);
                $filename = end($filename);

                if (!copy($foundedFile, $targetDirectoryPath . '/' . $filename)) {
                    throw new Exception('Error: could not copy package ' . $foundedFile . ' to ' . $targetDirectoryPath . '/' . $filename);
                }
            }
        }
    }

    /**
     *  Delete specified directory recursively
     */
    public static function deleteRecursive(string $directoryPath)
    {
        /**
         *  Return true if there is nothing to delete
         */
        if (!is_dir($directoryPath)) {
            return true;
        }

        $myprocess = new Process('rm -rf "' . $directoryPath . '"');
        $myprocess->execute();
        $myprocess->close();

        if ($myprocess->getExitCode() != 0) {
            return false;
        }

        return true;
    }

    /**
     *  Uncompress specified gzip file 'file.gz' to 'file'
     */
    public static function gunzip(string $filename)
    {
        /**
         *  Output file
         */
        $filenameOut = str_replace('.gz', '', $filename);

        /**
         *  Buffer size, read 4kb at a time
         */
        $bufferSize = 4096;

        /**
         *  Open the files (in binary mode)
         */
        $fileOpen = gzopen($filename, 'rb');
        if ($fileOpen === false) {
            throw new Exception('Error while opening gziped file: ' . $filename);
        }

        $fileOut = fopen($filenameOut, 'wb');
        if ($fileOut === false) {
            throw new Exception('Error while opening gunzip output file: ' . $filenameOut);
        }

        /**
         *  Keep repeating until the end of the input file
         */
        while (!gzeof($fileOpen)) {
            // Read buffer-size bytes
            // Both fwrite and gzread and binary-safe
            if (!fwrite($fileOut, gzread($fileOpen, $bufferSize))) {
                throw new Exception('Error while reading gziped file content: ' . $filename);
            }
        }

        /**
         *  Close files
         */
        if (!fclose($fileOut)) {
            throw new Exception('Error while closing gunzip output file: ' . $filenameOut);
        }
        if (!gzclose($fileOpen)) {
            throw new Exception('Error while closing gziped file: ' . $filename);
        }
    }

    /**
     *  Uncompress specified xz file 'file.xz' to 'file'
     */
    public static function xzUncompress(string $filename)
    {
        $myprocess = new Process('/usr/bin/xz --decompress ' . $filename);
        $myprocess->execute();
        $content = $myprocess->getOutput();
        $myprocess->close();

        if ($myprocess->getExitCode() != 0) {
            throw new Exception('Error while uncompressing xz file ' . $filename . ': ' . $content);
        }
    }

    /**
     *  Return true if distant URL file exists
     */
    public static function urlFileExists(string $url, string $sslCertificatePath = null, string $sslPrivateKeyPath = null)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        /**
         *  If a custom SSL certificate and key have been specified
         */
        if (!empty($sslCertificatePath)) {
            curl_setopt($ch, CURLOPT_SSLCERT, $sslCertificatePath);
        }
        if (!empty($sslPrivateKeyPath)) {
            curl_setopt($ch, CURLOPT_SSLKEY, $sslPrivateKeyPath);
        }

        curl_exec($ch);

        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($responseCode != 200) {
            return false;
        }

        return true;
    }

    /**
     *  Get directory size in bytes
     */
    public static function getDirectorySize(string $path)
    {
        $bytestotal = 0;
        $path = realpath($path);

        if (!empty($path) and file_exists($path)) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)) as $object) {
                $bytestotal += $object->getSize();
            }
        }
        return $bytestotal;
    }

    /**
     *  Convert bytes size to the most suitable human format (B, MB, GB...)
     */
    public static function sizeFormat($bytes)
    {
        $kb = 1024;
        $mb = $kb * 1024;
        $gb = $mb * 1024;
        $tb = $gb * 1024;

        if (($bytes >= 0) && ($bytes < $kb)) {
            $value = $bytes;
            $format = 'B';
        } elseif (($bytes >= $kb) && ($bytes < $mb)) {
            $value = ceil($bytes / $kb);
            $format = 'K';
        } elseif (($bytes >= $mb) && ($bytes < $gb)) {
            $value = ceil($bytes / $mb);
            $format = 'M';
        } elseif (($bytes >= $gb) && ($bytes < $tb)) {
            $value = ceil($bytes / $gb);
            $format = 'G';
        } elseif ($bytes >= $tb) {
            $value = ceil($bytes / $tb);
            $format = 'T';
        } else {
            $value = $bytes;
            $format = 'B';
        }

        if ($value >= 1000 and $value <= 1024) {
            $value = 1;

            if ($format == 'B') {
                $format = 'K';
            } elseif ($format == 'K') {
                $format = 'M';
            } elseif ($format == 'M') {
                $format = 'G';
            } elseif ($format == 'G') {
                $format = 'T';
            } elseif ($format == 'T') {
                $format = 'P';
            }
        }

        return $value . $format;
    }

    /**
     *  Print product icon image
     */
    public static function printProductIcon(string $product)
    {
        if (preg_match('/python/i', $product)) {
            return '<img src="assets/icons/products/python.png" class="icon" />';
        } elseif (preg_match('/^code$/i', $product)) {
            return '<img src="assets/icons/products/vscode.png" class="icon" />';
        } elseif (preg_match('/^firefox/i', $product)) {
            return '<img src="assets/icons/products/firefox.png" class="icon" />';
        } elseif (preg_match('/^chrome-$/i', $product)) {
            return '<img src="assets/icons/products/chrome.png" class="icon" />';
        } elseif (preg_match('/^chromium-$/i', $product)) {
            return '<img src="assets/icons/products/chromium.png" class="icon" />';
        } elseif (preg_match('/^brave-browser$/i', $product)) {
            return '<img src="assets/icons/products/brave.png" class="icon" />';
        } elseif (preg_match('/^filezilla/i', $product)) {
            return '<img src="assets/icons/products/filezilla.png" class="icon" />';
        } elseif (preg_match('/^java/i', $product)) {
            return '<img src="assets/icons/products/java.png" class="icon" />';
        } elseif (preg_match('/^fonts-/i', $product)) {
            return '<img src="assets/icons/products/fonts.png" class="icon" />';
        } elseif (preg_match('/^teams$/i', $product)) {
            return '<img src="assets/icons/products/teams.png" class="icon" />';
        } elseif (preg_match('/^teamviewer$/i', $product)) {
            return '<img src="assets/icons/products/teamviewer.png" class="icon" />';
        } elseif (preg_match('/^thunderbird/i', $product)) {
            return '<img src="assets/icons/products/thunderbird.png" class="icon" />';
        } elseif (preg_match('/^vlc/i', $product)) {
            return '<img src="assets/icons/products/vlc.png" class="icon" />';
        } else {
            return '<img src="assets/icons/package.svg" class="icon" />';
        }
    }
}
