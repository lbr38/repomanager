<?php
if ($status == 'active') {
    if (OS_FAMILY == 'Redhat') echo '<p>Supprimer le repo <span class="label-white">'.$myrepo->getName().'</span> '.Common::envtag($myrepo->getEnv()).'</p>';
    if (OS_FAMILY == 'Debian') echo '<p>Supprimer la section <span class="label-white">'.$myrepo->getName().' ❯ '.$myrepo->getDist().' ❯ '.$myrepo->getSection().'</span> '.Common::envtag($myrepo->getEnv()).'</p>';
}
if ($status == 'archived') {
    if (OS_FAMILY == 'Redhat') echo '<p>Supprimer le repo archivé <span class="label-white">'.$myrepo->getName().'</span>⟶<span class="label-black">'.$myrepo->getDateFormatted().'</span></p>';
    if (OS_FAMILY == 'Debian') echo '<p>Supprimer la section archivée <span class="label-white">'.$myrepo->getName().' ❯ '.$myrepo->getDist().' ❯ '.$myrepo->getSection().'</span>⟶<span class="label-black">'.$myrepo->getDateFormatted().'</span></p>';
}
?>