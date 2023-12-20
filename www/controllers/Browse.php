<?php

namespace Controllers;

use Exception;
use Datetime;

class Browse
{
    /**
     *  Based on : https://phpfog.com/directory-trees-with-php-and-jquery/
     */
    public static function tree($path)
    {
        if (is_dir($path)) {
            echo '<ul>';

            /**
             *  Initialiation d'un array qui contiendra la liste de tous les fichiers et sous-répertoire dans le répertoire actuel
             *  Initialize array which will contain a list of the files inside the actual directory
             */
            $queue = array();

            /**
             *  Scanne le répertoire spécifié et traite chaque fichier trouvé
             *  Scan the specified directory then process each file found
             */
            foreach (scandir($path) as $file) {
                /**
                 *  Cas où c'est un répertoire
                 *  Case it is a directory
                 */
                if (is_dir($path . '/' . $file) && $file != '.' && $file != '..') {
                    \Controllers\Browse::printSubDir($file, $path, $queue);
                    continue;
                }

                /**
                 *  Cas où c'est un fichier
                 *  Case it is a file
                 */
                if (is_file($path . '/' . $file) and $file != '.' and $file != '..') {
                    /**
                     *  Si c'est un fichier alors on l'ajoute à l'array queue qui contient toute la liste des fichiers du répertoire ou sous-répertoire en cours
                     *  On indexe le nom du fichier $file ainsi que son chemin $path/$file auquel on retire le début du chemin complet afin qu'il ne soit pas visible dans le code source
                     */
                    $queue[$file] = str_replace(REPOS_DIR . '/', '', "$path/$file");
                }
            }

            \Controllers\Browse::printQueue($queue);

            echo '</ul>';
        }
    }

    /**
     *  Affichage de tous les fichiers d'un répertoire
     */
    public static function printQueue($queue)
    {
        /**
         *  D'abord on trie la liste par ordre alphabétique
         */
        ksort($queue);

        foreach ($queue as $file => $path) {
            \Controllers\Browse::printFile($file, $path);
        }
    }

    /**
     *  Affichage d'un fichier
     */
    public static function printFile($file, $path)
    {
        echo '<li>';
        echo '<div class="explorer-file-pkg header-light-blue"><input type="checkbox" class="packageName-checkbox pointer" name="packageName[]" filename="' . $file . '" path="' . $path . '" /><img src="/assets/icons/package.svg" class="icon" /><span>' . $file . '</span></div>';
        echo '</li>';
    }

    /**
     *  Affichage d'un sous-dossier
     */
    public static function printSubDir($dir, $path)
    {
        echo '<li>';

        /**
         *  If dir name is 'my_uploaded_packages' then print it in yellow
         */
        if ($dir == 'my_uploaded_packages') {
            echo '<div class="explorer-toggle header-blue yellowtext pointer"><img src="/assets/icons/folder.svg" class="icon" /><span class="yellowtext">' . $dir . '</span></div>';
        } else {
            echo '<div class="explorer-toggle header-blue pointer"><img src="/assets/icons/folder.svg" class="icon" /><span>' . $dir . '</span></div>';
        }

        /**
         *  Calling main tree function again to print this sub-directory tree
         */
        \Controllers\Browse::tree($path . '/' . $dir);

        echo '</li>';
    }

    /**
     *  Fonction permettant de reconstruire l'array $_FILES['packages'] qui est assez mal foutu et donc compliqué à parcourir
     *  https://www.php.net/manual/fr/features.file-upload.multiple.php
     */
    public static function reArrayFiles(&$file_post)
    {
        $file_array = array();
        $file_count = count($file_post['name']);
        $file_keys = array_keys($file_post);

        for ($i = 0; $i < $file_count; $i++) {
            foreach ($file_keys as $key) {
                $file_array[$i][$key] = $file_post[$key][$i];
            }
        }

        return $file_array;
    }
}
