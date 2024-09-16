<div class="reloadable-table flex" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <div class="flex-div-50">
        <p class="lowopacity-cst margin-bottom-15">Requests sent to the host</p>

        <?php
        if (empty($reloadableTableContent)) :
            echo '<p>Nothing for now!</p>';
        endif;

        if (!empty($reloadableTableContent)) :
            foreach ($reloadableTableContent as $item) :
                $class = 'table-container bck-blue-alt';
                $requestInfo = '';
                $successPackages = [];
                $failedPackages = [];

                /**
                 *  Set items content
                 */
                if ($item['Status'] == 'new') {
                    $statusColor = 'yellow';
                    $requestStatus = 'Pending';
                }
                if ($item['Status'] == 'sent') {
                    $statusColor = 'yellow';
                    $requestStatus = 'Sent';
                }
                if ($item['Status'] == 'received') {
                    $statusColor = 'yellow';
                    $requestStatus = 'Received';
                }
                if ($item['Status'] == 'canceled') {
                    $statusColor = 'red';
                    $requestStatus = 'Canceled';
                }
                if ($item['Status'] == 'failed') {
                    $statusColor = 'red';
                    $requestStatus = 'Failed';
                }
                if ($item['Status'] == 'completed') {
                    $statusColor = 'green';
                    $requestStatus = 'Completed';
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

                // If the request was a disconnect, skip it
                if ($item['Request'] == 'disconnect') {
                    continue;
                }

                /**
                 *  If request ended with an error
                 */
                if (!empty($item['Info_json'])) {
                    /**
                     *  If the request was a packages update, retrieve more informations from the summary (number of packages updated)
                     */
                    if ($item['Request'] == 'update-all-packages' and !empty($item['Info_json'])) {
                        $infoJson = json_decode($item['Info_json'], true);

                        /**
                         *  If there was no packages to update
                         */
                        if ($infoJson['update']['status'] == 'nothing-to-do') {
                            $requestInfo = 'No packages to update';
                        }

                        /**
                         *  If there was packages to update, retrieve the number of packages updated
                         */
                        if ($infoJson['update']['status'] == 'done' or $infoJson['update']['status'] == 'failed') {
                            $successCount = $infoJson['update']['success']['count'];
                            $failedCount = $infoJson['update']['failed']['count'];

                            // If the update was successful
                            if ($infoJson['update']['status'] == 'done') {
                                $requestInfo = '<span class="greentext">✔</span> Successful - ';
                            }

                            // If the update failed
                            if ($infoJson['update']['status'] == 'failed') {
                                $requestInfo = '<span class="redtext">✕</span> Failed - ';
                                $statusColor = 'red';
                                $requestStatus = 'Request completed with errors';
                            }

                            // Build a short info message
                            $requestInfo .= $successCount . ' package(s) updated, ' . $failedCount . ' failed';

                            // Retrieve the list of packages updated
                            $successPackages = $infoJson['update']['success']['packages'];

                            // Retrieve the list of packages failed
                            $failedPackages = $infoJson['update']['failed']['packages'];
                        }
                    } else {
                        $requestInfo = $item['Info'];
                    }

                    if (!empty($successPackages) or !empty($failedPackages)) {
                        $class .= ' request-show-more-info-btn pointer';
                    }
                } ?>

                <div class="<?= $class ?>" request-id="<?= $item['Id'] ?>">
                    <div>
                        <img class="icon-small" src="/assets/icons/<?= $statusColor ?>circle.png" title="<?= $requestStatus ?>">                    
                    </div>

                    <div>
                        <p><b><?= DateTime::createFromFormat('Y-m-d', $item['Date'])->format('d-m-Y') ?></b></p>
                        <p class="lowopacity-cst"><?= $item['Time']; ?>
                    </div>

                    <div>
                        <p title="<?= $requestTitle ?>"><?= $request ?></p>
                        <?php
                        if (!empty($requestInfo)) {
                            echo '<p class="lowopacity-cst">' . $requestInfo . '</p>';
                        } else {
                            echo '<p class="lowopacity-cst">' . $requestStatus . '</p>';
                        } ?>
                    </div>

                    <div class="flex align-item-center justify-end column-gap-5">
                        <?php
                        if (file_exists(WS_REQUESTS_LOGS_DIR . '/request-' . $item['Id'] . '.log')) : ?>
                            <img class="icon-lowopacity request-show-log-btn" request-id="<?= $item['Id'] ?>" src="/assets/icons/file.svg" title="Show global log">
                            <?php
                        endif;

                        if ($item['Status'] == 'new') : ?>
                            <img class="icon-lowopacity cancel-request-btn" src="/assets/icons/delete.svg" request-id="<?= $item['Id'] ?>" title="Cancel request">
                            <?php
                        endif ?>
                    </div>
                </div>

                <?php
                /**
                 *  Print a div with the details of the packages updated or failed
                 */
                if (!empty($successPackages) or !empty($failedPackages)) : ?>
                    <div class="request-details detailsDiv hide margin-bottom-10" request-id="<?= $item['Id'] ?>">
                        <?php
                        /**
                         *  If there was packages updated
                         */
                        if (!empty($successPackages)) : ?>
                            <p class="label-green"><?= count($successPackages) ?> updated</p>

                            <div class="grid grid-3 row-gap-5 justify-space-between margin-top-10">
                                <?php
                                // Print each package with its version and log
                                foreach ($successPackages as $package => $details) : ?>
                                    <div class="flex align-item-center column-gap-5">
                                        <?= \Controllers\Common::printProductIcon($package) ?>
                                        <p class="wordbreakall copy"><?= $package ?></p>
                                    </div>

                                    <p class="wordbreakall copy"><?= $details['version'] ?></p>

                                    <p class="text-right">
                                        <?php
                                        if (!empty($details['log'])) : ?>
                                            <img class="icon-lowopacity request-show-package-log-btn" request-id="<?= $item['Id'] ?>" package="<?= $package ?>" status="success" src="/assets/icons/file.svg" title="Show package log" />
                                            <?php
                                        endif ?>
                                    </p>
                                    <?php
                                endforeach ?>
                            </div>
                            <?php
                        endif;

                        /**
                         *  If there was packages failed
                         */
                        if (!empty($failedPackages)) : ?>
                            <p class="label-red"><?= count($failedPackages) ?> failed</p>

                            <div class="grid grid-3 row-gap-5 justify-space-between margin-top-10">
                                <?php
                                // Print each package with its version and log
                                foreach ($failedPackages as $package => $details) : ?>
                                    <div class="flex align-item-center column-gap-5">
                                        <?= \Controllers\Common::printProductIcon($package) ?>
                                        <p class="wordbreakall copy"><?= $package ?></p>
                                    </div>

                                    <p class="wordbreakall copy"><?= $details['version'] ?></p>

                                    <p class="text-right">
                                        <?php
                                        if (!empty($details['log'])) : ?>
                                            <img class="icon-lowopacity request-show-package-log-btn" request-id="<?= $item['Id'] ?>" package="<?= $package ?>" status="failed" src="/assets/icons/file.svg" title="Show package log" />
                                            <?php
                                        endif ?>
                                    </p>
                                    <?php
                                endforeach ?>
                            </div>
                            <?php
                        endif ?>
                    </div>
                    <?php
                endif;
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
