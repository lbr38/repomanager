<?php
/**
 *  Fonctions basées sur : https://phpfog.com/directory-trees-with-php-and-jquery/
 */
function tree($path) {
    global $repoPath;

    if ($handle = opendir($path)) {
        echo "<ul>";
        $queue = array(); // initialisation d'un tableau qui contiendra la liste des fichiers d'un répertoire
        
        while (false !== ($file = readdir($handle))) {
            
            if (is_dir("$path/$file") && $file != '.' && $file !='..') {
                printSubDir($file, $path, $queue);
                
            } else if ($file != '.' && $file !='..') {
                /**
                 *  Si c'est un fichier alors on l'ajoute à l'array queue qui contient toute la liste des fichiers du répertoire ou sous-répertoire en cours
                 *  On indexe le nom du fichier $file ainsi que son chemin $path/$file auquel on retire le début du chemin complet afin qu'il ne soit pas visible dans le code source
                 */
                $queue[$file] = str_replace("$repoPath/", '', "$path/$file");
            }
        }

        printQueue($queue);
        echo "</ul>";
    }
}

/**
 *  Affichage de tous les fichiers d'un répertoire
 */
function printQueue($queue) {
    /**
     *  D'abord on trie la liste par ordre alphabétique
     */
    ksort($queue);
    foreach ($queue as $file => $path) {
        printFile($file, $path);
    }
}

/**
 *  Affichage d'un fichier
 */
function printFile($file, $path) {
    /**
     *  On affiche une checkbox permettant de supprimer le fichier seulement si il s'agit d'un fichier .rpm ou .deb
     */
    if (substr($file, -4) == ".rpm" OR substr($file, -4) == ".deb") {
        echo "<li><span class=\"explorer-file\"><input type=\"checkbox\" class=\"packageName-checkbox\" name=\"packageName[]\" value=\"$path\" /> $file</span></li>";
    } else {
        echo "<li><span class=\"explorer-file\"> $file</span></li>";
    }
}

/**
 *  Affichage d'un sous-dossier
 */
function printSubDir($dir, $path) {
    if ($dir == "my_uploaded_packages") { // Si le nom du répertoire est 'my_uploaded_packages' alors on l'affiche en jaune
        echo "<li><span class=\"explorer-toggle\"><img src=\"ressources/icons/folder.png\" class=\"icon\" /> <span class=\"yellowtext\">$dir</span></span>";
    } else {
        echo "<li><span class=\"explorer-toggle\"><img src=\"ressources/icons/folder.png\" class=\"icon\" /> $dir</span>";
    }
    tree("$path/$dir"); // on rappelle la fonction principale afin d'afficher l'arbsorescence de ce sous-dossier
    echo "</li>";
}

/**
 *  Fonction permettant de reconstruire l'array $_FILES['packages'] qui est assez mal foutu et donc compliqué à parcourir
 *  https://www.php.net/manual/fr/features.file-upload.multiple.php
 */
function reArrayFiles(&$file_post) {
    $file_array = array();
    $file_count = count($file_post['name']);
    $file_keys = array_keys($file_post);

    for ($i=0; $i<$file_count; $i++) {
        foreach ($file_keys as $key) {
            $file_array[$i][$key] = $file_post[$key][$i];
        }
    }

    return $file_array;
}

?>