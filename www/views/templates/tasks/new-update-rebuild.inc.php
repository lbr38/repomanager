<div id="log-op-title" class="div-generic-blue flex justify-space-between align-item-center">
    <h3><?= $title ?></h3>

    <div class="text-right">
        <p title="Task Id">
            <b>#<?= $this->task->getId() ?></b>
        </p>
        <p title="Task execution date">
            <b><?= DateTime::createFromFormat('Y-m-d', $this->task->getDate())->format('d-m-Y') . ' ' . $this->task->getTime() ?></b>
        </p>
    </div>
</div>

<div class="div-generic-blue">
    <table class="op-table">
        <?php
        if ($this->task->getAction() != 'rebuild') {
            if (!empty($this->repo->getSource())) {
                echo '<tr><th>SOURCE REPO</th><td><span class="label-white">' . $this->repo->getSource() . '</span></td></tr>';
            }
        } ?>

        <tr>
            <th>REPOSITORY</th>
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
        if (!empty($this->repo->getArch())) : ?>
            <tr>
                <th>ARCHITECTURE</th>
                <td>
                    <div class="flex column-gap-5">
                        <?php
                        foreach ($this->repo->getArch() as $arch) {
                            echo '<span class="label-black">' . $arch . '</span>';
                        } ?>
                    </div>
                </td>
            </tr>
            <?php
        endif;

        if (!empty($this->repo->getDescription())) {
            echo '<tr><th>DESCRIPTION</th><td>' . $this->repo->getDescription() . '</td></tr>';
        }

        if (!empty($this->repo->getGpgCheck())) : ?>
            <tr>
                <th>CHECK GPG SIGNATURES</th>
                <td>
                    <?php
                    if ($this->repo->getGpgCheck() == 'true') : ?>
                        <div class="flex align-item-center column-gap-5">
                            <img src="/assets/icons/greencircle.png" class="icon-small" />
                            <span>Enabled</span>
                        </div>
                        <?php
                    endif;
                    if ($this->repo->getGpgCheck() == 'false') : ?>
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

        if (!empty($this->repo->getGpgSign())) : ?>
            <tr>
                <th>SIGN WITH GPG</th>
                <td>
                    <?php
                    if ($this->repo->getGpgSign() == 'true') : ?>
                        <div class="flex align-item-center column-gap-5">
                            <img src="/assets/icons/greencircle.png" class="icon-small" />
                            <span>Enabled</span>
                        </div>
                        <?php
                    endif;
                    if ($this->repo->getGpgSign() == 'false') : ?>
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

        if ($this->task->getAction() == 'update') :
            if (!empty($this->repo->getOnlySyncDifference())) : ?>
                <tr>
                    <th>ONLY SYNC THE DIFFERENCE</th>
                    <td>
                        <?php
                        if ($this->repo->getOnlySyncDifference() == 'true') : ?>
                            <div class="flex align-item-center column-gap-5">
                                <img src="/assets/icons/greencircle.png" class="icon-small" />
                                <span>Enabled</span>
                            </div>
                            <?php
                        endif;
                        if ($this->repo->getOnlySyncDifference() == 'false') : ?>
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

        if (!empty($this->repo->getGroup())) : ?>
            <tr>
                <th>ADD TO GROUP</th>
                <td>
                    <div class="flex">
                        <img src="/assets/icons/folder.svg" class="icon" />
                        <span><?= $this->repo->getGroup() ?></span>
                    </div>
                </td>
            </tr>
            <?php
        endif ?>
    </table>
</div>