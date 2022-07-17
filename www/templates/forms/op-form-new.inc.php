<section id="newRepoDiv" class="right">
    <img id="newRepoCloseButton" title="Fermer" class="icon-lowopacity float-right" src="resources/icons/close.png" />
    <?php

    /**
     *  Récupération de la liste de tous les groupes
     */

    $group = new \Controllers\Group('repo');
    $groupList = $group->listAllName();

    echo '<h3>CRÉER UN NOUVEAU REPO</h3>';
    ?>

    <form class="operation-form-container" autocomplete="off">
        <div class="operation-form" repo-id="none" action="new">
            <table>
                    <tr>
                        <td>Type de paquets</td>
                        <td>
                            <div class="switch-field">
                            <?php
                            /**
                             *  Cas où le serveur gère plusieurs types de repo différents
                             */
                            if (RPM_REPO == 'enabled' and DEB_REPO == 'enabled') : ?>
                                <input type="radio" id="packageType_rpm" class="operation_param" param-name="packageType" name="packageType" value="rpm" checked />
                                <label for="packageType_rpm">rpm</label>
                                <input type="radio" id="packageType_deb" class="operation_param" param-name="packageType" name="packageType" value="deb" />
                                <label for="packageType_deb">deb</label>
                            <?php elseif (RPM_REPO == 'enabled') : ?>
                                <input type="radio" id="packageType_rpm" class="operation_param" param-name="packageType" name="packageType" value="rpm" checked />
                                <label for="packageType_rpm">rpm</label>     
                            <?php elseif (DEB_REPO == 'enabled') : ?>
                                <input type="radio" id="packageType_deb" class="operation_param" param-name="packageType" name="packageType" value="deb" checked />
                                <label for="packageType_deb">deb</label> 
                            <?php endif ?>
                            </div>
                        </td>
                    </tr>
                

                <tr>
                    <td class="td-30">Type de repo</td>
                    <td>
                        <div class="switch-field">
                            <input type="radio" id="repoType_mirror" class="operation_param" param-name="type" name="repoType" value="mirror" package-type="all" checked />
                            <label for="repoType_mirror">Miroir</label>
                            <input type="radio" id="repoType_local" class="operation_param" param-name="type" name="repoType" value="local" package-type="all" />
                            <label for="repoType_local">Local</label>
                        </div>
                    </td>
                </tr>

                <tr field-type="mirror rpm deb">
                    <td class="td-30">Repo source</td>
                    <td>
                        <?php if (RPM_REPO == 'enabled') : ?>
                            <select id="repoSourceSelect" class="operation_param" param-name="source" field-type="mirror rpm" package-type="rpm">
                                <option value="">Sélectionner un repo source...</option>
                                <?php
                                $reposFiles = scandir(REPOMANAGER_YUM_DIR);

                                foreach ($reposFiles as $repoFileName) {
                                    if (($repoFileName != "..") and ($repoFileName != ".") and ($repoFileName != "repomanager.conf")) {
                                        /**
                                         *  On retire le suffixe .repo du nom du fichier afin que ça soit plus propre dans la liste
                                         */
                                        $repoFileNameFormated = str_replace(".repo", "", $repoFileName);

                                        echo '<option value="' . $repoFileNameFormated . '">' . $repoFileNameFormated . '</option>';
                                    }
                                } ?>
                            </select>
                        <?php endif;

                        if (DEB_REPO == 'enabled') : ?>
                            <select id="repoSourceSelect" class="operation_param" param-name="source" field-type="mirror deb" package-type="deb">
                                <option value="">Sélectionner un repo source...</option>
                                <?php
                                $source = new \Models\Source();
                                $sourcesList = $source->listAll();

                                if (!empty($sourcesList)) {
                                    foreach ($sourcesList as $source) {
                                        $sourceName = $source['Name'];
                                        $sourceUrl = $source['Url'];

                                        echo '<option value="' . $sourceName . '">' . $sourceName . ' (' . $sourceUrl . ')</option>';
                                    }
                                } ?>
                            </select>
                        <?php endif; ?>
                    </td>
                </tr>
         
                <tr>
                    <td class="td-30" field-type="mirror rpm deb">Nom personnalisé (fac.)</td>
                    <td class="td-30" field-type="local rpm deb">Nom du repo</td>
                    <td>
                        <input type="text" class="operation_param" param-name="alias" package-type="all" />
                    </td>
                </tr>

                <tr field-type="mirror local deb">
                    <td class="td-30">Distribution</td>
                    <td>
                        <input type="text" class="operation_param" param-name="dist" package-type="deb" />
                    </td>
                </tr>

                <tr field-type="mirror local deb">
                    <td class="td-30">Section</td>
                    <td>
                        <input type="text" class="operation_param" param-name="section" package-type="deb" />
                    </td>
                </tr>

                <tr>
                    <td class="td-30">Faire pointer un environnement</td>
                    <td>
                        <select id="new-repo-target-env-select" class="operation_param" param-name="targetEnv" package-type="all">
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
                    <td><input type="text" class="operation_param" param-name="targetDescription" package-type="all" /></td>
                </tr>

                <tr field-type="mirror rpm deb">
                    <td class="td-30">Vérification des signatures GPG</td>
                    <td>
                        <label class="onoff-switch-label">
                            <input name="repoGpgCheck" type="checkbox" class="onoff-switch-input operation_param" value="yes" param-name="targetGpgCheck" package-type="all" checked />
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </td>
                </tr>

                <tr field-type="mirror rpm deb">
                    <td class="td-30">Signer avec GPG</td>
                    <td>
                        <label class="onoff-switch-label" field-type="mirror rpm">
                            <input name="repoGpgResign" type="checkbox" class="onoff-switch-input operation_param type_rpm" value="yes" param-name="targetGpgResign" package-type="rpm" <?php echo (RPM_SIGN_PACKAGES == "yes") ? 'checked' : ''; ?> />
                            <span class="onoff-switch-slider"></span>
                        </label>
                        <label class="onoff-switch-label" field-type="mirror deb">
                            <input name="repoGpgResign" type="checkbox" class="onoff-switch-input operation_param type_deb" value="yes" param-name="targetGpgResign" package-type="deb" <?php echo (DEB_SIGN_REPO == "yes") ? 'checked' : ''; ?> />
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
                            <select class="operation_param" param-name="targetGroup" package-type="all" >
                                <option value="">Sélectionner un groupe...</option>
                                <?php
                                foreach ($groupList as $groupName) {
                                    echo '<option value="' . $groupName . '">' . $groupName . '</option>';
                                } ?>
                            </select>
                        </td>
                    </tr>
                <?php endif ?>

                <tr field-type="mirror rpm deb">
                    <td colspan="100%"><b>Paramètres avancés</b></td>
                </tr>

                <tr field-type="mirror rpm deb">
                    <td class="td-30">Architecture</td>
                    <td field-type="mirror rpm">
                        <select class="targetIncludeArchSelect operation_param" param-name="targetIncludeArch" package-type="rpm" multiple>
                            <option value="">Sélectionner l'architecture...</option>
                            <option value="x86_64">x86_64</option>
                            <option value="noarch">noarch</option>
                        </select>
                    </td>

                    <td field-type="mirror deb">
                        <select class="targetIncludeArchSelect operation_param" param-name="targetIncludeArch" package-type="deb" multiple>
                            <option value="">Sélectionner l'architecture...</option>
                            <option value="i386">i386</option>
                            <option value="amd64">amd64</option>
                            <option value="armhf">armhf</option>
                        </select>
                    </td>
                </tr>

                <tr field-type="mirror rpm deb">
                    <td class="td-30">Inclu. les sources</td>
                    <td>
                        <label class="onoff-switch-label">
                            <input name="repoIncludeSource" type="checkbox" class="onoff-switch-input operation_param" value="yes" param-name="targetIncludeSource" package-type="all" />
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </td>
                </tr>

                <tr field-type="mirror deb">
                    <td class="td-30">Inclu. les traductions de paquets</td>
                    <td>
                        <select id="targetIncludeTranslationSelect" class="operation_param" param-name="targetIncludeTranslation" package-type="deb" multiple>
                            <option value="">Sélectionner des traductions...</option>
                            <option value="en">en (english)</option>
                            <option value="fr">fr (french)</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>
        
        <br>
        <button class="btn-large-red">Confirmer et exécuter<img src="resources/icons/rocket.png" class="icon" /></button>

    </form>

    <script>
    $(document).ready(function(){
        // /**
        //  *  Affiche la description uniquement si un environnement est spécifié
        //  */
        // $(document).on('change','#new-repo-target-env-select',function(){
        //     if ($('#new-repo-target-env-select').val() == "") {
        //         $('#new-repo-target-description-tr').hide();
        //     } else {
        //         $('#new-repo-target-description-tr').show();
        //     }
        // }).trigger('change');

        // $('.targetIncludeArchSelect, #targetIncludeTranslationSelect').select2({
        //     closeOnSelect: false,
        //     placeholder: 'Sélectionner...'
        // });
    });
    </script>
</section>