<div id="log-op-title" class="div-generic-blue">
    <h3>DELETE REPOSITORY SNAPSHOT</h3>
</div>

<div class="div-generic-blue">
    <table class="op-table">
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
        <tr>
            <th>DATE</th>
            <td><span class="label-black"><?= $this->repo->getDateFormatted() ?></span></td>
        </tr>
    </table>
</div>