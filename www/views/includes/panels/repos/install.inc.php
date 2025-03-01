<?php ob_start(); ?>
<h6 class="required">SELECT AN ENVIRONMENT</h6>

<select id="repo-install-select-env">
    <option value=""></option>
    <?php
    foreach (ENVS as $env) {
        echo '<option value="' . $env['Name'] . '">' . $env['Name'] . '</option>';
    } ?>
</select>

<div id="repository-install-commands-container" class="hide">
    <?php
    /**
     *  Display a warning message if the user selected both deb and rpm repositories
     */
    if (in_array('deb', $packagesTypes) and in_array('rpm', $packagesTypes)) {
        echo '<p class="note margin-top-15"><img src="/assets/icons/warning.svg" class="icon-np" /> You have selected both deb and rpm repositories. Make sure to install them separately.</p>';
    }

    /**
     *  Print the GPG key installation commands (only for deb packages)
     */
    if (in_array('deb', $packagesTypes)) : ?>
        <h6>INSTALL THE GPG KEY</h6>
        <p class="note">For Debian based systems. Copy and paste the following commands in the shell of the target host.</p>
        <pre class="codeblock margin-top-10 margin-bottom-10 copy">curl -sS <?= WWW_REPOS_DIR_URL ?>/gpgkeys/<?= WWW_HOSTNAME ?>.pub | gpg --dearmor > /etc/apt/trusted.gpg.d/<?= WWW_HOSTNAME ?>.gpg</pre>
        <?php
    endif ?>

    <h6>INSTALL THE REPOSITORIES</h6>
    <p class="note">Copy and paste the following commands in the shell of the target host.</p>

    <?= $commands ?>    
</div>
<br><br>

<?php
$content = ob_get_clean();
$slidePanelName = 'repos/install';
$slidePanelTitle = 'INSTALL REPOSITORIES';

include(ROOT . '/views/includes/slide-panel.inc.php');
