<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- To tell mobile browsers to adjust the width of the window to the width of the device's screen, and set the document scale to 100% of its intended size -->
    <meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=yes">

    <!-- CSS for all pages -->
    <link rel="stylesheet" type="text/css" href="/resources/styles/reset.css?<?= VERSION ?>">
    <link rel="stylesheet" type="text/css" href="/resources/styles/normalize.css?<?= VERSION ?>">
    <link rel="stylesheet" type="text/css" href="/resources/styles/common.css?<?= VERSION ?>">
    <link rel="stylesheet" type="text/css" href="/resources/styles/components/layout.css?<?= VERSION ?>">
    <link rel="stylesheet" type="text/css" href="/resources/styles/components/alert.css?<?= VERSION ?>">
    <link rel="stylesheet" type="text/css" href="/resources/styles/components/icon.css?<?= VERSION ?>">
    <link rel="stylesheet" type="text/css" href="/resources/styles/components/input.css?<?= VERSION ?>">
    <link rel="stylesheet" type="text/css" href="/resources/styles/components/button.css?<?= VERSION ?>">
    <link rel="stylesheet" type="text/css" href="/resources/styles/components/label.css?<?= VERSION ?>">
    <link rel="stylesheet" type="text/css" href="/resources/styles/components/confirmbox.css?<?= VERSION ?>">
    <link rel="stylesheet" type="text/css" href="/resources/styles/components/modal.css?<?= VERSION ?>">
    <link rel="stylesheet" type="text/css" href="/resources/styles/components/tooltip.css?<?= VERSION ?>">
    <link rel="stylesheet" type="text/css" href="/resources/styles/components/scrollbar.css?<?= VERSION ?>">
    <link rel="stylesheet" type="text/css" href="/resources/styles/components/echart.css?<?= VERSION ?>">
    <link rel="stylesheet" type="text/css" href="/resources/styles/components/daterangepicker.css?<?= VERSION ?>">
    <link rel="stylesheet" type="text/css" href="/resources/styles/components/hide.css?<?= VERSION ?>">
    <link rel="stylesheet" type='text/css' href="/resources/styles/select2.css?<?= VERSION ?>">
    <link rel="stylesheet" type="text/css" href="/resources/styles/main.css?<?= VERSION ?>">

    <!-- To tell mobile browsers to adjust the width of the window to the width of the device's screen, and set the document scale to 100% of its intended size -->
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <?php
    /**
     *  Load additional CSS files depending on the current URI
     */
    $additionalCss = [
        "run"      => "run.css",
        "browse"   => "browse.css",
        "stat"     => "stats-hosts.css",
        "stats"    => "stats-hosts.css",
        "hosts"    => "stats-hosts.css",
        "host"     => "stats-hosts.css",
        "settings" => "settings.css",
        "cves"     => "cve.css",
        "cve"      => "cve.css"
    ];

    foreach ($additionalCss as $uri => $css) {
        if (__ACTUAL_URI__[1] == $uri) {
            echo '<link rel="stylesheet" type="text/css" href="/resources/styles/' . $css . '?' . VERSION . '">';
        }
    } ?>

    <!-- Load pre JS -->
    <script type="text/javascript" src="/resources/js/pre/functions/global.js?<?= VERSION ?>"></script>
    <script type="text/javascript" src="/resources/js/pre/pre.js?<?= VERSION ?>"></script>
    <!-- jQuery -->
    <script type="text/javascript" src="/resources/js/libs/jquery-3.7.1.min.js?<?= VERSION ?>"></script>
    <!-- Select2 https://select2.org/ -->
    <script type="text/javascript" src="/resources/js/libs/select2.js?<?= VERSION ?>"></script>
    <!-- ECharts -->
    <script type="text/javascript" src="/resources/js/libs/echarts.min.js?<?= VERSION ?>"></script>
    <!-- Morhpdom -->
    <script type="text/javascript" src="/resources/js/libs/morphdom-umd.min.js?<?= VERSION ?>"></script>
    <!-- Moment & Daterangepicker -->
    <script type="text/javascript" src="/resources/js/libs/moment.min.js"></script>
    <script type="text/javascript" src="/resources/js/libs/daterangepicker.min.js"></script>
    <!-- App config files -->
    <script type="text/javascript" src="/resources/js/app/container.config.js?<?= VERSION ?>"></script>
    <script type="text/javascript" src="/resources/js/app/checkbox.config.js?<?= VERSION ?>"></script>

    <?php
    $title = 'Repomanager';

    if (__ACTUAL_URI__[1] == "") {
        $title .= ' - Repositories';
    } elseif (__ACTUAL_URI__[1] == "run") {
        $title .= ' - Tasks';
    } elseif (__ACTUAL_URI__[1] == "browse") {
        $title .= ' - Browse repo';
    } elseif (__ACTUAL_URI__[1] == "stats") {
        $title .= ' - Statistics and metrics';
    } elseif (__ACTUAL_URI__[1] == "hosts") {
        $title .= ' - Manage hosts';
    } elseif (__ACTUAL_URI__[1] == "host") {
        $title .= ' - Manage host';
    } elseif (__ACTUAL_URI__[1] == "settings") {
        $title .= ' - Settings';
    } elseif (__ACTUAL_URI__[1] == "history") {
        $title .= ' - History';
    } elseif (__ACTUAL_URI__[1] == "status") {
        $title .= ' - Status';
    } ?>

    <title><?= $title ?></title>
</head>