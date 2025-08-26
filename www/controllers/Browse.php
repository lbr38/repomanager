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
             *  Initialize array which will contain a list of the files inside the actual directory
             */
            $queue = array();

            /**
             *  Scan the specified directory then process each file found
             */
            foreach (scandir($path) as $file) {
                /**
                 *  Case it is a directory
                 */
                if (is_dir($path . '/' . $file) && $file != '.' && $file != '..') {
                    \Controllers\Browse::printSubDir($file, $path, $queue);
                    continue;
                }

                /**
                 *  Case it is a file
                 */
                if (is_file($path . '/' . $file) and $file != '.' and $file != '..') {
                    /**
                     *  If it is a file then we add it to the queue array which contains the list of all files in the current directory or sub-directory
                     *  Index the file name $file and its path $path/$file by removing the beginning of the full path so it is not visible in the source code
                     */
                    $queue[$file] = str_replace(REPOS_DIR . '/', '', $path . '/' . $file);
                }
            }

            \Controllers\Browse::printQueue($queue);

            echo '</ul>';
        }
    }

    /**
     *  Print all files in a directory
     */
    public static function printQueue($queue)
    {
        // First we sort the list alphabetically
        ksort($queue);

        foreach ($queue as $file => $path) {
            \Controllers\Browse::printFile($file, $path);
        }
    }

    /**
     *  Print a file
     */
    public static function printFile($file, $path)
    {
        $checkbox = false;

        /**
         *  If file has .deb or .rpm extension, then it is a package
         *  If it has .db extension, then it is a metadate (database)
         *  If it has .xml extension, then it is a metadata (xml)
         *  Etc ...
         */
        $extension = pathinfo($file, PATHINFO_EXTENSION);

        if (in_array($extension, ['deb', 'rpm', 'xz', 'gz', 'dsc'])) {
            $title = 'Package file';
            $icon = 'package';
            $checkbox = true;
        } else if ($extension == 'db') {
            $title = 'Metadata (database) file';
            $icon = 'file';
        } else if ($extension == 'xml') {
            $title = 'Metadata (xml) file';
            $icon = 'file';
        } else {
            $title = 'File';
            $icon = 'file';
        } ?>

        <li>
            <div class="explorer-file-pkg header-light-blue flex align-item-center justify-space-between">
                <div class="flex column-gap-5 align-item-center" title="<?= $title ?>">
                    <img src="/assets/icons/<?= $icon ?>.svg" class="icon" />
                    <p><?= $file ?></p>
                </div>
                
                <?php
                if ($checkbox) : ?>
                    <input type="checkbox" class="package-checkbox pointer" name="packageName[]" filename="<?= $file ?>" path="<?= $path ?>" />
                    <?php
                endif ?>
            </div>
        </li>
        <?php
    }

    /**
     *  Print a sub-directory
     */
    public static function printSubDir($dir, $path)
    {
        ?>
        <li>
            <div class="explorer-toggle div-generic-blue pointer flex column-gap-5 align-item-center" title="Directory <?= $dir ?>">
                <img src="/assets/icons/folder.svg" class="icon" />
                <p><?= $dir ?></p>
            </div>
            <?php
            /**
             *  Calling main tree function again to print this sub-directory tree
             */
            \Controllers\Browse::tree($path . '/' . $dir); ?>

        </li>
        <?php
    }

    /**
     *  Function to rebuild the $_FILES['packages'] array which is quite badly done and therefore complicated to browse
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
