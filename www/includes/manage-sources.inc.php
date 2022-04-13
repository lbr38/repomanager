<section class="right" id="sourcesDiv">
    <?php $source = new Source(); ?>

    <img id="reposSourcesDivCloseButton" title="Fermer" class="icon-lowopacity float-right" src="ressources/icons/close.png" />
    <h3>REPOS SOURCES</h3>

    <p>Pour créer un miroir, repomanager doit connaitre l'URL du repo source.</p>
    <br>

    <div class="div-generic-gray">
        <h5>Ajouter un nouveau repo source</h5>
        <form id="sourceAddForm" autocomplete="off">
            <p>Nom :</p>
            <input type="text" class="input-large" name="addSourceName" required />

            <br>

            <p>Url :</p>

            <?php
            if (OS_FAMILY == "Redhat") { ?>
                <span>
                    <select id="addSourceUrlType" class="select-small" required>
                        <option value="baseurl">baseurl</option>
                        <option value="mirrorlist">mirrorlist</option>
                        <option value="metalink">metalink</option>
                    </select> 
                    <input type="text" name="addSourceUrl" class="input-large" required>
                </span>
                <br>
        <?php }
            if (OS_FAMILY == "Debian") { ?>
                <input type="text" name="addSourceUrl" class="input-large" required><br>
        <?php } 

            if (OS_FAMILY == "Redhat") { ?>
                <p>Ce repo source dispose d'une clé GPG : 
                <select id="newRepoGpgSelect" class="select-small">
                    <option id="newRepoGpgSelect_no">Non</option>
                    <option id="newRepoGpgSelect_yes">Oui</option>
                </select>
                </p>

                <div class="sourceGpgDiv hide">
                    <span>Vous pouvez utiliser une clé déjà présente dans le trousseau de repomanager ou renseignez l'URL vers la clé GPG ou bien importer une nouvelle clé GPG au format texte ASCII dans le trousseau de repomanager.</span><br><br>
                    <p>Clé GPG du trousseau de repomanager :</p>
                    <select name="existingGpgKey">
                        <option value="">Choisir une clé GPG...</option>
                        <?php
                        $gpgFiles = scandir(RPM_GPG_DIR);
                        if (!empty($gpgFiles)) {
                            foreach($gpgFiles as $gpgFile) {
                                if (($gpgFile != "..") AND ($gpgFile != ".")) {
                                    echo '<option value="'.$gpgFile.'">'.$gpgFile.'</option>';
                                }
                            }
                        } ?>
                    </select>

                    <p>URL ou fichier vers une clé GPG :</p>
                    <input type="text" name="gpgKeyURL" placeholder="https://www... ou file:///etc...">
                    <br>
                    <p>Importer une nouvelle clé GPG :</p>
                    <textarea id="gpgKeyText" class="textarea-100" placeholder="Format ASCII"></textarea>
                </div>
            <?php
            }

            /**
             *  Cas Debian
             */
            if (OS_FAMILY == "Debian") { ?>
                <p>Clé GPG (fac.) :</p>
                <textarea id="gpgKeyText" class="textarea-100" placeholder="Format ASCII"></textarea>
            <?php } ?>

            <br>
            <br>
            <button type="submit" class="btn-large-blue" title="Ajouter">Ajouter</button>

        </form>
    </div>

    <?php
    /**
     *  LISTE DES CLES GPG DU TROUSSEAU DE REPOMANAGER
     */

    /**
     *  Dans le cas de rpm, les clés gpg sont stockées dans RPM_GPG_DIR (en principe par défaut /etc/pki/rpm-gpg/repomanager)
    */
    if (OS_FAMILY == "Redhat") {
        $gpgKeys = scandir(RPM_GPG_DIR);
    }

    /**
     *  Dans le cas de apt, les clés sont stockées dans le trousseau GPG 'trustedkeys.gpg' de repomanager
     */
    if (OS_FAMILY == "Debian") {
        $gpgKeys = shell_exec("gpg --homedir ".GPGHOME." --no-default-keyring --keyring ".GPGHOME."/trustedkeys.gpg --list-key --fixed-list-mode --with-colons | sed 's/^pub/\\npub/g' | grep -v '^tru:'");
        $gpgKeys = explode("\n\n", $gpgKeys);
    }

    if (!empty($gpgKeys)) {
        echo '<div class="div-generic-gray">';
            echo '<h5>Liste des clés GPG du trousseau de repomanager</h5>';
            foreach($gpgKeys as $gpgKey) {
                if (OS_FAMILY == "Redhat") {
                    if (($gpgKey != "..") AND ($gpgKey != ".")) { ?>
                        <p>
                            <img src="ressources/icons/bin.png" class="gpgKeyDeleteBtn icon-lowopacity" gpgkey="<?php echo $gpgKey;?>" title="Supprimer la clé GPG <?php echo $gpgKey;?>" />
                            <?php echo $gpgKey;?>
                        </p>
<?php               }
                }
                if (OS_FAMILY == "Debian") {
                    // On récup uniquement l'ID de la clé GPG
                    $gpgKeyID = shell_exec("echo \"$gpgKey\" | sed -n -e '/pub/,/uid/p' | grep '^fpr:' | awk -F':' '{print $10}'");
                    // Retire tous les espaces blancs
                    $gpgKeyID = preg_replace('/\s+/', '', $gpgKeyID);
                    // Récupère le nom de la clé GPG
                    $gpgKeyName = shell_exec("echo \"$gpgKey\" | sed -n -e '/pub/,/uid/p' | grep '^uid:' | awk -F':' '{print $10}'");
                    if (!empty($gpgKeyID) AND !empty($gpgKeyName)) { ?>
                        <p>
                            <img src="ressources/icons/bin.png" class="gpgKeyDeleteBtn icon-lowopacity" gpgkey="<?php echo $gpgKeyID;?>" title="Supprimer la clé GPG <?php echo $gpgKeyID;?>" />
                            <?php echo $gpgKeyName." ($gpgKeyID)";?>
                        </p>
<?php               }
                }
            }
        echo '</div>';
    } ?>

        <?php
        /**
         *  AFFICHAGE DES REPOS SOURCES ACTUELS
         */

        /**
         *  1. Récupération de tous les noms de sources
         */
        if (OS_FAMILY == "Redhat") $sourcesList = scandir(REPOMANAGER_YUM_DIR);
        if (OS_FAMILY == "Debian") $sourcesList = $source->listAll();

        /**
         *  2. Affichage des groupes si il y en a
         */
        if (!empty($sourcesList)) {
            echo '<div class="div-generic-gray">';
                echo "<h5>Repos sources actuels</h5>";

                foreach($sourcesList as $source) {
                    if (OS_FAMILY == "Redhat") {
                        /**
                         *  Si le nom du fichier ne termine pas par '.repo' alors on passe au suivant
                         */
                        if (!preg_match('/.repo$/i', $source)) {
                            continue;
                        }
                        $sourceName = str_replace(".repo", "", $source);
                        
                        /**
                         *  On récupère le contenu du fichier
                         */
                        $content = explode("\n", file_get_contents(REPOMANAGER_YUM_DIR."/${source}", true));
                    }
                    if (OS_FAMILY == "Debian") {
                        $sourceName = $source['Name'];
                        $sourceUrl = $source['Url'];
                    }

                    /**
                     *  Affichage des sources
                     */ ?>
                    <div class="header-container sourceDivs">
                        <div class="header-blue-min">
                            <?php
                            if (OS_FAMILY == "Debian") {
                                echo '<input type="hidden" name="actualSourceUrl" value="'.$sourceUrl.'" />';
                            } ?>

                            <table class="table-large">
                                <tr>
                                    <td>
                                        <input class="sourceFormInput input-medium invisibleInput-blue" type="text" sourcename="<?php echo $sourceName;?>" value="<?php echo $sourceName;?>" />
                                    </td>
                                    <?php
                                    if (OS_FAMILY == "Debian") {
                                        echo '<td><input class="sourceFormUrlInput input-medium invisibleInput-blue" type="text" sourcename="'.$sourceName.'" value="'.$sourceUrl.'" /></td>';
                                    } ?>
                                    <td class="td-fit">
                                        <?php
                                        if (OS_FAMILY == "Redhat") {
                                            echo '<img src="ressources/icons/cog.png" class="sourceConfigurationBtn icon-mediumopacity" sourcename="'.$sourceName.'" title="Configuration de '.$sourceName.'" />';
                                        }
                                        echo '<img src="ressources/icons/bin.png" class="sourceDeleteToggleBtn icon-lowopacity" sourcename="'.$sourceName.'" title="Supprimer le repo source '.$sourceName.'" />';
                                        ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <?php
                        /**
                         *  4. La configuration des repos sources est placée dans un div caché
                         */
                        if (OS_FAMILY == "Redhat") { ?>
                            <div id="sourceConfigurationDiv-<?php echo $sourceName;?>" class="hide detailsDiv">
                        
                                <p>Paramètres :</p>
        
                                <form class="sourceConfForm" sourcename="<?php echo $sourceName;?>" autocomplete="off">
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
                                        if (isset($line[1]) AND $line[1] != "") {
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
                                        //if (substr($optionName, 0, 1) === "#") {
                                        if (preg_match('/^#/', $optionName)) {
                                            $comments .= str_replace('#', '', $optionName).PHP_EOL;
                                            continue;
                                        }

                                        /**
                                         *  Affichage de l'option et de sa valeur
                                         */
                                        echo '<input type="text" class="sourceConfForm-optionName input-small" name="option-name" option-id="'.$j.'" value="'.$optionName.'" readonly />';
                                        
                                        if ($optionValue == "1" OR $optionValue == "0" OR $optionValue == "yes" OR $optionValue == "no") {
                                            if ($optionValue == "1" OR $optionValue == "yes") {
                                                echo '<label class="onoff-switch-label">';
                                                echo '<input class="sourceConfForm-optionValue onoff-switch-input" name="option-value" option-id="'.$j.'" type="checkbox" value="yes" checked />';
                                                echo '<span class="onoff-switch-slider"></span>';
                                                echo '</label>';
                                            }
                                            if ($optionValue == "0" OR $optionValue == "no") {
                                                echo '<label class="onoff-switch-label">';
                                                echo '<input class="sourceConfForm-optionValue onoff-switch-input" name="option-value" option-id="'.$j.'" type="checkbox" value="yes" />';
                                                echo '<span class="onoff-switch-slider"></span>';
                                                echo '</label>';
                                            }

                                        } else {
                                            echo '<input type="text" class="sourceConfForm-optionValue input-large" name="option-value" option-id="'.$j.'" value="'.$optionValue.'" />';
                                        }
                                        echo '<br>';
                                        ++$j;
                                    } ?>

                                    <p>Notes :</p>

                                    <textarea name="comments" class="textarea-100" placeholder="Écrire un commentaire..."><?php if (!empty($comments)) echo trim($comments);?></textarea>

                                    <button type="submit" class="btn-large-blue" title="Enregistrer">Enregistrer</button>
                                </form>
                                <br>
                            </div>
                <?php   }
            echo '</div>';

                // echo '</div>';
            }
        }
    ?>
    </table>
</section>