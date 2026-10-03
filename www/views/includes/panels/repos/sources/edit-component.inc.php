<?php ob_start(); ?>

<div class="slide-panel-container" slide-panel="repos/sources/edit-component">
    <div class="slide-panel">
        <img src="/assets/icons/close.svg" class="slide-panel-close-btn float-right lowopacity" slide-panel="repos/sources/edit-component" title="Close" />

        <div class="slide-panel-reloadable-div" slide-panel="repos/sources/edit-component">
            <h3>EDIT <?= strtoupper($componentName) ?> COMPONENT</h3>

            <form class="source-repo-edit-component" source-id="<?= $sourceId ?>" distribution-id="<?= $distributionId ?>" component-id="<?= $componentId ?>">
                <h6>DESCRIPTION</h6>
                <input type="text" class="component-param" param-name="description" value="<?= $description ?>" placeholder="Description" />

                <h6>ARCHITECTURES</h6>
                <p class="note">The supported architectures for this component.</p>

                <select class="component-param" param-name="archs" multiple>
                    <?php
                    foreach (DEB_ARCHS as $arch) {
                        if (in_array($arch, $archs)) {
                            echo '<option value="' . $arch . '" selected>' . $arch . '</option>';
                        } else {
                            echo '<option value="' . $arch . '">' . $arch . '</option>';
                        }
                    } ?>
                </select>

                <br><br>
                <button type="submit" class="btn-medium-green">Save</button>
            </form>
        </div>

        <script>
            $(document).ready(function(){
                myselect2.convert('select.component-param[param-name="archs"]', 'Select architectures...');
            });
        </script>
    </div>
</div>
