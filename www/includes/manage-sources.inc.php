<div id="sourcesDiv" class="param-slide-container">
    <div class="param-slide">
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

        <img id="reposSourcesDivCloseButton" title="Close" class="close-btn lowopacity float-right" src="resources/icons/close.svg" />
        <h3>REPOS SOURCES</h3>

        <p>To create mirrors, you must specify source repositories URL.</p>
        <br>

        <h4>Add a new source repository</h4>

        <form id="addSourceForm" autocomplete="off">
            <table>
                <tr>
                    <td class="td-30">Repo type</td>
                    <td colspan="100%">
                        <div class="switch-field">
                            <?php
                            if (RPM_REPO == 'enabled' and DEB_REPO == 'enabled') : ?>
                                <input type="radio" id="repoType_rpm" name="addSourceRepoType" value="rpm" checked />
                                <label for="repoType_rpm">rpm</label>
                                <input type="radio" id="repoType_deb" name="addSourceRepoType" value="deb" />
                                <label for="repoType_deb">deb</label>
                                <?php
                            elseif (RPM_REPO == 'enabled') : ?>
                                <input type="radio" id="repoType_rpm" name="addSourceRepoType" value="rpm" checked />
                                <label for="repoType_rpm">rpm</label>     
                                <?php
                            elseif (DEB_REPO == 'enabled') : ?>
                                <input type="radio" id="repoType_deb" name="addSourceRepoType" value="deb" checked />
                                <label for="repoType_deb">deb</label> 
                                <?php
                            endif ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="td-30">Name</td>
                    <td colspan="100%">
                        <input type="text" name="addSourceName" required />
                    </td>
                </tr>
                <tr>
                    <td class="td-30">URL</td>
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
                    <td>This source repo has a GPG pub key</td>
                    <td class="td-10">
                        <select id="newRepoGpgSelect" class="select-small">
                            <option id="newRepoGpgSelect_no">No</option>
                            <option id="newRepoGpgSelect_yes">Yes</option>
                        </select>
                    </td>
                </tr>
                <tr field-type="rpm">
                    <td field-type="rpm" colspan="100%">
                        <div class="sourceGpgDiv hide">
                            <br>
                            <span>You can use a known GPG key or specify URL to the GPG key or import a new GPG key (ASCII text).</span><br><br>
                            <p>Pub keys imported into repomanager keychain:</p>

                            <select name="existingGpgKey">
                                <option value="">Select a GPG key...</option>
                                <?php
                                if (!empty($rpmGpgKeys)) {
                                    foreach ($rpmGpgKeys as $gpgFile) {
                                        if (($gpgFile != "..") and ($gpgFile != ".")) {
                                            echo '<option value="' . $gpgFile . '">' . $gpgFile . '</option>';
                                        }
                                    }
                                } ?>
                            </select>

                            <p>URL or file to the GPG key:</p>
                            <input type="text" name="gpgKeyURL" placeholder="https://www... or file:///etc...">
                            
                            <br>
                            <p>Import a GPG key:</p>
                            <textarea id="rpmGpgKeyText" class="textarea-100" placeholder="ASCII format"></textarea>
                        </div>
                    </td>
                </tr>
                <tr field-type="deb">
                    <td field-type="deb" colspan="100%">
                        <p>
                            <br>
                            <span>Import a GPG key</span>
                            <span class="lowopacity">(optionnal)</span>
                        </p>
                        <textarea id="debGpgKeyText" class="textarea-100" placeholder="ASCII format"></textarea>
                    </td>
                </tr>
            </table>
            <br>
            <button type="submit" class="btn-large-green" title="Add">Add source</button>
        </form>

        <?php
        /**
         *  Imported GPG keys list
         */
        if (!empty($rpmGpgKeys) or !empty($debGpgKeys)) : ?>
            <br>
            <h4>Imported GPG pub keys</h4>

            <?php
            if (!empty($rpmGpgKeys)) : ?>
                <table class="table-generic-blue">
                    <?php
                    foreach ($rpmGpgKeys as $gpgKey) :
                        if (($gpgKey != "..") and ($gpgKey != ".")) : ?>
                            <tr>
                                <td title="GPG key name <?= $gpgKey ?>">
                                    <?= $gpgKey ?>
                                </td>
                                <td class="td-fit">
                                    <img src="resources/icons/bin.svg" class="gpgKeyDeleteBtn icon-lowopacity" gpgkey="<?= $gpgKey ?>" repotype="rpm" title="Delete GPG key <?= $gpgKey ?>" />
                                </td>
                            </tr>
                            <?php
                        endif;
                    endforeach; ?>
                </table>
                <?php
            endif;

            if (!empty($debGpgKeys)) : ?>
                <table class="table-generic-blue">
                    <?php
                    foreach ($debGpgKeys as $gpgKey) :
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
                            <tr>
                                <td title="GPG key ID <?= $gpgKeyID ?>">
                                    <?= $gpgKeyName ?>
                                </td>
                                <td class="td-fit">
                                    <img src="resources/icons/bin.svg" class="gpgKeyDeleteBtn icon-lowopacity" gpgkey="<?= $gpgKeyID ?>" repotype="deb" title="Delete GPG key <?= $gpgKeyID ?>" />
                                </td>
                            </tr>
                            <?php
                        endif;
                    endforeach; ?>
                </table>
                <?php
            endif;
        endif;

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
            <br>
            <h4>Current source repositories</h4>

            <?php
            if (!empty($rpmSourcesList)) :
                echo '<h5>RPM</h5>';
                foreach ($rpmSourcesList as $source) :
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
                     */ ?>

                    <div class="header-container sourceDivs">
                        <div class="header-blue-min">
                            <table class="table-large">
                                <tr>
                                    <td>
                                        <input class="sourceFormInput input-medium invisibleInput-blue" type="text" sourcename="<?= $sourceName ?>" value="<?= $sourceName ?>" repotype="rpm" />
                                    </td>
                                    <td class="td-fit">
                                        <img src="resources/icons/cog.svg" class="sourceConfigurationBtn icon-mediumopacity" sourcename="<?= $sourceName ?>" title="<?= $sourceName ?> configuration" />
                                        <img src="resources/icons/bin.svg" class="sourceDeleteToggleBtn icon-lowopacity" sourcename="<?= $sourceName ?>" repotype="rpm" title="Delete <?= $sourceName ?> source repo" />
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div id="sourceConfigurationDiv-<?= $sourceName ?>" class="hide detailsDiv">
                    
                            <p>Parameters:</p>
    
                            <form class="sourceConfForm" sourcename="<?= $sourceName ?>" autocomplete="off">
                                <?php
                                $j = 0;
                                $comments = '';

                                foreach ($content as $line) :
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
                                endforeach ?>
                                <p>Notes:</p>
                                <textarea name="comments" class="textarea-100" placeholder="Write a note..."><?= trim($comments) ?></textarea>                                    
                                <button type="submit" class="btn-large-green" title="Save">Save</button>
                            </form>
                            <br>
                        </div>
                    </div>
                    <?php
                endforeach;
            endif;

            if (!empty($debSourcesList)) :
                echo '<h5>DEB</h5>';

                foreach ($debSourcesList as $source) :
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
                                        <img src="resources/icons/bin.svg" class="sourceDeleteToggleBtn icon-lowopacity" sourcename="<?= $sourceName ?>" repotype="deb" title="Delete <?= $sourceName ?> source repo" />
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <?php
                endforeach;
            endif;
        endif ?>
    </div>
</div>