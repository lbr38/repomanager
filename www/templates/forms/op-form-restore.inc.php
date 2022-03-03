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