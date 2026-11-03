<?php
// Based on : https://phpfog.com/directory-trees-with-php-and-jquery/

namespace Controllers\Repo;

use Controllers\Utils\Convert;
use Controllers\App\Session;

class Browse
{
    private const FILES_PER_PAGE = 300;

    /**
     *  Maximum number of snapshot directories the user is allowed to browse, kept in session
     */
    private const MAX_ALLOWED_ROOTS = 20;

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
     *  Return the root directory of a browsed path and its name: repositories (REPOS_DIR, published under /repo/)
     *  or mirrors (MIRRORS_DIR, published under /mirror/)
     *  Files paths are printed relatively to this root, with a 'root' attribute to know where to read or download them
     */
    public static function getRoot(string $path): array
    {
        if (str_starts_with($path, MIRRORS_DIR . '/')) {
            return ['dir' => MIRRORS_DIR, 'name' => 'mirror'];
        }

        return ['dir' => REPOS_DIR, 'name' => 'repo'];
    }

    /**
     *  Allow the current user to browse the specified snapshot directory
     *  Must only be called once the user's permissions to view the snapshot have been checked
     */
    public static function allowRoot(string $path): void
    {
        $realpath = realpath($path);

        if ($realpath === false) {
            return;
        }

        $roots = $_SESSION['browse-roots'] ?? [];

        // Move the directory to the end of the list, then only keep the most recent directories
        $roots = array_values(array_diff($roots, [$realpath]));
        $roots[] = $realpath;
        $roots = array_slice($roots, -self::MAX_ALLOWED_ROOTS);

        Session::set(['browse-roots' => $roots]);
    }

    /**
     *  Return true if the specified path exists, is inside the repositories or the mirrors directory,
     *  and is inside a snapshot directory the user has been allowed to browse
     *  Prevents a malicious person from providing a path that has nothing to do with the repo directory (e.g. /etc/...)
     *  or the path of a snapshot he is not allowed to view
     */
    public static function allowedPath(string $path): bool
    {
        $realpath = realpath($path);

        if ($realpath === false) {
            return false;
        }

        $reposDir = realpath(REPOS_DIR) ?: REPOS_DIR;
        $mirrorsDir = realpath(MIRRORS_DIR) ?: MIRRORS_DIR;

        if (!str_starts_with($realpath, $reposDir . '/') and !str_starts_with($realpath, $mirrorsDir . '/')) {
            return false;
        }

        foreach ($_SESSION['browse-roots'] ?? [] as $root) {
            if ($realpath === $root or str_starts_with($realpath, $root . '/')) {
                return true;
            }
        }

        return false;
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
        $root = self::getRoot($path);

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
                $files[$file] = str_replace($root['dir'] . '/', '', $path . '/' . $file);
            }
        }

        ksort($files);

        $total = count($files);
        $firstPage = array_slice($files, 0, self::FILES_PER_PAGE, true);
        Browse::printFiles($firstPage, $root);

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
        $root = self::getRoot($path);

        foreach (scandir($path) as $file) {
            if (is_file($path . '/' . $file) && $file != '.' && $file != '..') {
                $files[$file] = str_replace($root['dir'] . '/', '', $path . '/' . $file);
            }
        }

        ksort($files);

        $total = count($files);
        $slice = array_slice($files, $offset, self::FILES_PER_PAGE, true);
        Browse::printFiles($slice, $root);

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
        $root = self::getRoot($basePath);

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
                'path' => str_replace($root['dir'] . '/', '', $fileInfo->getPathname()),
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
            Browse::printSearchResultFiles($results, $root);
        }

        echo '</ul>';
    }

    /**
     *  Print search result file items (same structure as printFiles but with directory path shown)
     */
    private static function printSearchResultFiles(array $results, array $root): void
    {
        foreach ($results as $result) {
            $file = $result['file'];
            $path = $result['path'];
            $title = 'File';
            $icon = 'file';
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $class = 'header-light-blue flex align-item-center justify-space-between';

            $size = Convert::sizeToHuman(filesize($root['dir'] . '/' . $path));
            $mime = mime_content_type($root['dir'] . '/' . $path);

            if (str_starts_with($mime, 'text/')) {
                $class .= ' view-file pointer';
            }

            if (in_array($extension, ['deb', 'rpm', 'xz', 'gz', 'dsc', 'zst'])) {
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
                <div class="<?= $class ?>" path="<?= htmlspecialchars($path, ENT_QUOTES) ?>" root="<?= $root['name'] ?>" name="<?= htmlspecialchars($file, ENT_QUOTES) ?>">
                    <div class="flex column-gap-8 align-item-center" title="<?= $title ?>">
                        <img src="/assets/icons/<?= $icon ?>.svg" class="icon" />
                        <p><?= htmlspecialchars($file, ENT_QUOTES) ?></p>
                    </div>
                    <div class="flex align-item-center column-gap-15">
                        <span class="mediumopacity-cst"><?= $size ?></span>
                        <input type="checkbox" class="package-checkbox pointer lowopacity" name="packageName[]" filename="<?= htmlspecialchars($file, ENT_QUOTES) ?>" path="<?= htmlspecialchars($path, ENT_QUOTES) ?>" root="<?= $root['name'] ?>" />
                    </div>
                </div>
            </li>
            <?php
        }
    }

    /**
     *  Print all files in a directory
     */
    private static function printFiles(array $files, array $root): void
    {
        // Already sorted by caller; ksort is a no-op on sorted input but kept for safety
        ksort($files);

        foreach ($files as $file => $path) {
            $title = 'File';
            $icon = 'file';
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $class = 'header-light-blue flex align-item-center justify-space-between';

            // Get size
            $size = Convert::sizeToHuman(filesize($root['dir'] . '/' . $path));

            // Get MIME type
            $mime = mime_content_type($root['dir'] . '/' . $path);

            // If file contains text
            if (str_starts_with($mime, 'text/')) {
                $class .= ' view-file pointer';
            }

            if (in_array($extension, ['deb', 'rpm', 'xz', 'gz', 'dsc', 'zst'])) {
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
                <div class="<?= $class ?>" path="<?= htmlspecialchars($path, ENT_QUOTES) ?>" root="<?= $root['name'] ?>" name="<?= htmlspecialchars($file, ENT_QUOTES) ?>">
                    <div class="flex column-gap-8 align-item-center" title="<?= $title ?>">
                        <img src="/assets/icons/<?= $icon ?>.svg" class="icon" />
                        <p><?= htmlspecialchars($file, ENT_QUOTES) ?></p>
                    </div>
                    
                    <div class="flex align-item-center column-gap-15">
                        <span class="mediumopacity-cst"><?= $size ?></span>
                        <input type="checkbox" class="package-checkbox pointer lowopacity" name="packageName[]" filename="<?= htmlspecialchars($file, ENT_QUOTES) ?>" path="<?= htmlspecialchars($path, ENT_QUOTES) ?>" root="<?= $root['name'] ?>" />
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
        $fullPath = $path . '/' . $dir; ?>

        <li>
            <div class="explorer-toggle div-generic-blue pointer flex column-gap-8 align-item-center" title="Directory <?= htmlspecialchars($dir, ENT_QUOTES) ?>" data-path="<?= htmlspecialchars($fullPath, ENT_QUOTES) ?>">
                <img src="/assets/icons/folder.svg" class="icon" />
                <p><?= htmlspecialchars($dir, ENT_QUOTES) ?></p>
            </div>
            <ul style="display:none;"></ul>
        </li>
        <?php
    }
}
