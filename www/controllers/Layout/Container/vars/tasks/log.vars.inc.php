<?php
$logfile = 'none';
$output = '';

/**
 *  Get the log file to display from the cookie set by JS
 */
if (!empty($_COOKIE['task-log'])) {
    $logfileCookie = \Controllers\Common::validateData($_COOKIE['task-log']);

    if (file_exists(MAIN_LOGS_DIR . '/' . $logfileCookie)) {
        $logfile = $logfileCookie;
    }
}

/**
 *  If a logfile is specified in the URL, we take it
 *  It's the case for some links from scheduled tasks mails
 */
if (!empty($_GET['task-log'])) {
    $logfileGet = \Controllers\Common::validateData($_GET['task-log']);

    /**
     *  Logfile name must match the pattern
     */
    if (preg_match('/^(?:[0-9]{2})?[0-9]{2}-[0-3]?[0-9]-[0-3]?[0-9].*_(plan|repomanager|task)_.*.log$/', $logfileGet)) {
        if (file_exists(MAIN_LOGS_DIR . '/' . $logfileGet)) {
            $logfile = $logfileGet;

            /**
             *  Rewrite cookie
             */
            setcookie('task-log', $logfile, time() + 3600 * 24 * 30, '/');
        }
    }

    if ($logfileGet == 'latest') {
        if (file_exists(MAIN_LOGS_DIR . '/latest')) {
            $logfile = '/latest';

            /**
             *  Rewrite cookie
             */
            setcookie('task-log', $logfile, time() + 3600 * 24 * 30, '/');
        }
    }
}

/**
 *  If no logfile is specified, we take the last one
 */
if ($logfile == 'none') {
    $logfiles = array_diff(scandir(MAIN_LOGS_DIR, SCANDIR_SORT_DESCENDING), array('..', '.', 'latest'));

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
