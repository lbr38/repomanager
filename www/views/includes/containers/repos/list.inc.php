<?php
use \Controllers\User\Permission\Repo as RepoPermission; ?>

<section class="section-main reloadable-container" container="repos/list">
    <div id="repositories-list">
        <?php
        // Print repositories
        if (IS_ADMIN or (!empty(USER_PERMISSIONS['repositories']['view']['groups']) or in_array('all', USER_PERMISSIONS['repositories']['view']))) { ?>
            <div class="flex flex-wrap align-item-center column-gap-10 row-gap-10 margin-bottom-15 repo-toolbar">
                <input id="repo-search-input" class="flex-grow margin-bottom-0" type="text" placeholder="Search repositories" onkeyup="myrepo.search()" title="Search by repository name, distribution, section or release version" />

                <div class="flex align-item-center column-gap-5">
                    <?php
                    if (RepoPermission::allowedAction('edit-groups')) : ?>
                        <div class="slide-btn get-panel-btn mediumopacity" panel="repos/groups/list" title="Manage repos groups">
                            <img src="/assets/icons/folder.svg" />
                            <span>Groups</span>
                        </div>
                        <?php
                    endif;

                    if (RepoPermission::allowedAction('edit-source')) : ?>
                        <div class="slide-btn get-panel-btn mediumopacity" panel="repos/sources/list" title="Manage source repositories">
                            <img src="/assets/icons/internet.svg" />
                            <span>Source repositories</span>
                        </div>
                        <?php
                    endif;

                    if (RepoPermission::allowedAction('create')) : ?>
                        <div class="slide-btn get-panel-btn bkg-green" panel="repos/new" title="Create a new mirror or local repository">
                            <img src="/assets/icons/plus.svg" />
                            <span>Create a new repository</span>
                        </div>
                        <?php
                    endif ?>

                    <div id="hide-all-repo-groups" state="visible">
                        <img src="/assets/icons/view.svg" class="icon lowopacity pointer" title="Hide/Show all repositories groups" />
                    </div>
                </div>
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