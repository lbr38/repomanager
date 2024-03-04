<div id="log-op-title" class="div-generic-blue">
    <h3>REMOVE ENVIRONMENT</h3>
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
            <th>ENVIRONNEMENT</th>
            <td><?=\Controllers\Common::envtag($this->repo->getEnv())?></td>
        </tr>
    </table>
</div>