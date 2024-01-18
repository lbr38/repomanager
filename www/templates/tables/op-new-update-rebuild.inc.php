<div id="log-op-title" class="div-generic-blue">
    <h3><?= $title ?></h3>
</div>

<div class="div-generic-blue">
    <table class="op-table">
        <?php
        if ($this->operation->getAction() != 'rebuild') {
            if (!empty($this->repo->getSource())) {
                echo '<tr><th>SOURCE REPO</th><td><span class="label-white">' . $this->repo->getSource() . '</span></td></tr>';
            }
        } ?>

        <tr>
            <th>REPO</th>
            <td>
                <span class="label-white">
                <?php
                if (!empty($this->repo->getDist()) and !empty($this->repo->getSection())) {
                    echo $this->repo->getName() . ' ❯ ' . $this->repo->getDist() . ' ❯ ' . $this->repo->getSection();
                } else {
                    echo $this->repo->getName();
                } ?>
                </span>
            </td>
        </tr>

        <?php
        if (!empty($this->repo->getTargetArch())) : ?>
            <tr>
                <th>ARCHITECTURE</th>
                <td>
                    <div class="flex column-gap-5">
                        <?php
                        foreach ($this->repo->getTargetArch() as $arch) {
                            echo '<span class="label-black">' . $arch . '</span>';
                        } ?>
                    </div>
                </td>
            </tr>
            <?php
        endif;

        if (!empty($this->repo->getTargetDescription())) {
            echo '<tr><th>DESCRIPTION</th><td>' . $this->repo->getTargetDescription() . '</td></tr>';
        }

        if (!empty($this->repo->getTargetPackageTranslation())) : ?>
            <tr>
                <th>INCLUDE PACKAGES TRANSLATION</th>
                <td><?= implode(', ', $this->repo->getTargetPackageTranslation()) ?></td>
            </tr>
            <?php
        endif;

        if (!empty($this->repo->getTargetGpgCheck())) : ?>
            <tr>
                <th>CHECK GPG SIGNATURES</th>
                <td>
                    <?php
                    if ($this->repo->getTargetGpgCheck() == 'yes') : ?>
                        <div class="flex align-item-center column-gap-5">
                            <img src="/assets/icons/greencircle.png" class="icon-small" />
                            <span>Enabled</span>
                        </div>
                        <?php
                    endif;
                    if ($this->repo->getTargetGpgCheck() == 'no') : ?>
                        <div class="flex align-item-center column-gap-5">
                            <img src="/assets/icons/redcircle.png" class="icon-small" />
                            <span>Disabled</span>
                        </div>
                        <?php
                    endif; ?>
                </td>
            </tr>
            <?php
        endif;

        if (!empty($this->repo->getTargetGpgResign())) : ?>
            <tr>
                <th>SIGN WITH GPG</th>
                <td>
                    <?php
                    if ($this->repo->getTargetGpgResign() == 'yes') : ?>
                        <div class="flex align-item-center column-gap-5">
                            <img src="/assets/icons/greencircle.png" class="icon-small" />
                            <span>Enabled</span>
                        </div>
                        <?php
                    endif;
                    if ($this->repo->getTargetGpgResign() == 'no') : ?>
                        <div class="flex align-item-center column-gap-5">
                            <img src="/assets/icons/redcircle.png" class="icon-small" />
                            <span>Disabled</span>
                        </div>
                        <?php
                    endif; ?>
                </td>
            </tr>
            <?php
        endif;

        if ($this->operation->getAction() == 'update') :
            if (!empty($this->repo->getOnlySyncDifference())) : ?>
                <tr>
                    <th>ONLY SYNC THE DIFFERENCE</th>
                    <td>
                        <?php
                        if ($this->repo->getOnlySyncDifference() == 'yes') : ?>
                            <div class="flex align-item-center column-gap-5">
                                <img src="/assets/icons/greencircle.png" class="icon-small" />
                                <span>Enabled</span>
                            </div>
                            <?php
                        endif;
                        if ($this->repo->getOnlySyncDifference() == 'no') : ?>
                            <div class="flex align-item-center column-gap-5">
                                <img src="/assets/icons/redcircle.png" class="icon-small" />
                                <span>Disabled</span>
                            </div>
                            <?php
                        endif; ?>
                    </td>
                </tr>
                <?php
            endif;
        endif;

        if (!empty($this->repo->getTargetGroup())) : ?>
            <tr>
                <th>ADD TO GROUP</th>
                <td>
                    <div class="flex">
                        <img src="/assets/icons/folder.svg" class="icon" />
                        <span><?= $this->repo->getTargetGroup() ?></span>
                    </div>
                </td>
            </tr>
            <?php
        endif ?>
    </table>
</div>