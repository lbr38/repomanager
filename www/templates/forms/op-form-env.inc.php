<tr>
    <td colspan="100%">
        <?php
        if ($myrepo->getPackageType() == 'rpm') {
            echo 'Faire pointer un environnement sur :<br><br><span class="label-white">' . $myrepo->getName() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span>';
        }
        if ($myrepo->getPackageType() == 'deb') {
            echo 'Faire pointer un environnement sur :<br><br><span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span>';
        } ?>
        <br><br>
    </td>
</tr>

<tr>
    <td class="td-30">Environnement cible :</td>
    <td>
        <select class="operation_param" param-name="targetEnv" required>
            <?php
            foreach (ENVS as $env) {
                /**
                 *  On ne réaffiche pas l'env source
                 */
                if ($env !== $myrepo->getEnv()) {
                    echo '<option value="' . $env . '">' . $env . '</option>';
                }
            } ?>
        </select>
    </td>
</tr>

<tr>
    <td class="td-30">Description (fac.) :</td>
    <td><input type="text" class="operation_param" param-name="targetDescription" /></td>
</tr>