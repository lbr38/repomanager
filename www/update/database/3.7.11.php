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
$myrepo = new \Controllers\Repo\Repo();

/**
 *  Quit if 'access' table does not exist
 */
if ($statsDb->tableExist('access') !== true) {
    $statsDb->close();
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
        $type = '';
        $dist = '';
        $section = '';

        /**
         *  Try to determine the target repository type, name and environment
         */

        /**
         *  Case it's a deb repository
         */
        if (preg_match('#/repo/.*/pool/|/repo/.*/dists/|.*\.deb HTTP.*|.*\.dsc HTTP.*|.*\.tar\.gz HTTP.*#', $request)) {
            $type = 'deb';

            /**
             *  Retrieve name, distribution, section and environment from request
             */
            $requestExplode = explode('/', $request);
            $name = $requestExplode[2];
            $dist = $requestExplode[3];

            /**
             *  Ignore request if section and env are not in position 4
             *  (this can be the case for some repositories like 'debian-security')
             */
            if (!preg_match('/_/', $requestExplode[4])) {
                continue;
            }

            $section = explode('_', $requestExplode[4])[0];
            $env = explode('_', $requestExplode[4])[1];

        /**
         *  Case it's a rpm repository
         */
        } elseif (preg_match('#/repo/.*/packages/|/repo/.*/Packages/|/repo/.*/repodata/|.*\.rpm HTTP.*#', $request)) {
            $type = 'rpm';

            /**
             *  Retrieve name and environment from request
             */
            $requestExplode = explode('/', $request);
            $name = explode('_', $requestExplode[2])[0];
            $env = explode('_', $requestExplode[2])[1];

        /**
         *  If the request does not match any of the above patterns, skip it
         */
        } else {
            continue;
        }

        /**
         *  Add line in the new table
         */
        if (!empty($date) && !empty($time) && !empty($type) && !empty($name) && !empty($env) && !empty($sourceHost) && !empty($sourceIp) && !empty($request) && !empty($requestResult)) {
            $mystat->addAccess($date, $time, $type, $name, $dist, $section, $env, $sourceHost, $sourceIp, $request, $requestResult);
        }

        unset($name, $dist, $section, $env, $sourceHost, $sourceIp, $request, $requestResult, $type, $requestExplode);
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

unset($statsDb, $mystat, $myrepo);

echo 'Migration done.' . PHP_EOL;
