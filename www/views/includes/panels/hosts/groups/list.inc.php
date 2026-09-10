<?php ob_start(); ?>

<h6>CREATE A NEW GROUP</h6>

<form id="new-group" autocomplete="off">
    <input name="group-name" type="text" placeholder="Group name" />
    <button type="submit" class="btn-small-green margin-top-5" title="Create">Create</button>
</form>

<?php
if (!empty($hostGroupsList)) : ?>
    <h6 class="margin-top-30 margin-bottom-5">CURRENT GROUPS</h6>

    <?php
    foreach ($hostGroupsList as $group) :
        // Ignore if group name is 'Default'
        if ($group['Name'] === 'Default') {
            continue;
        }

        // Retrieve hosts members and hosts not members of the group
        $hostsIn = $mygroup->getHostsMembers($group['Id']);
        $hostsNotIn = $mygroup->getHostsNotMembers();
        $hostsInCount = count($hostsIn); ?>

        <div class="table-container-2 panel-list-item group-config-btn pointer" group-id="<?= $group['Id'] ?>">
            <div>
                <p><?= $group['Name'] ?></p>
                <p class="lowopacity-cst"><?= $hostsInCount ?> host<?= $hostsInCount > 1 ? 's' : '' ?></p>
            </div>

            <div class="flex justify-end">
                <input type="checkbox" class="child-checkbox lowopacity" checkbox-id="host-group" checkbox-data-attribute="group-id" group-id="<?= $group['Id'] ?>" title="Select group" />
            </div>
        </div>

        <div class="group-config-div details-div margin-bottom-5 hide" group-id="<?= $group['Id'] ?>">
            <form class="group-form" group-id="<?= $group['Id'] ?>" autocomplete="off">
                <h6 class="required margin-top-0">NAME</h6>
                <input class="group-name-input" type="text" group-id="<?= $group['Id'] ?>" value="<?= $group['Name'] ?>" />

                <h6>HOSTS</h6>
                <select class="group-hosts-list" group-id="<?= $group['Id'] ?>" name="group-hosts[]" multiple>
                    <?php
                    // Hosts members of the group will be selected by default in the list
                    if (!empty($hostsIn)) {
                        foreach ($hostsIn as $host) {
                            echo '<option value="' . $host['Id'] . '" selected>' . $host['Hostname'] . ' (' . $host['Ip'] . ')</option>';
                        }
                    }

                    // Hosts not members of the group will be unselected in the list
                    if (!empty($hostsNotIn)) {
                        foreach ($hostsNotIn as $host) {
                            echo '<option value="' . $host['Id'] . '">' . $host['Hostname'] . ' (' . $host['Ip'] . ')</option>';
                        }
                    } ?>
                </select>

                <br><br>
                <button type="submit" class="btn-large-green" title="Save">Save</button>
            </form>
        </div>
        <?php
    endforeach;
endif; ?>

<script>
$(document).ready(function(){
    myselect2.convert('select.group-hosts-list', 'Add host');
});
</script>

<?php
$content = ob_get_clean();
$slidePanelName = 'hosts/groups/list';
$slidePanelTitle = 'GROUPS';

include(ROOT . '/views/includes/slide-panel.inc.php');