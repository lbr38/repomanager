<tr>
    <td colspan="100%">
        Duplicate
        <?php
        if ($myrepo->getPackageType() == 'rpm') {
            echo ' <span class="label-white">' . $myrepo->getName() . '</span>';
        }
        if ($myrepo->getPackageType() == 'deb') {
            echo ' <span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>';
        } ?>
        ⟶<span class="label-black"><?=$myrepo->getDateFormatted()?></span>
    </td>
</tr>

<tr>
    <td>New repository name</td>
    <td>
        <input type="text" class="task-param" param-name="name" required />
    </td>
</tr>

<tr>
    <td>Point an environment</td>
    <td>
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
    </td>
</tr>

<tr id="duplicate-repo-target-description-tr">
    <td>
        <span>Description</span> <span class="lowopacity-cst">(optional)</span>
    </td>
    <td>
        <input type="text" class="task-param" param-name="description" />
    </td>
</tr>

<?php

/**
 *  Affichage de la liste des groupes
 */
$group = new \Controllers\Group('repo');
$groupList = $group->listAll();

if (!empty($groupList)) : ?>
    <tr>
        <td>
            <span>Add to group</span> <span class="lowopacity-cst">(optional)</span>
        </td>
        <td>
            <select class="task-param" param-name="group">
                <option value="">Select group...</option>
                <?php
                foreach ($groupList as $group) {
                    echo '<option value="' . $group['Name'] . '">' . $group['Name'] . '</option>';
                } ?>
            </select>
        </td>
    </tr>
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