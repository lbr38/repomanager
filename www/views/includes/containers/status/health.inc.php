<section id="health" class="section-main reloadable-container" container="settings/health">
    <h3>DATABASES HEALTH</h3>

    <div class="div-generic-blue">
        <div class="grid grid-rfr-2-4 row-gap-30 column-gap-20">
            <?php
            foreach ($appDatabases as $name => $properties) :
                $icon = 'check'; ?>

                <div>
                    <div class="flex align-item-center column-gap-5">
                        <h6 class="margin-top-0"><?= strtoupper($properties['title']) ?></h6>
                        <img src="/assets/icons/info.svg" class="icon-lowopacity icon-small icon-np tooltip" tooltip="<?= $properties['description'] ?>">
                    </div>

                    <?php
                    if (!empty($properties['errors'])) {
                        $icon = 'warning';
                    } ?>

                    <div class="flex column-gap-5 row-gap-5">
                        <img src="/assets/icons/<?= $icon ?>.svg" class="icon" />
                        <div class="flex flex-direction-column row-gap-5">
                            <?php
                            if (empty($properties['errors'])) {
                                echo '<p>Healthy</p>';
                            } else {
                                foreach ($properties['errors'] as $error) {
                                    echo '<p>' . $error . '</p>';
                                }
                            } ?>
                        </div>
                    </div>
                </div>
                <?php
            endforeach ?>
        </div>
    </div>
</section>