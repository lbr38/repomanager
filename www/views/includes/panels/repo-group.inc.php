<?php ob_start(); ?>

<h5>CREATE A NEW GROUP</h5>

<form id="newGroupForm" autocomplete="off">
    <input id="newGroupInput" type="text" class="input-medium" placeholder="Group name" />
    <button type="submit" class="btn-xxsmall-green" title="Add">+</button>
</form>

<br>

<?php
if (!empty($repoGroupsList)) : ?>
    <h5>CURRENT GROUPS</h5>

    <?php
    foreach ($repoGroupsList as $groupName) : ?>
        <div class="header-container">
            <div class="header-blue-min">
                <form class="groupForm" groupname="<?= $groupName ?>" autocomplete="off">
                    <input type="hidden" name="actualGroupName" value="<?= $groupName ?>" />
                    <table class="table-large">
                        <tr>
                            <td>
                                <input class="groupFormInput input-medium invisibleInput-blue" groupname="<?= $groupName ?>" type="text" value="<?= $groupName ?>" />
                            </td>
                            <td class="td-fit">
                                <img class="groupConfigurationButton icon-mediumopacity" name="<?= $groupName ?>" title="<?= $groupName ?> configuration" src="/assets/icons/cog.svg" />
                                <img src="/assets/icons/delete.svg" class="deleteGroupButton icon-lowopacity" name="<?= $groupName ?>" title="Delete <?= $groupName ?> group" />
                            </td>
                        </tr>
                    </table>
                </form>
            </div>

            <div id="groupConfigurationDiv-<?= $groupName ?>" class="hide">
                <form class="groupReposForm" groupname="<?= $groupName ?>" autocomplete="off">
                    <div class="detailsDiv">
                        <p><b>Include repos</b></p><br>
                        <div class="flex align-content-center">
                            <?php $myrepo->selectRepoByGroup($groupName); ?>
                            <button type="submit" class="btn-xxsmall-green" title="Add and save">+</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php
    endforeach;
endif; ?>

<?php
$content = ob_get_clean();
$slidePanelName = 'repo-groups';
$slidePanelTitle = 'REPOS GROUPS';

include(ROOT . '/views/includes/slide-panel.inc.php');
