<section class="section-left reloadable-container" container="repos/list">
    <div class="reposList">
        <div id="title-button-div">
            <h3>REPOS</h3>

            <div class="flex justify-space-between">
                <?php
                if (IS_ADMIN) : ?>
                    <div class="slide-btn slide-panel-btn" slide-panel="repos/groups" title="Manage repos groups">
                        <img src="/assets/icons/folder.svg" />
                        <span>Manage groups</span>
                    </div>

                    <div class="slide-btn slide-panel-btn" slide-panel="repos/sources" title="Manage source repositories">
                        <img src="/assets/icons/internet.svg" />
                        <span>Manage source repos</span>
                    </div>

                    <div class="slide-btn slide-panel-btn" slide-panel="repos/new" title="Create a new mirror or local repository">
                        <img src="/assets/icons/plus.svg" />
                        <span>Create a new repository</span>
                    </div>
                    <?php
                endif ?>
            </div>
        </div>

        <input id="repo-search-input" class="margin-bottom-10" type="text" placeholder="Search" onkeyup="searchRepo()" title="Search by repository name, distribution, section or release version" />

        <div class="flex justify-end margin-bottom-10">
            <span id="hideAllReposGroups" class="lowopacity pointer" state="visible">Hide / show all<img src="/assets/icons/up.svg" class="icon" /></span>
        </div>

        <div id="repos-list-container">
            <?php include_once(ROOT . '/views/includes/repos-list.inc.php'); ?>
        </div>
    </div>
</section>