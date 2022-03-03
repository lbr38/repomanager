<?php
if (OS_FAMILY == 'Redhat') echo '<p>L\'opération va reconstruire les metadonnées du repo <b>'.$myrepo->getName().'</b> '.Common::envtag($myrepo->getEnv()).'</p>';
if (OS_FAMILY == 'Debian') echo '<p>L\'opération va reconstruire les metadonnées de la section <b>'.$myrepo->getName().'</b> ('.$myrepo->getDist().' - '.$myrepo->getSection().') '.Common::envtag($myrepo->getEnv()).'</p>';
?>

<span class="op_span">Signer avec GPG</span>
<label class="onoff-switch-label">
    <input name="repoGpgResign" param-name="targetGpgResign" type="checkbox" class="onoff-switch-input operation_param" value="yes" <?php if (GPG_SIGN_PACKAGES == "yes") echo 'checked'; ?> />
    <span class="onoff-switch-slider"></span>
</label><br>