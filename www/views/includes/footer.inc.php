<footer>
    <div>
        <h5>HELP</h5>
        <a target="_blank" rel="noopener noreferrer" href="https://github.com/lbr38/repomanager/wiki">
            <span class="lowopacity">Documentation<img src="/assets/icons/external-link.svg" class="icon" /></span>
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
<?php
if (__ACTUAL_URI__[1] == "") {
    echo '<script src="/resources/js/repo.js?' . VERSION . '"></script>';
    echo '<script src="/resources/js/task.js?' . VERSION . '"></script>';
    echo '<script src="/resources/js/group.js?' . VERSION . '"></script>';
    echo '<script src="/resources/js/source.js?' . VERSION . '"></script>';
}
if (__ACTUAL_URI__[1] == "hosts" or __ACTUAL_URI__[1] == "host") {
    echo '<script src="/resources/js/host.js?' . VERSION . '"></script>';
}
if (__ACTUAL_URI__[1] == "browse") {
    echo '<script src="/resources/js/browse.js?' . VERSION . '"></script>';
}
if (__ACTUAL_URI__[1] == "profiles") {
    echo '<script src="/resources/js/profile.js?' . VERSION . '"></script>';
}
if (__ACTUAL_URI__[1] == "stats") {
    echo '<script src="/resources/js/stats.js?' . VERSION . '"></script>';
}
if (__ACTUAL_URI__[1] == "settings") {
    echo '<script src="/resources/js/settings.js?' . VERSION . '"></script>';
    echo '<script src="/resources/js/environment.js?' . VERSION . '"></script>';
}
if (__ACTUAL_URI__[1] == "run") {
    echo '<script src="/resources/js/run.js?' . VERSION . '"></script>';
}
if (__ACTUAL_URI__[1] == "cves") {
    echo '<script src="/resources/js/cve.js?' . VERSION . '"></script>';
} ?>