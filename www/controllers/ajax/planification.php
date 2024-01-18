<?php

/*
 *  Create a new plan
 */
if ($_POST['action'] == "newPlan") {
    $myplan = new \Controllers\Planification();

    try {
        $myplan->setAction($_POST['planAction']);
        if (!empty($_POST['day'])) {
            $myplan->setDay($_POST['day']);
        }
        if (!empty($_POST['date'])) {
            $myplan->setDate($_POST['date']);
        }
        if (!empty($_POST['time'])) {
            $myplan->setTime($_POST['time']);
        }
        if (!empty($_POST['type'])) {
            $myplan->setType($_POST['type']);
        }
        if (!empty($_POST['frequency'])) {
            $myplan->setFrequency($_POST['frequency']);
        }
        if (!empty($_POST['mailRecipient'])) {
            $myplan->setMailRecipient($_POST['mailRecipient']);
        }
        if (!empty($_POST['reminder'])) {
            $myplan->setReminder($_POST['reminder']);
        }
        if (!empty($_POST['notificationOnError']) and $_POST['notificationOnError'] == "yes") {
            $myplan->setNotification('on-error', 'yes');
        } else {
            $myplan->setNotification('on-error', 'no');
        }
        if (!empty($_POST['notificationOnSuccess']) and $_POST['notificationOnSuccess'] == "yes") {
            $myplan->setNotification('on-success', 'yes');
        } else {
            $myplan->setNotification('on-success', 'no');
        }

        /**
         *  If plan action is 'update' then set GPG params
         */
        if ($_POST['planAction'] == 'update') {
            if (!empty($_POST['gpgCheck']) and $_POST['gpgCheck'] == "yes") {
                $myplan->setTargetGpgCheck('yes');
            } else {
                $myplan->setTargetGpgCheck('no');
            }
            if (!empty($_POST['gpgResign']) and $_POST['gpgResign'] == "yes") {
                $myplan->setTargetGpgResign('yes');
            } else {
                $myplan->setTargetGpgResign('no');
            }
            if (!empty($_POST['onlySyncDifference']) and $_POST['onlySyncDifference'] == "yes") {
                $myplan->setOnlySyncDifference('yes');
            } else {
                $myplan->setOnlySyncDifference('no');
            }
        }

        /**
         *  Case it is a repo
         */
        if (!empty($_POST['snapId'])) {
            $myplan->setSnapId($_POST['snapId']);
        }

        /**
         *  Case it is a group of repos
         */
        if (!empty($_POST['groupId'])) {
            $myplan->setGroupId($_POST['groupId']);
        }

        /**
         *  Case a target env has been specified
         */
        if (!empty($_POST['targetEnv'])) {
            $myplan->setTargetEnv($_POST['targetEnv']);
        }

        $myplan->new();
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, "Scheduled task has been created");
}

/**
 *  Delete a plan
 */
if ($_POST['action'] == "deletePlan" and !empty($_POST['id'])) {
    $myplan = new \Controllers\Planification();

    try {
        $myplan->remove($_POST['id']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, "Scheduled task has been deleted");
}

/**
 *  Disable recurrent plan
 */
if ($_POST['action'] == "disablePlan" and !empty($_POST['id'])) {
    $myplan = new \Controllers\Planification();

    try {
        $myplan->suspend($_POST['id']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, "Recurrent task has been disabled");
}

/**
 *  Enable recurrent plan
 */
if ($_POST['action'] == "enablePlan" and !empty($_POST['id'])) {
    $myplan = new \Controllers\Planification();

    try {
        $myplan->enable($_POST['id']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, "Recurrent task has been enabled");
}

response(HTTP_BAD_REQUEST, 'Invalid action');
