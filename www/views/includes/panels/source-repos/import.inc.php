<?php ob_start(); ?>

<div class="slide-panel-container" slide-panel="source-repos/import">
    <div class="slide-panel">

        <img src="/assets/icons/close.svg" class="slide-panel-close-btn float-right lowopacity" slide-panel="source-repos/import" title="Close" />

        <div class="slide-panel-reloadable-div" slide-panel="source-repos/import">
            <h3>IMPORT SOURCE REPOSITORIES</h3>

            <form id="import-source-repos">
                <h6>IMPORT PRE-DEFINED SOURCE REPOSITORIES LIST</h6>
                <select name="source-repos-list" multiple required>
                    <!-- <option value="" selected disabled>Select a source repository</option> -->
                    <option value="debian-official" selected>Debian official repositories</option>
                    <!-- <option value="test" selected>test</option> -->
                </select>

                <br><br>
                <button type="submit" class="btn-small-green" title="Import source repositories">Import</button>
            </form>
        </div>
    </div>
</div>

<script>
    selectToSelect2('select[name="source-repos-list"]', 'Select list...');
</script>
