<?php
require_once('class/Group.php');
require_once('class/Source.php');

/**
 *  Récupération de la liste de tous les groupes
 */
$group = new Group();
$groupList = $group->listAllName();

/**
 *  Instanciation d'un objet Source pour pouvoir récupérer la liste de tous les repos sources (Debian)
 */
if ($OS_FAMILY == "Debian") {
    $source = new Source();
}

if ($OS_FAMILY == "Redhat") { echo '<h3>CRÉER UN NOUVEAU REPO</h3>'; }
if ($OS_FAMILY == "Debian") { echo '<h3>CRÉER UNE NOUVELLE SECTION</h3>'; }

echo '<form action="operation.php" method="get" class="actionform" autocomplete="off">';
echo '<input name="action" type="hidden" value="new" />';
echo '<table class="actiontable">';
echo '<tr>';
echo '<td>Type</td>';
echo '<td class="td-medium">';
echo '<div id="repoTypeDiv" class="switch-field">
<input type="radio" id="repoType_mirror" name="repoType" value="mirror" checked />
<label for="repoType_mirror">Miroir</label>
<input type="radio" id="repoType_local" name="repoType" value="local" />
<label for="repoType_local">Local</label>
</div>';
echo '</td>';
echo '</tr>';
echo '<tr class="type_mirror_input hide">';
echo '<td>Repo source</td>';
echo '<td>';
echo '<select id="repoSourceSelect" name="repoSource" />';
echo '<option value="">Sélectionner un repo source...</option>';
if ($OS_FAMILY == "Redhat") {
    $reposFiles = scandir($REPOMANAGER_YUM_DIR);
    foreach($reposFiles as $repoFileName) {
        if (($repoFileName != "..") AND ($repoFileName != ".") AND ($repoFileName != "repomanager.conf")) {
        // on retire le suffixe .repo du nom du fichier afin que ça soit plus propre dans la liste
        $repoFileNameFormated = str_replace(".repo", "", $repoFileName);
        echo "<option value=\"${repoFileNameFormated}\">${repoFileNameFormated}</option>";
        }
    }
}
if ($OS_FAMILY == "Debian") {
    $sourcesList = $source->listAll();
    if (!empty($sourcesList)) {
        foreach($sourcesList as $source) {
            $sourceName = $source['Name'];
            $sourceUrl = $source['Url'];
            echo "<option value=\"${sourceName}\">${sourceName} (${sourceUrl})</option>";
        }
    }
}
echo '</select>';
echo '</td>';
echo '</tr>';
echo '<tr>';
echo '<td class="type_mirror_input hide">Nom personnalisé (fac.)</td>'; // Si type = mirror alors on affiche
echo '<td class="type_local_input hide">Nom du repo</td>';              // Si type = local alors on affiche
echo '<td><input type="text" name="repoAlias" /></td>';
echo '</tr>';
if ($OS_FAMILY == "Debian") {
    echo '<tr>';
    echo '<td>Distribution</td>';
    echo '<td><input type="text" name="repoDist" required /></td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td>Section</td>';
    echo '<td><input type="text" name="repoSection" required /></td>';
    echo '</tr>';
}
echo '<tr>';
echo '<td>Description (fac.)</td>';
echo '<td><input type="text" name="repoDescription" /></td>';
echo '</tr>';
echo '<tr class="type_mirror_input hide">';
echo '<td>GPG check</td>';
echo '<td>';
echo '<label class="onoff-switch-label">';
echo '<input name="repoGpgCheck" type="checkbox" class="onoff-switch-input" value="yes" checked />';
echo '<span class="onoff-switch-slider"></span>';
echo '</label>';
echo '</td>';
echo '</tr>';
echo '<tr class="type_mirror_input hide">';
echo '<td>Signer avec GPG</td>';
echo '<td>';
echo '<label class="onoff-switch-label">';
echo '<input name="repoGpgResign" type="checkbox" class="onoff-switch-input" value="yes"'; if ($GPG_SIGN_PACKAGES == "yes") { echo 'checked'; } echo ' />';
echo '<span class="onoff-switch-slider"></span>';
echo '</label>';
echo '</td>';
echo '</tr>';

/**
 *  Possibilité d'ajouter à un groupe, si il y en a
 */
if (!empty($groupList)) {
    echo '<tr>';
    echo '<td>Ajouter à un groupe (fac.)</td>';
    echo '<td>';
    echo '<select name="repoGroup">';
    echo '<option value="">Sélectionner un groupe...</option>';
    foreach($groupList as $groupName) {
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
?>
<script>
/**
 *  Premier chargement de la page : Affiche les inputs supplémentaires en fonction du type de repo sélectionné par défaut
 */
if ($("#repoType_mirror").is(":checked")) {
    $(".type_mirror_input").show();
    $(".type_local_input").hide();
} else {
    $(".type_mirror_input").hide();
    $(".type_local_input").show();
}
/**
 *  Puis à chaque changement d'état, affiche ou masque les inputs supplémentaires en fonction de ce qui est coché
 */
$('input:radio[name="repoType"]').change(
    function(){
        if ($("#repoType_mirror").is(":checked")) {
            $(".type_mirror_input").show();
            $(".type_local_input").hide();
        } else {
            $(".type_mirror_input").hide();
            $(".type_local_input").show();
        }
    }
);

/*
$('#repoSourceSelect').select2({
  closeOnSelect: false,
  placeholder: 'Sélectionner un hôte source...'
});*/
</script>