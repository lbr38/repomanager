<?php ob_start(); ?>

<div class="slide-panel-container" slide-panel="repos/sources/import">
    <div class="slide-panel">
        <img src="/assets/icons/close.svg" class="slide-panel-close-btn float-right lowopacity" slide-panel="repos/sources/import" title="Close" />

        <div class="slide-panel-reloadable-div" slide-panel="repos/sources/import">
            <h3>IMPORT SOURCE REPOSITORIES</h3>

            <form id="import-source-repos">
                <h6>IMPORT SOURCE REPOSITORIES LIST</h6>
                <p class="note">Import from predefined or custom source repositories lists.</p>
                <p class="note">- Predefined lists are public and can be found <a href="https://github.com/lbr38/repomanager/tree/main/www/templates/source-repositories" target="_blank">here <img src="/assets/icons/external-link.svg" class="icon-small" /></a></p>
                <p class="note">- You can create your own custom lists. See documentation <a href="" target="_blank">here <img src="/assets/icons/external-link.svg" class="icon-small" /></a></p>

                <?php
                $validFiles = [];
                $sourceListsDefault = glob(DEFAULT_SOURCES_REPOS_LISTS_DIR . '/*/*.yml');
                $sourceListsCustom = glob(CUSTOM_SOURCES_REPOS_LISTS_DIR . '/*/*.yml');

                /**
                 *  Sort source lists by name
                 */
                asort($sourceListsDefault);
                asort($sourceListsCustom);

                if (empty($sourceListsDefault) and empty($sourceListsCustom)) : ?>
                    <p class="note">Nothing to import.</p>
                    <?php
                endif;

                if (!empty($sourceListsDefault)) {
                    foreach ($sourceListsDefault as $sourceList) {
                        $yaml = yaml_parse_file($sourceList);

                        // Ignore invalid YAML files
                        if ($yaml === false) {
                            continue;
                        }

                        // Ignore empty YAML files
                        if (empty($yaml)) {
                            continue;
                        }

                        /**
                         *  Get file path, type and description from YAML file
                         */
                        $file = str_replace(DEFAULT_SOURCES_REPOS_LISTS_DIR . '/', '', $sourceList);
                        $file = str_replace('.yml', '', $file);
                        $type = $yaml['type'];
                        $description = $yaml['description'];

                        $validFiles['github'][] = [
                            'file' => $file,
                            'type' => $type,
                            'description' => $description
                        ];
                    }
                }

                if (!empty($sourceListsCustom)) {
                    foreach ($sourceListsCustom as $sourceList) {
                        $yaml = yaml_parse_file($sourceList);

                        // Ignore invalid YAML files
                        if ($yaml === false) {
                            continue;
                        }

                        // Ignore empty YAML files
                        if (empty($yaml)) {
                            continue;
                        }

                        /**
                         *  Get file path, type and description from YAML file
                         */
                        $file = str_replace(CUSTOM_SOURCES_REPOS_LISTS_DIR . '/', '', $sourceList);
                        $file = str_replace('.yml', '', $file);
                        $type = $yaml['type'];
                        $description = $yaml['description'];

                        $validFiles['custom'][] = [
                            'file' => $file,
                            'type' => $type,
                            'description' => $description
                        ];
                    }
                }

                if (!empty($validFiles)) : ?>
                    <select name="source-repos-list" multiple required>
                        <?php
                        /**
                         *  List all custom lists (user made)
                         */
                        if (!empty($validFiles['custom'])) : ?>
                            <optgroup label="Your custom lists">
                                <?php
                                foreach ($validFiles['custom'] as $file) : ?>
                                    <option value="custom/<?= $file['file'] ?>"><?= $file['description'] ?> (<?= $file['type'] ?>)</option>
                                    <?php
                                endforeach; ?>
                            </optgroup>
                            <?php
                        endif;

                        /**
                         *  List all predefined lists (github)
                         */
                        if (!empty($validFiles['github'])) : ?>
                            <optgroup label="Predefined lists (github)">
                                <?php
                                foreach ($validFiles['github'] as $file) : ?>
                                    <option value="github/<?= $file['file'] ?>"><?= $file['description'] ?> (<?= $file['type'] ?>)</option>
                                    <?php
                                endforeach; ?>
                            </optgroup>
                            <?php
                        endif ?>
                    </select>
                    <?php
                endif ?>

                <br><br>
                <button type="submit" class="btn-small-green" title="Import source repositories">Import</button>
            </form>
        </div>

        <script>
            $(document).ready(function(){
                myselect2.convert('select[name="source-repos-list"]', 'Select list...');
            });
        </script>
    </div>
</div>


