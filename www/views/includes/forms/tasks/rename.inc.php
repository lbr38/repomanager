<h6>RENAME</h6>
<p class="note">The repository to be renamed.</p>

<div class="flex align-item-center">
    <p class="label-white">
        <?php
        if ($myrepo->getPackageType() == 'rpm') {
            echo $myrepo->getName() . ' ❯ ' . $myrepo->getReleasever();
        }
        if ($myrepo->getPackageType() == 'deb') {
            echo $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection();
        } ?>
    </p>
</div>
  
<h6 class="required">NEW REPOSITORY NAME</h6>
<p class="note">The new name of the repository.</p>
<input type="text" class="task-param" param-name="name" required />

<select class="task-param hide" param-name="arch" multiple>
    <?php
    foreach ($myrepo->getArch() as $arch) {
        echo '<option value="' . $arch . '" selected>' . $arch . '</option>';
    } ?>
</select>

<input type="hidden" class="task-param" param-name="gpg-sign" value="<?= $myrepo->getSigned() ?>" />

<?php
/**
 *  Define schedule form action and allowed type(s)
 */
$scheduleForm['action'] = 'rename';
$scheduleForm['type'] = ['unique'];
