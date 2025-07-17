<section class="section-left reloadable-container" container="repos/list">
    <div class="reposList">
        <div id="title-button-div">
            <h3>REPOSITORIES</h3>

            <div class="flex justify-space-between">
                <?php
                if (IS_ADMIN) : ?>
                    <div class="slide-btn get-panel-btn" panel="repos/groups/list" title="Manage repos groups">
                        <img src="/assets/icons/folder.svg" />
                        <span>Manage groups</span>
                    </div>

                    <div class="slide-btn get-panel-btn" panel="repos/sources/list" title="Manage source repositories">
                        <img src="/assets/icons/internet.svg" />
                        <span>Manage source repositories</span>
                    </div>
                    <?php
                endif;

                if (IS_ADMIN or in_array('create', USER_PERMISSIONS['repositories']['allowed-actions']['repos'])) : ?>
                    <div class="slide-btn get-panel-btn" panel="repos/new" title="Create a new mirror or local repository">
                        <img src="/assets/icons/plus.svg" />
                        <span>Create a new repository</span>
                    </div>
                    <?php
                endif ?>
            </div>
        </div>

        <?php
        if (IS_ADMIN or (!empty(USER_PERMISSIONS['repositories']['view']['groups']) or in_array('all', USER_PERMISSIONS['repositories']['view']))) { ?>
            <input id="repo-search-input" class="margin-bottom-10" type="text" placeholder="Search" onkeyup="searchRepo()" title="Search by repository name, distribution, section or release version" />

            <div id="hideAllReposGroups" class="flex justify-end column-gap-5 margin-bottom-10 margin-right-15 lowopacity pointer" state="visible">
                <img src="/assets/icons/view.svg" class="icon" title="Hide/Show all repositories groups" />
            </div>

            <div id="repos-list-container">
                <?php include_once(ROOT . '/views/includes/repos-list.inc.php'); ?>
            </div>
            <?php
        } else {
            echo '<p class="note">Nothing to show here!</p>';
        } ?>
    </div>
</section>