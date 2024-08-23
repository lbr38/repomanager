<div class="reloadable-table flex" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <div class="flex-div-50">
        <p class="lowopacity-cst margin-bottom-15">Requests sent to the host</p>

        <?php
        if (empty($reloadableTableContent)) :
            echo '<p>Nothing for now!</p>';
        endif;

        if (!empty($reloadableTableContent)) :
            foreach ($reloadableTableContent as $item) :
                $detailsDiv = false;
                $requestInfo = '';

                /**
                 *  Set items content
                 */
                if ($item['Status'] == 'new') {
                    $statusColor = 'yellow';
                    $status = 'Pending';
                }
                if ($item['Status'] == 'sent') {
                    $statusColor = 'yellow';
                    $status = 'Sent';
                }
                if ($item['Status'] == 'received') {
                    $statusColor = 'yellow';
                    $status = 'Received';
                }
                if ($item['Status'] == 'canceled') {
                    $statusColor = 'red';
                    $status = 'Canceled';
                }
                if ($item['Status'] == 'failed') {
                    $statusColor = 'red';
                    $status = 'Failed';
                }
                if ($item['Status'] == 'completed') {
                    $statusColor = 'green';
                    $status = 'Completed';
                }

                if ($item['Request'] == 'request-general-infos') {
                    $request = 'Requested general informations';
                    $requestTitle = 'Requested the host to send its general informations';
                }

                if ($item['Request'] == 'request-packages-infos') {
                    $request = 'Requested packages informations';
                    $requestTitle = 'Requested the host to send its packages informations';
                }

                if ($item['Request'] == 'update-all-packages') {
                    $request = 'Requested to update all packages';
                    $requestTitle = 'Requested the host to update all of its packages';
                }

                /**
                 *  If request ended with an error
                 */
                if (!empty($item['Info'])) {
                    /**
                     *  If the request was a packages update, retrieve more informations from the summary (number of packages updated)
                     */
                    if ($item['Request'] == 'update-all-packages' and !empty($item['Info_json'])) {
                        $summary = json_decode($item['Info_json'], true);

                        // If there was no packages to update
                        if ($summary['update']['status'] == 'nothing-to-do') {
                            $requestInfo = 'No packages to update';
                        }

                        // If there was packages to update, retrieve the number of packages updated
                        if ($summary['update']['status'] == 'done' or $summary['update']['status'] == 'failed') {
                            $successCount = $summary['update']['success']['count'];
                            $failedCount = $summary['update']['failed']['count'];

                            $requestInfo = $successCount . ' package(s) updated, ' . $failedCount . ' failed';
                        }
                    } else {
                        $requestInfo = $item['Info'];
                    }
                } ?>

                <div class="table-container bck-blue-alt">
                    <div>
                        <img class="icon-small" src="/assets/icons/<?= $statusColor ?>circle.png" title="<?= $status ?>">                    
                    </div>

                    <div>
                        <p><b><?= DateTime::createFromFormat('Y-m-d', $item['Date'])->format('d-m-Y') ?></b></p>
                        <p class="lowopacity-cst"><?= $item['Time']; ?>
                    </div>

                    <div>
                        <p title="<?= $requestTitle ?>"><?= $request ?></p>
                        <p class="lowopacity-cst"><?= $requestInfo ?></p>
                    </div>

                    <div class="flex align-item-center justify-end">
                        <?php
                        if ($item['Status'] == 'new') : ?>
                            <img class="icon-lowopacity cancel-request-btn" src="/assets/icons/delete.svg" request-id="<?= $item['Id'] ?>" title="Cancel request">
                            <?php
                        endif ?>
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

    <div class="flex-div-50">
        <div class="flex justify-center">
            <div class="flex-div-50">
                <p class="lowopacity-cst margin-bottom-15">Send a request</p>

                <div class="flex row-gap-15 flex-direction-column">
                    <button class="host-action-btn btn-large-blue" host-id="<?= $id ?>" action="request-general-infos" title="Request the host to send its general informations (OS, profile, agent status...)">Request general informations</button>
                    <button class="host-action-btn btn-large-blue" host-id="<?= $id ?>" action="request-packages-infos" title="Request the host to send its packages informations (available, installed, updated...).">Request packages informations</button>
                    <button class="host-action-btn btn-large-red" host-id="<?= $id ?>" action="update-all-packages" title="Request the host to update all packages">Update all packages</button>
                </div>
            </div>
        </div>
    </div>
</div>
