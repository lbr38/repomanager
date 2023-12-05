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
    <td class="td-30">New repo name</td>
    <td>
        <input type="text" class="operation_param" param-name="targetName" required />
        <?php /*if ($myrepo->getPackageType() == 'deb') : ?>
            <input type="hidden" class="operation_param" param-name="dist" value="<?= $myrepo->getDist() ?>" required />
            <input type="hidden" class="operation_param" param-name="section" value="<?= $myrepo->getSection() ?>" required />
        <?php endif */?>
    </td>
</tr>

<tr>
    <td class="td-30">Point an environment</td>
    <td>
        <select id="duplicate-repo-target-env-select-<?=$myrepo->getSnapId()?>" class="operation_param" param-name="targetEnv">
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
    <td class="td-30">
        <span>Description</span> <span class="lowopacity-cst">(optionnal)</span>
    </td>
    <td>
        <input type="text" class="operation_param" param-name="targetDescription" />
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
        <td class="td-30">
            <span>Add to group</span> <span class="lowopacity-cst">(optionnal)</span>
        </td>
        <td>
            <select class="operation_param" param-name="targetGroup">
                <option value="">Select group...</option>
                <?php
                foreach ($groupList as $group) {
                    echo '<option value="' . $group['Name'] . '">' . $group['Name'] . '</option>';
                } ?>
            </select>
        </td>
    </tr>
<?php endif ?>

<script>
$(document).ready(function(){
    /**
     *  Affiche la description uniquement si un environnement est spécifié
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