<footer>
    <?php
        echo '<p>' . VERSION . '</p>';
    if (UPDATE_AVAILABLE == "yes") {
        echo '<p class="yellowtext">New release available</p>';
    }
    ?>
    <br>
    <a target="_blank" rel="noopener noreferrer" href="https://github.com/lbr38/repomanager" id="github"><img src="resources/images/GitHub-Mark-Light-64px.png" /></a>
</footer>

<script src="resources/js/functions.js"></script>
<script src="resources/js/update.js"></script>
<?php
if (__ACTUAL_URI__ == "/index.php" or __ACTUAL_URI__ == "/") {
    echo '<script src="resources/js/repo.js"></script>';
    echo '<script src="resources/js/group.js"></script>';
    echo '<script src="resources/js/source.js"></script>';
}
if (__ACTUAL_URI__ == "/planifications.php") {
    echo '<script src="resources/js/repo.js"></script>';
    echo '<script src="resources/js/group.js"></script>';
    echo '<script src="resources/js/plan.js"></script>';
    echo '<script src="resources/js/source.js"></script>';
}
if (__ACTUAL_URI__ == "/hosts.php" or __ACTUAL_URI__ == "/host.php") {
    echo '<script src="resources/js/host.js"></script>';
}
if (__ACTUAL_URI__ == "/browse.php") {
    echo '<script src="resources/js/explore.js"></script>';
}
if (__ACTUAL_URI__ == "/profiles.php") {
    echo '<script src="resources/js/profile.js"></script>';
}
if (__ACTUAL_URI__ == "/stats.php") {
    echo '<script src="resources/js/stats.js"></script>';
}
if (__ACTUAL_URI__ == "/configuration.php") {
    echo '<script src="resources/js/configuration.js"></script>';
}
if (__ACTUAL_URI__ == "/run.php") {
    echo '<script src="resources/js/run.js"></script>';
} ?>