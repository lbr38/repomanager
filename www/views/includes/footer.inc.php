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
<script src="/resources/js/login.js?<?= VERSION ?>"></script>
<script src="/resources/js/notification.js?<?= VERSION ?>"></script>

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
 *  Additional JS files
 */
if (__ACTUAL_URI__[1] == '') {
    $jsFiles = ['repo', 'task', 'group', 'source', 'events/repo/source/distribution', 'events/repo/source/releasever', 'events/repo/source/source', 'events/repo/edit'];
}
if (__ACTUAL_URI__[1] == 'hosts') {
    $jsFiles = ['host', 'events/host/layout', 'events/host/actions', 'events/profile/actions'];
}
if (__ACTUAL_URI__[1] == 'host') {
    $jsFiles = ['host', 'events/host/layout', 'events/host/actions'];
}
if (__ACTUAL_URI__[1] == 'browse') {
    $jsFiles = ['functions/browse', 'events/browse/repository'];
}
if (__ACTUAL_URI__[1] == 'stats') {
    $jsFiles = ['stats'];
}
if (__ACTUAL_URI__[1] == 'settings') {
    $jsFiles = ['functions/environment', 'events/environment/actions', 'settings'];
}
if (__ACTUAL_URI__[1] == 'run') {
    $jsFiles = ['functions/task', 'events/task/actions'];
}
if (__ACTUAL_URI__[1] == 'history') {
    $jsFiles = ['events/history/actions'];
}
if (__ACTUAL_URI__[1] == 'cves') {
    $jsFiles = ['cve'];
}
if (!empty($jsFiles)) {
    foreach ($jsFiles as $jsFile) {
        if (is_file(ROOT . '/public/resources/js/' . $jsFile . '.js')) {
            echo '<script src="/resources/js/' . $jsFile . '.js?' . VERSION . '"></script>';
        }
    }
} ?>
