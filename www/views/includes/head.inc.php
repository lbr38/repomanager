<head>
    <meta charset="utf-8">
    <!-- CSS -->
    <link rel="stylesheet" type="text/css" href="resources/styles/reset.css">
    <link rel="stylesheet" type="text/css" href="resources/styles/normalize.css">
    <link rel="stylesheet" type="text/css" href="resources/styles/common.css">
    <link rel="stylesheet" type="text/css" href="resources/styles/main.css">
    <?php

    /**
     *  Chargement de CSS suplémentaires
     */
    if (__ACTUAL_URI__ == "/") {
        echo '<link rel="stylesheet" type="text/css" href="resources/styles/donut.css">';
    }
    if (__ACTUAL_URI__ == "/run") {
        echo '<link rel="stylesheet" type="text/css" href="resources/styles/run.css">';
    }
    if (__ACTUAL_URI__ == "/browse") {
        echo '<link rel="stylesheet" type="text/css" href="resources/styles/explore.css">';
    }
    if (__ACTUAL_URI__ == "/stats") {
        echo '<link rel="stylesheet" type="text/css" href="resources/styles/stats-hosts.css">';
    }
    if (__ACTUAL_URI__ == "/hosts") {
        echo '<link rel="stylesheet" type="text/css" href="resources/styles/stats-hosts.css">';
    }
    if (__ACTUAL_URI__ == "/host") {
        echo '<link rel="stylesheet" type="text/css" href="resources/styles/stats-hosts.css">';
    }
    if (__ACTUAL_URI__ == "/settings") {
        echo '<link rel="stylesheet" type="text/css" href="resources/styles/settings.css">';
    }
    if (__ACTUAL_URI__ == "/cve") {
        echo '<link rel="stylesheet" type="text/css" href="resources/styles/cve.css">';
    }?>

    <!-- jQuery -->
    <script src="resources/js/jquery/jquery-3.5.1.min.js"></script>
    <!-- Select2 https://select2.org/ -->
    <script src="resources/js/select2/select2.js"></script>
    <link rel="stylesheet" type='text/css' href="resources/styles/select2.css">
    <!-- ChartJS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.1/chart.min.js" integrity="sha512-QSkVNOCYLtj73J4hbmVoOV6KVZuMluZlioC+trLpewV8qMjsWqlIQvkn1KGX2StWvPMdWGBqim1xlC8krl1EKQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <!-- Favicon -->
    <link rel="icon" href="assets/favicon.ico" />

    <?php
    $title = 'Repomanager';

    if (__ACTUAL_URI__ == "/") {
        $title .= ' - Repos';
    } elseif (__ACTUAL_URI__ == "/plans") {
        $title .= ' - Planifications';
    } elseif (__ACTUAL_URI__ == "/run") {
        $title .= ' - Operations';
    } elseif (__ACTUAL_URI__ == "/browse") {
        $title .= ' - Browse repo';
    } elseif (__ACTUAL_URI__ == "/stats") {
        $title .= ' - Statistics and metrics';
    } elseif (__ACTUAL_URI__ == "/hosts") {
        $title .= ' - Manage hosts';
    } elseif (__ACTUAL_URI__ == "/host") {
        $title .= ' - Manage host';
    } elseif (__ACTUAL_URI__ == "/profiles") {
        $title .= ' - Manage profiles';
    } elseif (__ACTUAL_URI__ == "/settings") {
        $title .= ' - Settings';
    } elseif (__ACTUAL_URI__ == "/history") {
        $title .= ' - History';
    } elseif (__ACTUAL_URI__ == "/userspace") {
        $title .= ' - Userspace';
    } ?>

    <title><?= $title ?></title>
</head>