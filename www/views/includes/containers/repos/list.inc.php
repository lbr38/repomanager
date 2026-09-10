<?php
use \Controllers\User\Permission\Repo as RepoPermission; ?>

<section class="section-main reloadable-container" container="repos/list">
    <div id="repositories-list">
        <?php
        // Print repositories
        if (IS_ADMIN or (!empty(USER_PERMISSIONS['repositories']['view']['groups']) or !empty(USER_PERMISSIONS['repositories']['view']['repos']) or in_array('all', USER_PERMISSIONS['repositories']['view']))) : ?>
            <div class="flex flex-direction-column row-gap-10 margin-top-15 margin-bottom-30">
                <div class="flex column-gap-10 justify-space-between">
                    <div class="flex flex-wrap align-item-center column-gap-10 row-gap-10">
                        <?php
                        if (RepoPermission::allowedAction('edit-source')) : ?>
                            <div class="slide-btn get-panel-btn mediumopacity" panel="repos/sources/list" title="Manage source repositories">
                                <img src="/assets/icons/internet.svg" />
                                <span>Source repositories</span>
                            </div>
                            <?php
                        endif;

                        if (RepoPermission::allowedAction('edit-groups')) : ?>
                            <div class="slide-btn get-panel-btn mediumopacity" panel="repos/groups/list" title="Manage repos groups">
                                <img src="/assets/icons/folder.svg" />
                                <span>Groups</span>
                            </div>
                            <?php
                        endif;

                        if (RepoPermission::allowedAction('update') or RepoPermission::allowedAction('env') or RepoPermission::allowedAction('rebuild')) : ?>
                            <div class="slide-btn get-panel-btn mediumopacity" panel="repos/scheduled-task" title="Schedule a recurring task on all repositories latest snapshots">
                                <img src="/assets/icons/calendar.svg" />
                                <span>Schedule a task</span>
                            </div>
                            <?php
                        endif; ?>

                        <div class="slide-btn get-panel-btn mediumopacity" panel="repos/settings" title="Manage repositories settings">
                            <img src="/assets/icons/cog.svg" />
                            <span>Settings</span>
                        </div>

                        <div id="hide-all-repo-groups" class="slide-btn mediumopacity" state="visible" title="Hide all repository groups">
                            <img src="/assets/icons/view.svg" />
                            <span>Collapse all</span>
                        </div>
                    </div>

                    <?php
                    if (RepoPermission::allowedAction('create')) : ?>
                        <div class="slide-btn slide-btn-green get-panel-btn" panel="repos/new" title="Create a new mirror or local repository">
                            <img src="/assets/icons/plus.svg" />
                            <span>Create a new repository</span>
                        </div>
                        <?php
                    endif ?>
                </div>

                <input id="repo-search-input" type="text" placeholder="Search repositories, snapshots, environments..." onkeyup="myrepo.search()" title="Search by repository name, distribution, section or release version" />
            </div>

            <div id="repos-list-container">
                <?php include_once(ROOT . '/views/includes/repos-list.inc.php'); ?>
            </div>
            <?php
        else : ?>
            <div class="empty-state">
                <p class="empty-state-title">Nothing to see here!</p>
                <p class="note">You don't have permission to view any repository. Contact your administrator to request access.</p>
            </div>
            <?php
        endif ?>
    </div>
</section>