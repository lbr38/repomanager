<div class="reloadable-table" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <?php
    if (!empty($reloadableTableContent)) :
        foreach ($reloadableTableContent as $item) : ?>
            <div class="table-container bck-blue-alt">
                <div>
                    <?php
                    if ($item['Status'] == 'new') {
                        echo '<img class="icon-small" src="/assets/icons/yellowcircle.png" title="Pending">';
                    }

                    if ($item['Status'] == 'sent') {
                        echo '<img class="icon-small" src="/assets/icons/yellowcircle.png" title="Sent">';
                    }

                    if ($item['Status'] == 'received') {
                        echo '<img class="icon-small" src="/assets/icons/yellowcircle.png" title="Received">';
                    }

                    if ($item['Status'] == 'failed') {
                        echo '<img class="icon-small" src="/assets/icons/redcircle.png" title="Failed">';
                    }

                    if ($item['Status'] == 'completed') {
                        echo '<img class="icon-small" src="/assets/icons/greencircle.png" title="Completed">';
                    } ?>
                </div>

                <div>
                    <p><b><?= DateTime::createFromFormat('Y-m-d', $item['Date'])->format('d-m-Y') ?></b></p>
                    <p class="lowopacity-cst"><?= $item['Time']; ?>
                </div>

                <div>
                    <?php
                    if ($item['Request'] == 'general-status-update') {
                        echo '<p title="Requested the host to send its general informations">General informations</p>';
                    }

                    if ($item['Request'] == 'packages-status-update') {
                        echo '<p title="Requested the host to send its packages informations">Packages informations</p>';
                    }

                    if ($item['Request'] == 'update') {
                        echo '<p title="Requested the host to update all of its packages">Update all packages</p>';
                    }

                    /**
                     *  If request ended with an error
                     */
                    if (!empty($item['Info'])) {
                        echo '<p class="lowopacity-cst">' . $item['Info'] . '</p>';
                    } ?>
                </div>
            </div>
            <?php
        endforeach; ?>
        
        <div class="flex justify-end">
            <?php \Controllers\Layout\Table\Render::paginationBtn($reloadableTableCurrentPage, $reloadableTableTotalPages); ?>
        </div>

        <?php
    endif ?>
</div>
