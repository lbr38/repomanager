<section class="right" id="groupsDiv">
    <img id="groupsDivCloseButton" title="Fermer" class="icon-lowopacity float-right" src="resources/icons/close.png" />
    <h3>GROUPES</h3>
    <p>Les groupes permettent de regrouper plusieurs repos afin de les trier ou d'effectuer une action commune.</p>
    <br>

    <h5>Créer un groupe</h5>

    <form id="newGroupForm" autocomplete="off">
        <input id="newGroupInput" type="text" class="input-medium" />
        <button type="submit" class="btn-xxsmall-blue" title="Ajouter">+</button>
    </form>
<br>
    <br>
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
    if (!empty($groupsList)) {
        echo '<div class="div-generic-gray">';
            echo "<h5>Groupes actuels</h5>";

            $myrepo = new \Controllers\Repo();

        foreach ($groupsList as $groupName) {?>
                <div class="header-container">
                    <div class="header-blue-min">
                        <form class="groupForm" groupname="<?php echo $groupName;?>" autocomplete="off">
                            <input type="hidden" name="actualGroupName" value="<?php echo $groupName;?>" />
                            <table class="table-large">
                                <tr>
                                    <td>
                                        <input class="groupFormInput input-medium invisibleInput-blue" groupname="<?php echo $groupName;?>" type="text" value="<?php echo $groupName;?>" />
                                    </td>
                                    <td class="td-fit">
                                        <img class="groupConfigurationButton icon-mediumopacity" name="<?php echo $groupName;?>" title="Configuration de <?php echo $groupName;?>" src="resources/icons/cog.png" />
                                        <img src="resources/icons/bin.png" class="deleteGroupButton icon-lowopacity" name="<?php echo $groupName;?>" title="Supprimer le groupe <?php echo $groupName;?>" />
                                    </td>
                                </tr>
                            </table>
                        </form>
                    </div>

                    <div id="groupConfigurationDiv-<?php echo $groupName;?>" class="hide">
                        <form class="groupReposForm" groupname="<?php echo $groupName;?>" autocomplete="off">
                            <div class="detailsDiv">
                                <h5>Repos</h5>

                                <?php $myrepo->selectRepoByGroup($groupName); ?>

                                <br>
                                <br>
                                <button type="submit" class="btn-large-blue" title="Enregistrer">Enregistrer</button>
                            </div>
                        </form>
                    </div>
                </div>
        <?php }
        echo '</div>';
    } ?>
</section>