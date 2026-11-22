<?php
use \Controllers\User\Permission\Repo as RepoPermission;
use \Controllers\User\Permission\Mirror as MirrorPermission; ?>

<section class="section-main reloadable-container" container="repos/list">
    <div id="repositories-list">
        <?php
        // Print repositories
        if (IS_ADMIN or (!empty(USER_PERMISSIONS['repositories']['view']['groups']) or !empty(USER_PERMISSIONS['repositories']['view']['repos']) or !empty(USER_PERMISSIONS['repositories']['view']['mirrors']) or in_array('all', USER_PERMISSIONS['repositories']['view']))) : ?>
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

                        if (\Controllers\Task\Target::allowedAction('update') or \Controllers\Task\Target::allowedAction('env') or \Controllers\Task\Target::allowedAction('rebuild')) : ?>
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

                        <?php
                        // Collapse all groups button, only useful when there are at least 2 groups
                        if (count($groups) > 1) : ?>
                            <div id="hide-all-repo-groups" class="slide-btn mediumopacity" state="visible" title="Hide all repository groups">
                                <img src="/assets/icons/view.svg" />
                                <span>Collapse all</span>
                            </div>
                            <?php
                        endif ?>
                    </div>

                    <?php
                    /**
                     *  The repositories creation form also offers mirrors, the mirrors creation form is opened directly if the user can only create mirrors
                     *  or if only Arch Linux, Alpine Linux or OpenBSD repositories are enabled (they can only be mirrors)
                     */
                    $canCreateRepo = (RepoPermission::allowedAction('create') and (RPM_REPO == 'true' or DEB_REPO == 'true'));
                    $canCreateMirror = (MirrorPermission::allowedAction('create') and (RPM_REPO == 'true' or DEB_REPO == 'true' or ARCH_REPO == 'true' or ALPINE_REPO == 'true' or OPENBSD_REPO == 'true'));

                    if ($canCreateRepo or $canCreateMirror) : ?>
                        <div class="slide-btn slide-btn-green get-panel-btn" panel="<?= $canCreateRepo ? 'repos/new' : 'mirrors/new' ?>" title="Create a new curated repository, local repository or mirror">
                            <img src="/assets/icons/plus.svg" />
                            <span>Create a new repository</span>
                        </div>
                        <?php
                    endif ?>
                </div>

                <div class="search-input-container">
                    <input type="text" id="search-repo-input" autocomplete="off" spellcheck="false" placeholder="Search repositories, snapshots, environments... or env=prod" title="Search repositories by any field, or filter by repository attributes (name=, dist=, env=, tag=, ...)" />
                    <div class="search-input-actions">
                        <p id="search-repo-count" class="note hide"></p>
                        <img id="search-repo-clear" src="/assets/icons/close.svg" class="icon-small pointer lowopacity hide" title="Clear search" />
                        <img src="/assets/icons/info.svg" class="icon-small icon-np lowopacity search-repo-tooltip" />
                    </div>
                    <div id="search-repo-suggestions" class="search-suggestions hide"></div>
                </div>
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