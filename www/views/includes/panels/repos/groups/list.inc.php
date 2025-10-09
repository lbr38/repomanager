<?php ob_start(); ?>

<h6>CREATE A NEW GROUP</h6>

<form id="newGroupForm" autocomplete="off">
    <input id="newGroupInput" type="text" class="input-medium" placeholder="Group name" />
    <button type="submit" class="btn-xxsmall-green" title="Add">+</button>
</form>

<br>

<?php
if (!empty($repoGroupsList)) : ?>
    <h6>CURRENT GROUPS</h6>

    <?php
    foreach ($repoGroupsList as $group) :
        /**
         *  Retrieve repos members and repos not members of the group
         */
        $reposIn = $mygroup->getReposMembers($group['Id']);
        $reposNotIn = $mygroup->getReposNotMembers();
        $reposInCount = count($reposIn); ?>

        <div class="table-container grid-fr-4-1 bck-blue-alt group-config-btn pointer veil-on-reload" group-id="<?= $group['Id'] ?>">
            <div>
                <p><?= $group['Name'] ?></p>
                <p class="lowopacity-cst"><?= $reposInCount ?> <?= $reposInCount > 1 ? 'repositories' : 'repository' ?></p>
            </div>

            <div class="flex justify-end">
                <input type="checkbox" class="child-checkbox lowopacity" checkbox-id="repo-group" checkbox-data-attribute="group-id" group-id="<?= $group['Id'] ?>" title="Select group" />
            </div>
        </div>

        <div class="group-config-div details-div margin-bottom-5 hide veil-on-reload" group-id="<?= $group['Id'] ?>">
            <form class="group-form" group-id="<?= $group['Id'] ?>" autocomplete="off">
                <h6 class="required margin-top-0">NAME</h6>
                <input class="group-name-input" type="text" group-id="<?= $group['Id'] ?>" value="<?= $group['Name'] ?>" />

                <h6>REPOSITORIES</h6>
                <select class="group-repos-list" group-id="<?= $group['Id'] ?>" name="group-repos[]" multiple>
                    <?php
                    /**
                     *  Repos members of the group will be selected by default in the list
                     */
                    if (!empty($reposIn)) {
                        foreach ($reposIn as $repo) {
                            if ($repo['Package_type'] == 'rpm') {
                                echo '<option value="' . $repo['repoId'] . '" selected>' . $repo['Name'] . '</option>';
                            }
                            if ($repo['Package_type'] == 'deb') {
                                echo '<option value="' . $repo['repoId'] . '" selected>' . $repo['Name'] . ' ❯ ' . $repo['Dist'] . ' ❯ ' . $repo['Section'] . '</option>';
                            }
                        }
                    }

                    /**
                     *  Repos not members of the group will be unselected in the list
                     */
                    if (!empty($reposNotIn)) {
                        foreach ($reposNotIn as $repo) {
                            if ($repo['Package_type'] == 'rpm') {
                                echo '<option value="' . $repo['repoId'] . '">' . $repo['Name'] . '</option>';
                            }
                            if ($repo['Package_type'] == 'deb') {
                                echo '<option value="' . $repo['repoId'] . '">' . $repo['Name'] . ' ❯ ' . $repo['Dist'] . ' ❯ ' . $repo['Section'] . '</option>';
                            }
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
    myselect2.convert('select.group-repos-list', 'Add repository');
});
</script>

<?php
$content = ob_get_clean();
$slidePanelName = 'repos/groups/list';
$slidePanelTitle = 'REPOS GROUPS';

include(ROOT . '/views/includes/slide-panel.inc.php');
