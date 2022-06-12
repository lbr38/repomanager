<section class="right" id="sourcesDiv">
    <?php
    $source = new \Models\Source();

    /**
     *  Dans le cas de rpm, les clés gpg sont stockées dans RPM_GPG_DIR (en principe par défaut /etc/pki/rpm-gpg/repomanager)
     */
    if (is_dir(RPM_GPG_DIR)) {
        $rpmGpgKeys = scandir(RPM_GPG_DIR);
    }

    /**
     *  Dans le cas de apt, les clés sont stockées dans le trousseau GPG 'trustedkeys.gpg' de repomanager
     */
    if (file_exists(GPGHOME . "/trustedkeys.gpg")) {
        $debGpgKeys = shell_exec("gpg --homedir " . GPGHOME . " --no-default-keyring --keyring " . GPGHOME . "/trustedkeys.gpg --list-key --fixed-list-mode --with-colons | sed 's/^pub/\\npub/g' | grep -v '^tru:'");
        $debGpgKeys = explode("\n\n", $debGpgKeys);
    } ?>

    <img id="reposSourcesDivCloseButton" title="Fermer" class="icon-lowopacity float-right" src="ressources/icons/close.png" />
    <h3>REPOS SOURCES</h3>

    <p>Pour créer un miroir, repomanager doit connaitre l'URL du repo source.</p>
    <br>

    <div class="div-generic-gray">

        <h5>Ajouter un nouveau repo source</h5>

        <form id="addSourceForm" autocomplete="off">
            <table>
                <tr>
                    <td class="td-30">Type de repo source</td>
                    <td colspan="100%">
                        <div class="switch-field">
                            <input type="radio" id="repoType_rpm" name="addSourceRepoType" value="rpm" checked />
                            <label for="repoType_rpm">rpm</label>
                            <input type="radio" id="repoType_deb" name="addSourceRepoType" value="deb" />
                            <label for="repoType_deb">deb</label>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td class="td-30">Nom</td>
                    <td colspan="100%">
                        <input type="text" name="addSourceName" required />
                    </td>
                </tr>

                <tr>
                    <td class="td-30">Url</td>
                    <td class="td-10" field-type="rpm">
                        <select id="addSourceUrlType" class="select-small" required>
                            <option value="baseurl">baseurl</option>
                            <option value="mirrorlist">mirrorlist</option>
                            <option value="metalink">metalink</option>
                        </select>
                    </td>

                    <td>
                        <input type="text" name="addSourceUrl" required />
                    </td>
                </tr>

                <tr field-type="rpm">
                    <td>Ce repo source dispose d'une clé GPG</td>
                    <td class="td-10">
                        <select id="newRepoGpgSelect" class="select-small">
                            <option id="newRepoGpgSelect_no">Non</option>
                            <option id="newRepoGpgSelect_yes">Oui</option>
                        </select>
                    </td>
                </tr>

                <tr field-type="rpm">
                    <td colspan="100%">
                        <div class="sourceGpgDiv hide">
                            <span>Vous pouvez utiliser une clé déjà présente dans le trousseau de repomanager ou renseignez l'URL vers la clé GPG ou bien importer une nouvelle clé GPG au format texte ASCII dans le trousseau de repomanager.</span><br><br>
                            <p>Clé GPG du trousseau de repomanager :</p>

                            <select name="existingGpgKey">
                                <option value="">Choisir une clé GPG...</option>
                                <?php
                                if (!empty($rpmGpgKeys)) {
                                    foreach ($rpmGpgKeys as $gpgFile) {
                                        if (($gpgFile != "..") and ($gpgFile != ".")) {
                                            echo '<option value="' . $gpgFile . '">' . $gpgFile . '</option>';
                                        }
                                    }
                                } ?>
                            </select>

                            <p>URL ou fichier vers une clé GPG :</p>
                            <input type="text" name="gpgKeyURL" placeholder="https://www... ou file:///etc...">
                            
                            <br>

                            <p>Importer une nouvelle clé GPG :</p>
                            <textarea id="rpmGpgKeyText" class="textarea-100" placeholder="Format ASCII"></textarea>
                        </div>
                    </td>
                </tr>

                <tr field-type="deb">
                    <td colspan="100%">
                        <p>Clé GPG (fac.) :</p>
                        <textarea id="debGpgKeyText" class="textarea-100" placeholder="Format ASCII"></textarea>
                    </td>
                </tr>
            </table>
            <button type="submit" class="btn-large-blue" title="Ajouter">Ajouter</button>

        </form>
    </div>

    <?php

    /**
     * Liste des clés du trousseau de repomanager
     */
    if (!empty($rpmGpgKeys) or !empty($debGpgKeys)) : ?>
        <div class="div-generic-gray">
        
            <h5>Clés GPG importées</h5>

            <?php
            if (!empty($rpmGpgKeys)) {
                foreach ($rpmGpgKeys as $gpgKey) {
                    if (($gpgKey != "..") and ($gpgKey != ".")) : ?>
                        <p>
                            <img src="ressources/icons/bin.png" class="gpgKeyDeleteBtn icon-lowopacity" gpgkey="<?= $gpgKey ?>" repotype="rpm" title="Supprimer la clé GPG <?= $gpgKey ?>" />
                            <?= $gpgKey ?>
                        </p>
                    <?php endif;
                }
            }

            if (!empty($debGpgKeys)) {
                foreach ($debGpgKeys as $gpgKey) {
                    /**
                     *  On récupère uniquement l'ID de la clé GPG
                     */
                    $gpgKeyID = shell_exec("echo \"$gpgKey\" | sed -n -e '/pub/,/uid/p' | grep '^fpr:' | awk -F':' '{print $10}'");

                    /**
                     *  Retire tous les espaces blancs
                     */
                    $gpgKeyID = preg_replace('/\s+/', '', $gpgKeyID);

                    /**
                     *  Récupère le nom de la clé GPG
                     */
                    $gpgKeyName = shell_exec("echo \"$gpgKey\" | sed -n -e '/pub/,/uid/p' | grep '^uid:' | awk -F':' '{print $10}'");

                    if (!empty($gpgKeyID) and !empty($gpgKeyName)) : ?>
                        <p>
                            <img src="ressources/icons/bin.png" class="gpgKeyDeleteBtn icon-lowopacity" gpgkey="<?= $gpgKeyID ?>" repotype="deb" title="Supprimer la clé GPG <?= $gpgKeyID ?>" />
                            <?= $gpgKeyName . " ($gpgKeyID)" ?>
                        </p>
                    <?php endif;
                }
            } ?>
        </div>
    <?php endif ?>

    <?php
        /**
         *  AFFICHAGE DES REPOS SOURCES ACTUELS
         */

        /**
         *  1. Récupération de tous les noms de sources
         */
        $rpmSourcesList = glob(REPOMANAGER_YUM_DIR . '/*.repo');
        $debSourcesList = $source->listAll();

        /**
         *  2. Affichage des groupes si il y en a
         */
    if (!empty($rpmSourcesList) or !empty($debSourcesList)) : ?>
            <div class="div-generic-gray">
                <h5>Repos sources actuels</h5>

                <?php
                if (!empty($rpmSourcesList)) {
                    echo '<h4>Rpm</h4>';

                    foreach ($rpmSourcesList as $source) {
                        /**
                         *  Conserver uniquement le nom du fichier et pas son chemin complet
                         */
                        $source = basename($source);
                        $sourceName = str_replace(".repo", "", $source);

                        /**
                         *  On récupère le contenu du fichier
                         */
                        $content = explode("\n", file_get_contents(REPOMANAGER_YUM_DIR . '/' . $source, true));

                        /**
                         *  Affichage des sources
                         */
                        ?>
                        <div class="header-container sourceDivs">
                            <div class="header-blue-min">
                                <table class="table-large">
                                    <tr>
                                        <td>
                                            <input class="sourceFormInput input-medium invisibleInput-blue" type="text" sourcename="<?= $sourceName ?>" value="<?= $sourceName ?>" repotype="rpm" />
                                        </td>
                                        <td class="td-fit">
                                            <img src="ressources/icons/cog.png" class="sourceConfigurationBtn icon-mediumopacity" sourcename="<?= $sourceName ?>" title="Configuration de <?= $sourceName ?>" />
                                            <img src="ressources/icons/bin.png" class="sourceDeleteToggleBtn icon-lowopacity" sourcename="<?= $sourceName ?>" repotype="rpm" title="Supprimer le repo source <?= $sourceName ?>" />
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <div id="sourceConfigurationDiv-<?= $sourceName ?>" class="hide detailsDiv">
                        
                                <p>Paramètres :</p>
        
                                <form class="sourceConfForm" sourcename="<?= $sourceName ?>" autocomplete="off">
                                    <?php
                                    $j = 0;
                                    $comments = '';

                                    foreach ($content as $line) {
                                        /**
                                         *  Si la ligne est vide on passe à la suivante
                                         */
                                        if (empty($line)) {
                                            continue;
                                        }

                                        /**
                                         *  On sépare le nom du paramètre de sa valeur (ils sont séparés par un =)
                                         *  On indique une limite de 2 afin de n'obtenir pas plus de 2 termes (la valeur et son paramètre)
                                         */
                                        $line = explode('=', $line, 2);

                                        if (!empty($line[0])) {
                                            $optionName = $line[0];
                                        } else {
                                            $optionName = '';
                                        }

                                        if (isset($line[1]) and $line[1] != "") {
                                            $optionValue = $line[1];
                                        } else {
                                            $optionValue = '';
                                        }

                                        /**
                                         *  Si l'option contient [$sourceName], on ne l'affiche pas
                                         */
                                        if ($optionName == "[$sourceName]") {
                                            continue;
                                        }

                                        /**
                                         *  Si l'option contient un dièse # alors il s'agit d'un commentaire
                                         *  On l'ajoute à la liste des commentaires
                                         */
                                        if (preg_match('/^#/', $optionName)) {
                                            $comments .= str_replace('#', '', $optionName) . PHP_EOL;
                                            continue;
                                        }

                                        /**
                                         *  Affichage de l'option et de sa valeur
                                         */
                                        echo '<input type="text" class="sourceConfForm-optionName input-small" name="option-name" option-id="' . $j . '" value="' . $optionName . '" readonly />';

                                        if ($optionValue == "1" or $optionValue == "0" or $optionValue == "yes" or $optionValue == "no") {
                                            if ($optionValue == "1" or $optionValue == "yes") {
                                                echo '<label class="onoff-switch-label">';
                                                echo '<input class="sourceConfForm-optionValue onoff-switch-input" name="option-value" option-id="' . $j . '" type="checkbox" value="yes" checked />';
                                                echo '<span class="onoff-switch-slider"></span>';
                                                echo '</label>';
                                            }
                                            if ($optionValue == "0" or $optionValue == "no") {
                                                echo '<label class="onoff-switch-label">';
                                                echo '<input class="sourceConfForm-optionValue onoff-switch-input" name="option-value" option-id="' . $j . '" type="checkbox" value="yes" />';
                                                echo '<span class="onoff-switch-slider"></span>';
                                                echo '</label>';
                                            }
                                        } else {
                                            echo '<input type="text" class="sourceConfForm-optionValue input-large" name="option-value" option-id="' . $j . '" value="' . $optionValue . '" />';
                                        }
                                        echo '<br>';
                                        ++$j;
                                    } ?>

                                    <p>Notes :</p>

                                    <textarea name="comments" class="textarea-100" placeholder="Écrire un commentaire...">
                                        <?php
                                        if (!empty($comments)) {
                                            echo trim($comments);
                                        } ?>
                                    </textarea>

                                    <button type="submit" class="btn-large-blue" title="Enregistrer">Enregistrer</button>
                                </form>
                                <br>
                            </div>
                        </div>
                    <?php }
                }

                if (!empty($debSourcesList)) {
                    echo '<h4>Deb</h4>';

                    foreach ($debSourcesList as $source) {
                        $sourceName = $source['Name'];
                        $sourceUrl = $source['Url'];
                        ?>
                        <div class="header-container sourceDivs">
                            <div class="header-blue-min">
                                <input type="hidden" name="actualSourceUrl" value="<?= $sourceUrl ?>" />
                                <table class="table-large">
                                    <tr>
                                        <td>
                                            <input class="sourceFormInput input-medium invisibleInput-blue" type="text" sourcename="<?= $sourceName ?>" value="<?= $sourceName ?>" repotype="deb" />
                                        </td>
                                        <td>
                                            <input class="sourceFormUrlInput input-medium invisibleInput-blue" type="text" sourcename="<?= $sourceName ?>" value="<?= $sourceUrl ?>" />
                                        </td>
                                
                                        <td class="td-fit">
                                            <img src="ressources/icons/bin.png" class="sourceDeleteToggleBtn icon-lowopacity" sourcename="<?= $sourceName ?>" repotype="deb" title="Supprimer le repo source <?= $sourceName ?>" />
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    <?php }
                }
    endif; ?>
    </table>
</section>