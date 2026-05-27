<?php
use \Controllers\Utils\Convert;
ob_start(); ?>

<form id="repos-list-preferences">
    <h6>EXPAND REPOSITORY LIST</h6>
    <p class="note">If enabled, repositories will expand to take up the full width of the list when there is only one repository in the list.</p>
    <label class="onoff-switch-label">
        <input type="checkbox" class="onoff-switch-input" name="repositories.list.expand" value="true" <?= Convert::toBool($preferences['repositories']['list']['expand']) === true ? 'checked' : '' ?> />
        <span class="onoff-switch-slider"></span>
    </label>

    <h6>ROW BY ROW REPOSITORY LIST</h6>
    <p class="note">If enabled, repositories will be displayed in a row-by-row layout instead of a grid layout.</p>
    <label class="onoff-switch-label">
        <input type="checkbox" class="onoff-switch-input" name="repositories.list.row-by-row" value="true" <?= Convert::toBool($preferences['repositories']['list']['row-by-row']) === true ? 'checked' : '' ?> />
        <span class="onoff-switch-slider"></span>
    </label>

    <br><br>

    <button type="submit" class="btn-large-green margin-top-5" title="Save preferences">Save</button>
</form>

<?php
$content = ob_get_clean();
$slidePanelName = 'repos/settings';
$slidePanelTitle = 'SETTINGS';

include(ROOT . '/views/includes/slide-panel.inc.php');
