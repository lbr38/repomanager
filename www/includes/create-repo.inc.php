<?php
/**
 *  Récupération de la liste de tous les groupes
 */
$group = new Group('repo');
$groupList = $group->listAllName();

/**
 *  Instanciation d'un objet Source pour pouvoir récupérer la liste de tous les repos sources (Debian)
 */
if (OS_FAMILY == "Debian") {
    $source = new Source();
}

if (OS_FAMILY == "Redhat") echo '<h3>CRÉER UN NOUVEAU REPO</h3>';
if (OS_FAMILY == "Debian") echo '<h3>CRÉER UNE NOUVELLE SECTION</h3>'; ?>

<form action="operation.php" method="get" class="actionform" autocomplete="off">
    <input name="action" type="hidden" value="new" />
    <table class="table-large">
        <tr>
            <td>Type</td>
            <td class="td-medium">
                <div class="switch-field">
                    <input type="radio" id="repoType_mirror" name="repoType" value="mirror" checked />
                    <label for="repoType_mirror">Miroir</label>
                    <input type="radio" id="repoType_local" name="repoType" value="local" />
                    <label for="repoType_local">Local</label>
                </div>
            </td>
        </tr>
        <tr class="type_mirror_input">
            <td>Repo source</td>
            <td>
                <select id="repoSourceSelect" name="repoSource" />
                    <option value="">Sélectionner un repo source...</option>
                        <?php
                        if (OS_FAMILY == "Redhat") {
                            $reposFiles = scandir(REPOMANAGER_YUM_DIR);
                            foreach($reposFiles as $repoFileName) {
                                if (($repoFileName != "..") AND ($repoFileName != ".") AND ($repoFileName != "repomanager.conf")) {
                                // on retire le suffixe .repo du nom du fichier afin que ça soit plus propre dans la liste
                                $repoFileNameFormated = str_replace(".repo", "", $repoFileName);
                                echo "<option value=\"${repoFileNameFormated}\">${repoFileNameFormated}</option>";
                                }
                            }
                        }
                        if (OS_FAMILY == "Debian") {
                            $sourcesList = $source->listAll();
                            if (!empty($sourcesList)) {
                                foreach($sourcesList as $source) {
                                    $sourceName = $source['Name'];
                                    $sourceUrl = $source['Url'];
                                    echo "<option value=\"${sourceName}\">${sourceName} (${sourceUrl})</option>";
                                }
                            }
                        } ?>
                </select>
            </td>
        </tr>
        <tr>
            <td class="type_mirror_input">Nom personnalisé (fac.)</td>
            <td class="type_local_input hide">Nom du repo</td>
            <td><input type="text" name="repoAlias" /></td>
        </tr>
        <?php if (OS_FAMILY == "Debian") { ?>
        <tr>
            <td>Distribution</td>
            <td><input type="text" name="repoDist" required /></td>
        </tr>
        <tr>
            <td>Section</td>
            <td><input type="text" name="repoSection" required /></td>
        </tr>
        <?php } ?>
        <tr>
            <td>Description (fac.)</td>
            <td><input type="text" name="repoDescription" /></td>
        </tr>
        <tr class="type_mirror_input">
            <td>GPG check</td>
            <td>
                <label class="onoff-switch-label">
                    <input name="repoGpgCheck" type="checkbox" class="onoff-switch-input" value="yes" checked />
                    <span class="onoff-switch-slider"></span>
                </label>
            </td>
        </tr>
        <tr class="type_mirror_input">
            <td>Signer avec GPG</td>
            <td>
                <label class="onoff-switch-label">
                    <input name="repoGpgResign" type="checkbox" class="onoff-switch-input" value="yes" <?php if (GPG_SIGN_PACKAGES == "yes") { echo 'checked'; }?> />
                    <span class="onoff-switch-slider"></span>
                </label>
            </td>
        </tr>
        <?php
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
        } ?>
        <tr>
            <td colspan="2"><button type="submit" class="btn-large-red">Valider</button></td>
        </tr>
    </table>
</form>

<script>
/**
 *  Puis à chaque changement d'état, affiche ou masque les inputs supplémentaires en fonction de ce qui est coché
 */
$(document).on('change','input:radio[name="repoType"]',function(){
    if ($("#repoType_mirror").is(":checked")) {
        $(".type_mirror_input").show();
        $(".type_local_input").hide();
    } else {
        $(".type_mirror_input").hide();
        $(".type_local_input").show();
    }
});
</script>