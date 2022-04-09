<footer>
    <?php
        echo '<p>'.VERSION.'</p>';
        if (UPDATE_AVAILABLE == "yes") {
            echo '<p class="yellowtext">Une nouvelle version est disponible</p>';
        }
    ?>
    <br>
    <a target="_blank" rel="noopener noreferrer" href="https://github.com/lbr38/repomanager" id="github"><img src="ressources/images/GitHub-Mark-Light-64px.png" /></a>
</footer>

<script src="ressources/js/functions.js"></script>
<?php
if (__ACTUAL_URI__ == "/index.php" OR __ACTUAL_URI__ == "/") {
    echo '<script src="ressources/js/repo.js"></script>';
    echo '<script src="ressources/js/group.js"></script>';
    echo '<script src="ressources/js/source.js"></script>';
}
if (__ACTUAL_URI__ == "/planifications.php") {
    echo '<script src="ressources/js/repo.js"></script>';
    echo '<script src="ressources/js/group.js"></script>';
    echo '<script src="ressources/js/plan.js"></script>';
    echo '<script src="ressources/js/source.js"></script>';
}
if (__ACTUAL_URI__ == "/hosts.php" OR __ACTUAL_URI__ == "/host.php") {
    echo '<script src="ressources/js/host.js"></script>';
}
if (__ACTUAL_URI__ == "/explore.php") {
    echo '<script src="ressources/js/explore.js"></script>';
} 
if (__ACTUAL_URI__ == "/profiles.php") {
    echo '<script src="ressources/js/profile.js"></script>';
}
if (__ACTUAL_URI__ == "/stats.php") {
    echo '<script src="ressources/js/stats.js"></script>';
}?>