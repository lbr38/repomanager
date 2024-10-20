<?php ob_start(); ?>

<div class="slide-panel-container" slide-panel="repos/sources/import">
    <div class="slide-panel">

        <img src="/assets/icons/close.svg" class="slide-panel-close-btn float-right lowopacity" slide-panel="repos/sources/import" title="Close" />

        <div class="slide-panel-reloadable-div" slide-panel="repos/sources/import">
            <h3>IMPORT SOURCE REPOSITORIES</h3>

            <form id="import-source-repos">
                <h6>IMPORT PRE-DEFINED SOURCE REPOSITORIES LIST</h6>

                <?php
                $sourceLists = glob(SOURCE_LISTS_DIR . '/*.yml');
                $validFiles = [];

                if (empty($sourceLists)) : ?>
                    <p class="note">Nothing to import.</p>
                    <?php
                endif;

                if (!empty($sourceLists)) {
                    foreach ($sourceLists as $sourceList) {
                        $yaml = yaml_parse_file($sourceList);

                        // Ignore invalid YAML files
                        if ($yaml === false) {
                            continue;
                        }

                        // Ignore empty YAML files
                        if (empty($yaml)) {
                            continue;
                        }

                        $filename = basename($sourceList, '.yml');
                        $type = $yaml['type'];
                        $description = $yaml['description'];

                        $validFiles[] = [
                            'filename' => $filename,
                            'type' => $type,
                            'description' => $description
                        ];
                    }
                }

                if (!empty($validFiles)) : ?>
                    <select name="source-repos-list" multiple required>
                        <?php
                        foreach ($validFiles as $file) : ?>
                            <option value="<?= $file['filename'] ?>" type="<?= $file['type'] ?>"><?= $file['description'] ?></option>
                            <?php
                        endforeach; ?>
                    </select>
                    <?php
                endif; ?>

                <br><br>
                <button type="submit" class="btn-small-green" title="Import source repositories">Import</button>
            </form>
        </div>
    </div>
</div>

<script>
    selectToSelect2('select[name="source-repos-list"]', 'Select list...');
</script>
