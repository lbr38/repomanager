<footer>
    <div class="flex flex-direction-column row-gap-10">
        <div class="flex align-item-center column-gap-5 max-width-fit mediumopacity">
            <img src="/assets/icons/file.svg" class="icon-np" />
            <a target="_blank" rel="noopener noreferrer" href="https://github.com/lbr38/repomanager/wiki">
                <p>Documentation</p>
            </a>
        </div>

        <div class="flex align-item-center column-gap-5 max-width-fit mediumopacity">
            <img src="/assets/icons/chatbubble.svg" class="icon-np" />
            <a target="_blank" rel="noopener noreferrer" href="https://discord.gg/34yeNsMmkQ">
                <p>Discord</p>
            </a>
        </div>

        <div class="flex align-item-center column-gap-5 max-width-fit mediumopacity">
            <img src="/assets/icons/at-circle.svg" class="icon-np" />
            <a href="mailto:repomanager@protonmail.com">
                <p>Contact</p>
            </a>
        </div>

        <div class="flex align-item-center column-gap-5 max-width-fit mediumopacity">
            <img src="/assets/icons/github.svg" class="icon-np" />
            <a target="_blank" rel="noopener noreferrer" href="https://github.com/lbr38/repomanager">
                <p>GitHub</p>
            </a>
        </div>
    </div> 

    <div class="flex flex-direction-column align-item-center row-gap-10 mediumopacity-cst">
        <img src="/assets/official-logo/repomanager-white.svg" class="icon-np" />
        <p>Repomanager - release version <?= VERSION ?></p>
        <p>Repomanager is a free and open source software, licensed under the <a target="_blank" rel="noopener noreferrer" href="https://www.gnu.org/licenses/gpl-3.0.en.html">GPLv3</a> license.</p>
    </div>
</footer>

<script src="/resources/js/functions.js?<?= VERSION ?>"></script>
<script src="/resources/js/general.js?<?= VERSION ?>"></script>
<script src="/resources/js/user.js?<?= VERSION ?>"></script>
<script src="/resources/js/notification.js?<?= VERSION ?>"></script>
<script src="/resources/js/events/checkbox.js?<?= VERSION ?>"></script>
<script src="/resources/js/events/tooltip.js?<?= VERSION ?>"></script>

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
<script src="/resources/js/classes/AsyncChart.js?<?= VERSION ?>"></script>
<script src="/resources/js/classes/System.js?<?= VERSION ?>"></script>

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
    const mysystem = new System();
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

if (__ACTUAL_URI__[1] == 'status') {
    $jsFiles = [
        'events/status/service',
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
