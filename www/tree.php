<?php
/**
 *  Génère le contenu d'un repo au format JSON, exploitable par explore.php
 *  tree.php est appelé par explore.php et prend en argument le nom du repo/section à traiter
 */

/**
 *  Import des constantes
 */
require_once('functions/load_common_variables.php');
require_once('functions/common-functions.php');

if (empty($_GET['repo'])) {
    echo 'Erreur : Aucun repo renseigné';
    return;
} else {
    $repo = validateData($_GET['repo']);
}

if ($OS_FAMILY == "Debian") {
    if (empty($_GET['dist'])) {
        echo 'Erreur : Aucune distribution renseignée';
        return;
    } else {
        $dist = validateData($_GET['dist']);
    }
    if (empty($_GET['section'])) {
        echo 'Erreur : Aucune section renseignée';
        return;
    } else {
        $section = validateData($_GET['section']);
    }
}

if (empty($_GET['env'])) {
    echo 'Erreur : Aucun environnement renseigné';
    return;
} else {
    $env = validateData($_GET['env']);
}

if (empty($_GET['state'])) {
    echo 'Erreur : Etat non renseigné';
    return;
} else {
    $state = validateData($_GET['state']);
    if ($state != "active" AND $state != "archived") {
        echo 'Erreur : Etat invalide';
        return;
    }
}

if ($state == "active") {
    if ($OS_FAMILY == "Redhat") {
        $path = "${REPOS_DIR}/${repo}_${env}/";
    }
    if ($OS_FAMILY == "Debian") {
        $path = "${REPOS_DIR}/${repo}/${dist}/${section}_${env}/";
    }
}
/*
if ($state == "archived") {
    if ($OS_FAMILY == "Redhat") {
        $path = "${REPOS_DIR}/${repo}_${env}/";
    }
    if ($OS_FAMILY == "Debian") {
        $path = "${REPOS_DIR}/${repo}/${dist}/${section}_${env}/";
    }
}*/


header('Content-Type: application/json');
echo json_encode(dir_to_jstree_array($path));

/**
 *  https://stackoverflow.com/questions/14389757/need-help-formatting-results-of-a-directory-listing-in-php-javascript-tree-cont
 *  On passe à la fonction le chemin ainsi qu'un array avec les extensions de fichiers affichables dans l'explorer
 */
function dir_to_jstree_array($dir, $order = "a", $ext = array()) {
    if(empty($ext)) {
        $ext = array (
            "rpm", "deb", "xml", "tar", "gz", "txt", "gpg"
        );
    }

    $listDir = array(
        'text' => basename($dir),
        //'data' => basename($dir),
        'attr' => array (
            'rel' => 'folder'
        ),
        'metadata' => array (
            'id' => $dir
        ),
        'children' => array()
    );

    $files = array();
    $dirs = array();

    if ($handler = opendir($dir)) {
        while (($sub = readdir($handler)) !== FALSE) {
            if ($sub != "." && $sub != "..") {
                
                if (is_file($dir."/".$sub)) {
                    $extension = pathinfo($dir."/".$sub, PATHINFO_EXTENSION);
                    if(in_array($extension, $ext)) {
                        $files[] = $sub;
                    }
                } elseif (is_dir($dir."/".$sub)) {
                    $dirs[] = $dir."/".$sub;
                }
            }
        }

        if($order === "a") {
            asort($dirs);
        } else {
            arsort($dirs);
        }

        foreach($dirs as $d) {
            $listDir['children'][] = dir_to_jstree_array($d);
        }

        if($order === "a") {
            asort($files);
        } else {
            arsort($files);
        }

        foreach($files as $file) {
            $listDir['children'][] = $file;
        }

        closedir($handler);
    }
    return $listDir;
}

?>