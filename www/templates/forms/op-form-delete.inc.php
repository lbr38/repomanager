<?php
if ($status == 'active') {
    if (OS_FAMILY == 'Redhat') echo '<p>Supprimer le repo <span class="label-black">'.$myrepo->getName().'</span> '.Common::envtag($myrepo->getEnv()).'</p>';
    if (OS_FAMILY == 'Debian') echo '<p>Supprimer la section <span class="label-black">'.$myrepo->getName().'<img src="ressources/icons/link.png" class="icon" />'.$myrepo->getDist().' <img src="ressources/icons/link.png" class="icon" />'.$myrepo->getSection().'</span> '.Common::envtag($myrepo->getEnv()).'</p>';
}
if ($status == 'archived') {
    if (OS_FAMILY == 'Redhat') echo '<p>Supprimer le repo archivé <span class="label-black">'.$myrepo->getName().'</span> <span class="label-black">'.$myrepo->getDateFormatted().'</span></p>';
    if (OS_FAMILY == 'Debian') echo '<p>Supprimer la section archivée <span class="label-black">'.$myrepo->getName().'<img src="ressources/icons/link.png" class="icon" />'.$myrepo->getDist().' <img src="ressources/icons/link.png" class="icon" />'.$myrepo->getSection().'</span> <span class="label-black">'.$myrepo->getDateFormatted().'</span></p>';
}
?>