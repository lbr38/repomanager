<div class="div-generic-blue bck-blue-alt">
    <div>
        <h6 class="margin-top-0">STARTED ON</h6>
        <p><?= $event['Time'] ?></p>
    </div>

    <?php
    if (!empty($event['Time_end'])) : ?>
        <div>
            <h6>ENDED ON</h6>
            <p><?= $event['Time_end'] ? : 'Unknown' ?></p>
        </div>
        <?php
    endif;

    if (!empty($event['Command'])) : ?>
        <div>
            <h6>COMMAND</h6>
            <pre class="codeblock copy margin-top-5"><?= $event['Command'] ? : 'Unknown' ?></pre>
        </div>
        <?php
    endif ?>

    <h6 class="margin-top-15">PACKAGES DETAILS</h6>
    <div class="flex align-item-center column-gap-20 row-gap-15 margin-top-5">
        <?php
        $count = count($installed);
        if ($count > 0) {
            $title = 'INSTALLED';
            $icon = 'package-installed.svg';
            include(ROOT . '/views/includes/labels/label-icon-tr.inc.php');
        }

        $count = count($depInstalled);
        if ($count > 0) {
            $title = 'DEP. INSTALLED';
            $icon = 'package-installed.svg';
            include(ROOT . '/views/includes/labels/label-icon-tr.inc.php');
        }

        $count = count($reinstalled);
        if ($count > 0) {
            $title = 'REINSTALLED';
            $icon = 'package-installed.svg';
            include(ROOT . '/views/includes/labels/label-icon-tr.inc.php');
        }

        $count = count($updated);
        if ($count > 0) {
            $title = 'UPDATED';
            $icon = 'package-updated.svg';
            include(ROOT . '/views/includes/labels/label-icon-tr.inc.php');
        }

        $count = count($removed);
        if ($count > 0) {
            $title = 'REMOVED';
            $icon = 'package-removed.svg';
            include(ROOT . '/views/includes/labels/label-icon-tr.inc.php');
        }

        $count = count($downgraded);
        if ($count > 0) {
            $title = 'DOWNGRADED';
            $icon = 'package-updated.svg';
            include(ROOT . '/views/includes/labels/label-icon-tr.inc.php');
        }

        $count = count($purged);
        if ($count > 0) {
            $title = 'PURGED';
            $icon = 'package-removed.svg';
            include(ROOT . '/views/includes/labels/label-icon-tr.inc.php');
        } ?>
    </div>
</div>
