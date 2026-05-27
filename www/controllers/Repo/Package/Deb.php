<?php
namespace Controllers\Repo\Package;

use Exception;

class Deb
{
    /**
     * Extract package info (name, version, arch) from a deb file path
     */
    public static function getInfo(string $path): array
    {
        // Build the command to extract package info using dpkg-deb
        $cmd = sprintf(
            '/usr/bin/dpkg-deb -f %s Package Version Architecture 2>/dev/null',
            escapeshellarg($path)
        );

        try {
            // Execute the command and capture the output
            exec($cmd, $out, $rc);

            if ($rc !== 0) {
                throw new Exception('DEB query command failed with return code ' . $rc);
            }

            if (empty($out)) {
                throw new Exception('no output from DEB query command');
            }

            $info = [];

            foreach ($out as $line) {
                $parts = explode(':', $line, 2);

                if (count($parts) !== 2) {
                    throw new Exception('Unexpected output format from DEB query command: ' . $line);
                }

                $field = trim($parts[0]);
                $value = trim($parts[1]);

                switch ($field) {
                    case 'Package':
                        $info['name'] = $value;
                        break;

                    case 'Version':
                        $info['version'] = $value;
                        break;

                    case 'Architecture':
                        $info['arch'] = $value;
                        break;
                }
            }

            if (!isset($info['name'], $info['version'], $info['arch'])) {
                throw new Exception('Missing required fields in DEB query output');
            }
        } catch (Exception $e) {
            throw new Exception('Failed to extract DEB info from file: ' . $path . ' (' . $e->getMessage() . ')');
        }

        return $info;
    }
}
