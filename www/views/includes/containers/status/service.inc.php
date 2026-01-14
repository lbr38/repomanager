<section id="service-units" class="section-main reloadable-container" container="status/service">
    <h3>SERVICE UNITS</h3>

    <div class="div-generic-blue grid grid-rfr-1-4 row-gap-30 column-gap-20">
        <div>
            <div class="flex align-item-center column-gap-5">
                <h6 class="margin-top-0">MAIN SERVICE</h6>
                <img src="/assets/icons/info.svg" class="icon-lowopacity icon-small icon-np tooltip" tooltip="This is the main service that controls all service units. If this service is not running, none of the service units will be running." />
            </div>

            <div class="flex align-item-center column-gap-5">
                <?php
                if (\Controllers\Service\Service::isRunning()) {
                    echo '<img src="/assets/icons/check.svg" class="icon-np" />';
                    echo '<p>Running</p>';
                } else {
                    echo '<img src="/assets/icons/warning.svg" class="icon-np" />';
                    echo '<p>Service is not running</p>';
                } ?>
            </div>
        </div>

        <?php
        foreach ($units as $name => $properties) : ?>
            <div>
                <div class="flex align-item-center column-gap-5">
                    <h6 class="margin-top-0"><?= strtoupper($properties['title']) ?></h6>
                    <img src="/assets/icons/info.svg" class="icon-lowopacity icon-small icon-np unit-tooltip" unit="<?= $name ?>" description="<?= $properties['description'] ?>" frequency="<?= $properties['frequency'] ?>" day="<?= $properties['day'] ?? '' ?>" time="<?= $properties['time'] ?? '' ?>" />
                </div>

                <div class="flex align-item-center column-gap-5">
                    <?php
                    if (\Controllers\Service\Service::isRunning($name)) {
                        echo '<img src="/assets/icons/check.svg" class="icon-np" />';
                        echo '<p title="This service unit is currently running">Running</p>';
                    } else {
                        echo '<p class="note" title="This service unit is currently not running">Not running</p>';
                    } ?>
                </div>

                <?php
                // Get logs for this unit
                $logDir = $properties['log-dir'] ?? $name;
                $logs   = glob(SERVICE_LOGS_DIR . '/' . $logDir . '/*.log');
                rsort($logs);

                if (!empty($logs)) { ?>
                    <div class="grid grid-2-1 align-item-center column-gap-10 unit-logs-container">
                        <select unit="<?= $name ?>">
                            <?php
                            foreach ($logs as $log) {
                                $logFile = basename($log);
                                echo '<option value="' . $logFile . '">' . $logFile . '</option>';
                            } ?>
                        </select>
                        <p><span class="unit-log-view-btn btn-xsmall-green" unit="<?= $name ?>" title="View log">View</span></p>
                    </div>
                    <?php
                } ?>
            </div>
            <?php
        endforeach;

        unset($units, $name, $properties); ?>
    </div>
</section>
