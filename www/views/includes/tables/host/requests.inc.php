<div class="reloadable-table flex justify-space-between" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <div class="flex-div-50">
        <p class="lowopacity-cst margin-bottom-15">Requests sent to the host</p>

        <?php
        if (empty($reloadableTableContent)) :
            echo '<p>Nothing for now!</p>';
        endif;

        if (!empty($reloadableTableContent)) :
            foreach ($reloadableTableContent as $item) :
                $class = 'table-container bck-blue-alt';
                $request = '';
                $requestData = [];
                $requestDetails = null;
                $responseDetails = null;
                $successPackages = [];
                $failedPackages = [];

                /**
                 *  Retrieve and decode JSON data
                 */
                $requestJson = json_decode($item['Request'], true);

                /**
                 *  Request name
                 */
                $request = $requestJson['request'];

                /**
                 *  Request data
                 */
                if (isset($requestJson['data'])) {
                    $requestData = $requestJson['data'];
                }

                /**
                 *  Response data
                 */
                if (!empty($item['Response_json'])) {
                    $responseJson = json_decode($item['Response_json'], true);
                }

                /**
                 *  Request status
                 */
                if ($item['Status'] == 'new') {
                    $requestStatus = 'Pending';
                    $requestStatusIcon = 'pending';
                }
                if ($item['Status'] == 'sent') {
                    $requestStatus = 'Sent';
                    $requestStatusIcon = 'pending';
                }
                if ($item['Status'] == 'running') {
                    $requestStatus = 'Running';
                    $requestStatusIcon = 'loading.svg';
                }
                if ($item['Status'] == 'canceled') {
                    $requestStatus = 'Canceled';
                    $requestStatusIcon = 'crossmark';
                }
                if ($item['Status'] == 'failed') {
                    $requestStatus = 'Failed';
                    $requestStatusIcon = 'crossmark';
                }
                if ($item['Status'] == 'completed') {
                    $requestStatus = 'Completed';
                    $requestStatusIcon = 'checkmark';
                }

                /**
                 *  Request title
                 */
                if ($request == 'request-general-infos') {
                    $request = 'Requested general informations';
                    $requestTitle = 'Requested the host to send its general informations';
                }

                if ($request == 'request-packages-infos') {
                    $request = 'Requested packages informations';
                    $requestTitle = 'Requested the host to send its packages informations';
                }

                if ($request == 'request-specific-packages-installation') {
                    $request = 'Request to install a list of package(s)';
                    $requestTitle = 'Requested the host to install a list of package(s)';

                    if (!empty($requestJson['packages'])) {
                        $requestDetails = count($requestJson['packages']) . ' package(s) to install';
                    }

                    /**
                     *  If there was packages to update, retrieve the number of packages updated
                     */
                    if (!empty($responseJson)) {
                        if ($responseJson['update']['status'] == 'done' or $responseJson['update']['status'] == 'failed') {
                            $successCount = $responseJson['update']['success']['count'];
                            $failedCount  = $responseJson['update']['failed']['count'];

                            // If the update was successful
                            if ($responseJson['update']['status'] == 'done') {
                                $requestStatus = 'Successful';
                                $requestStatusIcon = 'checkmark';
                            }

                            // If the update failed
                            if ($responseJson['update']['status'] == 'failed') {
                                $requestStatus = 'Failed with errors';
                                $requestStatusIcon = 'crossmark';
                            }

                            // Build a short info message
                            $responseDetails .= $successCount . ' package(s) updated, ' . $failedCount . ' failed';

                            // Retrieve the list of packages updated
                            $successPackages = $responseJson['update']['success']['packages'];

                            // Retrieve the list of packages failed
                            $failedPackages = $responseJson['update']['failed']['packages'];

                            if (!empty($successPackages) or !empty($failedPackages)) {
                                $class .= ' request-show-more-info-btn pointer';
                            }
                        }
                    }
                }

                if ($request == 'update-all-packages') {
                    $request = 'Requested to update all packages';
                    $requestTitle = 'Requested the host to update all of its packages';

                    if (!empty($responseJson)) {
                        /**
                         *  If there was no packages to update
                         */
                        if ($responseJson['update']['status'] == 'nothing-to-do') {
                            $responseDetails = 'No packages to update';
                        }

                        /**
                         *  If there was packages to update, retrieve the number of packages updated
                         */
                        if ($responseJson['update']['status'] == 'done' or $responseJson['update']['status'] == 'failed') {
                            $successCount = $responseJson['update']['success']['count'];
                            $failedCount  = $responseJson['update']['failed']['count'];

                            // If the update was successful
                            if ($responseJson['update']['status'] == 'done') {
                                $requestStatus = 'Successful';
                                $requestStatusIcon = 'checkmark';
                            }

                            // If the update failed
                            if ($responseJson['update']['status'] == 'failed') {
                                $requestStatus = 'Failed with errors';
                                $requestStatusIcon = 'crossmark';
                            }

                            // Build a short info message
                            $responseDetails .= $successCount . ' package(s) updated, ' . $failedCount . ' failed';

                            // Retrieve the list of packages updated
                            $successPackages = $responseJson['update']['success']['packages'];

                            // Retrieve the list of packages failed
                            $failedPackages = $responseJson['update']['failed']['packages'];

                            if (!empty($successPackages) or !empty($failedPackages)) {
                                $class .= ' request-show-more-info-btn pointer';
                            }
                        }
                    }
                }

                // If the request was a disconnect, skip it
                if ($request == 'disconnect') {
                    continue;
                } ?>

                <div class="<?= $class ?>" request-id="<?= $item['Id'] ?>">
                    <div>
                        <?php
                        if (!empty($requestStatusIcon)) {
                            if (str_ends_with($requestStatusIcon, '.svg')) {
                                echo '<img class="icon" src="/assets/icons/' . $requestStatusIcon . '" title="' . $requestStatus . '">';
                            } else {
                                echo '<span class="' . $requestStatusIcon . '" title="' . $requestStatus . '"></span> ';
                            }
                        } ?>
                    </div>

                    <div>
                        <p><b><?= DateTime::createFromFormat('Y-m-d', $item['Date'])->format('d-m-Y') ?></b></p>
                        <p class="lowopacity-cst"><?= $item['Time']; ?>
                    </div>

                    <div>
                        <p title="<?= $requestTitle ?>"><?= $request ?></p>

                        <p class="lowopacity-cst">
                            <?php
                            echo $requestStatus;

                            if (!empty($responseDetails)) {
                                echo ' - ' . $responseDetails;
                            } ?>
                        </p>
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
