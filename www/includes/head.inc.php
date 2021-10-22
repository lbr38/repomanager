<head>
    <meta charset="utf-8">
    <!-- CSS -->
    <link rel="stylesheet" type="text/css" href="styles/main.css">
    <link rel="stylesheet" type="text/css" href="styles/colors.php">
    <?php
        /**
         *  Chargement de CSS suplémentaires
         */
        if (parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH) == "/run.php")     echo '<link rel="stylesheet" type="text/css" href="styles/run.css">';
        if (parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH) == "/explore.php") echo '<link rel="stylesheet" type="text/css" href="styles/explore.css">';
        if (parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH) == "/stats.php")   echo '<link rel="stylesheet" type="text/css" href="styles/stats.css">';
    ?>

    <!-- jQuery -->
    <script src="js/jquery/jquery-3.5.1.min.js"></script>
    <!-- Select2 https://select2.org/ -->
    <script src="js/jquery/select2.js"></script>
    <link rel="stylesheet" type='text/css' href="styles/select2.css">
    <!-- ChartJS -->
    <script src="js/chartjs/Chart.bundle.min.js"></script>    
    <!-- Favicon -->
    <link rel="icon" href="favicon.ico" />
    <title>Repomanager</title>
</head>

<!-- Affichage d'un logo chargement de la page -->
<!--<div class="loader-wrapper">
    <img src="images/loading.gif" />
    <span class="loader"><span class="loader-inner"></span></span>
</div>-->