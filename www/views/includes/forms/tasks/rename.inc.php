<h6>RENAME</h6>
<p class="note">The repository to be renamed.</p>

<div class="flex align-item-center">
    <p class="label-white">
        <?php
        if ($repoController->getPackageType() == 'rpm') {
            echo $repoController->getName() . ' ❯ ' . $repoController->getReleasever();
        }
        if ($repoController->getPackageType() == 'deb') {
            echo $repoController->getName() . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection();
        } ?>
    </p>
</div>
  
<h6 class="required">NEW REPOSITORY NAME</h6>
<p class="note">The new name of the repository.</p>
<input type="text" class="task-param" param-name="name" required />

<select class="task-param hide" param-name="arch" multiple>
    <?php
    foreach ($repoController->getArch() as $arch) {
        echo '<option value="' . $arch . '" selected>' . $arch . '</option>';
    } ?>
</select>

<input type="hidden" class="task-param" param-name="old-name" value="<?= $repoController->getName() ?>" />

<input type="hidden" class="task-param" param-name="gpg-sign" value="<?= $repoController->getSigned() ?>" />

<?php
// Define schedule form action (useful for the schedule form)
$scheduleForm['action'] = 'rename';
