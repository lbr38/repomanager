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
 *  If a filter has been selected for the main chart, the page is reloaded in the background by jquery and retrieves the chart data from the selected filter
 */
if (!empty($_GET['repo_access_chart_filter'])) {
    if (\Controllers\Common::validateData($_GET['repo_access_chart_filter']) == "1week") {
        $repo_access_chart_filter = "1week";
    }
    if (\Controllers\Common::validateData($_GET['repo_access_chart_filter']) == "1month") {
        $repo_access_chart_filter = "1month";
    }
    if (\Controllers\Common::validateData($_GET['repo_access_chart_filter']) == "3months") {
        $repo_access_chart_filter = "3months";
    }
    if (\Controllers\Common::validateData($_GET['repo_access_chart_filter']) == "6months") {
        $repo_access_chart_filter = "6months";
    }
    if (\Controllers\Common::validateData($_GET['repo_access_chart_filter']) == "1year") {
        $repo_access_chart_filter = "1year";
    }
}
