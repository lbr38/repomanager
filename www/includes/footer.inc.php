<footer>
    <?php
        echo '<p>'.VERSION.'</p>';
        if (UPDATE_AVAILABLE == "yes") {
            echo '<p>Une nouvelle version est disponible</p>';
        }
    ?>
    <a target="_blank" rel="noopener noreferrer" href="https://github.com/lbr38/repomanager" id="github"><img src="ressources/images/GitHub-Mark-Light-64px.png" /></a>
</footer>

<script src="js/functions.js"></script>
<?php
if (__ACTUAL_URI__ == "/index.php" OR __ACTUAL_URI__ == "/") {
    echo '<script src="js/repo.js"></script>';
    echo '<script src="js/group.js"></script>';
}
if (__ACTUAL_URI__ == "/operation.php") {
    echo '<script src="js/repo.js"></script>';
    echo '<script src="js/group.js"></script>';
}
if (__ACTUAL_URI__ == "/planifications.php") {
    echo '<script src="js/repo.js"></script>';
    echo '<script src="js/group.js"></script>';
    echo '<script src="js/plan.js"></script>';
}
if (__ACTUAL_URI__ == "/hosts.php") {
    echo '<script src="js/host.js"></script>';
}
if (__ACTUAL_URI__ == "/host.php") {
    echo '<script src="js/host.js"></script>';
} 
if (__ACTUAL_URI__ == "/explore.php") {
    echo '<script src="js/explore.js"></script>';
} 
if (__ACTUAL_URI__ == "/profiles.php") {
    echo '<script src="js/profile.js"></script>';
}?>