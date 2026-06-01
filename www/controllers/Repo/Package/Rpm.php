<?php

namespace Controllers\Repo\Package;

use Exception;

class Rpm
{
    /**
     *  Extract package info (name, version, release, arch) from an rpm file path
     */
    public static function getInfo(string $path): array
    {
        // Define query format
        $format = '%{NAME}|%{VERSION}|%{RELEASE}|%{ARCH}';

        // Build the command to extract package info using rpm query
        $cmd = '/usr/bin/rpm -qp --queryformat ' . escapeshellarg($format) . ' ' . escapeshellarg($path) . ' 2>/dev/null';

        try {
            // Execute the command and capture the output
            exec($cmd, $out, $rc);

            if ($rc !== 0) {
                throw new Exception('RPM query command failed with return code ' . $rc);
            }

            if (empty($out)) {
                throw new Exception('no output from RPM query command');
            }

            // The output should be in the format: name|version|release|arch
            $parts = explode('|', $out[0], 4);

            // Validate that we got exactly 4 parts
            if (count($parts) !== 4) {
                throw new Exception('unexpected output format from RPM query command: ' . $out[0]);
            }
        } catch (Exception $e) {
            throw new Exception('Failed to extract RPM info from file:' .  $path . ' (' . $e->getMessage() . ')');
        }

        return [
            'name' => $parts[0],
            'version' => $parts[1],
            'release' => $parts[2],
            'arch' => $parts[3],
        ];
    }
}
