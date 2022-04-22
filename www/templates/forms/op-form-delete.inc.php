<tr>
    <td colspan="100%">
        <?php
        if ($myrepo->getPackageType() == 'rpm') {
            echo 'Supprimer <span class="label-white">' . $myrepo->getName() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span>';
        }
        if ($myrepo->getPackageType() == 'deb') {
            echo 'Supprimer <span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>⟶<span class="label-black">' . $myrepo->getDateFormatted() . '</span>';
        } ?>
    </td>
</tr>