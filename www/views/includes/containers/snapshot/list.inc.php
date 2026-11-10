<section class="section-main reloadable-container" container="snapshot/list" data-snapshot-path="<?= htmlspecialchars($snapshotPath, ENT_QUOTES) ?>">
    <div id="loading-tree">
        <div class="flex align-item-center column-gap-5">
            <img src="/assets/icons/loading.svg" class="icon" />
            <p class="note">Generating tree structure...</p>
        </div>
    </div>

    <div class="flex flex-direction-column row-gap-10 margin-top-15 margin-bottom-15">
        <div class="flex flex-wrap align-item-center column-gap-10 row-gap-10">
            <?php
            if ($isMirror) : ?>
                <div class="note-info margin-top-0">
                    <img src="/assets/icons/info.svg" />
                    <p>This is a mirror snapshot: its content is an exact copy of the source repository and cannot be modified.</p>
                </div>
                <?php
            // If the snapshot does not have a protected environment, display the upload and rebuild buttons
            elseif (!$protectedEnv) : ?>
                <div class="slide-btn get-panel-btn mediumopacity" panel="snapshot/upload" title="Upload packages to this snapshot">
                    <img src="/assets/icons/upload.svg" />
                    <span>Upload packages</span>
                </div>

                <?php
                // If a task is not running for this snapshot, display the rebuild button
                if (!$taskRunning) : ?>
                    <div class="slide-btn get-panel-btn mediumopacity" panel="snapshot/rebuild" title="Rebuild metadata for this snapshot">
                        <img src="/assets/icons/update.svg" />
                        <span>Rebuild metadata</span>
                    </div>
                    <?php
                endif;
            else : ?>
                <div class="note-warning margin-top-0">
                    <img src="/assets/icons/warning.svg" />
                    <p>This snapshot is used by <?= \Controllers\Environment::nameList($protectedEnvs, 'protected') ?> and cannot be modified.</p>
                </div>
                <?php
            endif ?>
        </div>

        <input type="text" id="browse-search-input" placeholder="Search files by name..." autocomplete="off" />
    </div>

    <div id="browse-search-results" class="hide"></div>

    <div id="explorer" class="hide">
        <form id="packages-list" snap-id="<?= htmlspecialchars($snapId) ?>" <?= $isMirror ? 'read-only' : '' ?>>
        </form>
    </div>

    <script>
        $(document).ready(function () {
            mysnapshot.printTree('<?= $snapshotPath ?>');
        });
    </script>
</section>