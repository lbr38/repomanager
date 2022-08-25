<?php
/**
 *  If an update file log is found, display error or success message
 */
if (file_exists(UPDATE_ERROR_LOG) or file_exists(UPDATE_SUCCESS_LOG)) :
    /**
     *  Default messages in case they can't be etrieved from log files
     */
    $updateLogMessage = 'Cannot retrieve update log message.';
    $updateVersion = 'unknow';

    /**
     *  Get update success log if there are
     */
    if (file_exists(UPDATE_SUCCESS_LOG)) {
        $updateLogContent = json_decode(file_get_contents(UPDATE_SUCCESS_LOG), true);
        $updateTitle = 'UPDATE SUCCESS';
    }

    /**
     *  Get update error log if there are
     */
    if (file_exists(UPDATE_ERROR_LOG)) {
        $updateLogContent = json_decode(file_get_contents(UPDATE_ERROR_LOG), true);
        $updateTitle = 'UPDATE ERROR';
    }

    /**
     *  Get release version and update message from log content
     */
    if (!empty($updateLogContent['Message'])) {
        $updateLogMessage = $updateLogContent['Message'];
    }
    if (!empty($updateLogContent['Version'])) {
        $updateVersion = $updateLogContent['Version'];
    } ?>

    <div id="update-log-container">
        <div id="update-log">

            <h3>REPOMANAGER <?= $updateTitle ?></h3>

            <p><?= $updateLogMessage ?></p>

            <br>
            <p class="lowopacity">From release version: <?= VERSION ?></p>
            <p class="lowopacity">To target release version: <?= $updateVersion ?></p>
            <br>

            <button id="update-continue-btn" class="btn-medium-blue">Continue</button>
        </div>
    </div>
    <?php
endif;