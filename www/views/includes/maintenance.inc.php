<?php
if (UPDATE_RUNNING === true) :
    /**
     *  La page de maintenance s'affiche sur toutes les pages sauf sur /settings
     */
    if (__ACTUAL_URI__[1] != "/settings") : ?>
        <div id="maintenance-container">    
            <div id="maintenance">
                <h3 class="margin-top-0">UPDATE RUNNING</h3>
                <p>Repomanager will be available soon.</p>
                <br>
                <button class="btn-medium-green" onClick="window.location.reload();">Refresh</button>
            </div>
        </div>
        <?php
    endif;
endif;