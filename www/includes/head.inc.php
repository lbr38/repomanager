<head>
    <meta charset="utf-8">
    <!-- CSS -->
    <link rel="stylesheet" type="text/css" href="ressources/styles/reset.css">
    <link rel="stylesheet" type="text/css" href="ressources/styles/normalize.css">
    <link rel="stylesheet" type="text/css" href="ressources/styles/main.css">
    <?php
        /**
         *  Chargement de CSS suplémentaires
         */
        if (!defined('__ACTUAL_URI__')) define('__ACTUAL_URI__', parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH));

        if (__ACTUAL_URI__ == "/index.php")   echo '<link rel="stylesheet" type="text/css" href="ressources/styles/donut.css">';
        if (__ACTUAL_URI__ == "/run.php")     echo '<link rel="stylesheet" type="text/css" href="ressources/styles/run.css">';
        if (__ACTUAL_URI__ == "/explore.php") echo '<link rel="stylesheet" type="text/css" href="ressources/styles/explore.css">';
        if (__ACTUAL_URI__ == "/stats.php")   echo '<link rel="stylesheet" type="text/css" href="ressources/styles/stats-hosts.css">';
        if (__ACTUAL_URI__ == "/hosts.php")   echo '<link rel="stylesheet" type="text/css" href="ressources/styles/stats-hosts.css">';
        if (__ACTUAL_URI__ == "/host.php")    echo '<link rel="stylesheet" type="text/css" href="ressources/styles/stats-hosts.css">';
    ?>

    <!-- jQuery -->
    <script src="ressources/js/jquery/jquery-3.5.1.min.js"></script>
    <!-- Select2 https://select2.org/ -->
    <script src="ressources/js/jquery/select2.js"></script>
    <link rel="stylesheet" type='text/css' href="ressources/styles/select2.css">
    <!-- ChartJS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.1/chart.min.js" integrity="sha512-QSkVNOCYLtj73J4hbmVoOV6KVZuMluZlioC+trLpewV8qMjsWqlIQvkn1KGX2StWvPMdWGBqim1xlC8krl1EKQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <!-- Favicon -->
    <link rel="icon" href="ressources/favicon.ico" />

    <?php
    if     (__ACTUAL_URI__ == "/index.php")          echo '<title>Repomanager - accueil</title>';
    elseif (__ACTUAL_URI__ == "/planifications.php") echo '<title>Repomanager - planifications</title>';
    elseif (__ACTUAL_URI__ == "/run.php")            echo '<title>Repomanager - journal</title>';
    elseif (__ACTUAL_URI__ == "/explore.php")        echo '<title>Repomanager - explorer</title>';
    elseif (__ACTUAL_URI__ == "/stats.php")          echo '<title>Repomanager - statistiques</title>';
    elseif (__ACTUAL_URI__ == "/hosts.php")          echo '<title>Repomanager - gestion des hôtes</title>';
    elseif (__ACTUAL_URI__ == "/host.php")           echo '<title>Repomanager - gestion des hôtes</title>';
    elseif (__ACTUAL_URI__ == "/profiles.php")       echo '<title>Repomanager - gestion des profils</title>';
    elseif (__ACTUAL_URI__ == "/configuration.php")  echo '<title>Repomanager - configuration</title>';
    elseif (__ACTUAL_URI__ == "/history.php")        echo '<title>Repomanager - historique</title>';
    elseif (__ACTUAL_URI__ == "/user.php")           echo '<title>Repomanager - espace utilisateur</title>';
    else echo '<title>Repomanager</title>';
    ?>
    <title>Repomanager</title>
</head>