<?php
require_once('class/Group.php');
require_once('class/Source.php');
$group = new Group();
$groupList = $group->listAll();
if ($OS_FAMILY == "Debian") {
    $source = new Source();
}

if ($OS_FAMILY == "Redhat") {
    echo '<h5>CRÉER UN NOUVEAU REPO</h5>';
    echo '<form action="/check.php" method="get" class="actionform" autocomplete="off">';
    echo '<input name="actionId" type="hidden" value="newRepo">';
    echo '<table class="actiontable">';
    echo '<tr>';
    echo '<td>Nom du repo source</td>';
    echo '<td>';
    echo '<select name="repoSource" required>';
    echo '<option value="">Sélectionner un repo source...</option>';
    $reposFiles = scandir($REPOMANAGER_YUM_DIR);
    foreach($reposFiles as $repoFileName) {
        if (($repoFileName != "..") AND ($repoFileName != ".") AND ($repoFileName != "repomanager.conf")) {
        // on retire le suffixe .repo du nom du fichier afin que ça soit plus propre dans la liste
        $repoFileNameFormated = str_replace(".repo", "", $repoFileName);
        echo "<option value=\"${repoFileNameFormated}\">${repoFileNameFormated}</option>";
        }
    }
    echo '</select>';
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td>Nom personnalisé (fac.)</td>';
    echo '<td><input type="text" name="repoAlias" placeholder="Ne pas utiliser d\'underscore \'_\'"></td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td>Description (fac.)</td>';
    echo '<td><input type="text" name="repoDescription"></td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td>GPG check</td>';
    echo '<td colspan="2">';
    echo '<input type="radio" id="repoGpgCheck_yes" name="repoGpgCheck" value="yes" checked="yes">';
    echo '<label for="repoGpgCheck_yes">Yes</label>';
    echo '<input type="radio" id="repoGpgCheck_no" name="repoGpgCheck" value="no">';
    echo '<label for="repoGpgCheck_no">No</label>';
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td>Re-signer avec GPG</td>';
    echo '<td colspan="2">';
    if ($GPG_SIGN_PACKAGES == "yes") {
        echo '<input type="radio" id="repoGpgResign_yes" name="repoGpgResign" value="yes" checked="yes">';
        echo '<label for="repoGpgResign_yes">Yes</label>';
        echo '<input type="radio" id="repoGpgResign_no" name="repoGpgResign" value="no">';
        echo '<label for="repoGpgResign_no">No</label>';
    } else {
        echo '<input type="radio" id="repoGpgResign_yes" name="repoGpgResign" value="yes">';
        echo '<label for="repoGpgResign_yes">Yes</label>';
        echo '<input type="radio" id="repoGpgResign_no" name="repoGpgResign" value="no" checked="yes">';
        echo '<label for="repoGpgResign_no">No</label>';
    } 
    echo '</td>';
    echo '</tr>';
    // Possibilité d'ajouter à un groupe, si il y en a
    if (!empty($groupList)) {
        echo '<tr>';
        echo '<td>Ajouter à un groupe (fac.)</td>';
        echo '<td>';
        echo '<select name="repoGroup">';
        echo '<option value="">Sélectionner un groupe...</option>';
        $i = 0;
        $j = 0;
        foreach($groupList as $groupName) {
        $groupName = str_replace(["[", "]"], "", $groupName);
        echo "<option value=\"$groupName\">$groupName</option>";
        }
        echo '</select>';
        echo '</td>';
        echo '</tr>';
    }
    echo '<tr>';
    echo '<td colspan="2"><button type="submit" class="button-submit-large-red">Exécuter</button></td>';
    echo '</tr>';
    echo '</table>';
    echo '</form>';
}

if ($OS_FAMILY == "Debian") {
    echo '<h5>CRÉER UNE NOUVELLE SECTION</h5>';
    echo '<form action="/check.php" method="get" class="actionform" autocomplete="off">';
    echo '<input name="actionId" type="hidden" value="newRepo">';
    echo '<table class="actiontable">';
    echo '<tr>';
    echo '<td>Nom de l\'hôte source</td>';
    echo '<td>';
    echo '<select id="repoSourceSelect" name="repoSource" required>';
    echo '<option value="">Sélectionner un hôte source...</option>';
    $sourcesList = $source->listAll();
    if (!empty($sourcesList)) {
        foreach($sourcesList as $source) {
            $sourceName = $source['Name'];
            $sourceUrl = $source['Url'];
            echo "<option value=\"${sourceName}\">${sourceName} (${sourceUrl})</option>";
        }
    }
    echo '</select>';
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td>Nom personnalisé (fac.)</td>';
    echo '<td><input type="text" name="repoAlias" placeholder="Ne pas utiliser d\'underscore \'_\'"></td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td>Distribution</td>';
    echo '<td><input type="text" name="repoDist" required></td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td>Section</td>';
    echo '<td><input type="text" name="repoSection" required></td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td>Description (fac.)</td>';
    echo '<td><input type="text" name="repoDescription"></td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td>GPG check</td>';
    echo '<td colspan="2">';
    echo '<input type="radio" id="repoGpgCheck_yes" name="repoGpgCheck" value="yes" checked="yes">';
    echo '<label for="repoGpgCheck_yes">Yes</label>';
    echo '<input type="radio" id="repoGpgCheck_no" name="repoGpgCheck" value="no">';
    echo '<label for="repoGpgCheck_no">No</label>';
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td>Re-signer avec GPG</td>';
    echo '<td colspan="2">';
    if ($GPG_SIGN_PACKAGES == "yes") {
        echo '<input type="radio" id="repoGpgResign_yes" name="repoGpgResign" value="yes" checked="yes">';
        echo '<label for="repoGpgResign_yes">Yes</label>';
        echo '<input type="radio" id="repoGpgResign_no" name="repoGpgResign" value="no">';
        echo '<label for="repoGpgResign_no">No</label>';
    } else {
        echo '<input type="radio" id="repoGpgResign_yes" name="repoGpgResign" value="yes">';
        echo '<label for="repoGpgResign_yes">Yes</label>';
        echo '<input type="radio" id="repoGpgResign_no" name="repoGpgResign" value="no" checked="yes">';
        echo '<label for="repoGpgResign_no">No</label>';
    }
    echo '</td>';
    echo '</tr>';
    // Possibilité d'ajouter à un groupe, si il y en a
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
    }
    echo '<tr>';
    echo '<td colspan="2"><button type="submit" class="button-submit-large-red">Valider</button></td>';
    echo '</tr>';
    echo '</table>';
    echo '</form>';
}
?>
<script>
/*
$('#repoSourceSelect').select2({
  closeOnSelect: false,
  placeholder: 'Sélectionner un hôte source...'
});*/
</script>