<div id="groupsDiv" class="param-slide-container">
    <div class="param-slide">
        <img id="groupsDivCloseButton" title="Close" class="close-btn lowopacity float-right" src="resources/icons/close.svg" />

        <h3>REPOS GROUPS</h3>

        <h4><b>Create a new group</b></h4>

        <form id="newGroupForm" autocomplete="off">
            <input id="newGroupInput" type="text" class="input-medium" placeholder="Group name" />
            <button type="submit" class="btn-xxsmall-green" title="Add">+</button>
        </form>

        <br><br>

        <?php
        /**
         *  AFFICHAGE DES GROUPES ACTUELS
         */

        /**
         *  1. Récupération de tous les noms de groupes (en excluant le groupe par défaut)
         */
        $group = new \Controllers\Group('repo');
        $groupsList = $group->listAllName();

        /**
         *  2. Affichage des groupes si il y en a
         */
        if (!empty($groupsList)) : ?>
            <h4><b>Current groups</b></h4>

            <?php
            $myrepo = new \Controllers\Repo();

            foreach ($groupsList as $groupName) : ?>
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
                                        <img class="groupConfigurationButton icon-mediumopacity" name="<?= $groupName ?>" title="<?= $groupName ?> configuration" src="resources/icons/cog.svg" />
                                        <img src="resources/icons/bin.svg" class="deleteGroupButton icon-lowopacity" name="<?= $groupName ?>" title="Delete <?= $groupName ?> group" />
                                    </td>
                                </tr>
                            </table>
                        </form>
                    </div>
                    <div id="groupConfigurationDiv-<?= $groupName ?>" class="hide">
                        <form class="groupReposForm" groupname="<?= $groupName ?>" autocomplete="off">
                            <div class="detailsDiv">
                                <h5>Repos</h5>
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
    </div>
</div>