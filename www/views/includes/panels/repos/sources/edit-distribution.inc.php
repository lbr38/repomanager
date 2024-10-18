<?php ob_start(); ?>

<div class="slide-panel-container" slide-panel="repos/sources/edit-distribution">
    <div class="slide-panel">
        <img src="/assets/icons/close.svg" class="slide-panel-close-btn float-right lowopacity" slide-panel="repos/sources/edit-distribution" title="Close" />

        <div class="slide-panel-reloadable-div" slide-panel="repos/sources/edit-distribution">
            <h3>EDIT DISTRIBUTION <?= strtoupper($distribution) ?></h3>

            <form class="source-repo-edit-distribution" source-id="<?= $id ?>" distribution="<? $distribution ?>">
                <input type="hidden" class="distribution-param" param-name="current-name" value="<?= $distribution ?>" placeholder="Name" />

                <h6>NAME</h6>
                <input type="text" class="distribution-param" param-name="name" value="<?= $distribution ?>" placeholder="Name" />

                <h6>DESCRIPTION</h6>
                <input type="text" class="distribution-param" param-name="description" value="<?= $description ?>" placeholder="Name" />

                <h6>SECTIONS / COMPONENTS</h6>
                <?php
                foreach ($components as $componentName => $componentDetails) : ?>
                    <div class="table-container grid-2 bck-blue-alt pointer" source-id="<?= $id ?>" distribution="<?= $distribution ?>">
                        <div>
                            <p><?= $componentName ?></p>
                        </div>

                        <div class="flex justify-end">
                            <img src="/assets/icons/delete.svg" class="icon-lowopacity" source-id="<?= $id ?>" distribution="<?= $distribution ?>" section="<?= $componentName ?>" title="Delete <?= $componentName ?> section" />
                        </div>
                    </div>
                    <?php
                endforeach; ?>

                <h6>GPG KEYS</h6>

                <br><br>
                <button type="submit" class="btn-medium-green">Save</button>
            </form>
        </div>
    </div>
</div>
