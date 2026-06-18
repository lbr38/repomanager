<?php
// Based on : https://phpfog.com/directory-trees-with-php-and-jquery/

namespace Controllers\Repo;

use Controllers\Utils\Convert;

class Browse
{
    private const FILES_PER_PAGE = 300;

    /**
     *  Render the repository tree structure (first page of files only)
     */
    public static function render(string $path): string
    {
        ob_start();
        Browse::tree($path);
        return ob_get_clean();
    }

    /**
     *  Render the next page of files for a directory (no <ul> wrapper, returns <li> fragments)
     */
    public static function renderPage(string $path, int $offset): string
    {
        ob_start();
        Browse::printFilesPage($path, $offset);
        return ob_get_clean();
    }

    /**
     *  Search for files matching a query string across the entire repository subtree.
     *  Results are capped at $limit to keep response times reasonable.
     */
    public static function search(string $basePath, string $query, int $limit = 200): string
    {
        ob_start();
        Browse::printSearchResults($basePath, $query, $limit);
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

        ksort($files);

        $total = count($files);
        $firstPage = array_slice($files, 0, self::FILES_PER_PAGE, true);
        Browse::printFiles($firstPage);

        if ($total > self::FILES_PER_PAGE) {
            Browse::printLoadMore($path, self::FILES_PER_PAGE, $total);
        }

        echo '</ul>';
    }

    /**
     *  Render the next page of files for a directory (no <ul> wrapper)
     */
    private static function printFilesPage(string $path, int $offset): void
    {
        $files = [];

        foreach (scandir($path) as $file) {
            if (is_file($path . '/' . $file) && $file != '.' && $file != '..') {
                $files[$file] = str_replace(REPOS_DIR . '/', '', $path . '/' . $file);
            }
        }

        ksort($files);

        $total = count($files);
        $slice = array_slice($files, $offset, self::FILES_PER_PAGE, true);
        Browse::printFiles($slice);

        if ($total > $offset + self::FILES_PER_PAGE) {
            Browse::printLoadMore($path, $offset + self::FILES_PER_PAGE, $total);
        }
    }

    /**
     *  Print a "load more" button for paginated file lists
     */
    private static function printLoadMore(string $path, int $nextOffset, int $total): void
    {
        $remaining = $total - $nextOffset;
        $toLoad = min(self::FILES_PER_PAGE, $remaining); ?>
        <li class="load-more-files" data-path="<?= htmlspecialchars($path, ENT_QUOTES) ?>" data-offset="<?= $nextOffset ?>">
            <div class="header-light-blue flex align-item-center justify-center pointer">
                <p>Load <?= $toLoad ?> more files (<?= $remaining ?> remaining)</p>
            </div>
        </li>
        <?php
    }

    /**
     *  Perform a recursive filename search and print results as a <ul>
     */
    private static function printSearchResults(string $basePath, string $query, int $limit): void
    {
        $results = [];
        $count = 0;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }

            if (stripos($fileInfo->getFilename(), $query) === false) {
                continue;
            }

            $results[] = [
                'file' => $fileInfo->getFilename(),
                'path' => str_replace(REPOS_DIR . '/', '', $fileInfo->getPathname()),
            ];

            if (++$count >= $limit) {
                break;
            }
        }

        usort($results, fn($a, $b) => strcasecmp($a['file'], $b['file']));

        $truncated = ($count >= $limit);

        echo '<ul>';

        if (empty($results)) {
            echo '<li><p class="note">No files found matching &quot;' . htmlspecialchars($query, ENT_QUOTES) . '&quot;</p></li>';
        } else {
            if ($truncated) {
                echo '<li><p class="note">Showing first ' . $limit . ' results — refine your search to see more.</p></li>';
            }
            Browse::printSearchResultFiles($results);
        }

        echo '</ul>';
    }

    /**
     *  Print search result file items (same structure as printFiles but with directory path shown)
     */
    private static function printSearchResultFiles(array $results): void
    {
        foreach ($results as $result) {
            $file = $result['file'];
            $path = $result['path'];
            $title = 'File';
            $icon = 'file';
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $class = 'header-light-blue flex align-item-center justify-space-between';

            $size = Convert::sizeToHuman(filesize(REPOS_DIR . '/' . $path));
            $mime = mime_content_type(REPOS_DIR . '/' . $path);

            if (str_starts_with($mime, 'text/')) {
                $class .= ' view-file pointer';
            }

            if (in_array($extension, ['deb', 'rpm', 'xz', 'gz', 'dsc'])) {
                $title = 'Package file';
                $icon = 'package';
            }

            if ($extension == 'db') {
                $title = 'Metadata (database) file';
            }

            if ($extension == 'xml') {
                $title = 'Metadata (xml) file';
            } ?>

            <li>
                <div class="<?= $class ?>" path="<?= htmlspecialchars($path, ENT_QUOTES) ?>" name="<?= htmlspecialchars($file, ENT_QUOTES) ?>">
                    <div class="flex column-gap-5 align-item-center" title="<?= $title ?>">
                        <img src="/assets/icons/<?= $icon ?>.svg" class="icon" />
                        <p><?= htmlspecialchars($file, ENT_QUOTES) ?></p>
                    </div>
                    <div class="flex align-item-center column-gap-15">
                        <span class="mediumopacity-cst"><?= $size ?></span>
                        <input type="checkbox" class="package-checkbox pointer lowopacity" name="packageName[]" filename="<?= htmlspecialchars($file, ENT_QUOTES) ?>" path="<?= htmlspecialchars($path, ENT_QUOTES) ?>" />
                    </div>
                </div>
            </li>
            <?php
        }
    }

    /**
     *  Print all files in a directory
     */
    private static function printFiles(array $files): void
    {
        // Already sorted by caller; ksort is a no-op on sorted input but kept for safety
        ksort($files);

        foreach ($files as $file => $path) {
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
     *  Print a sub-directory stub for lazy-loading.
     *  The subtree is NOT rendered here; the browser fetches it on first expand.
     */
    private static function printSubDir(string $dir, string $path): void
    {
        $fullPath = $path . '/' . $dir;
        ?>
        <li>
            <div class="explorer-toggle div-generic-blue pointer flex column-gap-5 align-item-center" title="Directory <?= $dir ?>" data-path="<?= htmlspecialchars($fullPath, ENT_QUOTES) ?>">
                <img src="/assets/icons/folder.svg" class="icon" />
                <p><?= $dir ?></p>
            </div>
            <ul style="display:none;"></ul>
        </li>
        <?php
    }
}
