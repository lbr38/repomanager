<?php
use \Controllers\Utils\Generate\Html\Label; ?>

<div class="div-generic-blue bck-blue-alt">
    <div>
        <h6 class="margin-top-0">STARTED ON</h6>
        <p><?= htmlspecialchars($event['Time'] ?? '') ?></p>
    </div>

    <?php
    if (!empty($event['Time_end'])) : ?>
        <div>
            <h6>ENDED ON</h6>
            <p><?= htmlspecialchars($event['Time_end']) ?></p>
        </div>
        <?php
    endif;

    if (!empty($event['Command'])) : ?>
        <div>
            <h6>COMMAND</h6>
            <pre class="codeblock copy margin-top-5"><?= htmlspecialchars($event['Command']) ?></pre>
        </div>
        <?php
    endif ?>

    <h6 class="margin-top-15">PACKAGES DETAILS</h6>
    <div class="package-states margin-top-5">
        <?php
        $states = [
            'installed'     => $installed,
            'dep-installed' => $depInstalled,
            'reinstalled'   => $reinstalled,
            'upgraded'      => $updated,
            'removed'       => $removed,
            'downgraded'    => $downgraded,
            'purged'        => $purged
        ];

        foreach ($states as $state => $statePackages) {
            if (!empty($statePackages)) {
                echo Label::packageState($state, count($statePackages));
            }
        } ?>
    </div>
</div>
