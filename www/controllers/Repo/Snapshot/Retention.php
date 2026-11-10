<?php

namespace Controllers\Repo\Snapshot;

use Exception;

/**
 *  Per repository (or mirror) snapshots retention
 *  Stored in the Retention column of the repos and mirrors tables:
 *  - NULL: the global RETENTION setting is used
 *  - KEEP_ALL: unused snapshots are never deleted
 *  - 0 or more: number of unused snapshots to keep
 */
class Retention
{
    public const KEEP_ALL = -1;

    /**
     *  Check and convert the retention values sent by a form
     *  Empty value means the global setting
     */
    public static function fromInput(mixed $value, mixed $keepAll = 'false'): int|null
    {
        if ($keepAll === 'true') {
            return self::KEEP_ALL;
        }

        if ($value === null or $value === '') {
            return null;
        }

        if (!is_numeric($value) or (int) $value != $value or (int) $value < 0) {
            throw new Exception('Invalid retention value');
        }

        return (int) $value;
    }

    /**
     *  Return the number of unused snapshots to keep, or null if all of them must be kept
     */
    public static function get(int|string|null $retention): int|null
    {
        if ($retention === null or $retention === '') {
            return RETENTION;
        }

        if ((int) $retention == self::KEEP_ALL) {
            return null;
        }

        return (int) $retention;
    }
}
