<html>
<?php include('common-head.inc.php'); ?>

<?php
    // Import des variables nécessaires, ne pas changer l'ordre des require
    require 'common-vars.php';
    require 'common-functions.php';
    require 'common.php';
    require 'display.php';
    if ($debugMode == "enabled") { print_r($_POST); }
?>

<body>
<?php include('common-header.inc.php'); ?>

<article class="main">

  <!-- REPOS ACTIFS -->
  <article class="left">
    <?php include('common-repos-list.inc.php'); ?>
  </article>

  <article class="right">
    <h5>OPERATIONS</h5>

    <!--<form action="traitement.php" method="post" class="actionform">-->
    <form action="traitement.php" method="get" class="actionform">
    <table class="actiontable">
    <?php

    // On vérifie qu'une action a été demandée
    if (empty($_POST['actionId']) AND empty($_GET['actionId'])) {
        echo "<tr>";
        echo "<td>";
        echo "Erreur : aucune action n'a été demandée";
        echo "</td>";
        echo "</tr>";
        return 1;
    } else { // et on la récupère si c'est le cas
        if (!empty($_POST['actionId'])) {   // Récupération de actionId si elle a été transmise en POST
            $actionId = validateData($_POST['actionId']);
        }
        if (!empty($_GET['actionId'])) {    // Récupération de actionId si elle a été transmise en GET
            $actionId = validateData($_GET['actionId']);
        }
    }


// TRAITEMENT DES ACTIONS //

// Action newRepo :
function checkAction_newRepo() {
    require 'common-vars.php';
    
    // On va devoir retransmettre l'actionId à cette même page pour demander confirmation
    if (!empty($_POST['actionId'])) {
        $actionId = validateData($_POST['actionId']);
        echo "<td><input type=\"hidden\" name=\"actionId\" value=\"$actionId\"></td>";
    }
    

// 1ère étape : on vérifie qu'on a bien reçu toutes les variables nécéssaires en POST :

    // Pour Debian si repoHostName est vide, on affiche un formulaire pour le demander 
    if ($OS_TYPE == "deb" AND empty($_POST['repoHostName'])) {
        echo "<tr>";
        echo "<td>Nom de l'hôte</td>";
        echo "<td><input type=\"text\" name=\"repoHostName\" autocomplete=\"off\" placeholder=\"Vous devez renseigner un nom d'hôte\"></td>";
        echo "<tr>";
    } else {
        $repoHostName = validateData($_POST['repoHostName']);
        echo "<td><input type=\"hidden\" name=\"repoHostName\" value=\"$repoHostName\"></td>"; 
    }

    // Pour Redhat si repoRealname est vide, on affiche un formulaire pour le demander 
    if ($OS_TYPE == "rpm" AND empty($_POST['repoRealname'])) {
        echo "<tr>";
        echo "<td>Nom du repo source</td>";
        echo "<td><input type=\"text\" name=\"repoRealname\" autocomplete=\"off\" placeholder=\"Vous devez renseigner le nom du repo source\"></td>";
        echo "<tr>";
    } else {
        $repoRealname = validateData($_POST['repoRealname']);
        echo "<td><input type=\"hidden\" name=\"repoRealname\" value=\"$repoRealname\"></td>"; 
    }

    // Si un alias a été renseigné, on le récupère
    if (empty($_POST['repoAlias'])) {
        $repoAlias = "noalias";
    } else {
        $repoAlias = validateData($_POST['repoAlias']);
    }
    echo "<td><input type=\"hidden\" name=\"repoAlias\" value=\"$repoAlias\"></td>";

    // Si repoDist est vide, on affiche un formulaire pour le demander (Debian uniquement) :
    if ($OS_TYPE == "deb" AND empty($_POST['repoDist'])) {
        echo "<tr>";
        echo "<td>Distribution</td>";
        echo "<td><input type=\"text\" name=\"repoDist\" autocomplete=\"off\" placeholder=\"Vous devez renseigner une distribution\"></td>";
        echo "<tr>";
    } else {
        $repoDist = validateData($_POST['repoDist']);
        echo "<td><input type=\"hidden\" name=\"repoDist\" value=\"$repoDist\"></td>";
    }

    // Si repoSection est vide, on affiche un formulaire pour le demander (Debian uniquement) :
    if ($OS_TYPE == "deb" AND empty($_POST['repoSection'])) {
        echo "<tr>";
        echo "<td>Section</td>";
        echo "<td><input type=\"text\" name=\"repoSection\" autocomplete=\"off\" placeholder=\"Vous devez renseigner une section\"></td>";
        echo "<tr>";
    } else {
        $repoSection = validateData($_POST['repoSection']);
        echo "<td><input type=\"hidden\" name=\"repoSection\" value=\"$repoSection\"></td>";
    }

    // La description peut rester vide
    if (empty($_POST['repoDescription'])) {
        $repoDescription = "nodescription";
    } else {
        $repoDescription = validateData($_POST['repoDescription']);
    }
    echo "<td><input type=\"hidden\" name=\"repoDescription\" value=\"$repoDescription\"></td>";

    // newRepoGpgCheck ne peut pas être vide car un des deux boutons radio est forcément coché, donc on le récupère.
    // Dans tous les cas le traitement ne pourra pas se faire si on envoie une valeur vide
    $newRepoGpgCheck = validateData($_POST['newRepoGpgCheck']);
    echo "<td><input type=\"hidden\" name=\"newRepoGpgCheck\" value=\"$newRepoGpgCheck\"></td>";
    
    // repoGpgResign ne peut pas être vide car un des deux boutons radio est forcément coché, donc on le récupère.
    // Dans tous les cas le traitement ne pourra pas se faire si on envoie une valeur vide
    $repoGpgResign = validateData($_POST['repoGpgResign']);
    echo "<td><input type=\"hidden\" name=\"repoGpgResign\" value=\"$repoGpgResign\"></td>";

// 2ème étape, si on a toutes les variables, on demande une confirmation puis si on a la confirmation alors on lance l'exécution

    // Cas Redhat :
    if ($OS_TYPE == "rpm") {
        if (!empty($repoRealname) AND !empty($repoAlias) AND !empty($repoDescription) AND !empty($newRepoGpgCheck) AND !empty($repoGpgResign)) {
            if ($repoAlias === "noalias") {
                $repoName = $repoRealname;
            } else {
                $repoName = $repoAlias;
            }

            // Ok on a toutes les infos mais il faut vérifier qu'un repo du même nom n'existe pas déjà (ou repoAlias)
            // On vérifie qu'un repo de même nom n'exite pas déjà :
            $checkifRepoExist = exec("grep '^Name=\"${repoName}\",Realname=\"${repoHostName}\",Env=\"${REPO_DEFAULT_ENV}\"' $REPO_FILE");
            if (!empty($checkifRepoExist)) {
                echo "<tr>";
                echo "<td>Erreur : Un repo du même nom existe déjà en ${REPO_DEFAULT_ENV}</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan=\"100%\"><a href=\"index.php\"><button class=\"button-submit-large-red\">Retour</button></a></td>";
                echo "</tr>";
                return 1; // On sort de la fonction pour ne pas que les conditions suivantes (ci-dessous) s'exécutent
            }

            // On vérifie que le repo existe dans /etc/yum.repos.d/ :
            $checkifRepoRealnameExist = exec("grep '^\\[${repoRealname}\\]' ${REPOMANAGER_YUM_DIR}/*.repo");
            if (empty($checkifRepoRealnameExist)) {
                echo "<tr>";
                echo "<td>Erreur : Il n'existe aucun fichier de repo dans ${REPOMANAGER_YUM_DIR}/ pour le nom de repo [${repoRealname}]</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan=\"100%\"><a href=\"index.php\"><button class=\"button-submit-large-red\">Retour</button></a></td>";
                echo "</tr>";
                return 1; // On sort de la fonction pour ne pas que les conditions suivantes (ci-dessous) s'exécutent
            }

            // Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
            if (empty($_POST['confirm'])) {
                echo "<tr>";
                echo "<td>L'opération va créer un nouveau repo ${repoName}</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td>Confirmer :</td>";
                echo "</tr>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan=\"100%\"><button type=\"submit\" class=\"button-submit-large-red\" name=\"confirm\" value=\"yes\">Confirmer et exécuter</button></td>";
                echo "</tr>";
            }

            // Si on a reçu la confirmation en POST alors on traite :
            if (!empty($_POST['confirm']) AND (validateData($_POST['confirm']) == "yes")) {
                if ($newRepoGpgCheck == "no") {
                    if ($repoGpgResign == "no") {
                        exec("${REPOMANAGER} --web --newRepo --gpg-check no --gpg-resign no --repo-name $repoName --repo-real-name $repoRealname --repo-description $repoDescription >/dev/null 2>/dev/null &");
                    } else {
                        exec("${REPOMANAGER} --web --newRepo --gpg-check no --gpg-resign yes --repo-name $repoName --repo-real-name $repoRealname --repo-description $repoDescription >/dev/null 2>/dev/null &");
                    }
                } else {
                    if ($repoGpgResign == "no") {
                        exec("${REPOMANAGER} --web --newRepo --gpg-check yes --gpg-resign no --repo-name $repoName --repo-real-name $repoRealname --repo-description $repoDescription >/dev/null 2>/dev/null &");
                    } else {
                        exec("${REPOMANAGER} --web --newRepo --gpg-check yes --gpg-resign yes --repo-name $repoName --repo-real-name $repoRealname --repo-description $repoDescription >/dev/null 2>/dev/null &");
                    }
                }
                echo "<script>window.location.replace('/journal.php');</script>"; // Dans les deux cas on redirige vers la page de logs pour voir l'exécution
            }

        // Dans le cas où on n'a pas transmis toutes les infos, un formulaire est apparu pour demander les infos manquantes, on ajoute alors un bouton submit pour valider ce formulaire :
        } else {
            echo "<tr>";
            echo "<td colspan=\"100%\"><button type=\"submit\" class=\"button-submit-large-red\">Valider</button></td>";
            echo "</tr>";
        }
    }


    // Cas Debian :
    if ($OS_TYPE == "deb") {
        if (!empty($repoHostName) AND !empty($repoAlias) AND !empty($repoDist) AND !empty($repoSection) AND !empty($repoDescription) AND !empty($newRepoGpgCheck)) {
            // Si repoAlias a été transmis vide, alors repoName reprend le nom de l'hote et l'alias est set à 'null'
            if ($repoAlias === "noalias") {
                $repoName = $repoHostName;
            } else {
                $repoName = $repoAlias;
            }

            // Ok on a toutes les infos mais il faut vérifier qu'un repo du même nom n'existe pas déjà (ou repoAlias)
            // On vérifie qu'un repo de même nom, de même distribution et de même section n'exite pas déjà :
            $checkifRepoExist = exec("grep '^Name=\"${repoName}\",Host=\"${repoHostName}\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Env=\"${REPO_DEFAULT_ENV}\"' $REPO_FILE");
            if (!empty($checkifRepoExist)) {
                echo "<tr>";
                echo "<td>Erreur : Une section et un repo du même nom existe déjà en ${REPO_DEFAULT_ENV}</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan=\"100%\"><a href=\"index.php\"><button class=\"button-submit-large-red\">Retour</button></a></td>";
                echo "</tr>";
                return 1; // On sort de la fonction pour ne pas que les conditions suivantes (ci-dessous) s'exécutent
            }

            // On vérifie qu'une url hôte source existe pour le nom de repo renseigné :
            $checkifRepoHostExist = exec("grep '^Name=\"${repoHostName}\",' $REPO_ORIGIN_FILE");
            if (empty($checkifRepoHostExist)) {
                echo "<tr>";
                echo "<td>Erreur : Il n'existe aucune URL hôte pour le nom de repo ${repoName}</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan=\"100%\"><a href=\"index.php\"><button class=\"button-submit-large-red\">Retour</button></a></td>";
                echo "</tr>";
                return 1; // On sort de la fonction pour ne pas que les conditions suivantes (ci-dessous) s'exécutent
            }

            // Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
            if (empty($_POST['confirm'])) {
                echo "<tr>";
                echo "<td>L'opération va créer un nouveau repo ${repoName}, de distribution ${repoDist} et de section ${repoSection}</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td>Confirmer :</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan=\"100%\"><button type=\"submit\" class=\"button-submit-large-red\" name=\"confirm\" value=\"yes\">Confirmer et exécuter</button></td>";
                echo "</tr>";
            }

            // Si on a reçu la confirmation en POST alors on traite :
            if (!empty($_POST['confirm']) AND (validateData($_POST['confirm']) == "yes")) {
                if ($newRepoGpgCheck == "no") {
                    exec("${REPOMANAGER} --web --newRepo --gpg-check no --repo-name $repoName --repo-host-name $repoHostName --repo-dist $repoDist --repo-section $repoSection --repo-description $repoDescription >/dev/null 2>/dev/null &");
                } else {
                    exec("${REPOMANAGER} --web --newRepo --gpg-check yes --repo-name $repoName --repo-host-name $repoHostName --repo-dist $repoDist --repo-section $repoSection --repo-description $repoDescription >/dev/null 2>/dev/null &");
                }
                echo "<script>window.location.replace('/journal.php');</script>"; // Dans les deux cas on redirige vers la page de logs pour voir l'exécution
            }

        // Dans le cas où on n'a pas transmis toutes les infos, un formulaire est apparu pour demander les infos manquantes, on ajoute alors un bouton submit pour valider ce formulaire :
        } else {
            echo "<tr>";
            echo "<td colspan=\"100%\"><button type=\"submit\" class=\"button-submit-large-red\">Valider</button></td>";
            echo "</tr>";
        }
    }
}




function checkAction_updateRepo() {
    require 'common-vars.php';
    
    // On va devoir retransmettre l'actionId à cette même page pour demander confirmation
    if (!empty($_POST['actionId'])) {
        $actionId = validateData($_POST['actionId']);
        echo "<td><input type=\"hidden\" name=\"actionId\" value=\"$actionId\"></td>";
    }


// 1ère étape : on vérifie qu'on a bien reçu toutes les variables nécéssaires en POST :
    // Si repoName est vide, on affiche un formulaire pour le demander 
    if (empty($_POST['repoName'])) {
        echo "<tr>";
        echo "<td>Nom du repo</td>";
        echo "<td><input type=\"text\" name=\"repoName\" autocomplete=\"off\" placeholder=\"Vous devez renseigner un nom de repo\"></td>";
        echo "<tr>";
    } else {
        $repoName = validateData($_POST['repoName']);
        echo "<td><input type=\"hidden\" name=\"repoName\" value=\"$repoName\"></td>";
    }

    // Si repoDist est vide, on affiche un formulaire pour le demander (Debian uniquement) :
    if ($OS_TYPE == "deb" AND empty($_POST['repoDist'])) {
        echo "<tr>";
        echo "<td>Distribution</td>";
        echo "<td><input type=\"text\" name=\"repoDist\" autocomplete=\"off\" placeholder=\"Vous devez renseigner une distribution\"></td>";
        echo "<tr>";
    } else {
        $repoDist = validateData($_POST['repoDist']);
        echo "<td><input type=\"hidden\" name=\"repoDist\" value=\"$repoDist\"></td>";
    }

    // Si repoSection est vide, on affiche un formulaire pour le demander (Debian uniquement) :
    if ($OS_TYPE == "deb" AND empty($_POST['repoSection'])) {
        echo "<tr>";
        echo "<td>Section</td>";
        echo "<td><input type=\"text\" name=\"repoSection\" autocomplete=\"off\" placeholder=\"Vous devez renseigner une section\"></td>";
        echo "<tr>";
    } else {
        $repoSection = validateData($_POST['repoSection']);
        echo "<td><input type=\"hidden\" name=\"repoSection\" value=\"$repoSection\"></td>";
    }

    // updateRepoGpgCheck ne peut pas être vide car un des deux boutons radio est forcément coché, donc on le récupère.
    // Dans tous les cas le traitement ne pourra pas se faire si on envoie une valeur vide
    $updateRepoGpgCheck = validateData($_POST['updateRepoGpgCheck']);
    echo "<td><input type=\"hidden\" name=\"updateRepoGpgCheck\" value=\"$updateRepoGpgCheck\"></td>";
    
    // repoGpgResign ne peut pas être vide car un des deux boutons radio est forcément coché, donc on le récupère.
    // Dans tous les cas le traitement ne pourra pas se faire si on envoie une valeur vide
    $repoGpgResign = validateData($_POST['repoGpgResign']);
    echo "<td><input type=\"hidden\" name=\"repoGpgResign\" value=\"$repoGpgResign\"></td>";

// 2ème étape, si on a toutes les variables, on demande une confirmation puis si on a la confirmation alors on lance l'exécution

    // Cas Redhat :
    if ($OS_TYPE == "rpm") {
        if (!empty($repoName) AND !empty($updateRepoGpgCheck) AND !empty($repoGpgResign)) {
            // recup du vrai nom du repo :
            $repoRealname = exec("grep '^Name=\"${repoName}\",Realname=\".*\",' $REPO_FILE | awk -F ',' '{print $2}' | cut -d'=' -f2 | sed 's/\"//g'");
            // On vérifie que le repo existe dans /etc/yum.repos.d/ :
            $checkifRepoRealnameExist = exec("grep '^\\[${repoRealname}\\]' ${REPOMANAGER_YUM_DIR}/*.repo");
            if (empty($checkifRepoRealnameExist)) {
                echo "<tr>";
                echo "<td>Erreur : Il n'existe aucun fichier de repo dans ${REPOMANAGER_YUM_DIR}/ pour le nom de repo [${repoRealname}]</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan=\"100%\"><a href=\"index.php\"><button class=\"button-submit-large-red\">Retour</button></a></td>";
                echo "</tr>";
                return 1; // On sort de la fonction pour ne pas que les conditions suivantes (ci-dessous) s'exécutent
            }

            // Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
            if (empty($_POST['confirm'])) {
                echo "<tr>";
                echo "<td>L'opération va mettre à jour le repo ${repoName}</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td>Confirmer :</td>";
                echo "</tr>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan=\"100%\"><button type=\"submit\" class=\"button-submit-large-red\" name=\"confirm\" value=\"yes\">Confirmer et exécuter</button></td>";
                echo "</tr>";
            }

            // Si on a reçu la confirmation en POST alors on traite :
            if (!empty($_POST['confirm']) AND (validateData($_POST['confirm']) == "yes")) {
                if ($updateRepoGpgCheck == "no") {
                    if ($repoGpgResign == "no") {
                        exec("${REPOMANAGER} --web --updateRepo --gpg-check no --gpg-resign no --repo-name $repoName --repo-real-name $repoRealname >/dev/null 2>/dev/null &");
                    } else {
                        exec("${REPOMANAGER} --web --updateRepo --gpg-check no --gpg-resign yes --repo-name $repoName --repo-real-name $repoRealname >/dev/null 2>/dev/null &");
                    }
                } else {
                    if ($repoGpgResign == "no") {
                        exec("${REPOMANAGER} --web --updateRepo --gpg-check yes --gpg-resign no --repo-name $repoName --repo-real-name $repoRealname >/dev/null 2>/dev/null &");
                    } else {
                        exec("${REPOMANAGER} --web --updateRepo --gpg-check yes --gpg-resign yes --repo-name $repoName --repo-real-name $repoRealname >/dev/null 2>/dev/null &");
                    }
                }
                echo "<script>window.location.replace('/journal.php');</script>"; // Dans les deux cas on redirige vers la page de logs pour voir l'exécution
            }

        // Dans le cas où on n'a pas transmis toutes les infos, un formulaire est apparu pour demander les infos manquantes, on ajoute alors un bouton submit pour valider ce formulaire :
        } else {
            echo "<tr>";
            echo "<td colspan=\"100%\"><button type=\"submit\" class=\"button-submit-large-red\">Valider</button></td>";
            echo "</tr>";
        }
    }

    // Cas Debian :
    if ($OS_TYPE == "deb") {
        if (!empty($repoName) AND !empty($repoDist) AND !empty($repoSection) AND !empty($updateRepoGpgCheck)) {

            // on récupère le nom de l'hôte :
            $repoHostName = exec("grep '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\"' $REPO_FILE | awk -F ',' '{print $2}' | cut -d'=' -f2 | sed 's/\"//g'");
            // on vérifie dans le fichiers des hotes que l'hote récupéré existe bien :
            $checkIfHostExists = exec("grep 'Name=\"$repoHostName\",' $REPO_ORIGIN_FILE");
            if (empty($checkIfHostExists)) {
                echo "<tr>";
                echo "<td>Erreur : Il n'existe aucun hôte $checkIfHostExists pour le repo $repoHostName</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan=\"100%\"><a href=\"index.php\"><button class=\"button-submit-large-red\">Retour</button></a></td>";
                echo "</tr>";
                return 1;
            }

            // Ok on a toutes les infos mais pour mettre à jour un repo, il faut vérifier qu'il existe
            // On vérifie qu'un repo de même nom, de même distribution et de même section existe :
            $checkifRepoExist = exec("grep '^Name=\"${repoName}\",Host=\"${repoHostName}\",Dist=\"${repoDist}\",Section=\"${repoSection}\"' $REPO_FILE");
            if (empty($checkifRepoExist)) {
                echo "<tr>";
                echo "<td>Erreur : Il n'existe aucune section ${repoSection} du repo ${repoName} (distribution ${repoDist}) en ${REPO_DEFAULT_ENV} à mettre à jour. Il faut choisir l'option 'Créer une nouvelle section'</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan=\"100%\"><a href=\"index.php\"><button class=\"button-submit-large-red\">Retour</button></a></td>";
                echo "</tr>";
                return 1; // On sort de la fonction pour ne pas que les conditions suivantes (ci-dessous) s'exécutent
            }        

            // Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
            if (empty($_POST['confirm'])) {
                echo "<tr>";
                echo "<td>L'opération va mettre à jour la section ${repoSection} du repo ${repoName} (distribution ${repoDist})</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td>Confirmer :</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan=\"100%\"><button type=\"submit\" class=\"button-submit-large-red\" name=\"confirm\" value=\"yes\">Confirmer et exécuter</button></td>";
                echo "</tr>";
            }

            // Si on a reçu la confirmation en POST alors on traite :
            if (!empty($_POST['confirm']) AND (validateData($_POST['confirm']) == "yes")) {
                if ($updateRepoGpgCheck == "no") {
                    exec("${REPOMANAGER} --web --updateRepo --gpg-check no --repo-name $repoName --repo-host-name $repoHostName --repo-dist $repoDist --repo-section $repoSection >/dev/null 2>/dev/null &");
                } else {
                    exec("${REPOMANAGER} --web --updateRepo --gpg-check yes --repo-name $repoName --repo-host-name $repoHostName --repo-dist $repoDist --repo-section $repoSection >/dev/null 2>/dev/null &");
                }
                echo "<script>window.location.replace('/journal.php');</script>"; // Dans les deux cas on redirige vers la page de logs pour voir l'exécution
            }

        // Dans le cas où on n'a pas transmis toutes les infos, un formulaire est apparu pour demander les infos manquantes, on ajoute alors un bouton submit pour valider ce formulaire :
        } else {
            echo "<tr>";
            echo "<td colspan=\"100%\"><button type=\"submit\" class=\"button-submit-large-red\">Valider</button></td>";
            echo "</tr>";
        }
    }
}




function checkAction_changeEnv() {
    require 'common-vars.php';
    
    // On va devoir retransmettre l'actionId à cette même page pour demander confirmation
    if (!empty($_POST['actionId'])) {
        $actionId = validateData($_POST['actionId']);
        echo "<td><input type=\"hidden\" name=\"actionId\" value=\"$actionId\"></td>";
    }


// 1ère étape : on vérifie qu'on a bien reçu toutes les variables nécéssaires en POST :
    // Si repoName est vide, on affiche un formulaire pour le demander 
    if (empty($_POST['repoName'])) {
        echo "<tr>";
        echo "<td>Nom du repo</td>";
        echo "<td><input type=\"text\" name=\"repoName\" autocomplete=\"off\" placeholder=\"Vous devez renseigner un nom de repo\"></td>";
        echo "<tr>";
    } else {
        $repoName = validateData($_POST['repoName']);
        echo "<td><input type=\"hidden\" name=\"repoName\" value=\"$repoName\"></td>";
    }

    // Si repoDist est vide, on affiche un formulaire pour le demander (Debian uniquement) :
    if ($OS_TYPE == "deb" AND empty($_POST['repoDist'])) {
        echo "<tr>";
        echo "<td>Distribution</td>";
        echo "<td><input type=\"text\" name=\"repoDist\" autocomplete=\"off\" placeholder=\"Vous devez renseigner une distribution\"></td>";
        echo "<tr>";
    } else {
        $repoDist = validateData($_POST['repoDist']);
        echo "<td><input type=\"hidden\" name=\"repoDist\" value=\"$repoDist\"></td>";
    }

    // Si repoSection est vide, on affiche un formulaire pour le demander (Debian uniquement) :
    if ($OS_TYPE == "deb" AND empty($_POST['repoSection'])) {
        echo "<tr>";
        echo "<td>Section</td>";
        echo "<td><input type=\"text\" name=\"repoSection\" autocomplete=\"off\" placeholder=\"Vous devez renseigner une section\"></td>";
        echo "<tr>";
    } else {
        $repoSection = validateData($_POST['repoSection']);
        echo "<td><input type=\"hidden\" name=\"repoSection\" value=\"$repoSection\"></td>";
    }

    // Si repoEnv est vide, on affiche un formulaire pour le demander 
    if (empty($_POST['repoEnv'])) {
        echo "<tr>";
        echo "<td>Env actuel</td>";
        echo "<td><input type=\"text\" name=\"repoEnv\" autocomplete=\"off\" placeholder=\"Vous devez renseigner l'env actuel du repo\"></td>";
        echo "<tr>";
    } else {
        $repoEnv = validateData($_POST['repoEnv']);
        echo "<td><input type=\"hidden\" name=\"repoEnv\" value=\"$repoEnv\"></td>";
    }

    // Si repoNewEnv est vide, on affiche un formulaire pour le demander 
    if (empty($_POST['repoNewEnv'])) {
        echo "<tr>";
        echo "<td>Nouvel env</td>";
        echo "<td><input type=\"text\" name=\"repoNewEnv\" autocomplete=\"off\" placeholder=\"Vous devez renseigner le nouvel env du repo\"></td>";
        echo "<tr>";
    } else {
        $repoNewEnv = validateData($_POST['repoNewEnv']);
        echo "<td><input type=\"hidden\" name=\"repoNewEnv\" value=\"$repoNewEnv\"></td>";
    }

    // La description peut rester vide
    if (empty($_POST['repoDescription'])) {
        $repoDescription = "nodescription";
    } else {
        $repoDescription = validateData($_POST['repoDescription']);
    }
    echo "<td><input type=\"hidden\" name=\"repoDescription\" value=\"$repoDescription\"></td>";


// 2ème étape, si on a toutes les variables, on demande une confirmation puis si on a la confirmation alors on lance l'exécution

    // Cas Redhat :
    if ($OS_TYPE == "rpm") {
        if (!empty($repoName) AND !empty($repoEnv) AND !empty($repoNewEnv) AND !empty($repoDescription)) {
 
            // Ok on a toutes les infos mais pour changer l'env d'un repo, il faut vérifier qu'il existe
            // On vérifie qu'un repo de même nom existe à l'env indiqué :
            $checkifRepoExist = exec("grep '^Name=\"${repoName}\",Realname=\".*\",Env=\"${repoEnv}\"' $REPO_FILE");
            if (empty($checkifRepoExist)) {
                echo "<tr>";
                echo "<td>Erreur : Il n'existe aucun repo ${repoName} en ${repoEnv}.</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan=\"100%\"><a href=\"index.php\"><button class=\"button-submit-large-red\">Retour</button></a></td>";
                echo "</tr>";
                return 1; // On sort de la fonction pour ne pas que les conditions suivantes (ci-dessous) s'exécutent
            }

            // Ensuite on vérifie si un repo existe déjà dans le nouvel env indiqué. Si c'est le cas, alors il sera archivé (sauf si il est toujours utilisé par un autre environnement)
            $repoArchive = "no"; // on déclare une variable à 'no' par défaut
            $checkifRepoExist2 = exec("grep '^Name=\"${repoName}\",Realname=\".*\",Env=\"${repoNewEnv}\"' $REPO_FILE");
            if (!empty($checkifRepoExist2)) { // si un repo existe
                // du coup on vérifie que le repo à archiver n'est pas utilisé par un autre environnement :
                // on récupère sa date de synchro et on regarde si elle est utilisée par un autre env :
                $repoArchiveDate = exec("grep '^Name=\"${repoName}\",Realname=\".*\",Env=\"${repoNewEnv}\"' $REPO_FILE | awk -F ',' '{print $4}'");
                $checkifRepoExist3 = exec("grep '^Name=\"${repoName}\",Realname=\".*\",Env=\".*\",Date=\"${repoArchiveDate}\"' $REPO_FILE | grep -v '^Name=\"${repoName}\",Realname=\".*\",Env=\"${repoNewEnv}\"'");
                if (empty($checkifRepoExist3)) { // si le repo n'est pas utilisé par un autre environnement, alors on pourra indiquer qu'il sera archivé
                    $repoArchive = "yes";
                } 
            }

            // Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
            if (empty($_POST['confirm'])) {
                echo "<tr>";
                echo "<td>L'opération va modifier l'environnement du repo ${repoName} en passant de ${repoEnv} à ${repoNewEnv}</td>";
                echo "</tr>";
                if ($repoArchive == "yes") { echo "<tr><td>Le repo actuellement en ${repoNewEnv} à la date du ${repoArchiveDate} sera archivé</td></tr>"; } // si il y a un repo à archiver, on l'indique ainsi que sa date de synchro
                echo "<tr>";
                echo "<td>Confirmer :</td>";
                echo "</tr>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan=\"100%\"><button type=\"submit\" class=\"button-submit-large-red\" name=\"confirm\" value=\"yes\">Confirmer et exécuter</button></td>";
                echo "</tr>";
            }

            // Si on a reçu la confirmation en POST alors on traite :
            if (!empty($_POST['confirm']) AND (validateData($_POST['confirm']) == "yes")) {
                exec("${REPOMANAGER} --web --changeEnv --repo-name $repoName --repo-env $repoEnv --repo-new-env $repoNewEnv --repo-description $repoDescription >/dev/null 2>/dev/null &");
                echo "<script>window.location.replace('/journal.php');</script>"; // on redirige vers la page de logs pour voir l'exécution
            }

        // Dans le cas où on n'a pas transmis toutes les infos, un formulaire est apparu pour demander les infos manquantes, on ajoute alors un bouton submit pour valider ce formulaire :
        } else {
            echo "<tr>";
            echo "<td colspan=\"100%\"><button type=\"submit\" class=\"button-submit-large-red\">Valider</button></td>";
            echo "</tr>";
        }
    }


    // Cas Debian :
    if ($OS_TYPE == "deb") {
        if (!empty($repoName) AND !empty($repoDist) AND !empty($repoSection) AND !empty($repoEnv) AND !empty($repoNewEnv) AND !empty($repoDescription)) {

            // Ok on a toutes les infos mais pour changer l'env d'un repo (section en réalité), il faut vérifier qu'il existe
            // On vérifie qu'un repo de même nom existe à l'env indiqué :
            $checkifRepoExist = exec("grep '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Env=\"${repoEnv}\"' $REPO_FILE");
            if (empty($checkifRepoExist)) {
                echo "<tr>";
                echo "<td>Erreur : Il n'existe aucune section ${repoSection} du repo ${repoName} (distribution ${repoDist}) en ${repoEnv}</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan=\"100%\"><a href=\"index.php\"><button class=\"button-submit-large-red\">Retour</button></a></td>";
                echo "</tr>";
                return 1; // On sort de la fonction pour ne pas que les conditions suivantes (ci-dessous) s'exécutent
            }

            // Ensuite on vérifie si un repo existe déjà dans le nouvel env indiqué. Si c'est le cas, alors il sera archivé (sauf si il est toujours utilisé par un autre environnement)
            $repoArchive = "no"; // on déclare une variable à 'no' par défaut
            $checkifRepoExist2 = exec("grep '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Env=\"${repoNewEnv}\"' $REPO_FILE");
            if (!empty($checkifRepoExist2)) { // si un repo existe
                // du coup on vérifie que le repo à archiver n'est pas utilisé par un autre environnement :
                // on récupère sa date de synchro et on regarde si elle est utilisée par un autre env :
                $repoArchiveDate = exec("grep '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Env=\"${repoNewEnv}\"' $REPO_FILE | awk -F ',' '{print $6}'");
                $checkifRepoExist3 = exec("grep '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Env=\".*\",Date=\"${repoArchiveDate}\"' $REPO_FILE | grep -v '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Env=\"${repoNewEnv}\"'");
                if (empty($checkifRepoExist3)) { // si le repo n'est pas utilisé par un autre environnement, alors on pourra indiquer qu'il sera archivé
                    $repoArchive = "yes";
                }
            }

            // Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
            if (empty($_POST['confirm'])) {
                echo "<tr>";
                echo "<td>L'opération va modifier l'environnement de la section ${repoSection}, du repo ${repoName} (distribution ${repoDist}) en passant de ${repoEnv} à ${repoNewEnv}</td>";
                echo "</tr>";
                if ($repoArchive == "yes") { echo "<tr><td>La section actuellement en ${repoNewEnv} à la date du ${repoArchiveDate} sera archivée</td></tr>"; } // si il y a un repo à archiver, on l'indique ainsi que sa date de synchro
                echo "<tr>";
                echo "<td>Confirmer :</td>";
                echo "</tr>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan=\"100%\"><button type=\"submit\" class=\"button-submit-large-red\" name=\"confirm\" value=\"yes\">Confirmer et exécuter</button></td>";
                echo "</tr>";
            }

            // Si on a reçu la confirmation en POST alors on traite :
            if (!empty($_POST['confirm']) AND (validateData($_POST['confirm']) == "yes")) {
                exec("${REPOMANAGER} --web --changeEnv --repo-name $repoName --repo-dist $repoDist --repo-section $repoSection --repo-env $repoEnv --repo-new-env $repoNewEnv --repo-description $repoDescription >/dev/null 2>/dev/null &");
                echo "<script>window.location.replace('/journal.php');</script>"; // on redirige vers la page de logs pour voir l'exécution
            }

        // Dans le cas où on n'a pas transmis toutes les infos, un formulaire est apparu pour demander les infos manquantes, on ajoute alors un bouton submit pour valider ce formulaire :
        } else {
            echo "<tr>";
            echo "<td colspan=\"100%\"><button type=\"submit\" class=\"button-submit-large-red\">Valider</button></td>";
            echo "</tr>";
        }
    }
}




function checkAction_duplicateRepo() {
    require 'common-vars.php';
    
    // On va devoir retransmettre l'actionId à cette même page pour demander confirmation
    if (!empty($_POST['actionId'])) {
        $actionId = validateData($_POST['actionId']);
        echo "<td><input type=\"hidden\" name=\"actionId\" value=\"$actionId\"></td>";
    }


// 1ère étape : on vérifie qu'on a bien reçu toutes les variables nécéssaires en POST :

    // Si repoName est vide, on affiche un formulaire pour le demander 
    if (empty($_POST['repoName'])) {
        echo "<tr>";
        echo "<td>Nom du repo</td>";
        echo "<td><input type=\"text\" name=\"repoName\" autocomplete=\"off\" placeholder=\"Vous devez renseigner un nom de repo\"></td>";
        echo "<tr>";
    } else {
        $repoName = validateData($_POST['repoName']);
        echo "<td><input type=\"hidden\" name=\"repoName\" value=\"$repoName\"></td>";
    }

    // Si repoDist est vide, on affiche un formulaire pour le demander (Debian uniquement) :
    if ($OS_TYPE == "deb" AND empty($_POST['repoDist'])) {
        echo "<tr>";
        echo "<td>Distribution</td>";
        echo "<td><input type=\"text\" name=\"repoDist\" autocomplete=\"off\" placeholder=\"Vous devez renseigner une distribution\"></td>";
        echo "<tr>";
    } else {
        $repoDist = validateData($_POST['repoDist']);
        echo "<td><input type=\"hidden\" name=\"repoDist\" value=\"$repoDist\"></td>";
    }

    // Si repoSection est vide, on affiche un formulaire pour le demander (Debian uniquement) :
    if ($OS_TYPE == "deb" AND empty($_POST['repoSection'])) {
        echo "<tr>";
        echo "<td>Section</td>";
        echo "<td><input type=\"text\" name=\"repoSection\" autocomplete=\"off\" placeholder=\"Vous devez renseigner une section\"></td>";
        echo "<tr>";
    } else {
        $repoSection = validateData($_POST['repoSection']);
        echo "<td><input type=\"hidden\" name=\"repoSection\" value=\"$repoSection\"></td>";
    }

    // Si repoEnv est vide, on affiche un formulaire pour le demander 
    if (empty($_POST['repoEnv'])) {
        echo "<tr>";
        echo "<td>Env actuel</td>";
        echo "<td><input type=\"text\" name=\"repoEnv\" autocomplete=\"off\" placeholder=\"Vous devez renseigner l'env actuel du repo\"></td>";
        echo "<tr>";
    } else {
        $repoEnv = validateData($_POST['repoEnv']);
        echo "<td><input type=\"hidden\" name=\"repoEnv\" value=\"$repoEnv\"></td>";
    }

    // Si repoNewName est vide, on affiche un formulaire pour le demander 
    if (empty($_POST['repoNewName'])) {
        echo "<tr>";
        echo "<td>Env actuel</td>";
        echo "<td><input type=\"text\" name=\"repoNewName\" autocomplete=\"off\" placeholder=\"Vous devez renseigner le nom du nouveau repo\"></td>";
        echo "<tr>";
    } else {
        $repoNewName = validateData($_POST['repoNewName']);
        echo "<td><input type=\"hidden\" name=\"repoNewName\" value=\"$repoNewName\"></td>";
    }

    // La description peut rester vide
    if (empty($_POST['repoDescription'])) {
        $repoDescription = "nodescription";
    } else {
        $repoDescription = validateData($_POST['repoDescription']);
    }
    echo "<td><input type=\"hidden\" name=\"repoDescription\" value=\"$repoDescription\"></td>";


// 2ème étape, si on a toutes les variables, on demande une confirmation puis si on a la confirmation alors on lance l'exécution

    // Cas Redhat :
    if ($OS_TYPE == "rpm") {
        if (!empty($repoName) AND !empty($repoEnv) AND !empty($repoNewName) AND !empty($repoDescription)) {

            // Ok on a toutes les infos mais il faut vérifier qu'un repo du même nom n'existe pas déjà
            // On vérifie qu'un repo de même nom n'exite pas déjà :
            $checkifRepoExist = exec("grep '^Name=\"${repoNewName}\",Realname=\".*\",Env=\"${REPO_DEFAULT_ENV}\"' $REPO_FILE");
            if (!empty($checkifRepoExist)) {
                echo "<tr>";
                echo "<td>Erreur : Un repo du même nom existe déjà en ${REPO_DEFAULT_ENV}</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan=\"100%\"><a href=\"index.php\"><button class=\"button-submit-large-red\">Retour</button></a></td>";
                echo "</tr>";
                return 1; // On sort de la fonction pour ne pas que les conditions suivantes (ci-dessous) s'exécutent
            }

            // Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
            if (empty($_POST['confirm'])) {
                echo "<tr>";
                echo "<td>L'opération va créer un nouveau repo ${repoNewName} (copie de ${repoName})</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td>Confirmer :</td>";
                echo "</tr>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan=\"100%\"><button type=\"submit\" class=\"button-submit-large-red\" name=\"confirm\" value=\"yes\">Confirmer et exécuter</button></td>";
                echo "</tr>";
            }

            // Si on a reçu la confirmation en POST alors on traite :
            if (!empty($_POST['confirm']) AND (validateData($_POST['confirm']) == "yes")) {
                exec("${REPOMANAGER} --web --duplicateRepo --repo-name $repoName --repo-env $repoEnv --repo-new-name $repoNewName --repo-description $repoDescription >/dev/null 2>/dev/null &");
                echo "<script>window.location.replace('/journal.php');</script>"; // on redirige vers la page de logs pour voir l'exécution
            }

        // Dans le cas où on n'a pas transmis toutes les infos, un formulaire est apparu pour demander les infos manquantes, on ajoute alors un bouton submit pour valider ce formulaire :
        } else {
            echo "<tr>";
            echo "<td colspan=\"100%\"><button type=\"submit\" class=\"button-submit-large-red\">Valider</button></td>";
            echo "</tr>";
        }
    }


    // Cas Debian :
    if ($OS_TYPE == "deb") {
        if (!empty($repoName) AND !empty($repoDist) AND !empty($repoSection) AND !empty($repoEnv) AND !empty($repoNewName) AND !empty($repoDescription)) {

            // Ok on a toutes les infos mais il faut vérifier qu'un repo du même nom n'existe pas déjà
            // On vérifie qu'un repo de même nom n'exite pas déjà :
            $checkifRepoExist = exec("grep '^Name=\"${repoNewName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Env=\"${REPO_DEFAULT_ENV}\"' $REPO_FILE");
            if (!empty($checkifRepoExist)) {
                echo "<tr>";
                echo "<td>Erreur : Un repo du même nom existe déjà en ${REPO_DEFAULT_ENV}</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan=\"100%\"><a href=\"index.php\"><button class=\"button-submit-large-red\">Retour</button></a></td>";
                echo "</tr>";
                return 1; // On sort de la fonction pour ne pas que les conditions suivantes (ci-dessous) s'exécutent
            }

            // Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
            if (empty($_POST['confirm'])) {
                echo "<tr>";
                echo "<td>L'opération va créer un nouveau repo ${repoNewName} (distribution ${repoDist} et section ${repoSection}), copie de ${repoName}</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td>Confirmer :</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan=\"100%\"><button type=\"submit\" class=\"button-submit-large-red\" name=\"confirm\" value=\"yes\">Confirmer et exécuter</button></td>";
                echo "</tr>";
            }

            // Si on a reçu la confirmation en POST alors on traite :
            if (!empty($_POST['confirm']) AND (validateData($_POST['confirm']) == "yes")) {
                exec("${REPOMANAGER} --web --duplicateRepo --repo-name $repoName --repo-dist $repoDist --repo-section $repoSection --repo-env $repoEnv --repo-new-name $repoNewName --repo-description $repoDescription >/dev/null 2>/dev/null &");
                echo "<script>window.location.replace('/journal.php');</script>"; // Dans les deux cas on redirige vers la page de logs pour voir l'exécution
            }

        // Dans le cas où on n'a pas transmis toutes les infos, un formulaire est apparu pour demander les infos manquantes, on ajoute alors un bouton submit pour valider ce formulaire :
        } else {
            echo "<tr>";
            echo "<td colspan=\"100%\"><button type=\"submit\" class=\"button-submit-large-red\">Valider</button></td>";
            echo "</tr>";
        }
    }
}




function checkAction_deleteSection() {
    require 'common-vars.php';
    
    // On va devoir retransmettre l'actionId à cette même page pour demander confirmation
    if (!empty($_POST['actionId'])) {
        $actionId = validateData($_POST['actionId']);
        echo "<td><input type=\"hidden\" name=\"actionId\" value=\"$actionId\"></td>";
    }


// 1ère étape : on vérifie qu'on a bien reçu toutes les variables nécéssaires en POST :

    // Si repoName est vide, on affiche un formulaire pour le demander 
    if (empty($_POST['repoName'])) {
        echo "<tr>";
        echo "<td>Nom du repo</td>";
        echo "<td><input type=\"text\" name=\"repoName\" autocomplete=\"off\" placeholder=\"Vous devez renseigner un nom de repo\"></td>";
        echo "<tr>";
    } else {
        $repoName = validateData($_POST['repoName']);
        echo "<td><input type=\"hidden\" name=\"repoName\" value=\"$repoName\"></td>";
    }

    // Si repoDist est vide, on affiche un formulaire pour le demander (Debian uniquement) :
    if (empty($_POST['repoDist'])) {
        echo "<tr>";
        echo "<td>Distribution</td>";
        echo "<td><input type=\"text\" name=\"repoDist\" autocomplete=\"off\" placeholder=\"Vous devez renseigner une distribution\"></td>";
        echo "<tr>";
    } else {
        $repoDist = validateData($_POST['repoDist']);
        echo "<td><input type=\"hidden\" name=\"repoDist\" value=\"$repoDist\"></td>";
    }

    // Si repoSection est vide, on affiche un formulaire pour le demander (Debian uniquement) :
    if (empty($_POST['repoSection'])) {
        echo "<tr>";
        echo "<td>Section</td>";
        echo "<td><input type=\"text\" name=\"repoSection\" autocomplete=\"off\" placeholder=\"Vous devez renseigner une section\"></td>";
        echo "<tr>";
    } else {
        $repoSection = validateData($_POST['repoSection']);
        echo "<td><input type=\"hidden\" name=\"repoSection\" value=\"$repoSection\"></td>";
    }

    // Si repoEnv est vide, on affiche un formulaire pour le demander 
    if (empty($_POST['repoEnv'])) {
        echo "<tr>";
        echo "<td>Env actuel</td>";
        echo "<td><input type=\"text\" name=\"repoEnv\" autocomplete=\"off\" placeholder=\"Vous devez renseigner l'env actuel de la section\"></td>";
        echo "<tr>";
    } else {
        $repoEnv = validateData($_POST['repoEnv']);
        echo "<td><input type=\"hidden\" name=\"repoEnv\" value=\"$repoEnv\"></td>";
    }


// 2ème étape, si on a toutes les variables, on demande une confirmation puis si on a la confirmation alors on lance l'exécution

    if (!empty($repoName) AND !empty($repoDist) AND !empty($repoSection) AND !empty($repoEnv)) {

        // Ok on a toutes les infos mais il faut vérifier que la section mentionnée existe :
        $checkifRepoExist = exec("grep '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Env=\"${repoEnv}\"' $REPO_FILE");
        if (empty($checkifRepoExist)) {
            echo "<tr>";
            echo "<td>Erreur : Il n'existe aucune section ${repoSection} du repo ${repoName} (distribution ${repoDist}) en ${repoEnv}</td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td colspan=\"100%\"><a href=\"index.php\"><button class=\"button-submit-large-red\">Retour</button></a></td>";
            echo "</tr>";
            return 1; // On sort de la fonction pour ne pas que les conditions suivantes (ci-dessous) s'exécutent
        }

        // Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
        if (empty($_POST['confirm'])) {
            echo "<tr>";
            echo "<td>L'opération va supprimer la section ${repoSection} du repo ${repoName} (distribution ${repoDist}) en ${repoEnv}</td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td>Confirmer :</td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td colspan=\"100%\"><button type=\"submit\" class=\"button-submit-large-red\" name=\"confirm\" value=\"yes\">Confirmer et exécuter</button></td>";
            echo "</tr>";
        }

        // Si on a reçu la confirmation en POST alors on traite :
        if (!empty($_POST['confirm']) AND (validateData($_POST['confirm']) == "yes")) {
            exec("${REPOMANAGER} --web --deleteSection --repo-name $repoName --repo-dist $repoDist --repo-section $repoSection --repo-env $repoEnv >/dev/null 2>/dev/null &");
            echo "<script>window.location.replace('/journal.php');</script>"; // Dans les deux cas on redirige vers la page de logs pour voir l'exécution
        }

    // Dans le cas où on n'a pas transmis toutes les infos, un formulaire est apparu pour demander les infos manquantes, on ajoute alors un bouton submit pour valider ce formulaire :
    } else {
        echo "<tr>";
        echo "<td colspan=\"100%\"><button type=\"submit\" class=\"button-submit-large-red\">Valider</button></td>";
        echo "</tr>";
    }
}




function checkAction_deleteDist() {
    require 'common-vars.php';
    
    // On va devoir retransmettre l'actionId à cette même page pour demander confirmation
    if (!empty($_POST['actionId'])) {
        $actionId = validateData($_POST['actionId']);
        echo "<td><input type=\"hidden\" name=\"actionId\" value=\"$actionId\"></td>";
    }


// 1ère étape : on vérifie qu'on a bien reçu toutes les variables nécéssaires en POST :

    // Si repoName est vide, on affiche un formulaire pour le demander 
    if (empty($_POST['repoName'])) {
        echo "<tr>";
        echo "<td>Nom du repo</td>";
        echo "<td><input type=\"text\" name=\"repoName\" autocomplete=\"off\" placeholder=\"Vous devez renseigner un nom de repo\"></td>";
        echo "<tr>";
    } else {
        $repoName = validateData($_POST['repoName']);
        echo "<td><input type=\"hidden\" name=\"repoName\" value=\"$repoName\"></td>";
    }

    // Si repoDist est vide, on affiche un formulaire pour le demander (Debian uniquement) :
    if (empty($_POST['repoDist'])) {
        echo "<tr>";
        echo "<td>Distribution</td>";
        echo "<td><input type=\"text\" name=\"repoDist\" autocomplete=\"off\" placeholder=\"Vous devez renseigner une distribution\"></td>";
        echo "<tr>";
    } else {
        $repoDist = validateData($_POST['repoDist']);
        echo "<td><input type=\"hidden\" name=\"repoDist\" value=\"$repoDist\"></td>";
    }


// 2ème étape, si on a toutes les variables, on demande une confirmation puis si on a la confirmation alors on lance l'exécution

    if (!empty($repoName) AND !empty($repoDist)) {

        // Ok on a toutes les infos mais il faut vérifier que la distribution mentionnée existe :
        $checkifDistExist = exec("grep '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\"' $REPO_FILE");
        if (empty($checkifDistExist)) {
            echo "<tr>";
            echo "<td>Erreur : Il n'existe aucune distribution ${repoSection} du repo ${repoName}</td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td colspan=\"100%\"><a href=\"index.php\"><button class=\"button-submit-large-red\">Retour</button></a></td>";
            echo "</tr>";
            return 1; // On sort de la fonction pour ne pas que les conditions suivantes (ci-dessous) s'exécutent
        }

        // Ok la distribution existe mais peut être que celle-ci contient plusieurs sections qui seront supprimées, on récupère les sections concernées   Section=toto, Env=pprd
        // et on les affichera dans la demande de confirmation
        $sectionsToBeDeleted = shell_exec("grep '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\"' $REPO_FILE | awk -F ',' '{print $4, $5}' | sed 's|Section=\"||g' | sed 's|\" Env=\"| (|g' | sed 's|\"|)|g'");
        $sectionsToBeDeleted = explode("\n", $sectionsToBeDeleted);
        $sectionsToBeDeleted = array_filter($sectionsToBeDeleted);

        // Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
        if (empty($_POST['confirm'])) {
            echo "<tr>";
            echo "<td>L'opération va supprimer tout le contenu de la distribution ${repoDist} du repo ${repoName}, incluant les versions archivées si il y en a</td>";
            echo "</tr>";
            echo "<tr>";
            if (!empty($sectionsToBeDeleted)) {
                echo "<td>";
                echo "Attention, cela supprimera les sections suivantes :";
                foreach ($sectionsToBeDeleted as $section) {
                    echo "<br> - ${section}";
                }
                echo "</td>";
            } else {
                echo "<td>Attention, impossible de récupérer le nom des sections impactées.<br>L'opération supprimera tout le contenu de la distribution et donc les sections qu'elle contient (tout env confondu)</td>";
            }
            echo "</tr>";
            echo "<tr>";
            echo "<td>Confirmer :</td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td colspan=\"100%\"><button type=\"submit\" class=\"button-submit-large-red\" name=\"confirm\" value=\"yes\">Confirmer et exécuter</button></td>";
            echo "</tr>";
        }

        // Si on a reçu la confirmation en POST alors on traite :
        if (!empty($_POST['confirm']) AND (validateData($_POST['confirm']) == "yes")) {
            exec("${REPOMANAGER} --web --deleteDist --repo-name $repoName --repo-dist $repoDist >/dev/null 2>/dev/null &");
            echo "<script>window.location.replace('/journal.php');</script>"; // Dans les deux cas on redirige vers la page de logs pour voir l'exécution
        }

    // Dans le cas où on n'a pas transmis toutes les infos, un formulaire est apparu pour demander les infos manquantes, on ajoute alors un bouton submit pour valider ce formulaire :
    } else {
        echo "<tr>";
        echo "<td colspan=\"100%\"><button type=\"submit\" class=\"button-submit-large-red\">Valider</button></td>";
        echo "</tr>";
    }
}




function checkAction_deleteRepo() {
    require 'common-vars.php';
    
    // On va devoir retransmettre l'actionId à cette même page pour demander confirmation
    if (!empty($_GET['actionId'])) {
        $actionId = validateData($_GET['actionId']);
        echo "<td><input type=\"hidden\" name=\"actionId\" value=\"$actionId\"></td>";
    }

// 1ère étape : on vérifie qu'on a bien reçu toutes les variables nécéssaires en POST :

    // Si repoName est vide, on affiche un formulaire pour le demander 
    if (empty($_GET['repoName'])) {
        echo "<tr>";
        echo "<td>Nom du repo</td>";
        echo "<td><input type=\"text\" name=\"repoName\" autocomplete=\"off\" placeholder=\"Vous devez renseigner un nom de repo\"></td>";
        echo "<tr>";
    } else {
        $repoName = validateData($_GET['repoName']);
        echo "<td><input type=\"hidden\" name=\"repoName\" value=\"$repoName\"></td>";
    }

    // Pour Rehdat seulement : si repoEnv est vide, on affiche un formulaire pour le demander 
    if ($OS_TYPE == "rpm" AND empty($_GET['repoEnv'])) {
        echo "<tr>";
        echo "<td>Env du repo</td>";
        echo "<td><input type=\"text\" name=\"repoEnv\" autocomplete=\"off\" placeholder=\"Vous devez renseigner l'env du repo\"></td>";
        echo "<tr>";
    } else {
        $repoEnv = validateData($_GET['repoEnv']);
        echo "<td><input type=\"hidden\" name=\"repoEnv\" value=\"$repoEnv\"></td>";
    }


// 2ème étape, si on a toutes les variables, on demande une confirmation puis si on a la confirmation alors on lance l'exécution

    // Cas Redhat :
    if ($OS_TYPE == "rpm") {
        if (!empty($repoName) AND !empty($repoEnv)) {

            // Ok on a toutes les infos mais il faut vérifier que le repo mentionné existe :
            $checkifRepoExist = exec("grep '^Name=\"${repoName}\",Realname=\".*\",Env=\"${repoEnv}\"' $REPO_FILE");
            if (empty($checkifRepoExist)) {
                echo "<tr>";
                echo "<td>Erreur : Il n'existe aucun repo ${repoName} en ${repoEnv}</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan=\"100%\"><a href=\"index.php\"><button class=\"button-submit-large-red\">Retour</button></a></td>";
                echo "</tr>";
                return 1; // On sort de la fonction pour ne pas que les conditions suivantes (ci-dessous) s'exécutent
            }

            // Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
            if (empty($_GET['confirm'])) {
                echo "<tr>";
                echo "<td>L'opération va supprimer le repo ${repoName} en ${repoEnv}</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td>Confirmer :</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan=\"100%\"><button type=\"submit\" class=\"button-submit-large-red\" name=\"confirm\" value=\"yes\">Confirmer et exécuter</button></td>";
                echo "</tr>";
            }

            // Si on a reçu la confirmation en GET alors on traite :
            if (!empty($_GET['confirm']) AND (validateData($_GET['confirm']) == "yes")) {
                exec("${REPOMANAGER} --web --deleteRepo --repo-name $repoName --repo-env $repoEnv >/dev/null 2>/dev/null &");;
                echo "<script>window.location.replace('/journal.php');</script>"; // Dans les deux cas on redirige vers la page de logs pour voir l'exécution
            }
    
        // Dans le cas où on n'a pas transmis toutes les infos, un formulaire est apparu pour demander les infos manquantes, on ajoute alors un bouton submit pour valider ce formulaire :
        } else {
            echo "<tr>";
            echo "<td colspan=\"100%\"><button type=\"submit\" class=\"button-submit-large-red\">Valider</button></td>";
            echo "</tr>";
        }
    }

    
    // Cas Debian :
    if ($OS_TYPE == "deb") {    
        if (!empty($repoName)) {
            // Ok on a toutes les infos mais il faut vérifier que le repo mentionné existe :
            $checkifRepoExist = exec("grep '^Name=\"${repoName}\"' $REPO_FILE");
            if (empty($checkifRepoExist)) {
                echo "<tr>";
                echo "<td>Erreur : Il n'existe aucun repo ${repoName}</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan=\"100%\"><a href=\"index.php\"><button class=\"button-submit-large-red\">Retour</button></a></td>";
                echo "</tr>";
                return 1; // On sort de la fonction pour ne pas que les conditions suivantes (ci-dessous) s'exécutent
            }

            // Ok le repo existe mais peut être que celui-ci contient plusieurs distrib et sections qui seront supprimées, on récupère les distrib et les sections concernées
            // et on les affichera dans la demande de confirmation
            $distAndSectionsToBeDeleted = shell_exec("grep '^Name=\"${repoName}\"' $REPO_FILE | awk -F ',' '{print $3, $4, $5}' | sed 's|Dist=\"||g' | sed 's|\" Section=\"| -> |g'  | sed 's|\" Env=\"| (|g' | sed 's|\"|)|g'");
            $distAndSectionsToBeDeleted = explode("\n", $distAndSectionsToBeDeleted);
            $distAndSectionsToBeDeleted = array_filter($distAndSectionsToBeDeleted);

            // Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
            if (empty($_POST['confirm'])) {
                echo "<tr>";
                echo "<td>L'opération va supprimer tout le contenu du repo ${repoName}, incluant des sections archivées si il y en a</td>";
                echo "</tr>";
                echo "<tr>";
                if (!empty($distAndSectionsToBeDeleted)) {
                    echo "<td>";
                    echo "Attention, cela supprimera les distributions et sections suivantes :";
                    foreach ($distAndSectionsToBeDeleted as $distAndSection) {
                        echo "<br> - ${distAndSection}";
                    }
                    echo "</td>";
                } else {
                    echo "<td>Attention, impossible de récupérer le nom des distributions et des sections impactées.<br>L'opération supprimera tout le contenu du repo et donc les distributions et les sections qu'il contient (tout env confondu)</td>";
                }
                echo "</tr>";
                echo "<tr>";
                echo "<td>Confirmer :</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan=\"100%\"><button type=\"submit\" class=\"button-submit-large-red\" name=\"confirm\" value=\"yes\">Confirmer et exécuter</button></td>";
                echo "</tr>";
            }

            // Si on a reçu la confirmation en POST alors on traite :
            if (!empty($_POST['confirm']) AND (validateData($_POST['confirm']) == "yes")) {
                echo "reponame : $repoName";
                exec("${REPOMANAGER} --web --deleteRepo --repo-name $repoName >/dev/null 2>/dev/null &");
                echo "<script>window.location.replace('/journal.php');</script>"; // Dans les deux cas on redirige vers la page de logs pour voir l'exécution
            }

        // Dans le cas où on n'a pas transmis toutes les infos, un formulaire est apparu pour demander les infos manquantes, on ajoute alors un bouton submit pour valider ce formulaire :
        } else {
            echo "<tr>";
            echo "<td colspan=\"100%\"><button type=\"submit\" class=\"button-submit-large-red\">Valider</button></td>";
            echo "</tr>";
        }
    }
}









































































function checkAction_deleteOldRepo() {
    require 'common-vars.php';
    
    // On va devoir retransmettre l'actionId à cette même page pour demander confirmation
    if (!empty($_POST['actionId'])) {
        $actionId = validateData($_POST['actionId']);
        echo "<td><input type=\"hidden\" name=\"actionId\" value=\"$actionId\"></td>";
    }


// 1ère étape : on vérifie qu'on a bien reçu toutes les variables nécéssaires en POST :

    // Si repoName est vide, on affiche un formulaire pour le demander 
    if (empty($_POST['repoName'])) {
        echo "<tr>";
        echo "<td>Nom du repo</td>";
        echo "<td><input type=\"text\" name=\"repoName\" autocomplete=\"off\" placeholder=\"Vous devez renseigner un nom de repo\"></td>";
        echo "<tr>";
    } else {
        $repoName = $_POST['repoName'];
        echo "<td><input type=\"hidden\" name=\"repoName\" value=\"$repoName\"></td>";
    }

    // Debian seulement si repoDist est vide, on affiche un formulaire pour le demander 
    if ($OS_TYPE = "deb" AND empty($_POST['repoDist'])) {
        echo "<tr>";
        echo "<td>Distribution</td>";
        echo "<td><input type=\"text\" name=\"repoDist\" autocomplete=\"off\" placeholder=\"Vous devez renseigner la distribution\"></td>";
        echo "<tr>";
    } else {
        $repoDist = $_POST['repoDist'];
        echo "<td><input type=\"hidden\" name=\"repoDist\" value=\"$repoDist\"></td>";
    }

    // Debian seulement si repoSection est vide, on affiche un formulaire pour le demander 
    if ($OS_TYPE = "deb" AND empty($_POST['repoSection'])) {
        echo "<tr>";
        echo "<td>Section</td>";
        echo "<td><input type=\"text\" name=\"repoSection\" autocomplete=\"off\" placeholder=\"Vous devez renseigner la section\"></td>";
        echo "<tr>";
    } else {
        $repoSection = $_POST['repoSection'];
        echo "<td><input type=\"hidden\" name=\"repoSection\" value=\"$repoSection\"></td>";
    }

    // Si repoDate est vide, on affiche un formulaire pour le demander 
    if (empty($_POST['repoDate'])) {
        echo "<tr>";
        echo "<td>Date</td>";
        echo "<td><input type=\"text\" name=\"repoDate\" autocomplete=\"off\" placeholder=\"Vous devez renseigner la date du repo\"></td>";
        echo "<tr>";
    } else {
        $repoDate = $_POST['repoDate'];
        echo "<td><input type=\"hidden\" name=\"repoDate\" value=\"$repoDate\"></td>";
    }


// 2ème étape, si on a toutes les variables, on demande une confirmation puis si on a la confirmation alors on lance l'exécution

    // Cas Redhat :
    if ($OS_TYPE == "rpm") {
        if (!empty($repoName) AND !empty($repoDate)) {

            // Ok on a toutes les infos mais il faut vérifier que le repo archivé mentionné existe :
            $checkifRepoExist = exec("grep '^Name=\"${repoName}\",Realname=\".*\",Date=\"${repoDate}\"' $REPO_ARCHIVE_FILE");
            if (empty($checkifRepoExist)) {
                echo "<tr>";
                echo "<td>Erreur : Il n'existe aucun repo archivé ${repoName} en date du ${repoDate}</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan=\"100%\"><a href=\"index.php\"><button class=\"button-submit-large-red\">Retour</button></a></td>";
                echo "</tr>";
                return 1; // On sort de la fonction pour ne pas que les conditions suivantes (ci-dessous) s'exécutent
            }

            // Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
            if (empty($_POST['confirm'])) {
                echo "<tr>";
                echo "<td>L'opération va supprimer le repo ${repoName} en date du ${repoDate}</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td>Confirmer :</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan=\"100%\"><button type=\"submit\" class=\"button-submit-large-red\" name=\"confirm\" value=\"yes\">Confirmer et exécuter</button></td>";
                echo "</tr>";
            }

            // Si on a reçu la confirmation en POST alors on traite :
            if (!empty($_POST['confirm']) AND (validateData($_POST['confirm']) == "yes")) {
                exec("${REPOMANAGER} --web --deleteOldRepo --repo-name $repoName --repo-date $repoDate >/dev/null 2>/dev/null &");;
                echo "<script>window.location.replace('/journal.php');</script>"; // Dans les deux cas on redirige vers la page de logs pour voir l'exécution
            }
    
        // Dans le cas où on n'a pas transmis toutes les infos, un formulaire est apparu pour demander les infos manquantes, on ajoute alors un bouton submit pour valider ce formulaire :
        } else {
            echo "<tr>";
            echo "<td colspan=\"100%\"><button type=\"submit\" class=\"button-submit-large-red\">Valider</button></td>";
            echo "</tr>";
        }
    }


    // Cas Debian :
    if ($OS_TYPE == "deb") {    
        if (!empty($repoName) AND !empty($repoDist) AND !empty($repoSection) AND !empty($repoDate)) {

            // Ok on a toutes les infos mais il faut vérifier que la section archivée mentionnée existe :
            $checkifRepoExist = exec("grep '^${repoName}:${repoDist}:${repoSection}:' $REPO_ARCHIVE_FILE");
            if (empty($checkifRepoExist)) {
                echo "<tr>";
                echo "<td>Erreur : Il n'existe aucune section archivée ${repoSection} du repo ${repoName} (distribution ${repoDist})</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan=\"100%\"><a href=\"index.php\"><button class=\"button-submit-large-red\">Retour</button></a></td>";
                echo "</tr>";
                return 1; // On sort de la fonction pour ne pas que les conditions suivantes (ci-dessous) s'exécutent
            }

            // Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
            if (empty($_POST['confirm'])) {
                echo "<tr>";
                echo "<td>L'opération va supprimer la section archivée ${repoSection} du repo ${repoName} (distribution ${repoDist}) en date du ${repoDate}</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td>Confirmer :</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan=\"100%\"><button type=\"submit\" class=\"button-submit-large-red\" name=\"confirm\" value=\"yes\">Confirmer et exécuter</button></td>";
                echo "</tr>";
            }

            // Si on a reçu la confirmation en POST alors on traite :
            if (!empty($_POST['confirm']) AND (validateData($_POST['confirm']) == "yes")) {
                exec("${REPOMANAGER} --web --deleteOldRepo --repo-name $repoName --repo-dist $repoDist --repo-section $repoSection --repo-date $repoDate >/dev/null 2>/dev/null &");
                echo "<script>window.location.replace('/journal.php');</script>"; // Dans les deux cas on redirige vers la page de logs pour voir l'exécution
            }

        // Dans le cas où on n'a pas transmis toutes les infos, un formulaire est apparu pour demander les infos manquantes, on ajoute alors un bouton submit pour valider ce formulaire :
        } else {
            echo "<tr>";
            echo "<td colspan=\"100%\"><button type=\"submit\" class=\"button-submit-large-red\">Valider</button></td>";
            echo "</tr>";
        }
    }
}



























































// Les repos archivés n'ont plus d'env, alors quand on restaure un repo il faut forcément restaurer en $REPO_DEFAULT_ENV
// vérifier qu'un repo du même nom et en $REPO_DEFAULT_ENV n'existe pas déjà avant de restaurer

function checkAction_restoreOldRepo() {
    require 'common-vars.php';
    
    // On va devoir retransmettre l'actionId à cette même page pour demander confirmation
    if (!empty($_POST['actionId'])) {
        $actionId = validateData($_POST['actionId']);
        echo "<td><input type=\"hidden\" name=\"actionId\" value=\"$actionId\"></td>";
    }


// 1ère étape : on vérifie qu'on a bien reçu toutes les variables nécéssaires en POST :

    // Si repoName est vide, on affiche un formulaire pour le demander 
    if (empty($_POST['repoName'])) {
        echo "<tr>";
        echo "<td>Nom du repo</td>";
        echo "<td><input type=\"text\" name=\"repoName\" autocomplete=\"off\" placeholder=\"Vous devez renseigner un nom de repo\"></td>";
        echo "<tr>";
    } else {
        $repoName = $_POST['repoName'];
        echo "<td><input type=\"hidden\" name=\"repoName\" value=\"$repoName\"></td>";
    }

    // Debian seulement si repoDist est vide, on affiche un formulaire pour le demander 
    if ($OS_TYPE = "deb" AND empty($_POST['repoDist'])) {
        echo "<tr>";
        echo "<td>Distribution</td>";
        echo "<td><input type=\"text\" name=\"repoDist\" autocomplete=\"off\" placeholder=\"Vous devez renseigner la distribution\"></td>";
        echo "<tr>";
    } else {
        $repoDist = $_POST['repoDist'];
        echo "<td><input type=\"hidden\" name=\"repoDist\" value=\"$repoDist\"></td>";
    }

    // Debian seulement si repoSection est vide, on affiche un formulaire pour le demander 
    if ($OS_TYPE = "deb" AND empty($_POST['repoSection'])) {
        echo "<tr>";
        echo "<td>Section</td>";
        echo "<td><input type=\"text\" name=\"repoSection\" autocomplete=\"off\" placeholder=\"Vous devez renseigner la section\"></td>";
        echo "<tr>";
    } else {
        $repoSection = $_POST['repoSection'];
        echo "<td><input type=\"hidden\" name=\"repoSection\" value=\"$repoSection\"></td>";
    }

    // Si repoDate est vide, on affiche un formulaire pour le demander 
    if (empty($_POST['repoDate'])) {
        echo "<tr>";
        echo "<td>Date</td>";
        echo "<td><input type=\"text\" name=\"repoDate\" autocomplete=\"off\" placeholder=\"Vous devez renseigner la date du repo\"></td>";
        echo "<tr>";
    } else {
        $repoDate = $_POST['repoDate'];
        echo "<td><input type=\"hidden\" name=\"repoDate\" value=\"$repoDate\"></td>";
    }

    // Si repoDescription est vide, on affiche un formulaire pour le demander 
    if (empty($_POST['repoDescription'])) {
        echo "<tr>";
        echo "<td>Description</td>";
        echo "<td><input type=\"text\" name=\"repoDescription\" autocomplete=\"off\" placeholder=\"Vous devez renseigner une description\"></td>";
        echo "<tr>";
    } else {
        $repoDescription = $_POST['repoDescription'];
        echo "<td><input type=\"hidden\" name=\"repoDescription\" value=\"$repoDescription\"></td>";
    }


// 2ème étape, si on a toutes les variables, on demande une confirmation puis si on a la confirmation alors on lance l'exécution

    // Cas Redhat :
    if ($OS_TYPE == "rpm") {
        if (!empty($repoName) AND !empty($repoDate) AND !empty($repoDescription)) {

            // Ok on a toutes les infos mais il faut vérifier que le repo archivé mentionné existe :
            $checkifRepoExist = exec("grep '^${repoName}:${repoDate}:' $REPO_ARCHIVE_FILE");
            if (empty($checkifRepoExist)) {
                echo "<tr>";
                echo "<td>Erreur : Il n'existe aucun repo archivé ${repoName} en date du ${repoDate}</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan=\"100%\"><a href=\"index.php\"><button class=\"button-submit-large-red\">Retour</button></a></td>";
                echo "</tr>";
                return 1; // On sort de la fonction pour ne pas que les conditions suivantes (ci-dessous) s'exécutent
            }

            // Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
            if (empty($_POST['confirm'])) {
                echo "<tr>";
                echo "<td>L'opération va restaurer le repo ${repoName} en date du ${repoDate}</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td>Confirmer :</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan=\"100%\"><button type=\"submit\" class=\"button-submit-large-red\" name=\"confirm\" value=\"yes\">Confirmer et exécuter</button></td>";
                echo "</tr>";
            }

            // Si on a reçu la confirmation en POST alors on traite :
            if (!empty($_POST['confirm']) AND (validateData($_POST['confirm']) == "yes")) {
                exec("${REPOMANAGER} --web --restoreOldRepo --repo-name $repoName --repo-date $repoDate --repo-description $repoDescription >/dev/null 2>/dev/null &");;
                echo "<script>window.location.replace('/journal.php');</script>"; // Dans les deux cas on redirige vers la page de logs pour voir l'exécution
            }
    
        // Dans le cas où on n'a pas transmis toutes les infos, un formulaire est apparu pour demander les infos manquantes, on ajoute alors un bouton submit pour valider ce formulaire :
        } else {
            echo "<tr>";
            echo "<td colspan=\"100%\"><button type=\"submit\" class=\"button-submit-large-red\">Valider</button></td>";
            echo "</tr>";
        }
    }


    // Cas Debian :
    if ($OS_TYPE == "deb") {    
        if (!empty($repoName) AND !empty($repoDist) AND !empty($repoSection) AND !empty($repoDate) AND !empty($repoDescription)) {

            // Ok on a toutes les infos mais il faut vérifier que la section archivée mentionnée existe :
            $checkifRepoExist = exec("grep '^${repoName}:${repoDist}:${repoSection}:' $REPO_ARCHIVE_FILE");
            if (empty($checkifRepoExist)) {
                echo "<tr>";
                echo "<td>Erreur : Il n'existe aucune section archivée ${repoSection} du repo ${repoName} (distribution ${repoDist})</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan=\"100%\"><a href=\"index.php\"><button class=\"button-submit-large-red\">Retour</button></a></td>";
                echo "</tr>";
                return 1; // On sort de la fonction pour ne pas que les conditions suivantes (ci-dessous) s'exécutent
            }

            // Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
            if (empty($_POST['confirm'])) {
                echo "<tr>";
                echo "<td>L'opération va restaurer la section archivée ${repoSection} du repo ${repoName} (distribution ${repoDist}) en date du ${repoDate}</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td>Confirmer :</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan=\"100%\"><button type=\"submit\" class=\"button-submit-large-red\" name=\"confirm\" value=\"yes\">Confirmer et exécuter</button></td>";
                echo "</tr>";
            }

            // Si on a reçu la confirmation en POST alors on traite :
            if (!empty($_POST['confirm']) AND (validateData($_POST['confirm']) == "yes")) {
                exec("${REPOMANAGER} --web --restoreOldRepo --repo-name $repoName --repo-dist $repoDist --repo-section $repoSection --repo-date $repoDate --repo-description $repoDescription >/dev/null 2>/dev/null &");
                echo "<script>window.location.replace('/journal.php');</script>"; // Dans les deux cas on redirige vers la page de logs pour voir l'exécution
            }

        // Dans le cas où on n'a pas transmis toutes les infos, un formulaire est apparu pour demander les infos manquantes, on ajoute alors un bouton submit pour valider ce formulaire :
        } else {
            echo "<tr>";
            echo "<td colspan=\"100%\"><button type=\"submit\" class=\"button-submit-large-red\">Valider</button></td>";
            echo "</tr>";
        }
    }
}

    if ($actionId == "newRepo") { checkAction_newRepo(); }
    if ($actionId == "updateRepo") { checkAction_updateRepo(); }
    if ($actionId == "changeEnv") { checkAction_changeEnv(); }
    if ($actionId == "duplicateRepo") { checkAction_duplicateRepo(); }
    if ($actionId == "deleteSection") { checkAction_deleteSection(); }
    if ($actionId == "deleteDist") { checkAction_deleteDist(); }
    if ($actionId == "deleteRepo") { checkAction_deleteRepo(); }
    if ($actionId == "deleteOldRepo") { checkAction_deleteOldRepo(); }
    if ($actionId == "restoreOldRepo") { checkAction_restoreOldRepo(); }

    ?>
    </table>
    </form>
  </article>
</article>
<!-- divs cachées de base -->
<!-- div des groupes de repos -->
<?php include('common-groupslist.inc.php'); ?>

<!-- div des hotes et fichers de repos -->
<?php include('common-repos-sources.inc.php'); ?>

<?php include('common-footer.inc.php'); ?>

</body>
</html>