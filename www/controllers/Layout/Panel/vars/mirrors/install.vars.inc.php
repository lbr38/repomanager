<?php
use \Controllers\Mirror\Mirror;

$mirrorSnapshotController = new \Controllers\Mirror\Snapshot();
$sourceController = new \Controllers\Repo\Source\Source();
$mirrorEnvController = new \Controllers\Mirror\Environment();

/**
 *  Installation configuration of each selected mirror
 *  Clients use the environment URL of the mirror: commands are templates in which __ENV__ is replaced by the selected environment (see events/mirror/actions.js)
 */
$installs = [];

foreach ($mirrorSnapshotController->getSelection($item['snapshots'] ?? '') as $selected) {
    $mirror = $selected['mirror'];
    $snapshot = $selected['snapshot'];

    // Snapshot date, as in the repositories installation panel, with the time as a mirror can have several snapshots a day
    $date = DateTime::createFromFormat('Y-m-d', $snapshot['Date'])->format('d-m-Y') . ' ' . substr($snapshot['Time'], 0, 5);

    // The configuration only depends on the mirror, several snapshots of the same mirror give the same commands
    if (isset($installs[$mirror['Id']])) {
        $installs[$mirror['Id']]['dates'][] = $date;
        $installs[$mirror['Id']]['snapIds'][] = $snapshot['Id'];
        continue;
    }

    // Environments of the mirror and the snapshot they point to, to warn if the selected environment does not point to a selected snapshot
    $envs = [];

    foreach ($mirrorEnvController->getByMirrorId((int) $mirror['Id']) as $env => $envSnapId) {
        $envSnapshot = $mirrorSnapshotController->getById((int) $envSnapId);

        if (!empty($envSnapshot)) {
            $envs[$env] = ['id' => $envSnapshot['Id'], 'date' => DateTime::createFromFormat('Y-m-d', $envSnapshot['Date'])->format('d-m-Y') . ' ' . substr($envSnapshot['Time'], 0, 5)];
        }
    }

    $params = Mirror::getParams($mirror);
    $name = REPO_CONF_FILES_PREFIX . $mirror['Name'];
    // Mirror base URL, based on the URL used by the user's browser
    $url = __SERVER_URL__ . Mirror::URL_PATH . '/' . Mirror::getDirOf($mirror) . '/__ENV__';
    $commands = [];

    // GPG keys declared in the source repository, clients must trust them as they check the upstream signature
    $fingerprints = [];
    $source = $sourceController->get($mirror['Package_type'], $mirror['Source']);
    $definition = json_decode($source['Definition'] ?? '', true) ?: [];
    $keysDefinitions = [];

    if ($mirror['Package_type'] == 'deb') {
        foreach ($definition['distributions'] ?? [] as $distribution) {
            if (in_array($distribution['name'] ?? '', $params['dists'] ?? [])) {
                $keysDefinitions = array_merge($keysDefinitions, $distribution['gpgkeys'] ?? []);
            }
        }
    }

    if ($mirror['Package_type'] == 'rpm') {
        foreach ($definition['releasever'] ?? [] as $releasever) {
            if (($releasever['name'] ?? '') == $mirror['Releasever']) {
                $keysDefinitions = array_merge($keysDefinitions, $releasever['gpgkeys'] ?? []);
            }
        }
    }

    if ($mirror['Package_type'] == 'arch') {
        $keysDefinitions = $definition['gpgkeys'] ?? [];
    }

    foreach ($keysDefinitions as $key) {
        if (!empty($key['fingerprint'])) {
            $fingerprints[] = $key['fingerprint'];
        }
    }

    if ($mirror['Package_type'] == 'deb') {
        // Flat (non-compliant) repositories have no distribution
        $dists = empty($params['dists']) ? ['./'] : $params['dists'];
        $components = empty($params['dists']) ? '' : implode(' ', $params['components'] ?? []);

        $legacy = 'cat << EOF > /etc/apt/sources.list.d/' . $name . '.list' . PHP_EOL;
        foreach ($dists as $dist) {
            $legacy .= trim('deb ' . $url . ' ' . $dist . ' ' . $components) . PHP_EOL;
        }
        $legacy .= 'EOF';

        $deb822 = 'cat << EOF > /etc/apt/sources.list.d/' . $name . '.sources' . PHP_EOL;
        $deb822 .= 'Types: deb' . PHP_EOL;
        $deb822 .= 'URIs: ' . $url . PHP_EOL;
        $deb822 .= 'Suites: ' . implode(' ', $dists) . PHP_EOL;
        if ($components !== '') {
            $deb822 .= 'Components: ' . $components . PHP_EOL;
        }
        $deb822 .= 'Enabled: yes' . PHP_EOL;
        $deb822 .= 'EOF';

        $commands = ['legacy' => $legacy, 'deb822' => $deb822];
    }

    if ($mirror['Package_type'] == 'rpm') {
        // Repositories mirrored per architecture have no repodata directory at the root of the snapshot
        $snapshotPath = Mirror::getSnapshotPath(Mirror::getDirOf($mirror), $snapshot['Date'], $snapshot['Time']);
        $baseurl = $url . (is_dir($snapshotPath . '/repodata') ? '' : '/$basearch');

        $rpm = 'cat << EOF > /etc/yum.repos.d/' . $name . '.repo' . PHP_EOL;
        $rpm .= '[' . $name . '___ENV__]' . PHP_EOL;
        $rpm .= 'name=' . $mirror['Name'] . ' mirror on ' . __SERVER_HOST__ . PHP_EOL;
        $rpm .= 'baseurl=' . str_replace('$basearch', '\$basearch', $baseurl) . PHP_EOL;
        $rpm .= 'enabled=1' . PHP_EOL;
        $rpm .= 'gpgcheck=1' . PHP_EOL;
        $rpm .= 'EOF';

        $commands = ['rpm' => $rpm];
    }

    if ($mirror['Package_type'] == 'arch') {
        // Arch Linux repositories are declared in pacman.conf, official ones (core, extra...) usually already exist and include the mirrorlist
        $pacman = '';
        foreach ($params['repos'] ?? [] as $repo) {
            $pacman .= '[' . $repo . ']' . PHP_EOL;
            $pacman .= 'Server = ' . $url . '/$repo/os/$arch' . PHP_EOL . PHP_EOL;
        }

        $commands = ['arch' => trim($pacman)];
    }

    $installs[$mirror['Id']] = [
        'mirror'       => $mirror,
        'dates'        => [$date],
        'snapIds'      => [$snapshot['Id']],
        'envs'         => $envs,
        'label'        => Mirror::getFullLabel($mirror),
        'accent'       => Mirror::getAccent($mirror['Package_type']),
        'commands'     => $commands,
        'fingerprints' => array_unique($fingerprints),
        'multipleDists' => count($params['dists'] ?? []) > 1,
        'mirrorlist'   => $mirror['Package_type'] == 'arch' ? 'Server = ' . $url . '/$repo/os/$arch' : ''
    ];
}

$packagesTypes = array_unique(array_column(array_column($installs, 'mirror'), 'Package_type'));

unset($mirrorSnapshotController, $mirrorEnvController, $sourceController, $selected);
