<?php
$logfile = 'none';
$output = '';

/**
 *  Get the log file to display from the cookie set by JS
 */
if (!empty($_COOKIE['view-logfile'])) {
    if (file_exists(MAIN_LOGS_DIR . '/' . $_COOKIE['view-logfile'])) {
        $logfile = $_COOKIE['view-logfile'];
    }
}

/**
 *  If a logfile is specified in the URL, we take it
 *  It's the case for some links from planification mails or planification tab
 */
if (!empty($_GET['view-logfile'])) {
    if (file_exists(MAIN_LOGS_DIR . '/' . $_GET['view-logfile'])) {
        $logfile = $_GET['view-logfile'];
        /**
         *  Rewrite cookie
         */
        setcookie('view-logfile', $_GET['logfile'], time() + 3600 * 24 * 30, '/');
    }
}

/**
 *  If no logfile is specified, we take the last one
 */
if ($logfile == 'none') {
    $logfiles = array_diff(scandir(MAIN_LOGS_DIR, SCANDIR_SORT_DESCENDING), array('..', '.', 'lastlog.log'));

    if (!empty($logfiles[1])) {
        $logfile = $logfiles[1];
    }
}

/**
 *  Get the content of the log file
 */
if (!empty($logfile)) {
    $output = file_get_contents(MAIN_LOGS_DIR . '/' . $logfile);
}

/**
 *  Remove ANSI codes (colors) in the file
 */
$output = preg_replace('/\x1b(\[|\(|\))[;?0-9]*[0-9A-Za-z]/', "", $output);
