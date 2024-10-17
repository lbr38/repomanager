<?php ob_start(); ?>

<div class="slide-panel-container" slide-panel="repos/sources/edit-distribution">
    <div class="slide-panel">

        <img src="/assets/icons/close.svg" class="slide-panel-close-btn float-right lowopacity" slide-panel="repos/sources/edit-distribution" title="Close" />

        <div class="slide-panel-reloadable-div" slide-panel="repos/sources/edit-distribution">
            <h3>EDIT DISTRIBUTION <?= strtoupper($item['distribution']) ?></h3>

            <form class="" source-id="<?= $item['id'] ?>" distribution="<? $item['distribution'] ?>">
                <h6>NAME</h6>

                <h6>DESCRIPTION</h6>

            
            </form>
        </div>
    </div>
</div>

<script>
    selectToSelect2('select[name="source-repos-list"]', 'Select list...');
</script>
