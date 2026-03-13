<div class="div-generic-blue margin-top-15">
    <div class="flex justify-space-between">
        <div>
            <p class="font-size-18 mediumopacity-cst">
                <?php
                if ($repo['Package_type'] == 'deb') : ?>
                    <?= $repo['Dist'] ?> ● <?= $repo['Section'] ?>
                    <?php
                endif;

                if ($repo['Package_type'] == 'rpm') : ?>
                    Release version <?= $repo['Releasever'] ?>
                    <?php
                endif ?>
            </p>

            <p class="note">The repository description</p>
        </div>

        <?php
        if ($repo['Type'] == 'local') {
            echo '<img src="/assets/icons/pin.svg" class="icon-np mediumopacity-cst" title="Local repository">';
        } elseif ($repo['Type'] == 'mirror') {
            echo '<img src="/assets/icons/internet.svg" class="icon-np mediumopacity-cst" title="Remote repository">';
        } ?>
    </div>

    <div class="flex flex-direction-column row-gap-30 margin-top-50">
        <?php
        foreach ($myrepoListing->listSnapshots($repo['repoId']) as $snapshot) : ?>
            <div class="snap-container">
                <span class="snap-label"><?= $snapshot['Date'] ?></span>
                <span class="snap-size">90G</span>
            </div>


            <?php
        endforeach ?>
    </div>
</div>
