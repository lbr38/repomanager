<?php ob_start(); ?>

<h5>CREATE A NEW GROUP</h5>

<form id="newGroupForm" autocomplete="off">
    <input id="newGroupInput" type="text" class="input-medium" placeholder="Group name" /></td>
    <button type="submit" class="btn-xxsmall-green" title="Add">+</button></td>
</form>

<br>

<?php
if (!empty($hostGroupsList)) : ?>
    <h5>CURRENT GROUPS</h5>

    <div class="groups-list-container">
        <?php
        foreach ($hostGroupsList as $group) :
            /**
             *  Ignore if group name is 'Default'
             */
            if ($group['Name'] === 'Default') {
                continue;
            }

            /**
             *  Retrieve hosts members and hosts not members of the group
             */
            $hostsIn = $mygroup->getHostsMembers($group['Id']);
            $hostsNotIn = $mygroup->getHostsNotMembers();
            $hostsInCount = count($hostsIn); ?>

            <div class="table-container grid-fr-4-1 bck-blue-alt group-config-btn pointer" group-id="<?= $group['Id'] ?>">
                <div>
                    <p><?= $group['Name'] ?></p>
                    <p class="lowopacity-cst"><?= $hostsInCount ?> host<?= $hostsInCount > 1 ? 's' : '' ?></p>
                </div>

                <div class="flex justify-end">
                    <img src="/assets/icons/delete.svg" class="delete-group-btn icon-lowopacity" group-id="<?= $group['Id'] ?>" group-name="<?= $group['Name'] ?>" title="Delete <?= $group['Name'] ?> group" />
                </div>
            </div>

            <div class="group-config-div detailsDiv margin-bottom-5 hide" group-id="<?= $group['Id'] ?>">
                <form class="group-form" group-id="<?= $group['Id'] ?>" autocomplete="off">
                    <div class="grid grid-fr-1-2 align-item-center column-gap-10">
                        <span>Name</span>
                        <input class="group-name-input" type="text" group-id="<?= $group['Id'] ?>" value="<?= $group['Name'] ?>" />

                        <span>Include hosts</span>
                        <select class="group-hosts-list" group-id="<?= $group['Id'] ?>" name="group-hosts[]" multiple>
                            <?php
                            /**
                             *  Hosts members of the group will be selected by default in the list
                             */
                            if (!empty($hostsIn)) {
                                foreach ($hostsIn as $host) {
                                    echo '<option value="' . $host['Id'] . '" selected>' . $host['Hostname'] . ' (' . $host['Ip'] . ')</option>';
                                }
                            }

                            /**
                             *  Hosts not members of the group will be unselected in the list
                             */
                            if (!empty($hostsNotIn)) {
                                foreach ($hostsNotIn as $host) {
                                    echo '<option value="' . $host['Id'] . '">' . $host['Hostname'] . ' (' . $host['Ip'] . ')</option>';
                                }
                            } ?>
                        </select>
                    </div>

                    <br>
                    <button type="submit" class="btn-large-green" title="Save">Save</button>
                </form>
            </div>
            <?php
        endforeach ?>
        </div>
    </div>
    <?php
endif;

$content = ob_get_clean();
$slidePanelName = 'hosts/groups';
$slidePanelTitle = 'GROUPS';

include(ROOT . '/views/includes/slide-panel.inc.php');