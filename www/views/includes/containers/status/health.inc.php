<section id="health" class="section-main reloadable-container" container="settings/health">
    <h3>DATABASES HEALTH</h3>

    <div class="grid grid-rfr-1-4 row-gap-30 column-gap-20">
        <?php
        foreach ($appDatabases as $name => $properties) :
            $icon = 'check';

            if (!empty($properties['errors'])) {
                $icon = 'warning';
            } ?>

            <div class="kpi-card">
                <img src="/assets/icons/<?= $icon ?>.svg" class="icon-np icon-medium" />
                <div>
                    <p class="kpi-value tooltip" tooltip="<?= $properties['description'] ?>"><?= $properties['title'] ?></p>
                    <div class="flex align-item-center column-gap-5 row-gap-5">
                        <p class="mediumopacity-cst" title="Health status of the database">
                            <?php
                            if (empty($properties['errors'])) {
                                echo 'Healthy';
                            } else {
                                foreach ($properties['errors'] as $error) {
                                    echo $error;
                                }
                            } ?>
                        </p>
                        <p class="font-size-13 mediumopacity-cst" title="Total tables">(<?= $properties['total'] ?>)</p>
                    </div>
                </div>
            </div>
            <?php
        endforeach ?>
    </div>
</section>