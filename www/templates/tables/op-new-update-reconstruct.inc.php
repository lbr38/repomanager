
<h3><?= $title ?></h3>

<table class="op-table">

    <?php
    if ($this->op->getAction() != 'reconstruct') {
        if (!empty($this->source)) {
            echo '<tr><th>SOURCE REPO:</th><td>' . $this->source . '</td></tr>';
        }
    } ?>

    <tr>
        <th>REPO:</th>
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
        echo '<tr><th>DESCRIPTION:</th><td>' . $this->targetDescription . '</td></tr>';
    }

    echo '<tr><th>ARCHITECTURE:</th><td>' . implode(', ', $this->targetIncludeArch) . '</td></tr>';

    echo '<tr><th>INCLUDE PACKAGES SOURCES:</th><td>' . $this->targetIncludeSource . '</td></tr>';

    if (!empty($this->targetIncludeTranslation)) {
        echo '<tr><th>INCLUDE PACKAGES TRANSLATION:</th><td>' . implode(', ', $this->targetIncludeTranslation) . '</td></tr>';
    }

    if (!empty($this->targetGpgCheck)) {
        echo "<tr><th>GPG SIGNATURE CHECK:</th><td>";

        if ($this->targetGpgCheck == 'yes') {
            echo '<span><img src="resources/icons/greencircle.png" class="icon-small" /> Enabled</span>';
        }
        if ($this->targetGpgCheck == 'no') {
            echo '<span><img src="resources/icons/redcircle.png" class="icon-small" /> Disabled</span>';
        }
        echo "</td></tr>";
    }
    if (!empty($this->targetGpgResign)) {
        echo '<tr><th>GPG REPO SIGNATURE:</th><td>';

        if ($this->targetGpgResign == "yes") {
            echo '<span><img src="resources/icons/greencircle.png" class="icon-small" /> Enabled</span>';
        }
        if ($this->targetGpgResign == "no") {
            echo '<span><img src="resources/icons/redcircle.png" class="icon-small" /> Disabled</span>';
        }
        echo '</tr>';
    }
    if (!empty($this->targetGroup)) {
        echo '<tr><th>ADD TO GROUP:</th><td><img src="resources/icons/folder.png" class="icon" />' . $this->targetGroup . '</td></tr>';
    } ?>
</table>