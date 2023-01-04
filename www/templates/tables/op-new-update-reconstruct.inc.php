<div id="log-op-title" class="div-generic-blue">
    <h3><?= $title ?></h3>
</div>

<div class="div-generic-blue">
    <table class="op-table">
        <?php
        if ($this->op->getAction() != 'reconstruct') {
            if (!empty($this->source)) {
                echo '<tr><th>SOURCE REPO</th><td><span class="label-white">' . $this->source . '</span></td></tr>';
            }
        } ?>

        <tr>
            <th>REPO</th>
            <td>
                <span class="label-white">
                <?php
                if (!empty($this->dist) and !empty($this->section)) {
                    echo $this->name . ' ❯ ' . $this->dist . ' ❯ ' . $this->section;
                } else {
                    echo $this->name;
                } ?>
                </span>
            </td>
        </tr>

        <?php
        if (!empty($this->targetDescription)) {
            echo '<tr><th>DESCRIPTION</th><td>' . $this->targetDescription . '</td></tr>';
        }

        if (!empty($this->targetArch)) {
            echo '<tr><th>ARCHITECTURE</th><td>' . implode(', ', $this->targetArch) . '</td></tr>';
        }

        if (!empty($this->targetSourcePackage)) {
            echo '<tr><th>INCLUDE SOURCES PACKAGES</th><td>' . $this->targetSourcePackage . '</td></tr>';
        }

        if (!empty($this->targetPackageTranslation)) {
            echo '<tr><th>INCLUDE PACKAGES TRANSLATION</th><td>' . implode(', ', $this->targetPackageTranslation) . '</td></tr>';
        }

        if (!empty($this->targetGpgCheck)) {
            echo "<tr><th>CHECK GPG SIGNATURES</th><td>";

            if ($this->targetGpgCheck == 'yes') {
                echo '<span><img src="resources/icons/greencircle.png" class="icon-small" /> Enabled</span>';
            }
            if ($this->targetGpgCheck == 'no') {
                echo '<span><img src="resources/icons/redcircle.png" class="icon-small" /> Disabled</span>';
            }
            echo "</td></tr>";
        }
        if (!empty($this->targetGpgResign)) {
            echo '<tr><th>SIGN WITH GPG</th><td>';

            if ($this->targetGpgResign == "yes") {
                echo '<span><img src="resources/icons/greencircle.png" class="icon-small" /> Enabled</span>';
            }
            if ($this->targetGpgResign == "no") {
                echo '<span><img src="resources/icons/redcircle.png" class="icon-small" /> Disabled</span>';
            }
            echo '</tr>';
        }
        if (!empty($this->onlySyncDifference)) {
            echo '<tr><th>ONLY SYNC THE DIFFERENCE</th><td>';

            if ($this->onlySyncDifference == "yes") {
                echo '<span><img src="resources/icons/greencircle.png" class="icon-small" /> Enabled</span>';
            }
            if ($this->onlySyncDifference == "no") {
                echo '<span><img src="resources/icons/redcircle.png" class="icon-small" /> Disabled</span>';
            }
            echo '</tr>';
        }
        if (!empty($this->targetGroup)) {
            echo '<tr><th>ADD TO GROUP</th><td><img src="resources/icons/folder.svg" class="icon" />' . $this->targetGroup . '</td></tr>';
        } ?>
    </table>
</div>