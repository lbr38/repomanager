<h6>DUPLICATE SNAPSHOT</h6>

<?php
if ($myrepo->getPackageType() == 'rpm') {
    echo ' <span class="label-white">' . $myrepo->getName() . '</span>';
}
if ($myrepo->getPackageType() == 'deb') {
    echo ' <span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>';
} ?>
⟶<span class="label-black"><?=$myrepo->getDateFormatted()?></span>
  
<h6 class="required">NEW REPOSITORY NAME</h6>
<input type="text" class="task-param" param-name="name" required />

<h6>POINT AN ENVIRONMENT</h6>
<select id="duplicate-repo-target-env-select-<?=$myrepo->getSnapId()?>" class="task-param" param-name="env">
    <option value=""></option>
    <?php
    foreach (ENVS as $env) {
        if ($env == DEFAULT_ENV) {
            echo '<option value="' . $env . '" selected>' . $env . '</option>';
        } else {
            echo '<option value="' . $env . '">' . $env . '</option>';
        }
    } ?>
</select>

<div id="duplicate-repo-target-description-tr">
    <h6>DESCRIPTION</h6>
    <input type="text" class="task-param" param-name="description" />

<?php
/**
 *  Print group list
 */
$group = new \Controllers\Group('repo');
$groupList = $group->listAll();

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

/**
 *  Define schedule form action and allowed type(s)
 */
$scheduleForm['action'] = 'duplicate';
$scheduleForm['type'] = array('unique'); ?>

<script>
$(document).ready(function(){
    /**
     *  Print description field only if an environment is specified
     */
    $(document).on('change','#duplicate-repo-target-env-select-<?=$myrepo->getSnapId()?>',function(){
        if ($('#duplicate-repo-target-env-select-<?=$myrepo->getSnapId()?>').val() == "") {
            $('#duplicate-repo-target-description-tr').hide();
        } else {
            $('#duplicate-repo-target-description-tr').show();
        }
    }).trigger('change');
});
</script>