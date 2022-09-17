<head>
    <meta charset="utf-8">
    <!-- CSS -->
    <link rel="stylesheet" type="text/css" href="resources/styles/reset.css">
    <link rel="stylesheet" type="text/css" href="resources/styles/normalize.css">
    <link rel="stylesheet" type="text/css" href="resources/styles/main.css">
    <?php

        /**
         *  Chargement de CSS suplémentaires
         */

    if (!defined('__ACTUAL_URI__')) {
        define('__ACTUAL_URI__', parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH));
    }

    if (__ACTUAL_URI__ == "/index.php") {
        echo '<link rel="stylesheet" type="text/css" href="resources/styles/donut.css">';
    }
    if (__ACTUAL_URI__ == "/run.php") {
        echo '<link rel="stylesheet" type="text/css" href="resources/styles/run.css">';
    }
    if (__ACTUAL_URI__ == "/browse.php") {
        echo '<link rel="stylesheet" type="text/css" href="resources/styles/explore.css">';
    }
    if (__ACTUAL_URI__ == "/stats.php") {
        echo '<link rel="stylesheet" type="text/css" href="resources/styles/stats-hosts.css">';
    }
    if (__ACTUAL_URI__ == "/hosts.php") {
        echo '<link rel="stylesheet" type="text/css" href="resources/styles/stats-hosts.css">';
    }
    if (__ACTUAL_URI__ == "/host.php") {
        echo '<link rel="stylesheet" type="text/css" href="resources/styles/stats-hosts.css">';
    }
    ?>

    <!-- jQuery -->
    <script src="resources/js/jquery/jquery-3.5.1.min.js"></script>
    <!-- Select2 https://select2.org/ -->
    <script src="resources/js/select2/select2.js"></script>
    <link rel="stylesheet" type='text/css' href="resources/styles/select2.css">
    <!-- ChartJS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.1/chart.min.js" integrity="sha512-QSkVNOCYLtj73J4hbmVoOV6KVZuMluZlioC+trLpewV8qMjsWqlIQvkn1KGX2StWvPMdWGBqim1xlC8krl1EKQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <!-- Favicon -->
    <link rel="icon" href="resources/favicon.ico" />

    <?php
    if (__ACTUAL_URI__ == "/index.php") {
        echo '<title>Repomanager - Repos</title>';
    } elseif (__ACTUAL_URI__ == "/planifications.php") {
        echo '<title>Repomanager - Planifications</title>';
    } elseif (__ACTUAL_URI__ == "/run.php") {
        echo '<title>Repomanager - Operations</title>';
    } elseif (__ACTUAL_URI__ == "/browse.php") {
        echo '<title>Repomanager - Browse</title>';
    } elseif (__ACTUAL_URI__ == "/stats.php") {
        echo '<title>Repomanager - Statistics and metrics</title>';
    } elseif (__ACTUAL_URI__ == "/hosts.php") {
        echo '<title>Repomanager - Manage hosts</title>';
    } elseif (__ACTUAL_URI__ == "/host.php") {
        echo '<title>Repomanager - Manage host</title>';
    } elseif (__ACTUAL_URI__ == "/profiles.php") {
        echo '<title>Repomanager - Manage profiles</title>';
    } elseif (__ACTUAL_URI__ == "/configuration.php") {
        echo '<title>Repomanager - Configuration</title>';
    } elseif (__ACTUAL_URI__ == "/history.php") {
        echo '<title>Repomanager - History</title>';
    } elseif (__ACTUAL_URI__ == "/user.php") {
        echo '<title>Repomanager - Userspace</title>';
    } else {
        echo '<title>Repomanager</title>';
    }
    ?>
    <title>Repomanager</title>
</head>