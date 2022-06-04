<section id="newRepoDiv" class="right">
    <img id="newRepoCloseButton" title="Fermer" class="icon-lowopacity float-right" src="ressources/icons/close.png" />
    <?php

    /**
     *  Récupération de la liste de tous les groupes
     */

    $group = new \Controllers\Group('repo');
    $groupList = $group->listAllName();

    if (OS_FAMILY == "Redhat") {
        echo '<h3>CRÉER UN NOUVEAU REPO</h3>';
    }
    if (OS_FAMILY == "Debian") {
        echo '<h3>CRÉER UNE NOUVELLE SECTION</h3>';
    } ?>

    <form class="operation-form-container" autocomplete="off">
        <div class="operation-form" repo-id="none" repo-status="active" action="new">
            <?php
            /**
             *  Pour le moment on indique en dur le type de package du repo à traiter en fonction de la famille d'OS
             */
            if (OS_FAMILY == 'Redhat') {
                echo '<input type="hidden" class="operation_param" param-name="packageType" value="rpm" />';
            }
            if (OS_FAMILY == 'Debian') {
                echo '<input type="hidden" class="operation_param" param-name="packageType" value="deb" />';
            } ?>

            <table>
                <tr>
                    <td class="td-30">Type</td>
                    <td>
                        <div class="switch-field">
                            <input type="radio" id="repoType_mirror" class="operation_param" param-name="type" name="repoType" value="mirror" checked />
                            <label for="repoType_mirror">Miroir</label>
                            <input type="radio" id="repoType_local" class="operation_param" param-name="type" name="repoType" value="local" />
                            <label for="repoType_local">Local</label>
                        </div>
                    </td>
                </tr>

            <!-- <tr>
            <td>Type de paquets</td>
            <td>
            <div class="switch-field">
                <input type="radio" id="packageType_rpm" class="operation_param" param-name="packageType" name="packageType" value="rpm" checked />
                <label for="packageType_rpm">rpm</label>
                <input type="radio" id="packageType_deb" class="operation_param" param-name="packageType" name="packageType" value="deb" />
                <label for="packageType_deb">deb</label>
            </div> 
            </td>
            </tr>-->

                <tr class="type_mirror_input">
                    <td class="td-30">Repo source</td>
                    <td>
                        <select id="repoSourceSelect" class="operation_param" param-name="source">
                            <option value="">Sélectionner un repo source...</option>
                            <?php
                            if (OS_FAMILY == "Redhat") {
                                $reposFiles = scandir(REPOMANAGER_YUM_DIR);
                                foreach ($reposFiles as $repoFileName) {
                                    if (($repoFileName != "..") and ($repoFileName != ".") and ($repoFileName != "repomanager.conf")) {
                                        /**
                                         *  On retire le suffixe .repo du nom du fichier afin que ça soit plus propre dans la liste
                                         */
                                        $repoFileNameFormated = str_replace(".repo", "", $repoFileName);
                                        echo '<option value="' . $repoFileNameFormated . '">' . $repoFileNameFormated . '</option>';
                                    }
                                }
                            }

                            if (OS_FAMILY == "Debian") {
                                $source = new \Models\Source();
                                $sourcesList = $source->listAll();
                                if (!empty($sourcesList)) {
                                    foreach ($sourcesList as $source) {
                                        $sourceName = $source['Name'];
                                        $sourceUrl = $source['Url'];
                                        echo '<option value="' . $sourceName . '">' . $sourceName . ' (' . $sourceUrl . ')</option>';
                                    }
                                }
                            } ?>
                        </select>
                    </td>
                </tr>
         
                <tr>
                    <td class="type_mirror_input td-30">Nom personnalisé (fac.)</td>
                    <td class="type_local_input td-30 hide">Nom du repo</td>
                    <td><input type="text" class="operation_param" param-name="alias" /></td>
                </tr>
                
                <?php if (OS_FAMILY == "Debian") : ?>
                    <tr>
                        <td class="td-30">Distribution</td>
                        <td><input type="text" class="operation_param" param-name="dist" required /></td>
                    </tr>
                    
                    <tr>
                        <td class="td-30">Section</td>
                        <td><input type="text" class="operation_param" param-name="section" required /></td>
                    </tr>
                <?php endif ?>

                <tr>
                    <td class="td-30">Faire pointer un environnement</td>
                    <td>
                        <select id="new-repo-target-env-select" class="operation_param" param-name="targetEnv">
                            <option value=""></option>
                            <?php
                            foreach (ENVS as $env) {
                                if ($env == DEFAULT_ENV) {
                                    echo '<option value="' . $env . '" selected>' . $env . '</option>';
                                } else {
                                    echo '<option value="' . $env . '">' . $env . '</option>';
                                }
                            } ?>
                        </select>
                    </td>
                </tr>

                <tr id="new-repo-target-description-tr">
                    <td class="td-30">Description (fac.)</td>
                    <td><input type="text" class="operation_param" param-name="targetDescription" /></td>
                </tr>

                <tr class="type_mirror_input">
                    <td class="td-30">Vérification des signatures GPG</td>
                    <td>
                        <label class="type_mirror_input onoff-switch-label">
                            <input name="repoGpgCheck" type="checkbox" class="onoff-switch-input operation_param" value="yes" param-name="targetGpgCheck" checked />
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </td>
                </tr>

                <tr class="type_mirror_input">
                    <td class="td-30">Signer avec GPG</td>
                    <td>
                        <label class="type_mirror_input onoff-switch-label">
                            <input name="repoGpgResign" type="checkbox" class="onoff-switch-input operation_param" value="yes" param-name="targetGpgResign" <?php echo (GPG_SIGN_PACKAGES == "yes") ? 'checked' : ''; ?>>
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </td>
                </tr>

                <?php
                /**
                 *  Possibilité d'ajouter à un groupe, si il y en a
                 */
                if (!empty($groupList)) : ?>
                    <tr>
                        <td class="td-30">Ajouter à un groupe (fac.)</td>
                        <td>
                            <select class="operation_param" param-name="targetGroup">
                                <option value="">Sélectionner un groupe...</option>
                                <?php
                                foreach ($groupList as $groupName) {
                                    echo '<option value="' . $groupName . '">' . $groupName . '</option>';
                                } ?>
                            </select>
                        </td>
                    </tr>
                <?php endif ?>
            </table>
        </div>
        
        <br>
        <button class="btn-large-red">Confirmer et exécuter<img src="ressources/icons/rocket.png" class="icon" /></button>

    </form>
</section>

<script>
$(document).ready(function(){
    /**
     *  Affiche la description uniquement si un environnement est spécifié
     */
    $(document).on('change','#new-repo-target-env-select',function(){
        if ($('#new-repo-target-env-select').val() == "") {
            $('#new-repo-target-description-tr').hide();
        } else {
            $('#new-repo-target-description-tr').show();
        }
    }).trigger('change');
});
</script>