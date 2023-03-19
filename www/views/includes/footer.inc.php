<footer>
    <div class="text-center">
        <p>Repomanager - release version <?= VERSION ?></p>
        <br>
        <br>
        <a target="_blank" rel="noopener noreferrer" href="https://github.com/lbr38/repomanager" id="github"><img src="resources/images/GitHub-Mark-Light-64px.png" /></a>
    </div>
</footer>

<script src="resources/js/functions.js"></script>
<script src="resources/js/general.js"></script>
<script src="resources/js/login.js"></script>
<script src="resources/js/update.js"></script>
<script src="resources/js/notification.js"></script>
<?php
if (__ACTUAL_URI__ == "" or __ACTUAL_URI__ == "/") {
    echo '<script src="resources/js/repo.js"></script>';
    echo '<script src="resources/js/group.js"></script>';
    echo '<script src="resources/js/source.js"></script>';
}
if (__ACTUAL_URI__ == "/plans") {
    echo '<script src="resources/js/repo.js"></script>';
    echo '<script src="resources/js/group.js"></script>';
    echo '<script src="resources/js/plan.js"></script>';
    echo '<script src="resources/js/source.js"></script>';
}
if (__ACTUAL_URI__ == "/hosts" or __ACTUAL_URI__ == "/host") {
    echo '<script src="resources/js/host.js"></script>';
}
if (__ACTUAL_URI__ == "/browse") {
    echo '<script src="resources/js/explore.js"></script>';
}
if (__ACTUAL_URI__ == "/profiles") {
    echo '<script src="resources/js/profile.js"></script>';
}
if (__ACTUAL_URI__ == "/stats") {
    echo '<script src="resources/js/stats.js"></script>';
}
if (__ACTUAL_URI__ == "/settings") {
    echo '<script src="resources/js/settings.js"></script>';
    echo '<script src="resources/js/environment.js"></script>';
}
if (__ACTUAL_URI__ == "/run") {
    echo '<script src="resources/js/run.js"></script>';
} ?>