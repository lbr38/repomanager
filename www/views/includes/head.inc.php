<head>
    <meta charset="utf-8">

    <!-- CSS for all pages -->
    <link rel="stylesheet" type="text/css" href="/resources/styles/reset.css?<?= VERSION ?>">
    <link rel="stylesheet" type="text/css" href="/resources/styles/normalize.css?<?= VERSION ?>">
    <link rel="stylesheet" type="text/css" href="/resources/styles/common.css?<?= VERSION ?>">
    <link rel="stylesheet" type="text/css" href="/resources/styles/main.css?<?= VERSION ?>">

    <?php
    /**
     *  Load additional CSS files depending on the current URI
     */
    $additionalCss = array(
        "run"      => "run.css",
        "browse"   => "browse.css",
        "stats"    => "stats-hosts.css",
        "hosts"    => "stats-hosts.css",
        "host"     => "stats-hosts.css",
        "settings" => "settings.css",
        "cves"     => "cve.css",
        "cve"      => "cve.css"
    );

    foreach ($additionalCss as $uri => $css) {
        if (__ACTUAL_URI__[1] == $uri) {
            echo '<link rel="stylesheet" type="text/css" href="/resources/styles/' . $css . '?' . VERSION . '">';
        }
    } ?>

    <!-- Load pre JS -->
    <script src="/resources/js/pre.js?<?= VERSION ?>"></script>
    <!-- jQuery -->
    
    <script src="/resources/js/jquery/jquery-3.7.1.min.js?<?= VERSION ?>"></script>
    <!-- Select2 https://select2.org/ -->

    <script src="/resources/js/select2/select2.js?<?= VERSION ?>"></script>
    <link rel="stylesheet" type='text/css' href="/resources/styles/select2.css?<?= VERSION ?>">

    <!-- ChartJS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.1/chart.min.js?<?= VERSION ?>" integrity="sha512-QSkVNOCYLtj73J4hbmVoOV6KVZuMluZlioC+trLpewV8qMjsWqlIQvkn1KGX2StWvPMdWGBqim1xlC8krl1EKQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    
    <!-- Favicon -->
    <link rel="icon" href="/assets/favicon.ico" />

    <?php
    $title = 'Repomanager';

    if (__ACTUAL_URI__[1] == "") {
        $title .= ' - Repos';
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
    } elseif (__ACTUAL_URI__[1] == "profiles") {
        $title .= ' - Manage profiles';
    } elseif (__ACTUAL_URI__[1] == "settings") {
        $title .= ' - Settings';
    } elseif (__ACTUAL_URI__[1] == "history") {
        $title .= ' - History';
    } elseif (__ACTUAL_URI__[1] == "userspace") {
        $title .= ' - Userspace';
    } ?>

    <title><?= $title ?></title>
</head>