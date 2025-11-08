<h6>DELETE</h6>
<p class="note">The repository snapshot to delete.</p>
<?php
if ($myrepo->getPackageType() == 'rpm') {
    echo '<span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getReleasever() . '</span>⸺<span class="label-black">' . $myrepo->getDateFormatted() . '</span>';
}
if ($myrepo->getPackageType() == 'deb') {
    echo '<span class="label-white">' . $myrepo->getName() . ' ❯ ' . $myrepo->getDist() . ' ❯ ' . $myrepo->getSection() . '</span>⸺<span class="label-black">' . $myrepo->getDateFormatted() . '</span>';
}

/**
 *  Define schedule form action and allowed type(s)
 */
$scheduleForm['action'] = 'delete';
$scheduleForm['type'] = ['unique']; ?>
