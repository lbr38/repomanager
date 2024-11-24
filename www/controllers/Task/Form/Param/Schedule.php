<?php

namespace Controllers\Task\Form\Param;

use Exception;
use DateTime;

class Schedule
{
    public static function check(array $scheduleParams) : void
    {
        /**
         *  Valid values for recurring schedule parameters
         */
        $validDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $validDaysPosition = ['first', 'second', 'third', 'last'];

        if (empty($scheduleParams)) {
            throw new Exception('Schedule parameters are empty');
        }

        /**
         *  Quit if scheduled is false
         */
        if ($scheduleParams['scheduled'] == 'false') {
            return;
        }

        /**
         *  If schedule type is 'unique'
         */
        if ($scheduleParams['schedule-type'] == 'unique') {
            /**
             *  Check that schedule date is set and valid
             */
            self::checkDate($scheduleParams['schedule-date']);

            /**
             *  Check that schedule time is set and valid
             */
            self::checkTime($scheduleParams['schedule-time']);

            /**
             *  Check that date and time are not in the past
             */
            if (strtotime($scheduleParams['schedule-date'] . ' ' . $scheduleParams['schedule-time']) < strtotime(date('Y-m-d H:i'))) {
                throw new Exception('You cannot schedule a task in the past');
            }
        }

        /**
         *  If schedule type is 'recurring'
         */
        if ($scheduleParams['schedule-type'] == 'recurring') {
            /**
             *  Check that schedule frequency is set and valid
             */
            self::checkFrequency($scheduleParams['schedule-frequency']);

            /**
             *  If schedule frequency is 'daily'
             *  Check that schedule time is set and valid
             */
            if ($scheduleParams['schedule-frequency'] == 'daily') {
                self::checkTime($scheduleParams['schedule-time']);
            }

            /**
             *  If schedule frequency is 'weekly'
             *  Check that schedule day and time is set and valid
             */
            if ($scheduleParams['schedule-frequency'] == 'weekly') {
                self::checkDay($scheduleParams['schedule-day']);
                self::checkTime($scheduleParams['schedule-time']);
            }

            /**
             *  If schedule frequency is 'monthly'
             */
            if ($scheduleParams['schedule-frequency'] == 'monthly') {
                /**
                 *  Check that schedule day position is set and valid
                 */
                if (empty($scheduleParams['schedule-monthly-day-position'])) {
                    throw new Exception('Schedule monthly day position must be specified');
                }

                if (!in_array($scheduleParams['schedule-monthly-day-position'], $validDaysPosition)) {
                    throw new Exception('Invalid schedule monthly day position');
                }

                /**
                 *  Check that schedule day is set and valid
                 */
                if (empty($scheduleParams['schedule-monthly-day'])) {
                    throw new Exception('Schedule monthly day must be specified');
                }

                if (!in_array($scheduleParams['schedule-monthly-day'], $validDays)) {
                    throw new Exception('Invalid schedule monthly day');
                }

                /**
                 *  Check that schedule time is set and valid
                 */
                self::checkTime($scheduleParams['schedule-time']);
            }
        }

        /**
         *  Check that schedule notify on error and success is set
         */
        self::checkNotifyOnError($scheduleParams['schedule-notify-error']);
        self::checkNotifyOnSuccess($scheduleParams['schedule-notify-success']);

        /**
         *  Check if a schedule reminder is set and is valid
         */
        self::checkReminder($scheduleParams['schedule-reminder']);

        /**
         *  If a notify or a reminder is set, check that the schedule recipient is set
         */
        if ($scheduleParams['schedule-notify-error'] == 'true' || $scheduleParams['schedule-notify-success'] == 'true' || !empty($scheduleParams['schedule-reminder'])) {
            if (empty($scheduleParams['schedule-recipient'])) {
                throw new Exception('A recipient must be specified');
            }
        }

        /**
         *  If a schedule recipient is set, check that it is valid
         */
        if (!empty($scheduleParams['schedule-recipient'])) {
            self::checkRecipient($scheduleParams['schedule-recipient']);
        }
    }

    private static function checkDate(string $date) : void
    {
        if (empty($date)) {
            throw new Exception('Schedule date must be specified');
        }

        if (!DateTime::createFromFormat('Y-m-d', $date)) {
            throw new Exception('Invalid schedule date format');
        }
    }

    private static function checkTime(string $time) : void
    {
        if (empty($time)) {
            throw new Exception('Schedule time must be specified');
        }

        if (!DateTime::createFromFormat('H:i', $time)) {
            throw new Exception('Invalid schedule time format');
        }
    }

    private static function checkDay(array $days) : void
    {
        if (empty($days)) {
            throw new Exception('Schedule day must be specified');
        }

        foreach ($days as $day) {
            if (!in_array($day, ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])) {
                throw new Exception('Invalid schedule day');
            }
        }
    }

    private static function checkFrequency(string $frequency) : void
    {
        if (empty($frequency)) {
            throw new Exception('Schedule frequency must be specified');
        }

        if (!in_array($frequency, ['hourly', 'daily', 'weekly', 'monthly'])) {
            throw new Exception('Invalid schedule frequency ' . $frequency);
        }
    }

    private static function checkNotifyOnError(string $notify) : void
    {
        if (empty($notify)) {
            throw new Exception('Notify on error must be specified');
        }

        if (!in_array($notify, ['true', 'false'])) {
            throw new Exception('Invalid notify on error value');
        }
    }

    private static function checkNotifyOnSuccess(string $notify) : void
    {
        if (empty($notify)) {
            throw new Exception('Notify on success must be specified');
        }

        if (!in_array($notify, ['true', 'false'])) {
            throw new Exception('Invalid notify on success value');
        }
    }

    private static function checkReminder(array $reminder) : void
    {
        if (empty($reminder)) {
            return;
        }

        foreach ($reminder as $remind) {
            if (!is_numeric($remind)) {
                throw new Exception('Invalid reminder value');
            }

            if ($remind < 1) {
                throw new Exception('Reminder value must be greater than 0');
            }
        }
    }

    private static function checkRecipient(array $recipients) : void
    {
        if (empty($recipients)) {
            throw new Exception('Recipient(s) email must be specified');
        }

        foreach ($recipients as $recipient) {
            if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid recipient email');
            }
        }
    }
}
