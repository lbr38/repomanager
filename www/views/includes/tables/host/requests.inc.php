<div class="reloadable-table" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
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
            $responseJson = null;
            $successPackages = [];
            $failedPackages = [];
            $dryRun = null;
            $ignoreExclusions = null;
            $fullUpgrade = null;
            $keepConfigFiles = null;

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
             *  Update params
             */
            if (!empty($requestData['update-params'])) {
                // Retrieve dry-run value
                if (!empty($requestData['update-params']['dry-run'])) {
                    $dryRun = \Controllers\Common::toBool($requestData['update-params']['dry-run']);
                }

                // Retrieve ignore-exclusions value
                if (!empty($requestData['update-params']['ignore-exclusions'])) {
                    $ignoreExclusions = \Controllers\Common::toBool($requestData['update-params']['ignore-exclusions']);
                }

                // Retrieve full-upgrade value
                if (!empty($requestData['update-params']['full-upgrade'])) {
                    $fullUpgrade = \Controllers\Common::toBool($requestData['update-params']['full-upgrade']);
                }

                // Retrieve keep-config-files value
                if (!empty($requestData['update-params']['keep-config-files'])) {
                    $keepConfigFiles = \Controllers\Common::toBool($requestData['update-params']['keep-config-files']);
                }
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
                $requestStatusIcon = 'pending.svg';
            }
            if ($item['Status'] == 'sent') {
                $requestStatus = 'Sent';
                $requestStatusIcon = 'pending.svg';
            }
            if ($item['Status'] == 'running') {
                $requestStatus = 'Running';
                $requestStatusIcon = 'loading.svg';
            }
            if ($item['Status'] == 'canceled') {
                $requestStatus = 'Canceled';
                $requestStatusIcon = 'warning-red.svg';
            }
            if ($item['Status'] == 'failed') {
                $requestStatus = 'Failed';
                $requestStatusIcon = 'error.svg';
            }
            if ($item['Status'] == 'completed') {
                $requestStatus = 'Completed';
                $requestStatusIcon = 'check.svg';
            }

            /**
             *  Request title
             */
            if ($request == 'request-general-infos') {
                $requestString = 'Requested general informations';
                $requestTitle = 'Requested the host to send its general informations';
            }

            if ($request == 'request-packages-infos') {
                $requestString = 'Requested packages informations';
                $requestTitle = 'Requested the host to send its packages informations';
            }

            if ($request == 'request-packages-update') {
                $requestString = 'Request to install a list of package(s)';
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
                            $requestStatusIcon = 'check.svg';
                        }

                        // If the update failed
                        if ($responseJson['update']['status'] == 'failed') {
                            $requestStatus = 'Failed with errors';
                            $requestStatusIcon = 'error.svg';
                        }

                        // If there was packages updated AND packages failed
                        if ($successCount >= 1 and $failedCount >= 1) {
                            $requestStatus = 'Partial success';
                            $requestStatusIcon = 'warning.svg';
                        }

                        // If there was no packages updated AND packages failed
                        if ($successCount == 0 and $failedCount >= 1) {
                            $requestStatus = 'Failed';
                            $requestStatusIcon = 'error.svg';
                        }

                        // Build a short info message
                        $responseDetails .= $successCount . ' package(s) updated, ' . $failedCount . ' failed';

                        // Retrieve the list of packages updated
                        $successPackages = $responseJson['update']['success']['packages'];

                        // Retrieve the list of packages failed
                        $failedPackages = $responseJson['update']['failed']['packages'];
                    }
                }
            }

            if ($request == 'request-all-packages-update') {
                $requestString = 'Requested to update all packages';
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
                            $requestStatusIcon = 'check.svg';
                        }

                        // If the update failed
                        if ($responseJson['update']['status'] == 'failed') {
                            $requestStatus = 'Failed with errors';
                            $requestStatusIcon = 'error.svg';
                        }

                        // Build a short info message
                        $responseDetails .= $successCount . ' package(s) updated, ' . $failedCount . ' failed';

                        // Retrieve the list of packages updated
                        $successPackages = $responseJson['update']['success']['packages'];

                        // Retrieve the list of packages failed
                        $failedPackages = $responseJson['update']['failed']['packages'];
                    }
                }
            }

            if ($request == 'request-packages-update' or $request == 'request-all-packages-update') {
                $class .= ' request-show-more-info-btn pointer';
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
                    <p title="<?= $requestTitle ?>"><?= $requestString ?></p>

                    <p class="lowopacity-cst">
                        <?php
                        echo $requestStatus;

                        if (!empty($responseDetails)) {
                            echo ' - ' . $responseDetails;
                        } ?>
                    </p>
                </div>

                <div class="flex align-item-center justify-end column-gap-15">
                    <?php
                    if (!empty($dryRun) and $dryRun) {
                        echo '<img class="icon-np lowopacity-cst" src="/assets/icons/dryrun.svg" title="Task as been executed in dry-run mode">';
                    }

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

            <div class="request-details detailsDiv hide margin-bottom-10" request-id="<?= $item['Id'] ?>">
                <?php
                /**
                 *  If there are update params
                 */
                if (!empty($requestData['update-params'])) : ?>
                    <div class="grid grid-4 column-gap-15 row-gap-15 justify-space-between">
                        <div>
                            <h6 class="margin-top-0">DRY RUN</h6>
                            <?php
                            if (isset($dryRun)) {
                                if ($dryRun) {
                                    echo '<p>Enabled</p>';
                                } else {
                                    echo '<p>Disabled</p>';
                                }
                            } ?>
                        </div>

                        <div>
                            <h6 class="margin-top-0">IGNORE EXCLUSIONS</h6>
                            <?php
                            if (isset($ignoreExclusions)) {
                                if ($ignoreExclusions) {
                                    echo '<p>Enabled</p>';
                                } else {
                                    echo '<p>Disabled</p>';
                                }
                            } ?>
                        </div>

                        <div>
                            <h6 class="margin-top-0">FULL UPGRADE</h6>
                            <?php
                            if (isset($fullUpgrade)) {
                                if ($fullUpgrade) {
                                    echo '<p>Enabled</p>';
                                } else {
                                    echo '<p>Disabled</p>';
                                }
                            } ?>
                        </div>

                        <div>
                            <h6 class="margin-top-0">KEEP CONFIG FILES</h6>
                            <?php
                            if (isset($keepConfigFiles)) {
                                if ($keepConfigFiles) {
                                    echo '<p>Enabled</p>';
                                } else {
                                    echo '<p>Disabled</p>';
                                }
                            } ?>
                        </div>
                    </div>
                    <br><br>
                    <?php
                endif;

                /**
                 *  If there was packages updated
                 */
                if (!empty($successPackages)) : ?>
                    <p class="label-green"><?= count($successPackages) ?> updated</p>

                    <div class="grid grid-3 row-gap-5 justify-space-between margin-top-10">
                        <?php
                        // Print each package with its version and log
                        foreach ($successPackages as $package => $details) : ?>
                            <div class="flex align-flex-start column-gap-5">
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
                            <div class="flex align-flex-start column-gap-5">
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
        endforeach; ?>
        
        <div class="flex justify-end">
            <?php \Controllers\Layout\Table\Render::paginationBtn($reloadableTableCurrentPage, $reloadableTableTotalPages); ?>
        </div>
        <?php
    endif ?>
</div>
