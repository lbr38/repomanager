<?php
// Paramètres d'affichage

// Affichage dans la liste des repos
$printRepoSize = "yes"; // afficher la taille des repos ou non dans la liste des repos
$filterByGroups = "no"; // filtrer par groupes dans la liste des repos
$concatenateReposName = "no"; // simplifier la vue en n'affichant pas les noms de repos similaires
$alternateColors = "yes"; // alterner les couleurs dans la liste
$alternativeColor1 = exec("grep 'color1:' ${WWW_DIR}/styles/vars/colors.css | awk -F '--color1:' '{print $2}' | sed 's/ //g' | sed 's/;//g'");
$alternativeColor2 = exec("grep 'color2:' ${WWW_DIR}/styles/vars/colors.css | awk -F '--color2:' '{print $2}' | sed 's/ //g' | sed 's/;//g'");
$debugMode = "disabled";
?>
