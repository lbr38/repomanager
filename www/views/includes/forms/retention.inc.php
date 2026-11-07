<?php
/**
 *  Snapshots retention fields, shared by the repositories and mirrors create and edit forms
 *  $retention: current retention value (null: global setting, -1: keep all)
 *  $retentionParamClass: class of the inputs collected by the form
 *  The retention input is hidden when keeping all snapshots (see events/repo/edit.js)
 */
$retentionKeepAll = ($retention !== null and (int) $retention == \Controllers\Repo\Snapshot\Retention::KEEP_ALL); ?>

<div class="retention-params">
    <h6>KEEP ALL SNAPSHOTS</h6>
    <p class="note">Never delete unused snapshots (not pointed by any environment). Disk usage will grow with each update.</p>
    <label class="onoff-switch-label">
        <input type="checkbox" param-name="retention-keep-all" class="onoff-switch-input <?= $retentionParamClass ?>" value="true" <?= $retentionKeepAll ? 'checked' : '' ?> />
        <span class="onoff-switch-slider"></span>
    </label>

    <div class="retention-input-container <?= $retentionKeepAll ? 'hide' : '' ?>">
        <h6>SNAPSHOTS RETENTION</h6>
        <p class="note">Number of unused snapshots (not pointed by any environment) to keep. Leave empty to use the global setting (<?= RETENTION ?>).</p>
        <input type="number" class="<?= $retentionParamClass ?>" param-name="retention" min="0" placeholder="Global setting (<?= RETENTION ?>)" value="<?= ($retention !== null and !$retentionKeepAll) ? (int) $retention : '' ?>" />
    </div>
</div>
