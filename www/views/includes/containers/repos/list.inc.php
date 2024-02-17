<section class="section-left reloadable-container" container="repos/list">
    <div class="reposList">
        <div id="title-button-div">
            <h3>REPOS</h3>

            <?php
            if (IS_ADMIN) : ?>
                <div id="title-button-container">
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
                        <span>Create a new repo</span>
                    </div>
                </div>
                <?php
            endif ?>
        </div>

        <input id="repo-search-input" type="text" placeholder="Search" onkeyup="searchRepo()" />

        <div class="flex justify-end margin-bottom-5">
            <span id="hideAllReposGroups" class="lowopacity pointer" state="visible">Hide / show all<img src="/assets/icons/up.svg" class="icon" /></span>
        </div>

        <div id="repos-list-container">
            <?php
            /**
             *  Generate cache file if it does not exist
             */
            if (!file_exists(WWW_CACHE . '/repomanager-repos-list-' . $_SESSION['role'] . '.html')) {
                \Controllers\App\Cache::generate($_SESSION['role']);
            }

            /**
             *  Print cache file
             */
            include(WWW_CACHE . '/repomanager-repos-list-' . $_SESSION['role'] . '.html'); ?>
        </div>
    </div>
</section>