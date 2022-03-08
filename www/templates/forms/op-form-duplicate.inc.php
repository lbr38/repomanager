<?php
if (OS_FAMILY == 'Redhat') echo '<p>Dupliquer <span class="label-white">'.$myrepo->getName().' '.Common::envtag($myrepo->getEnv()).'</p>';
if (OS_FAMILY == 'Debian') echo '<p>Dupliquer <span class="label-white">'.$myrepo->getName().' ❯ '.$myrepo->getDist().' ❯ '.$myrepo->getSection().'</span> '.Common::envtag($myrepo->getEnv()).'</p>';
?>

<span>Nouveau nom du repo :</span><input type="text" class="operation_param" param-name="targetName" required />
<span>Description (fac.) :</span><input type="text" class="operation_param" param-name="targetDescription" />

<?php
/**
 *  Affichage de la liste des groupes
 */
$group = new Group('repo');
$groupList = $group->listAllName();
if (!empty($groupList)) { ?>
    <span>Ajouter à un groupe (fac.)</span>
    <select class="operation_param" param-name="targetGroup">
        <option value="">Sélectionner un groupe...</option>
        <?php
        foreach($groupList as $groupName) {
            echo '<option value="'.$groupName.'">'.$groupName.'</option>';
        } ?>
    </select>
<?php
} ?>