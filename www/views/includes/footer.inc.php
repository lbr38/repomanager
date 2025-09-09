<footer>
    <div>
        <h5>HELP</h5>
        <a target="_blank" rel="noopener noreferrer" href="https://github.com/lbr38/repomanager/wiki">
            <span class="lowopacity">Documentation <img src="/assets/icons/external-link.svg" class="icon-small" /></span>
        </a>
        
        <br><br>
        
        <a href="mailto:repomanager@protonmail.com">
             <span class="lowopacity">Contact</span>
        </a>
    </div>

    <div>
        <h5>GITHUB</h5>
        <span class="lowopacity">
            <a target="_blank" rel="noopener noreferrer" href="https://github.com/lbr38/repomanager" id="github"><img src="/assets/images/github.png" /></a>
        </span>
    </div>    

    <div class="text-center margin-auto">
        <p class="lowopacity-cst">Repomanager - release version <?= VERSION ?></p>
        <br>
        <p class="lowopacity-cst">Repomanager is a free and open source software, licensed under the <a target="_blank" rel="noopener noreferrer" href="https://www.gnu.org/licenses/gpl-3.0.en.html">GPLv3</a> license.</p>
    </div>
</footer>

<script src="/resources/js/functions.js?<?= VERSION ?>"></script>
<script src="/resources/js/general.js?<?= VERSION ?>"></script>
<script src="/resources/js/user.js?<?= VERSION ?>"></script>
<script src="/resources/js/notification.js?<?= VERSION ?>"></script>
<script src="/resources/js/events/checkbox.js?<?= VERSION ?>"></script>

<!-- Import some classes -->
<script src="/resources/js/classes/Layout.js?<?= VERSION ?>"></script>
<script src="/resources/js/classes/Container.js?<?= VERSION ?>"></script>
<script src="/resources/js/classes/Table.js?<?= VERSION ?>"></script>
<script src="/resources/js/classes/Panel.js?<?= VERSION ?>"></script>
<script src="/resources/js/classes/Cookie.js?<?= VERSION ?>"></script>
<script src="/resources/js/classes/Alert.js?<?= VERSION ?>"></script>
<script src="/resources/js/classes/ConfirmBox.js?<?= VERSION ?>"></script>
<script src="/resources/js/classes/Modal.js?<?= VERSION ?>"></script>
<script src="/resources/js/classes/Tooltip.js?<?= VERSION ?>"></script>
<script src="/resources/js/classes/Select2.js?<?= VERSION ?>"></script>
<script src="/resources/js/classes/SessionStorage.js?<?= VERSION ?>"></script>

<script>
    const mylayout = new Layout();
    const mycontainer = new Container();
    const mytable = new Table();
    const mypanel = new Panel();
    const mycookie = new Cookie();
    const myalert = new Alert();
    const myconfirmbox = new ConfirmBox();
    const mymodal = new Modal();
    const mytooltip = new Tooltip();
    const myselect2 = new Select2();
    const mysessionstorage = new SessionStorage();
</script>

<script>
<?php
/**
 *  Store each environment and its colors in browser localStorage for later use
 */
if (!empty(ENVS)) {
    foreach (ENVS as $env) {
        $name = $env['Name'];
        $background = $env['Color'];
        $color = \Controllers\Common::getContrastingTextColor($background); ?>

        localStorage.setItem("env/<?= $name ?>", "{\"background\":\"<?= $background ?>\",\"color\":\"<?= $color ?>\"}");
        <?php
    }
} ?>
</script>

<?php
/**
 *  Additional JS classes and files to load, depending on the current page
 */
if (__ACTUAL_URI__[1] == '') {
    $jsClasses = [
        'Environment',
    ];

    $jsFiles = [
        'repo',
        'task',
        'group',
        'source',
        'events/repo/source/distribution',
        'events/repo/source/releasever',
        'events/repo/source/source',
        'events/repo/env',
        'events/repo/edit',
        'events/repo/install',
        'events/task/stop'
    ];
}
if (__ACTUAL_URI__[1] == 'hosts' or __ACTUAL_URI__[1] == 'host') {
    $jsClasses = [
        'Host',
    ];

    $jsFiles =[
        'host',
        'events/host/layout',
        'events/host/actions',
        'events/profile/actions',
        'events/task/stop'
    ];
}
if (__ACTUAL_URI__[1] == 'browse') {
    $jsFiles = [
        'functions/browse',
        'events/browse/repository',
        'events/task/stop'
    ];
}
if (__ACTUAL_URI__[1] == 'stats') {
    $jsFiles = [
        'stats',
        'events/task/stop'
    ];
}
if (__ACTUAL_URI__[1] == 'settings') {
    $jsFiles = [
        'functions/environment',
        'events/settings/environment',
        'events/settings/user',
        'events/settings/debug-mode',
        'events/task/stop',
        'settings'
    ];
}
if (__ACTUAL_URI__[1] == 'run') {
    $jsFiles = [
        'functions/task',
        'events/task/actions',
        'events/task/stop'
    ];
}
if (__ACTUAL_URI__[1] == 'history') {
    $jsFiles = [
        'events/history/actions',
        'events/task/stop'
    ];
}
if (__ACTUAL_URI__[1] == 'cves') {
    $jsFiles = [
        'cve',
        'events/task/stop'
    ];
}

// Load additional JS classes
if (!empty($jsClasses)) {
    foreach ($jsClasses as $jsClass) {
        if (is_file(ROOT . '/public/resources/js/classes/' . $jsClass . '.js')) {
            echo '<script src="/resources/js/classes/' . $jsClass . '.js?' . VERSION . '"></script>';
        }
    }
} ?>

<script>
    <?php
    if (!empty($jsClasses)) {
        foreach ($jsClasses as $jsClass) {
            echo 'const my' . strtolower($jsClass) . ' = new ' . $jsClass . '();';
        }
    } ?>
</script>

<?php
// Load additional JS files
if (!empty($jsFiles)) {
    foreach ($jsFiles as $jsFile) {
        if (is_file(ROOT . '/public/resources/js/' . $jsFile . '.js')) {
            echo '<script src="/resources/js/' . $jsFile . '.js?' . VERSION . '"></script>';
        }
    }
} ?>
