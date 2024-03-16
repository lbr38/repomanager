<tr>
    <td colspan="100%">
        <?php
        if ($myrepo->getPackageType() == 'rpm') {
            echo 'Point an environment on:<br><br><span class="label-white">' . $myrepo->getName() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span>';
        }
        if ($myrepo->getPackageType() == 'deb') {
            echo 'Point an environment on:<br><br><span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span>';
        } ?>
        <br><br>
    </td>
</tr>

<tr>
    <td class="td-30">Target environment</td>
    <td>
        <select class="task-param" param-name="env" required>
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
    <td class="td-30">
        <span>Description</span> <span class="lowopacity-cst">(optionnal)</span>
    </td>
    <td><input type="text" class="task-param" param-name="description" /></td>
</tr>