<div id="log-op-title" class="div-generic-blue">
    <h3><?= $title ?></h3>
</div>

<div class="div-generic-blue">
    <table class="op-table">
        <?php
        if ($this->operation->getAction() != 'reconstruct') {
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
        if (!empty($this->repo->getTargetDescription())) {
            echo '<tr><th>DESCRIPTION</th><td>' . $this->repo->getTargetDescription() . '</td></tr>';
        }

        if (!empty($this->repo->getTargetArch())) {
            echo '<tr><th>ARCHITECTURE</th><td>' . implode(', ', $this->repo->getTargetArch()) . '</td></tr>';
        }

        if (!empty($this->repo->getTargetSourcePackage())) {
            echo '<tr><th>INCLUDE SOURCES PACKAGES</th><td>' . $this->repo->getTargetSourcePackage() . '</td></tr>';
        }

        if (!empty($this->repo->getTargetPackageTranslation())) {
            echo '<tr><th>INCLUDE PACKAGES TRANSLATION</th><td>' . implode(', ', $this->repo->getTargetPackageTranslation()) . '</td></tr>';
        }

        if (!empty($this->repo->getTargetGpgCheck())) {
            echo "<tr><th>CHECK GPG SIGNATURES</th><td>";

            if ($this->repo->getTargetGpgCheck() == 'yes') {
                echo '<span><img src="assets/icons/greencircle.png" class="icon-small" /> Enabled</span>';
            }
            if ($this->repo->getTargetGpgCheck() == 'no') {
                echo '<span><img src="assets/icons/redcircle.png" class="icon-small" /> Disabled</span>';
            }
            echo "</td></tr>";
        }

        if (!empty($this->repo->getTargetGpgResign())) {
            echo '<tr><th>SIGN WITH GPG</th><td>';

            if ($this->repo->getTargetGpgResign() == "yes") {
                echo '<span><img src="assets/icons/greencircle.png" class="icon-small" /> Enabled</span>';
            }
            if ($this->repo->getTargetGpgResign() == "no") {
                echo '<span><img src="assets/icons/redcircle.png" class="icon-small" /> Disabled</span>';
            }
            echo '</tr>';
        }

        if ($this->operation->getAction() == 'update') {
            if (!empty($this->repo->getOnlySyncDifference())) {
                echo '<tr><th>ONLY SYNC THE DIFFERENCE</th><td>';

                if ($this->repo->getOnlySyncDifference() == "yes") {
                    echo '<span><img src="assets/icons/greencircle.png" class="icon-small" /> Enabled</span>';
                }
                if ($this->repo->getOnlySyncDifference() == "no") {
                    echo '<span><img src="assets/icons/redcircle.png" class="icon-small" /> Disabled</span>';
                }
                echo '</tr>';
            }
        }

        if (!empty($this->repo->getTargetGroup())) {
            echo '<tr><th>ADD TO GROUP</th><td><img src="assets/icons/folder.svg" class="icon" />' . $this->repo->getTargetGroup() . '</td></tr>';
        } ?>
    </table>
</div>