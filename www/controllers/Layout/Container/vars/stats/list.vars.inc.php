<?php
$myrepo = new \Controllers\Repo\Repo();
$mystats = new \Controllers\Stat();

if (empty(__ACTUAL_URI__[2])) {
    die('Error: no snapshot env ID specified.');
}

if (!is_numeric(__ACTUAL_URI__[2])) {
    die('Error: invalid snapshot env ID.');
}

$envId = __ACTUAL_URI__[2];

/**
 *  Retrieve repo infos from DB
 */
$myrepo->getAllById('', '', $envId);

/**
 *  Snapshot access stats
 */

/**
 *  If a filter has been selected for the main chart, the page is reloaded in the background by jquery and retrieves the chart data from the selected filter
 */
if (!empty($_GET['chartFilter'])) {
    if (\Controllers\Common::validateData($_GET['chartFilter']) == "1week") {
        $chartFilter = "1week";
    }
    if (\Controllers\Common::validateData($_GET['chartFilter']) == "1month") {
        $chartFilter = "1month";
    }
    if (\Controllers\Common::validateData($_GET['chartFilter']) == "3months") {
        $chartFilter = "3months";
    }
    if (\Controllers\Common::validateData($_GET['chartFilter']) == "6months") {
        $chartFilter = "6months";
    }
    if (\Controllers\Common::validateData($_GET['chartFilter']) == "1year") {
        $chartFilter = "1year";
    }
}

/**
 *  Retrieve last access logs from database
 */
if ($myrepo->getPackageType() == 'rpm') {
    $lastAccess = $mystats->getAccess('rpm', $myrepo->getName(), '', '', $myrepo->getEnv(), false, true, 0);
}
if ($myrepo->getPackageType() == 'deb') {
    $lastAccess = $mystats->getAccess('deb', $myrepo->getName(), $myrepo->getDist(), $myrepo->getSection(), $myrepo->getEnv(), false, true, 0);
}

/**
 *  Sort by date and time
 */
if (!empty($lastAccess)) {
    array_multisort(array_column($lastAccess, 'Date'), SORT_DESC, array_column($lastAccess, 'Time'), SORT_DESC, $lastAccess);
}

/**
 *  Count repo size and packages count
 */
if ($myrepo->getPackageType() == 'rpm') {
    $repoSize = \Controllers\Filesystem\Directory::getSize(REPOS_DIR . '/' . $myrepo->getDateFormatted() . '_' . $myrepo->getName());
    $packagesCount = count(\Controllers\Filesystem\File::findRecursive(REPOS_DIR . '/' . $myrepo->getDateFormatted() . '_' . $myrepo->getName(), 'rpm'));
}
if ($myrepo->getPackageType() == 'deb') {
    $repoSize = \Controllers\Filesystem\Directory::getSize(REPOS_DIR . '/' . $myrepo->getName() . '/' . $myrepo->getDist() . '/' . $myrepo->getDateFormatted() . '_' . $myrepo->getSection());
    $packagesCount = count(\Controllers\Filesystem\File::findRecursive(REPOS_DIR . '/' . $myrepo->getName() . '/' . $myrepo->getDist() . '/' . $myrepo->getDateFormatted() . '_' . $myrepo->getSection(), 'deb'));
}

/**
 *  Convert repo size in the most suitable byte format
 */
$repoSize = \Controllers\Common::sizeFormat($repoSize);

/**
 *  If no filter has been selected by the user then we set it to 1 week by default
 */
if (empty($chartFilter)) {
    $chartFilter = "1week";
}

/**
 *  Initialize the starting date of the chart, according to the selected filter
 */
if ($chartFilter == "1week") {
    // the beginning of the counter starts at the current date -1 week.
    $dateCounter = date('Y-m-d', strtotime('-1 week', strtotime(DATE_YMD)));
}
if ($chartFilter == "1month") {
    // the beginning of the counter starts at the current date -1 month.
    $dateCounter = date('Y-m-d', strtotime('-1 month', strtotime(DATE_YMD)));
}
if ($chartFilter == "3months") {
    // the beginning of the counter starts at the current date -3 months.
    $dateCounter = date('Y-m-d', strtotime('-3 months', strtotime(DATE_YMD)));
}
if ($chartFilter == "6months") {
    // the beginning of the counter starts at the current date -6 months.
    $dateCounter = date('Y-m-d', strtotime('-6 months', strtotime(DATE_YMD)));
}
if ($chartFilter == "1year") {
    // the beginning of the counter starts at the current date -1 year.
    $dateCounter = date('Y-m-d', strtotime('-1 year', strtotime(DATE_YMD)));
}

$repoAccessChartDates = '';
$repoAccessChartData = '';

/**
 *  Process all dates until the current date (which is also processed)
 */
while ($dateCounter != date('Y-m-d', strtotime('+1 day', strtotime(DATE_YMD)))) {
    if ($myrepo->getPackageType() == 'rpm') {
        $dateAccessCount = $mystats->getDailyAccessCount('rpm', $myrepo->getName(), '', '', $myrepo->getEnv(), $dateCounter);
    }
    if ($myrepo->getPackageType() == 'deb') {
        $dateAccessCount = $mystats->getDailyAccessCount('deb', $myrepo->getName(), $myrepo->getDist(), $myrepo->getSection(), $myrepo->getEnv(), $dateCounter);
    }

    /**
     *  Add the current count to the data
     */
    $repoAccessChartData .= $dateAccessCount . ', ';

    /**
     *  Add the current date to the labels
     */
    $repoAccessChartDates .= "'$dateCounter', ";

    /**
     *  Increment by 1 day to be able to process the next date
     */
    $dateCounter = date('Y-m-d', strtotime('+1 day', strtotime($dateCounter)));
}

/**
 *  Remove the last comma
 */
$repoAccessChartDates = rtrim($repoAccessChartDates, ', ');
$repoAccessChartData  = rtrim($repoAccessChartData, ', ');

/**
 *  Snapshot size stats
 */

/**
 *  Get stats for the last 60 days
 */
$stats = $mystats->getAll($myrepo->getEnvId());
$envSizeStats = $mystats->getEnvSize($myrepo->getEnvId(), 60);

if (!empty($envSizeStats)) {
    $sizeDateLabels = '';
    $sizeData = '';

    foreach ($envSizeStats as $stat) {
        $date = DateTime::createFromFormat('Y-m-d', $stat['Date'])->format('d-m-Y');

        // Convert bytes to MB
        $size = round(round($stat['Size'] / 1024) / 1024);

        /**
         *  Build data for chart
         */
        $sizeDateLabels .= '"' . $date . '", ';
        $sizeData .= '"' . $size . '", ';
    }

    /**
     *  Remove last comma
     */
    $sizeDateLabels = rtrim($sizeDateLabels, ', ');
    $sizeData   = rtrim($sizeData, ', ');
}

/**
 *  Snapshot package count stats
 */

$pkgCountStats = $mystats->getPkgCount($myrepo->getEnvId(), 60);

if (!empty($pkgCountStats)) {
    $countDateLabels = '';
    $countData = '';

    foreach ($pkgCountStats as $stat) {
        $date = DateTime::createFromFormat('Y-m-d', $stat['Date'])->format('d-m-Y');
        $count = $stat['Packages_count'];

        /**
         *  Build data for chart
         */
        $countDateLabels .= '"' . $date . '", ';
        $countData .= '"' . $count . '", ';
    }

    /**
     *  Remove last comma
     */
    $countDateLabels = rtrim($countDateLabels, ', ');
    $countData  = rtrim($countData, ', ');
}
