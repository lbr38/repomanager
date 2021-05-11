<?php
function checkArguments($argumentType, $argumentName) {
    global $GPG_SIGN_PACKAGES;
    global $ENVS;

    // Vérifie si les arguments passés sont vides
    // Certains sont facultatifs en fonction de l'action effectuée et peuvent rester vides

    // Le type de l'argument (argument required ou non), son nom ne doivent pas être vides. Sa valeur peut être vide (argument facultatif ou non), cette fonction est là justement pour le vérifier
    if (empty($argumentType) OR empty($argumentName)) {
        echo '<td>Erreur lors de la vérification des arguments</td>';
        return 1;
    }
    // L'action à effectuer sera toujours obligatoire
    if ($argumentName === 'actionId') {
        if (empty($_GET['actionId'])) {
            echo '<tr>';
            echo '<td>Erreur : aucune action n\'a été demandée </td>';
            echo '</tr>';
            return 1;
        }
        if (!empty($_GET['actionId'])) {
            $actionId = validateData($_GET['actionId']);
            echo "<td><input type=\"hidden\" name=\"actionId\" value=\"$actionId\"></td>";
            return $actionId;
        }
    }
    // Le nom du repo source sera toujours obligatoire
    if ($argumentName === 'repoSource') {
        if (empty($_GET['repoSource'])) {
            echo '<tr>';
            echo '<td>Nom du repo source</td>';
            echo '<td><input type="text" name="repoSource" placeholder="Vous devez renseigner un nom de repo source" required /></td>';
            echo '</tr>';
        }
        if (!empty($_GET['repoSource'])) {
            $repoSource = validateData($_GET['repoSource']);
            echo "<td><input type=\"hidden\" name=\"repoSource\" value=\"$repoSource\"></td>";
            return $repoSource;
        }
    }
    // Le nom de l'hôte source sera toujours obligatoire
    /*if ($argumentName === 'repoSource') {
        if (empty($_GET['repoSource'])) {
            echo '<tr>';
            echo '<td>Nom de l\'hôte</td>';
            echo '<td><input type="text" name="repoSource" placeholder="Vous devez renseigner un nom d\'hôte source" required /></td>';
            echo '</tr>';
        }
        if (!empty($_GET['repoSource'])) {
            $repoSource = validateData($_GET['repoSource']);
            echo "<td><input type=\"hidden\" name=\"repoSource\" value=\"$repoSource\"></td>";
            return $repoSource;
        }
    }*/
    // Le nom du repo sera toujours obligatoire
    if ($argumentName === 'repoName') {
        if (empty($_GET['repoName'])) {
            echo '<tr>';
            echo '<td>Nom du repo</td>';
            echo '<td><input type="text" name="repoName" placeholder="Vous devez renseigner le nom du repo" required /></td>';
            echo '</tr>';
        }
        if (!empty($_GET['repoName'])) {
            $repoName = validateData($_GET['repoName']);
            echo "<td><input type=\"hidden\" name=\"repoName\" value=\"$repoName\"></td>";
            return $repoName;
        }
    }
    // La distribution sera toujours obligatoire
    if ($argumentName === 'repoDist') {
        if (empty($_GET['repoDist'])) {
            echo '<tr>';
            echo '<td>Distribution</td>';
            echo '<td><input type="text" name="repoDist" placeholder="Vous devez renseigner une distribution" required /></td>';
            echo '</tr>';
        }
        if (!empty($_GET['repoDist'])) {
            $repoDist = validateData($_GET['repoDist']);
            echo "<td><input type=\"hidden\" name=\"repoDist\" value=\"$repoDist\"></td>";
            return $repoDist;
        }
    }
    // La section sera toujours obligatoire
    if ($argumentName === 'repoSection') {
        if (empty($_GET['repoSection'])) {
            echo '<tr>';
            echo '<td>Section</td>';
            echo '<td><input type="text" name="repoSection" placeholder="Vous devez renseigner une section" required /></td>';
            echo '</tr>';
        }
        if (!empty($_GET['repoSection'])) {
            $repoSection = validateData($_GET['repoSection']);
            echo "<td><input type=\"hidden\" name=\"repoSection\" value=\"$repoSection\"></td>";
            return $repoSection;
        }
    }
    // Alias (nom personnalisé) facultatif ou obligatoire
    if ($argumentName === 'repoAlias') {
        if ($argumentType == 'required') {
            if (empty($_GET['repoAlias'])) {
                echo '<tr>';
                echo '<td>Nom personnalisé (fac.)</td>';
                echo '<td><input type="text" name="repoAlias" placeholder="Vous devez renseigner un nom" required /></td>';
                echo '</tr>';
            }
            if (!empty($_GET['repoAlias'])) {
                $repoAlias = validateData($_GET['repoAlias']);
                echo "<td><input type=\"hidden\" name=\"repoAlias\" value=\"$repoAlias\"></td>";
                return $repoAlias;
            }
        }
        // Si l'alias est optionnel et qu'il a été transmis vide, alors on le set à 'noalias'
        if ($argumentType == 'optionnal') {
            if (empty($_GET['repoAlias'])) {
                $repoAlias = 'noalias';
            } else {
                $repoAlias = validateData($_GET['repoAlias']);
            }
            echo "<td><input type=\"hidden\" name=\"repoAlias\" value=\"$repoAlias\"></td>";
            return $repoAlias;
        }       
    }
    // La date sera toujours obligatoire
    if ($argumentName === 'repoDate') {
        if (empty($_GET['repoDate'])) {
            echo '<tr>';
            echo '<td>Date</td>';
            echo '<td><input type="text" name="repoDate" placeholder="Vous devez renseigner la date au format JJ-MM-AAAA" required /></td>';
            echo '</tr>';
        }
        if (!empty($_GET['repoDate'])) {
            $repoDate = validateData($_GET['repoDate']);
            echo "<td><input type=\"hidden\" name=\"repoDate\" value=\"$repoDate\"></td>";
            return $repoDate;
        }
    }
    // L'environnement sera toujours obligatoire
    if ($argumentName === 'repoEnv') {
        if (empty($_GET['repoEnv'])) {
            echo '<tr>';
            echo '<td>Env. actuel</td>';
            echo '<td>';
            echo '<select name="repoEnv" required>';
            foreach($ENVS as $env) {
                echo "<option value=\"${env}\">${env}</option>";
            }
            echo '</select>';
            echo '</td>';
            echo '</tr>';
        }
        if (!empty($_GET['repoEnv'])) {
            $repoEnv = validateData($_GET['repoEnv']);
            echo "<td><input type=\"hidden\" name=\"repoEnv\" value=\"$repoEnv\"></td>";
            return $repoEnv;
        }
    }
    // Le nouvel environnement sera toujours obligatoire
    if ($argumentName === 'repoNewEnv') {
        // récupère l'environnement en cours si cela est possible afin de ne pas le réafficher dans la liste déroulante
        if (!empty($_GET['repoEnv'])) {
            $repoEnv = validateData($_GET['repoEnv']);
        } else {
            $repoEnv = ''; // Si on ne peut pas récupérer repoEnv, alors tant pis on le set à '', il sera affiché dans la liste déroulante
        }
        if (empty($_GET['repoNewEnv'])) {
            echo '<tr>';
            echo '<td>Nouvel env</td>';
            echo '<td>';
            echo '<select name="repoNewEnv" required>';
            foreach($ENVS as $env) {
                if ($env !== "$repoEnv") { // on ne réaffiche pas l'env en cours
                    echo "<option value=\"${env}\">${env}</option>";
                }
            }
            echo '</select>';
            echo '</td>';
            echo '</tr>';
        }
        if (!empty($_GET['repoNewEnv'])) {
            $repoNewEnv = validateData($_GET['repoNewEnv']);
            echo "<td><input type=\"hidden\" name=\"repoNewEnv\" value=\"$repoNewEnv\"></td>";
            return $repoNewEnv;
        }
    }
    // Le nouveau nom du repo sera toujours obligatoire
    if ($argumentName === 'repoNewName') {
        if (empty($_GET['repoNewName'])) {
            echo '<tr>';
            echo '<td>Nouveau nom du repo</td>';
            echo '<td><input type="text" name="repoNewName" placeholder="Vous devez renseigner le nom du nouveau repo" required /></td>';
            echo '</tr>';
        }
        if (!empty($_GET['repoNewName'])) {
            $repoNewName = validateData($_GET['repoNewName']);
            echo "<td><input type=\"hidden\" name=\"repoNewName\" value=\"$repoNewName\"></td>";
            return $repoNewName;
        }
    }
    // La description est toujours facultative
    if ($argumentName === 'repoDescription') {
        if (isset($_GET['repoDescription']) AND empty($_GET['repoDescription'])) {
            $repoDescription = 'nodescription';
            echo "<td><input type=\"hidden\" name=\"repoDescription\" value=\"$repoDescription\"></td>";
            return $repoDescription;
        }
        if (!empty($_GET['repoDescription'])) {
            $repoDescription = validateData($_GET['repoDescription']);
            echo "<td><input type=\"hidden\" name=\"repoDescription\" value=\"$repoDescription\"></td>";
            return $repoDescription;
        }
        if (!isset($_GET['repoDescription'])) {
            echo '<tr>';
            echo '<td>Description (fac.)</td>';
            echo '<td><input type="text" name="repoDescription" placeholder="Pas de caractères spéciaux" /></td>';
            echo '</tr>';
        }
    }
    // Ajout à un groupe sera toujours facultatif
    if ($argumentName === 'repoGroup') {
        require_once('class/Group.php');

        $group = new Group();

        // Si le groupe est optionnel et qu'il a été transmis vide, alors on le set à 'nogroup'
        if ($argumentType == 'optionnal') {
            if (isset($_GET['repoGroup']) AND empty($_GET['repoGroup'])) {
                $repoGroup = 'nogroup';
                echo "<td><input type=\"hidden\" name=\"repoGroup\" value=\"$repoGroup\"></td>";
                return $repoGroup;
            }
            if (!empty($_GET['repoGroup'])) {
                $repoGroup = validateData($_GET['repoGroup']);
                echo "<td><input type=\"hidden\" name=\"repoGroup\" value=\"$repoGroup\"></td>";
                return $repoGroup;
            }
            if (!isset($_GET['repoGroup'])) {
                $groupList = $group->listAll();
                // on va afficher le tableau de groupe seulement si la commande précédente a trouvé des groupes dans le fichier (résultat non vide) :
                if (!empty($groupList)) {
                    echo '<tr>';
                    echo '<td>Ajouter à un groupe (fac.)</td>';
                    echo '<td>';
                    echo '<select name="repoGroup">';
                    echo '<option value="">Sélectionner un groupe...</option>';
                    foreach($groupList as $groupName) {
                        $groupName = str_replace(["[", "]"], "", $groupName);
                        echo "<option value=\"$groupName\">$groupName</option>";
                    }
                    echo '</select>';
                    echo '</td>';
                    echo '</tr>';
                } else { // Si on a aucun groupe sur ce serveur, alors aucune liste ne s'affichera. Dans ce cas il faut définir $repoGroup à 'nogroup'
                    $repoGroup = 'nogroup';
                    echo "<td><input type=\"hidden\" name=\"repoGroup\" value=\"$repoGroup\"></td>";
                    return $repoGroup;
                }
            }
        }        
    }
    // Le check GPG du repo/hote source sera toujours obligatoire
    if ($argumentName === 'repoGpgCheck') {
        if (empty($_GET['repoGpgCheck'])) {
            echo '<tr>';
            echo '<td>GPG check</td>';
            echo '<td colspan="2">';
            echo '<input type="radio" id="repoGpgCheck_yes" name="repoGpgCheck" value="yes" checked="yes">';
            echo '<label for="repoGpgCheck_yes">Yes</label>';
            echo '<input type="radio" id="repoGpgCheck_no" name="repoGpgCheck" value="no">';
            echo '<label for="repoGpgCheck_no">No</label>';
            echo '</td>';
            echo '</tr>';
        }
        if (!empty($_GET['repoGpgCheck'])) {
            $repoGpgCheck = validateData($_GET['repoGpgCheck']);
            echo "<td><input type=\"hidden\" name=\"repoGpgCheck\" value=\"$repoGpgCheck\"></td>";
            return $repoGpgCheck;
        }
    }
    // La signature du repo/des paquets avec GPG sera toujours obligatoire
    if ($argumentName === 'repoGpgResign') {
        if (empty($_GET['repoGpgResign'])) {
            echo '<tr>';
            echo "<td>Re-signer avec GPG</td>";
            echo '<td>';
            if ($GPG_SIGN_PACKAGES == "yes") {
                echo "<input type=\"radio\" id=\"repoGpgResign_yes\" name=\"repoGpgResign\" value=\"yes\" checked=\"yes\">";
                echo "<label for=\"repoGpgResign_yes\">Yes</label>";
                echo "<input type=\"radio\" id=\"repoGpgResign_no\" name=\"repoGpgResign\" value=\"no\">";
                echo "<label for=\"repoGpgResign_no\">No</label>";
            } else {
                echo "<input type=\"radio\" id=\"repoGpgResign_yes\" name=\"repoGpgResign\" value=\"yes\">";
                echo "<label for=\"repoGpgResign_yes\">Yes</label>";
                echo "<input type=\"radio\" id=\"repoGpgResign_no\" name=\"repoGpgResign\" value=\"no\" checked=\"yes\">";
                echo "<label for=\"repoGpgResign_no\">No</label>";
            }
            echo '</td>';
            echo '</tr>';
        }
        if (!empty($_GET['repoGpgResign'])) {
            $repoGpgResign = validateData($_GET['repoGpgResign']);
            echo "<td><input type=\"hidden\" name=\"repoGpgResign\" value=\"$repoGpgResign\"></td>";
            return $repoGpgResign;
        }
    }
}
?>