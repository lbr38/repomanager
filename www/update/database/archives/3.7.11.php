<?php
/**
 *  3.7.11 database update
 */

if (!file_exists(STATS_DB)) {
    return;
}

/**
 *  Open stats database
 */
$statsDb = new \Models\Connection('stats');
$mystat = new \Controllers\Stat();
$myrepoListing = new \Controllers\Repo\Listing();

/**
 *  Quit if 'access' table does not exist
 */
if ($statsDb->tableExist('access') !== true) {
    $statsDb->close();
    return;
}

/**
 *  Get all repos
 */
$reposList = $myrepoListing->list();

/**
 *  Quit if no repo
 */
if (empty($reposList)) {
    return;
}

/**
 *  Table migration: splitting access table into two tables (access_deb and access_rpm)
 */
echo 'Migrating stats access table...' . PHP_EOL;

/**
 *  Retrieve all lines from access table, by batch of 100000 to avoid memory limit issues
 */
$offset = 0;

while (true) {
    $data = array();

    $result = $statsDb->query("SELECT * FROM access LIMIT 100000 OFFSET $offset");

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $data[] = $row;
    }

    unset($result);

    /**
     *  Quit if no more data to process
     */
    if (empty($data)) {
        break;
    }

    foreach ($data as $line) {
        $id = $line['Id'];
        $date = $line['Date'];
        $time = $line['Time'];
        $sourceHost = $line['Source'];
        $sourceIp = $line['IP'];
        $request = str_replace('"', '', $line['Request']);
        $requestResult = $line['Request_result'];

        /**
         *  Loop through repos list until the repo called in the request is found
         */
        foreach ($reposList as $repo) {
            $dist = '';
            $section = '';

            /**
             *  Continue if the repo snapshot has no environment, because stats are only generated for snapshots environments
             */
            if (empty($repo['envId'])) {
                continue;
            }

            /**
             *  Build repository URI path
             */

            /**
             *  Case the repo is a deb repo
             */
            if ($repo['Package_type'] == 'deb') {
                $repoUri = '/repo/' . $repo['Name'] . '/' . $repo['Dist'] . '/' . $repo['Section'] . '_' . $repo['Env'];
            }

            /**
             *  Case the repo is a rpm repo
             */
            if ($repo['Package_type'] == 'rpm') {
                $repoUri = '/repo/' . $repo['Name'] . '_' . $repo['Env'];
            }

            /**
             *  Now if the repo URI is found in the request, it means that the request is made for this repo
             */
            if (!empty($repoUri) and !empty($request)) {
                if (preg_match('#' . $repoUri . '#', $request)) {
                    $type = $repo['Package_type'];
                    $name = $repo['Name'];
                    $env = $repo['Env'];
                    if (!empty($repo['Dist']) and !empty($repo['Section'])) {
                        $dist = $repo['Dist'];
                        $section = $repo['Section'];
                    }

                    /**
                     *  Add line in the new table
                     */
                    if (!empty($date) && !empty($time) && !empty($type) && !empty($name) && isset($dist) && isset($section) && !empty($env) && !empty($sourceHost) && !empty($sourceIp) && !empty($request) && !empty($requestResult)) {
                        $mystat->addAccess($date, $time, $type, $name, $dist, $section, $env, $sourceHost, $sourceIp, $request, $requestResult);
                    }
                }
            }
        }
    }

    /**
     *  Increment offset
     */
    $offset += 100000;

    $memory = memory_get_usage();
    echo 'Migration still running, please wait. Memory usage: ' . round($memory / 1024 / 1024, 2) . ' MB' . PHP_EOL;
}

/**
 *  Drop old index
 */
$statsDb->exec("DROP INDEX IF EXISTS access_index");

/**
 *  Vacuum and quit
 */
$statsDb->exec("VACUUM");
$statsDb->exec("ANALYZE");
$statsDb->close();

unset($statsDb, $mystat, $myrepoListing);

echo 'Migration done.' . PHP_EOL;
