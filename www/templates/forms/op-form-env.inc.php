<?php
if (OS_FAMILY == 'Redhat') echo '<p>Pointer un environnement sur <span class="label-white">'.$myrepo->getName().'</span> '.Common::envtag($myrepo->getEnv()) . '⟶<span class="label-black">'.$myrepo->getDateFormatted().'</span></p>';
if (OS_FAMILY == 'Debian') echo '<p>Pointer un environnement sur <span class="label-white">'.$myrepo->getName().' ❯ '.$myrepo->getDist().' ❯ '.$myrepo->getSection().'</span> '.Common::envtag($myrepo->getEnv()) . '⟶<span class="label-black">'.$myrepo->getDateFormatted().'</span></p>';
?>

<span>Environnement cible :</span>
<select class="operation_param" param-name="targetEnv" required>
    <?php
    foreach(ENVS as $env) {
        /**
         *  On ne réaffiche pas l'env source
         */
        if ($env !== $myrepo->getEnv()) {
            echo '<option value="'.$env.'">'.$env.'</option>';
        }
    } ?>
</select>

<span>Description (fac.) :</span><input type="text" class="operation_param" param-name="targetDescription" />