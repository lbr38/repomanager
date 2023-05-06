<section class="section-left reloadable-container" container="operations/log">
    <h3>LOG</h3>

    <div id="log-container">
        <div id="scrollButtons-container">
            <div id="scrollButtons">

                <?php
                if (!empty($_COOKIE['display-log']) && $_COOKIE['display-log'] == 'true') : ?>
                    <div id="display-log-btn" display="false" class="button-top-down-details pointer" title="Show/hide details">
                        <img src="assets/icons/search.svg" />
                    </div>
                    <?php
                else : ?>
                    <div id="display-log-btn" display="true" class="button-top-down-details pointer" title="Show/hide details">
                        <img src="assets/icons/search.svg" />
                    </div>
                    <?php
                endif; ?>

                <br><br>
                <div>
                    <a href="#top" class="button-top-down" title="Go to the top"><img src="assets/icons/up.svg" /></a>
                </div>
                <div>
                    <a href="#bottom" class="button-top-down" title="Go to the bottom"><img src="assets/icons/down.svg" /></a>
                </div>
            </div>
        </div>

        <div id="log-refresh-container">
            <div id="log">

                <?php
                if (!empty($_COOKIE['display-log']) && $_COOKIE['display-log'] == 'true') : ?>
                    <style>
                        .getPackagesDiv { display: block }
                        .signRepoDiv { display: block }
                        .createRepoDiv { display: block }
                    </style>
                    <?php
                else : ?>
                    <style>
                        .getPackagesDiv { display: none }
                        .signRepoDiv { display: none }
                        .createRepoDiv { display: none }
                    </style>
                    <?php
                endif; ?>

                <?= $output; ?>
            </div>
        </div>
    </div>
</section>