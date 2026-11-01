<?php
if ($repoController->getPackageType() == 'rpm') {
    echo '<span class="label-white">' . $repoController->getName() . ' ❯ ' . $repoController->getReleasever() . '</span>';
}
if ($repoController->getPackageType() == 'deb') {
    echo '<span class="label-white">' . $repoController->getName() . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection() . '</span>';
} ?>

<?php
if ($repoController->getType() == 'curated') : ?>
    <h6 class="required">SOURCE REPOSITORY</h6>
    <p class="note">The repository to get packages from. Want more? <span class="note pointer lowopacity get-panel-btn" panel="repos/sources/list">Add or import a source repository</span>.</p>
    
    <?php
    if ($repoController->getPackageType() == 'rpm') {
        if (empty($newRepoRpmSourcesList)) {
            echo '<div class="flex align-item-center column-gap-5 margin-top-10" field-type="curated rpm"><img src="/assets/icons/warning.svg" class="icon vertical-align-text-top" /><p class="note">No rpm source repositories available. Please add a source repository first.</p></div>';
        }

        if (!empty($newRepoRpmSourcesList)) : ?>
            <select class="edit-param" param-name="source" field-type="curated rpm" package-type="rpm">
                <option value="">Select a source repository</option>
                <?php
                foreach ($newRepoRpmSourcesList as $source) {
                    $definition = json_decode($source['Definition'], true);
                    $name = $definition['name'];

                    // If the source is the same as the current source, select it
                    if ($name == $repoController->getSource()) {
                        echo '<option value="' . $name . '" selected>' . $name . '</option>';
                    } else {
                        echo '<option value="' . $name . '">' . $name . '</option>';
                    }
                } ?>
            </select>
            <?php
        endif;
    }

    if ($repoController->getPackageType() == 'deb') {
        if (empty($newRepoDebSourcesList)) {
            echo '<div class="flex align-item-center column-gap-5 margin-top-10" field-type="curated deb"><img src="/assets/icons/warning.svg" class="icon vertical-align-text-top" /><p class="note">No deb source repositories available. Please add a source repository first.</p></div>';
        }

        if (!empty($newRepoDebSourcesList)) : ?>
            <select class="edit-param" param-name="source" field-type="curated deb" package-type="deb">
                <option value="">Select a source repository</option>
                <?php
                foreach ($newRepoDebSourcesList as $source) {
                    $definition = json_decode($source['Definition'], true);
                    $name = $definition['name'];

                    // If the source is the same as the current source, select it
                    if ($name == $repoController->getSource()) {
                        echo '<option value="' . $name . '" selected>' . $name . '</option>';
                    } else {
                        echo '<option value="' . $name . '">' . $name . '</option>';
                    }
                } ?>
            </select>
            <?php
        endif;
    } ?>
    
    <?php
endif ?>

<h6>SNAPSHOT</h6>
<span class="label-black"><?= $repoController->getDateFormatted() ?></span>

<p class="note margin-top-5">Nothing editable for now!</p>
