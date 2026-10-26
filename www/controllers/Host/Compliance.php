<?php

namespace Controllers\Host;

use Controllers\Host\Package\Package as HostPackage;

class Compliance
{
    private array $settings;

    public function __construct()
    {
        $hostController = new Host();

        // Loaded once and reused for every evaluated host
        $this->settings = $hostController->getSettings()['compliance'];
    }

    /**
     *  Return an array with the compliance status of a host, from its row in the hosts table
     *  A HostPackage controller already opened by the caller can be passed to reuse its connection
     */
    public function get(array $host, ?HostPackage $hostPackageController = null): array
    {
        // Both queries share a single connection to the host database
        $hostPackageController ??= new HostPackage($host['Id']);
        $available = $hostPackageController->getAvailable();
        $latestUpdate = $hostPackageController->getLastPackageUpgradeEvent()['Date'] ?? null;

        $metrics = [
            'available_updates_count' => count($available),
            'latest_update' => $latestUpdate,
            'security_update' => in_array('true', array_column($available, 'Security'), true),
            'reboot_required' => $host['Reboot_required'] ?? 'false'
        ];

        // Excluded hosts are always compliant but still report their metrics
        foreach ($this->settings['exclusions']['hosts'] as $excludedHost) {
            if (fnmatch($excludedHost, $host['Hostname'])) {
                return ['compliant' => true, 'reason' => 'Host is excluded from compliance checks'] + $metrics;
            }
        }

        $compliant = true;
        $reason = '';
        $complianceAvailableCount = 0;
        $complianceSecurityUpdate = false;

        foreach ($available as $package) {
            // setSettings() only allows letters, digits, '-', '_' and '*' in patterns
            foreach ($this->settings['exclusions']['packages'] as $excludedPackage) {
                // If the package name matches the excluded pattern, ignore it for compliance checks
                if (fnmatch($excludedPackage, $package['Name'])) {
                    continue 2;
                }
            }

            $complianceAvailableCount++;

            if ($package['Security'] === 'true') {
                $complianceSecurityUpdate = true;
            }
        }

        // Calculate the threshold date based on the compliance threshold in days
        $thresholdDate = strtotime('-' . $this->settings['thresholds']['days'] . ' days');

        // The host is not compliant if the available updates count is >= threshold
        if ($complianceAvailableCount >= $this->settings['thresholds']['count']) {
            $compliant = false;
            $reason = 'Pending updates count (' . $complianceAvailableCount . ') is greater than or equal to the threshold (' . $this->settings['thresholds']['count'] . ')';
        }

        // The host is not compliant if no package update has been performed yet
        if (!$latestUpdate and $this->settings['thresholds']['not-updated-yet']) {
            $compliant = false;
            $reason = 'No package update has been performed yet';
        }

        // The host is not compliant if the latest update is older than the compliance threshold defined in settings (in days)
        if ($latestUpdate and $thresholdDate and strtotime($latestUpdate) < $thresholdDate) {
            $compliant = false;
            $reason = 'Latest update date (' . $latestUpdate . ') is older than the compliance threshold (' . $this->settings['thresholds']['days'] . ' days)';
        }

        // Optional rule: host is not compliant when there is a security update available
        if ((int) $this->settings['thresholds']['security-update'] === 1 and $complianceSecurityUpdate) {
            $compliant = false;
            $reason = 'Host has a security update available';
        }

        // Optional rule: host is not compliant when a reboot is required
        if ((int) $this->settings['thresholds']['reboot-required'] === 1 and $metrics['reboot_required'] === 'true') {
            $compliant = false;
            $reason = 'Host requires a reboot';
        }

        return ['compliant' => $compliant, 'reason' => $reason] + $metrics;
    }
}
