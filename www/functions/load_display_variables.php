<?php
// Chargement des paramètres d'affichage //

$WWW_DIR = dirname(__FILE__, 2);

// PARAMETRES D'AFFICHAGE //

// Récupération de tous les paramètres définis dans le fichier display.ini
$display_ini_array = parse_ini_file("${WWW_DIR}/configurations/display.ini");

// Chargement des paramètres d'affichage de la liste des repos
$printRepoSize = $display_ini_array['printRepoSize'];
$filterByGroups = $display_ini_array['filterByGroups'];
$concatenateReposName = $display_ini_array['concatenateReposName'];
$alternateColors = $display_ini_array['alternateColors'];
$alternativeColor1 = $display_ini_array['alternativeColor1'];
$alternativeColor2 = $display_ini_array['alternativeColor2'];
$dividingLine = $display_ini_array['dividingLine'];
$cache_repos_list = $display_ini_array['cache_repos_list'];

$display_serverInfo_reposInfo = $display_ini_array['display_serverInfo_reposInfo'];
$display_serverInfo_rootSpace = $display_ini_array['display_serverInfo_rootSpace'];
$display_serverInfo_reposDirSpace = $display_ini_array['display_serverInfo_reposDirSpace'];
$display_serverInfo_planInfo = $display_ini_array['display_serverInfo_planInfo'];

unset($display_ini_array);
?>