<?php ob_start(); ?>
<h6 class="required">SELECT AN ENVIRONMENT</h6>

<select id="repo-install-select-env" class="margin-bottom-20">
    <option value=""></option>
    <?php
    foreach (ENVS as $env) {
        echo '<option value="' . $env['Name'] . '">' . $env['Name'] . '</option>';
    } ?>
</select>

<div id="repository-install-commands-container" class="hide">
    <?php
    // Display a warning message if the user selected both deb and rpm repositories
    if (in_array('deb', $packagesTypes) and in_array('rpm', $packagesTypes)) {
        echo '<p class="note margin-top-15 margin-bottom-15"><img src="/assets/icons/warning.svg" class="icon-np" /> You have selected both deb and rpm repositories. Make sure to install them separately.</p>';
    }

    echo $commands; ?>    
</div>
<br><br>

<?php
$content = ob_get_clean();
$slidePanelName = 'repos/install';
$slidePanelTitle = 'INSTALL REPOSITORIES';

include(ROOT . '/views/includes/slide-panel.inc.php');
