<?php

namespace Controllers\Utils;

use DateTime;
use Exception;

class Cron
{
    /**
     *  Validate a cron expression. Throws an exception if the expression is invalid.
     */
    public static function validate(string $expression): void
    {
        self::parse($expression);
    }

    /**
     *  Return true if the given DateTime matches the cron expression, false otherwise.
     */
    public static function matches(string $expression, DateTime $dateTime): bool
    {
        $parsed = self::parse($expression);
        $minute = (int)$dateTime->format('i');
        $hour = (int)$dateTime->format('H');
        $dayOfMonth = (int)$dateTime->format('j');
        $month = (int)$dateTime->format('n');
        $dayOfWeek = (int)$dateTime->format('w'); // 0 (Sun) - 6 (Sat)

        if (!in_array($minute, $parsed['minute']['values'], true)) {
            return false;
        }

        if (!in_array($hour, $parsed['hour']['values'], true)) {
            return false;
        }

        if (!in_array($month, $parsed['month']['values'], true)) {
            return false;
        }

        $domMatch = in_array($dayOfMonth, $parsed['day_of_month']['values'], true);
        $dowMatch = in_array($dayOfWeek, $parsed['day_of_week']['values'], true);

        if (!$parsed['day_of_month']['is_star'] && !$parsed['day_of_week']['is_star']) {
            return $domMatch || $dowMatch;
        }

        return $domMatch && $dowMatch;
    }

    /**
     *  Return the next occurrence of the cron expression after the given DateTime.
     *  Return null if no occurrence is found within the next $maxMinutes minutes (default: 1 year).
     */
    public static function nextOccurrence(string $expression, DateTime $from, int $maxMinutes = 527040): DateTime|null
    {
        $cursor = clone $from;
        $cursor->setTime((int)$cursor->format('H'), (int)$cursor->format('i'), 0);

        if ((int)$from->format('s') > 0) {
            $cursor->modify('+1 minute');
        }

        for ($i = 0; $i < $maxMinutes; $i++) {
            if (self::matches($expression, $cursor)) {
                return clone $cursor;
            }

            $cursor->modify('+1 minute');
        }

        return null;
    }

    /**
     *  Parse a cron expression and return an array with the allowed values for each field and whether the field is a star (*).
     */
    private static function parse(string $expression): array
    {
        $expression = trim($expression);

        if ($expression === '') {
            throw new Exception('Cron expression must be specified');
        }

        $parts = preg_split('/\s+/', $expression);

        if (count($parts) !== 5) {
            throw new Exception('Cron expression must have 5 fields');
        }

        [$minField, $hourField, $domField, $monthField, $dowField] = $parts;

        $minute = self::parseField($minField, 0, 59);
        $hour = self::parseField($hourField, 0, 23);
        $dayOfMonth = self::parseField($domField, 1, 31);
        $month = self::parseField($monthField, 1, 12);
        $dayOfWeek = self::parseField($dowField, 0, 7, true);

        return [
            'minute' => $minute,
            'hour' => $hour,
            'day_of_month' => $dayOfMonth,
            'month' => $month,
            'day_of_week' => $dayOfWeek,
        ];
    }

    /**
     *  Parse a single field of a cron expression and return an array of allowed values and whether the field is a star (*).
     */
    private static function parseField(string $field, int $min, int $max, bool $dayOfWeek = false) : array
    {
        $field = trim($field);

        if ($field === '*') {
            return [
                'values' => range($min, $max),
                'is_star' => true,
            ];
        }

        $values = [];
        $parts = explode(',', $field);

        foreach ($parts as $part) {
            $part = trim($part);

            if ($part === '') {
                throw new Exception('Cron field cannot be empty');
            }

            if ($part === '*') {
                throw new Exception('Cron field cannot mix "*" with other values');
            }

            if (preg_match('/^\*\/(\d+)$/', $part, $matches)) {
                $step = (int)$matches[1];

                if ($step <= 0) {
                    throw new Exception('Cron step must be greater than 0');
                }

                for ($i = $min; $i <= $max; $i += $step) {
                    $values[] = $dayOfWeek && $i === 7 ? 0 : $i;
                }

                continue;
            }

            if (preg_match('/^(\d+)-(\d+)(?:\/(\d+))?$/', $part, $matches)) {
                $start = (int)$matches[1];
                $end = (int)$matches[2];
                $step = isset($matches[3]) ? (int)$matches[3] : 1;

                if ($step <= 0) {
                    throw new Exception('Cron step must be greater than 0');
                }

                if ($start < $min || $start > $max || $end < $min || $end > $max) {
                    throw new Exception('Cron field value is out of range');
                }

                if ($start > $end) {
                    throw new Exception('Cron field range is invalid');
                }

                for ($i = $start; $i <= $end; $i += $step) {
                    $values[] = $dayOfWeek && $i === 7 ? 0 : $i;
                }

                continue;
            }

            if (preg_match('/^\d+$/', $part)) {
                $value = (int)$part;

                if ($value < $min || $value > $max) {
                    throw new Exception('Cron field value is out of range');
                }

                $values[] = $dayOfWeek && $value === 7 ? 0 : $value;
                continue;
            }

            throw new Exception('Invalid cron field value');
        }

        $values = array_values(array_unique($values));
        sort($values);

        return [
            'values' => $values,
            'is_star' => false,
        ];
    }
}
