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
<p class="note">The name of the new repository. It can be the same name with another <?= $repoController->getPackageType() == 'rpm' ? 'release version' : 'distribution' ?>.</p>
<input type="text" class="task-param" param-name="name" value="<?= htmlspecialchars($repoController->getName(), ENT_QUOTES) ?>" required />

<?php
/**
 *  The release version (rpm) or the distribution (deb) of the new repository can be changed, the component (deb) cannot
 *  Only one value can be selected. The suggestions are the ones of the new repository form: values of the source repository
 *  of a curated repository, then the possible values. None for a local repository (the value is typed)
 */
if ($repoController->getPackageType() == 'rpm') {
    $versionParam = 'releasever';
    $versionCurrent = $repoController->getReleasever();
}
if ($repoController->getPackageType() == 'deb') {
    $versionParam = 'dist';
    $versionCurrent = $repoController->getDist();
}

$versionSuggestions = [];
$versionCurrentListed = false;

if ($repoController->getType() == 'curated') {
    try {
        if ($repoController->getPackageType() == 'rpm') {
            $versionSuggestions = (new \Controllers\Repo\Source\Rpm())->getPredefinedReleasever($repoController->getSource());
        }
        if ($repoController->getPackageType() == 'deb') {
            $versionSuggestions = (new \Controllers\Repo\Source\Deb())->getPredefinedDistributions($repoController->getSource());
        }
    } catch (\Exception $e) {
        // The source repository may have been deleted since, the value can still be typed
    }
}

foreach ($versionSuggestions as $suggestionGroup) {
    if (in_array($versionCurrent, array_column($suggestionGroup['children'], 'id'))) {
        $versionCurrentListed = true;
    }
}

if ($repoController->getPackageType() == 'rpm') : ?>
    <h6 class="required">RELEASE VERSION</h6>
    <p class="note">Select or enter the release version of the new repository, e.g. to duplicate a <b>9</b> repository into a <b>9.9</b> one.</p>
    <?php
endif;

if ($repoController->getPackageType() == 'deb') : ?>
    <h6 class="required">DISTRIBUTION</h6>
    <p class="note">Select or enter the distribution of the new repository. The component <b><?= htmlspecialchars($repoController->getSection()) ?></b> stays the same.</p>
    <?php
endif ?>

<?php
/**
 *  Same select2 as the new repository form (value typed in the field, no dropdown search field), limited to one value:
 *  the select is multiple only for its look, the value sent is the one of the hidden task parameter below
 */ ?>
<select class="duplicate-version-select" multiple>
    <?php
    // Current value of the snapshot, selected by default
    if (!$versionCurrentListed) {
        echo '<option value="' . htmlspecialchars($versionCurrent, ENT_QUOTES) . '" selected>' . htmlspecialchars($versionCurrent) . '</option>';
    }

    foreach ($versionSuggestions as $suggestionGroup) {
        echo '<optgroup label="' . htmlspecialchars($suggestionGroup['text'], ENT_QUOTES) . '">';

        foreach ($suggestionGroup['children'] as $suggestion) {
            echo '<option value="' . htmlspecialchars($suggestion['id'], ENT_QUOTES) . '" ' . ($suggestion['id'] == $versionCurrent ? 'selected' : '') . '>' . htmlspecialchars(trim($suggestion['text'])) . '</option>';
        }

        echo '</optgroup>';
    } ?>
</select>
<input type="hidden" class="task-param" param-name="<?= $versionParam ?>" value="<?= htmlspecialchars($versionCurrent, ENT_QUOTES) ?>" />
<?php

// A curated repository is updated from its source repository, with its own release version or distribution
if ($repoController->getType() == 'curated') : ?>
    <div class="note-info">
        <img src="/assets/icons/info.svg" />
        <p>The new repository will be updated from the same source repository, with its own <?= $repoController->getPackageType() == 'rpm' ? 'release version' : 'distribution' ?>.</p>
    </div>
    <?php
endif ?>

<h6>POINT AN ENVIRONMENT</h6>
<p class="note">Select one or multiple environments to point to the new repository snapshot.</p>
<select class="task-param" param-name="env" multiple>
    <option value=""></option>
    <?php
    // Protected environments cannot be pointed, they are not listed
    foreach (\Controllers\Environment::getPointable() as $env) {
        if ($env['Name'] == DEFAULT_ENV) {
            echo '<option value="' . $env['Name'] . '" selected>' . $env['Name'] . '</option>';
        } else {
            echo '<option value="' . $env['Name'] . '">' . $env['Name'] . '</option>';
        }
    } ?>
</select>
<?= \Controllers\Environment::protectedNote() ?>

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

        // Release version or distribution: only one value, a selected or typed value replaces the previous one
        const versionForm = $('.task-form-params[snap-id="<?= $repoController->getSnapId() ?>"]');
        const versionSelect = versionForm.find('select.duplicate-version-select');

        versionSelect.select2(Object.assign(myselect2.options('<?= $versionParam == 'releasever' ? 'Select or enter release version' : 'Select or enter distribution' ?>', true), { closeOnSelect: true }));

        versionSelect.on('select2:select', function (e) {
            $(this).val([e.params.data.id]).trigger('change');
        });

        versionSelect.on('change', function () {
            versionForm.find('.task-param[param-name="<?= $versionParam ?>"]').val(($(this).val() || [])[0] || '');
        });
    });
</script>