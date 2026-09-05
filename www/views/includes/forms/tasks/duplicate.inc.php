<?php
use \Controllers\Group\Repo as RepoGroup; ?>

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

    <p>⸺<span class="label-black"><?= $repoController->getDateFormatted() ?></span></p>
</div>
  
<h6 class="required">NEW REPOSITORY NAME</h6>
<p class="note">The name of the new repository.</p>
<input type="text" class="task-param" param-name="name" required />

<h6>POINT AN ENVIRONMENT</h6>
<p class="note">Select one or multiple environments to point to the new repository snapshot.</p>
<select class="task-param" param-name="env" multiple>
    <option value=""></option>
    <?php
    foreach (ENVS as $env) {
        if ($env['Name'] == DEFAULT_ENV) {
            echo '<option value="' . $env['Name'] . '" selected>' . $env['Name'] . '</option>';
        } else {
            echo '<option value="' . $env['Name'] . '">' . $env['Name'] . '</option>';
        }
    } ?>
</select>

<h6>DESCRIPTION</h6>
<p class="note">Optional. A description for this repository.</p>
<input type="text" class="task-param" param-name="description" />

<h6>TAGS</h6>
<p class="note">Optional. Add tags to the repository. Tags can be used to filter repositories.</p>
<select class="task-param" param-name="tags" multiple>
    <?php
    foreach ($tags as $tag) {
        echo '<option value="' . htmlspecialchars($tag, ENT_QUOTES) . '">' . htmlspecialchars($tag) . '</option>';
    } ?>
</select>

<select class="task-param hide" param-name="arch" multiple>
    <?php
    foreach ($repoController->getArch() as $arch) {
        echo '<option value="' . $arch . '" selected>' . $arch . '</option>';
    } ?>
</select>

<input type="hidden" class="task-param" param-name="gpg-sign" value="<?= $repoController->getSigned() ?>" />

<?php
/**
 *
 */
// Print group list
$groupController = new RepoGroup();
$groupList = $groupController->listAll();

if (!empty($groupList)) : ?>
    <h6>ADD TO GROUP</h6>
    <select class="task-param" param-name="group">
        <option value="">Select group...</option>
        <?php
        foreach ($groupList as $group) {
            echo '<option value="' . $group['Name'] . '">' . $group['Name'] . '</option>';
        } ?>
    </select>
    <?php
endif;

// Define schedule form action (useful for the schedule form)
$scheduleForm['action'] = 'duplicate'; ?>

<script>
    $(document).ready(function(){
        myselect2.convert('select.task-param[param-name="env"]', 'Select environment(s)', true);
        myselect2.convert('select.task-param[param-name="tags"]', 'Specify tags', true);
    });
</script>