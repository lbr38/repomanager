<section class="section-main reloadable-container" container="snapshot/list">
    <div id="loading-tree" class="flex align-item-center column-gap-5">
        <img src="/assets/icons/loading.svg" class="icon" />
        <p class="note">Generating tree structure...</p>
    </div>

    <div class="flex flex-direction-column row-gap-10 margin-top-15 margin-bottom-15">
        <div class="flex flex-wrap align-item-center column-gap-10 row-gap-10">
            <?php
            // If the snapshot does not have a protected environment, display the upload and rebuild buttons
            if (!$protectedEnv) : ?>
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
                <div class="flex align-item-center column-gap-5">
                    <img src="/assets/icons/warning.svg" class="icon" />
                    <span class="note">This snapshot has a protected environment and cannot be modified.</span>
                </div>
                <?php
            endif ?>
        </div>

        <input type="text" id="browse-search-input" placeholder="Search files by name..." autocomplete="off" />
    </div>

    <div id="browse-search-results" class="hide"></div>

    <div id="explorer" class="hide">
        <form id="packages-list" snap-id="<?= $snapId ?>">
        </form>
    </div>

    <script>
        $(document).ready(function () {
            mysnapshot.printTree('<?= $snapshotPath ?>');
        });
    </script>
</section>