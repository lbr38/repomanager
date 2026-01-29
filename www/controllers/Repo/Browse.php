<?php
// Based on : https://phpfog.com/directory-trees-with-php-and-jquery/

namespace Controllers\Repo;

use Controllers\Utils\Convert;

class Browse
{
    /**
     *  Render the repository tree structure
     */
    public static function render(string $path): string
    {
        ob_start();
        Browse::tree($path);
        return ob_get_clean();
    }

    /**
     *  Generate the repository tree structure
     */
    private static function tree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        // Initialize array which will contain a list of the files inside the actual directory
        $files = [];

        echo '<ul>';

        // Scan the specified directory then process each file found
        foreach (scandir($path) as $file) {
            // Case it is a directory
            if (is_dir($path . '/' . $file) && $file != '.' && $file != '..') {
                Browse::printSubDir($file, $path);
                continue;
            }

            // Case it is a file
            if (is_file($path . '/' . $file) and $file != '.' and $file != '..') {
                /**
                 *  If it is a file then we add it to the queue array which contains the list of all files in the current directory or sub-directory
                 *  Index the file name $file and its path $path/$file by removing the beginning of the full path so it is not visible in the source code
                 */
                $files[$file] = str_replace(REPOS_DIR . '/', '', $path . '/' . $file);
            }
        }

        Browse::printFiles($files);

        echo '</ul>';
    }

    /**
     *  Print all files in a directory
     */
    private static function printFiles(array $files): void
    {
        // Sort the list alphabetically
        ksort($files);

        foreach ($files as $file => $path) {
            // Browse::printFile($file, $path);
            $title = 'File';
            $icon = 'file';
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $class = 'header-light-blue flex align-item-center justify-space-between';

            // Get size
            $size = Convert::sizeToHuman(filesize(REPOS_DIR . '/' . $path));

            // Get MIME type
            $mime = mime_content_type(REPOS_DIR . '/' . $path);

            // If file contains text
            if (str_starts_with($mime, 'text/')) {
                $class .= ' view-file pointer';
            }

            if (in_array($extension, ['deb', 'rpm', 'xz', 'gz', 'dsc'])) {
                $title = 'Package file';
                $icon = 'package';
            }

            if ($extension == 'db') {
                $title = 'Metadata (database) file';
                $icon = 'file';
            }

            if ($extension == 'xml') {
                $title = 'Metadata (xml) file';
                $icon = 'file';
            } ?>

            <li>
                <div class="<?= $class ?>" path="<?= $path ?>" name="<?= $file ?>">
                    <div class="flex column-gap-5 align-item-center" title="<?= $title ?>">
                        <img src="/assets/icons/<?= $icon ?>.svg" class="icon" />
                        <p><?= $file ?></p>
                    </div>
                    
                    <div class="flex align-item-center column-gap-15">
                        <span class="mediumopacity-cst"><?= $size ?></span>
                        <input type="checkbox" class="package-checkbox pointer lowopacity" name="packageName[]" filename="<?= $file ?>" path="<?= $path ?>" />
                    </div>
                </div>
            </li>
            <?php
        }
    }

    /**
     *  Print a sub-directory
     */
    private static function printSubDir(string $dir, string $path): void
    {
        ?>
        <li>
            <div class="explorer-toggle div-generic-blue pointer flex column-gap-5 align-item-center" title="Directory <?= $dir ?>">
                <img src="/assets/icons/folder.svg" class="icon" />
                <p><?= $dir ?></p>
            </div>

            <?php
            // Calling main tree function again to print this sub-directory tree
            Browse::tree($path . '/' . $dir); ?>
        </li>
        <?php
    }

    /**
     *  Function to rebuild the $_FILES['packages'] array which is quite badly done and therefore complicated to browse
     *  https://www.php.net/manual/fr/features.file-upload.multiple.php
     */
    public static function reArrayFiles(&$file_post): array
    {
        $file_array = [];
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
