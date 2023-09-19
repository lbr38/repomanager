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
        foreach ($hostGroupsList as $groupName) : ?>
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
                                    <img class="groupConfigurationButton icon-mediumopacity" name="<?= $groupName ?>" title="<?= $groupName ?> group configuration" src="/assets/icons/cog.svg" />
                                    <img src="/assets/icons/delete.svg" class="deleteGroupButton icon-lowopacity" name="<?= $groupName ?>" title="Delete <?= $groupName ?> group" />
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
                <div id="groupConfigurationDiv-<?= $groupName ?>" class="hide">
                    <form class="groupHostsForm" groupname="<?= $groupName ?>" autocomplete="off">
                        <div class="detailsDiv">
                            <p><b>Include hosts</b></p><br>
                            <div class="flex align-content-center">
                                <?php $myhost->selectServers($groupName); ?>
                                <button type="submit" class="btn-xxsmall-green" title="Add and save">+</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php
        endforeach ?>
        </div>
    </div>
    <?php
endif;

$content = ob_get_clean();
$slidePanelName = 'hosts-groups';
$slidePanelTitle = 'GROUPS';

include(ROOT . '/views/includes/slide-panel.inc.php');