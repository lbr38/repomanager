<section id="newRepoDiv" class="right">
    <img id="newRepoCloseButton" title="Fermer" class="icon-lowopacity" src="ressources/icons/close.png" />
    <?php
    /**
     *  Récupération de la liste de tous les groupes
     */
    $group = new Group('repo');
    $groupList = $group->listAllName();

    if (OS_FAMILY == "Redhat") echo '<h3>CRÉER UN NOUVEAU REPO</h3>';
    if (OS_FAMILY == "Debian") echo '<h3>CRÉER UNE NOUVELLE SECTION</h3>'; ?>

    <form class="operation-form-container" autocomplete="off">
        <div class="operation-form" repo-id="none" repo-status="active" action="new">
            <span>Type</span>
            <div class="switch-field">
                <input type="radio" id="repoType_mirror" class="operation_param" param-name="type" name="repoType" value="mirror" checked />
                <label for="repoType_mirror">Miroir</label>
                <input type="radio" id="repoType_local" class="operation_param" param-name="type" name="repoType" value="local" />
                <label for="repoType_local">Local</label>
            </div>

            <span class="type_mirror_input">Repo source</span>

            <select id="repoSourceSelect" class="type_mirror_input operation_param" param-name="source">
                <option value="">Sélectionner un repo source...</option>
                    <?php
                    if (OS_FAMILY == "Redhat") {
                        $reposFiles = scandir(REPOMANAGER_YUM_DIR);
                        foreach($reposFiles as $repoFileName) {
                            if (($repoFileName != "..") AND ($repoFileName != ".") AND ($repoFileName != "repomanager.conf")) {
                                /**
                                 *  On retire le suffixe .repo du nom du fichier afin que ça soit plus propre dans la liste
                                 */
                                $repoFileNameFormated = str_replace(".repo", "", $repoFileName);
                                echo '<option value="'.$repoFileNameFormated.'">'.$repoFileNameFormated.'</option>';
                            }
                        }
                    }
                    if (OS_FAMILY == "Debian") {
                        $source = new Source();
                        $sourcesList = $source->listAll();
                        if (!empty($sourcesList)) {
                            foreach($sourcesList as $source) {
                                $sourceName = $source['Name'];
                                $sourceUrl = $source['Url'];
                                echo '<option value="'.$sourceName.'">'.$sourceName.' ('.$sourceUrl.')</option>';
                            }
                        }
                    } ?>
            </select>
         
            <span class="type_mirror_input">Nom personnalisé (fac.)</span>
            <span class="type_local_input hide">Nom du repo</span>
            <input type="text" class="operation_param" param-name="alias" />
            
            <?php if (OS_FAMILY == "Debian") { ?>
                <span>Distribution</span>
                <input type="text" class="operation_param" param-name="dist" required />
                
                <span>Section</span>
                <input type="text" class="operation_param" param-name="section" required />
        
            <?php } ?>
        
            <span>Description (fac.)</span>
            <input type="text" class="operation_param" param-name="targetDescription" />

            <span class="type_mirror_input">Vérification des signatures GPG</span>
            <label class="type_mirror_input onoff-switch-label">
                <input name="repoGpgCheck" type="checkbox" class="onoff-switch-input operation_param" value="yes" param-name="targetGpgCheck" checked />
                <span class="onoff-switch-slider"></span>
            </label>

            <span class="type_mirror_input">Signer <?php if (OS_FAMILY == 'Redhat') echo 'les paquets'; if (OS_FAMILY == 'Debian') echo 'le repo';?> avec GPG</span>
            <label class="type_mirror_input onoff-switch-label">
                <input name="repoGpgResign" type="checkbox" class="onoff-switch-input operation_param" value="yes" param-name="targetGpgResign" <?php if (GPG_SIGN_PACKAGES == "yes") echo 'checked';?> />
                <span class="onoff-switch-slider"></span>
            </label>
         
            <?php
            /**
             *  Possibilité d'ajouter à un groupe, si il y en a
             */
            if (!empty($groupList)) { ?>
                <span>Ajouter à un groupe (fac.)</span>
                <select class="operation_param" param-name="targetGroup">
                    <option value="">Sélectionner un groupe...</option>
                    <?php
                    foreach($groupList as $groupName) {
                        echo '<option value="'.$groupName.'">'.$groupName.'</option>';
                    } ?>
                </select>
    <?php   } ?>
   
        </div>
        
        <br>
        <button class="btn-large-red">Confirmer et exécuter<img src="ressources/icons/rocket.png" class="icon" /></button>

    </form>
</section>