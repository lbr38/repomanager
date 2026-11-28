<?php
ob_start();

/**
 *  Return the status of a GPG key or subkey: revoked, expired, disabled, invalid, expiring (within 90 days) or empty if valid
 */
$gpgStatus = function (array $key): string {
    $statuses = ['r' => 'revoked', 'e' => 'expired', 'd' => 'disabled', 'i' => 'invalid'];

    if (isset($statuses[$key['validity']])) {
        return $statuses[$key['validity']];
    }

    if (!empty($key['expires']) and $key['expires'] < time()) {
        return 'expired';
    }

    if (!empty($key['expires']) and $key['expires'] < time() + 90 * 86400) {
        return 'expiring';
    }

    return '';
};

/**
 *  Return the status label and description of a GPG key or subkey: no label if valid, a yellow label if expiring within 90 days,
 *  a red label if expired, revoked, disabled or invalid
 */
$gpgStatusInfo = function (array $key) use ($gpgStatus): array {
    $status = $gpgStatus($key);
    $expires = empty($key['expires']) ? '' : date('Y-m-d', $key['expires']);

    if ($status == '') {
        return ['label' => '', 'title' => 'Valid' . (empty($expires) ? '' : ' until ' . $expires)];
    }

    if ($status == 'expiring') {
        return ['label' => '<span class="label-yellow" title="Expires on ' . $expires . '">Expires soon</span>', 'title' => 'Expires soon, on ' . $expires];
    }

    if ($status == 'expired') {
        return ['label' => '<span class="label-red" title="Expired on ' . $expires . '">Expired</span>', 'title' => 'Expired on ' . $expires];
    }

    return ['label' => '<span class="label-red">' . ucfirst($status) . '</span>', 'title' => ucfirst($status)];
};

/**
 *  Return the capabilities of a GPG key or subkey (its own ones, lowercase letters of the colon listing)
 */
$gpgCapabilities = function (string $capabilities): string {
    $names = ['s' => 'sign', 'c' => 'certify', 'e' => 'encrypt', 'a' => 'authenticate'];
    $result = [];

    foreach (str_split(preg_replace('/[^a-z]/', '', $capabilities)) as $capability) {
        if (isset($names[$capability])) {
            $result[] = $names[$capability];
        }
    }

    return implode(', ', $result);
};

/**
 *  Return a date from a timestamp, or 'Never' (expiration date)
 */
$gpgDate = function (int $timestamp): string {
    return empty($timestamp) ? 'Never' : date('Y-m-d', $timestamp);
};

/**
 *  Return the searchable text of a key (lowercase), from its fields
 *  The fingerprints are searchable separately (search-fpr attribute), so that short words do not match any hexadecimal string
 */
$searchText = function (array $fields): string {
    return htmlspecialchars(strtolower(implode(' ', array_filter($fields))));
};

/**
 *  GPG keys filters (key => [filter names]) and counts
 */
$gpgFilters = [];
$gpgCounts = ['problem' => 0, 'expiring' => 0, 'unreferenced' => 0];

foreach ($gpgKeys as $key) {
    $filters = [];
    $status = $gpgStatus($key);

    if ($status == 'expiring') {
        $filters[] = 'expiring';
    } elseif (!empty($status)) {
        $filters[] = 'problem';
    }

    if (empty($key['usage'])) {
        $filters[] = 'unreferenced';
    }

    foreach ($filters as $filter) {
        $gpgCounts[$filter]++;
    }

    $gpgFilters[$key['fingerprint']] = $filters;
}

/**
 *  Sections (one per type of key, displayed one at a time like the settings sections): navigation title and tooltip (header),
 *  count, description, empty state description and search placeholder
 */
$sections = [
    'gpg' => [
        'title' => 'GPG',
        'header' => 'GPG keys',
        'count' => count($gpgKeys),
        'note' => 'Keys of Repomanager trusted keyring, used to verify the signature of the deb, rpm and arch source repositories. The keyring is shared: a key verifies the signature of any of them. A key is imported from the distribution (deb), the release version (rpm) or the arch source repository.',
        'empty' => 'GPG keys are imported from the deb distributions, the rpm release versions and the arch source repositories.',
        'search' => 'Search by name, user Id, fingerprint or source repository'
    ],
    'alpine' => [
        'title' => 'RSA',
        'header' => 'RSA keys (Alpine)',
        'count' => array_sum(array_map(fn ($source) => count($source['keys']), $sourcesKeys['alpine'])),
        'note' => 'RSA public keys stored in each alpine source repository: only its own keys verify its indexes signature. The name must be the name of the key file on clients (<code>/etc/apk/keys/&lt;name&gt;</code>). A key is imported from the source repository.',
        'empty' => 'RSA keys are imported from the alpine source repositories.',
        'search' => 'Search by name, link, fingerprint or source repository'
    ],
    'openbsd' => [
        'title' => 'Signify',
        'header' => 'Signify keys (OpenBSD)',
        'count' => array_sum(array_map(fn ($source) => count($source['keys']), $sourcesKeys['openbsd'])),
        'note' => 'Signify public keys stored in each openbsd source repository: only its own keys verify its files lists signature (<code>SHA256.sig</code>). The name must be the name of the key file (e.g. <code>openbsd-80-pkg.pub</code>), as installed in <code>/etc/signify/</code> on clients. A key is imported from the source repository.',
        'empty' => 'Signify keys are imported from the openbsd source repositories.',
        'search' => 'Search by name, comment, key number or source repository'
    ]
]; ?>

<p class="note">Public keys used to verify the signature of the source repositories.</p>

<!-- Sections navigation, as the source repositories types tabs: the active section (set by signingKeysApply()) is fully visible with a blue count -->
<div class="flex flex-wrap align-item-center column-gap-20 row-gap-10 margin-top-15">
    <?php
    foreach ($sections as $section => $properties) : ?>
        <div class="flex align-item-center column-gap-5">
            <div class="slide-btn panel-tab-btn signing-keys-nav-btn<?= $section == 'gpg' ? '' : ' mediumopacity panel-tab-inactive' ?>" section="<?= $section ?>" title="<?= $properties['header'] ?>">
                <span><?= $properties['title'] ?></span>
            </div>
            <span class="<?= $section == 'gpg' ? 'label-count' : 'label-tr' ?> label-medium signing-keys-nav-count" section="<?= $section ?>" total="<?= $properties['count'] ?>"><?= $properties['count'] ?></span>
        </div>
        <?php
    endforeach ?>
</div>

<?php
foreach ($sections as $section => $properties) : ?>
    <div class="signing-keys-section margin-top-15 <?= $section == 'gpg' ? '' : 'hide' ?>" section="<?= $section ?>">
        <p class="note"><?= $properties['note'] ?></p>

        <?php
        // Section without key: no search, only an empty state
        if ($properties['count'] == 0) : ?>
            <div class="empty-state">
                <p class="empty-state-title">No <?= $properties['title'] ?> key imported yet.</p>
                <p class="note"><?= $properties['empty'] ?><?= $section != 'gpg' && empty($sourcesKeys[$section]) ? ' There is no ' . $section . ' source repository yet.' : '' ?></p>
            </div>
            <?php
        endif;

        if ($properties['count'] > 0) : ?>
            <!-- Search and filter of the keys of the section (as in the tasks list), and selection of all the keys shown -->
            <div class="flex align-item-center column-gap-10 margin-top-15 margin-bottom-15">
                <input type="text" class="signing-keys-search flex-1" section="<?= $section ?>" autocomplete="off" spellcheck="false" placeholder="<?= $properties['search'] ?>" title="<?= $properties['search'] ?>" />

                <?php
                if ($section == 'gpg') : ?>
                    <select class="signing-keys-gpg-filter max-width-fit" title="Filter by status">
                        <option value="all">All keys</option>
                        <?php
                        $filterLabels = ['problem' => 'Expired or revoked', 'expiring' => 'Expires soon', 'unreferenced' => 'Not referenced'];

                        foreach ($filterLabels as $filter => $label) :
                            if ($gpgCounts[$filter] == 0) {
                                continue;
                            } ?>
                            <option value="<?= $filter ?>"><?= $label ?> (<?= $gpgCounts[$filter] ?>)</option>
                            <?php
                        endforeach ?>
                    </select>
                    <?php
                endif ?>

                <input type="checkbox" class="signing-keys-select-all lowopacity margin-left-5 margin-right-20" section="<?= $section ?>" title="Select all the keys shown" />
            </div>
            <?php
        endif;

        /**
         *  GPG keys
         */
        if ($section == 'gpg') :
            foreach ($gpgKeys as $key) :
                $status = $gpgStatusInfo($key);
                $search = $searchText(array_merge(array_column($key['uids'], 'uid'), $key['usage']));
                $searchFpr = $searchText(array_merge([$key['fingerprint']], array_column($key['subkeys'], 'fingerprint'))); ?>

                <div class="signing-keys-item" search="<?= $search ?>" search-fpr="<?= $searchFpr ?>" filters="<?= implode(' ', $gpgFilters[$key['fingerprint']]) ?>">
                    <div class="table-container grid-fr-4-1 panel-list-item pointer signing-keys-row">
                        <div>
                            <p class="wordbreakall"><?= htmlspecialchars($key['name']) ?></p>
                            <p class="note" title="Long key Id"><?= htmlspecialchars($key['keyid']) ?></p>
                        </div>

                        <div class="flex align-item-center justify-end column-gap-10">
                            <?php
                            echo $status['label'];

                            if (empty($key['usage'])) {
                                echo '<span class="label-tr" title="Not linked to any deb distribution, rpm release version or arch source repository. The key is still trusted to verify signatures.">Not referenced</span>';
                            } ?>

                            <input type="checkbox" class="child-checkbox lowopacity" checkbox-id="signing-keys-gpg" checkbox-data-attribute="fingerprint" fingerprint="<?= htmlspecialchars($key['fingerprint']) ?>" usage-count="<?= count($key['usage']) ?>" title="Select GPG key <?= htmlspecialchars($key['name']) ?>" />
                        </div>
                    </div>

                    <div class="signing-keys-details hide details-div margin-bottom-10">
                        <h6 class="margin-top-0">FINGERPRINT</h6>
                        <p class="copy wordbreakall"><?= htmlspecialchars($key['fingerprint']) ?></p>

                        <h6>USER IDS</h6>
                        <?php
                        foreach ($key['uids'] as $uid) {
                            echo '<p class="wordbreakall">' . htmlspecialchars($uid['uid']) . '</p>';
                        } ?>

                        <div class="grid grid-4 column-gap-10">
                            <div>
                                <h6>STATUS</h6>
                                <p><?= $status['title'] ?></p>
                            </div>
                            <div>
                                <h6>ALGORITHM</h6>
                                <p><?= htmlspecialchars($key['algo']) ?></p>
                            </div>
                            <div>
                                <h6>CREATED</h6>
                                <p><?= $gpgDate($key['created']) ?></p>
                            </div>
                            <div>
                                <h6>EXPIRES</h6>
                                <p><?= $gpgDate($key['expires']) ?></p>
                            </div>
                        </div>

                        <?php
                        if (!empty($key['subkeys'])) : ?>
                            <h6>SUBKEYS</h6>
                            <?php
                            foreach ($key['subkeys'] as $subkey) :
                                $subkeyStatus = $gpgStatusInfo($subkey); ?>
                                <div class="table-container grid-fr-4-1 panel-list-item">
                                    <div>
                                        <p class="copy wordbreakall"><?= htmlspecialchars($subkey['fingerprint']) ?></p>
                                        <p class="note"><?= htmlspecialchars($subkey['algo']) ?>, <?= $gpgCapabilities($subkey['capabilities']) ?>, created <?= $gpgDate($subkey['created']) ?>, expires <?= $gpgDate($subkey['expires']) ?></p>
                                    </div>
                                    <div class="flex align-item-center justify-end">
                                        <?= $subkeyStatus['label'] ?>
                                    </div>
                                </div>
                                <?php
                            endforeach;
                        endif ?>

                        <h6>REFERENCED BY</h6>
                        <?php
                        if (empty($key['usage'])) : ?>
                            <p class="note">Not linked to any deb distribution, rpm release version or arch source repository.</p>
                            <?php
                        else : ?>
                            <div class="flex flex-wrap column-gap-5 row-gap-10">
                                <?php
                                foreach ($key['usage'] as $usage) {
                                    echo '<p class="label-black">' . htmlspecialchars($usage) . '</p>';
                                } ?>
                            </div>
                            <?php
                        endif ?>
                    </div>
                </div>
                <?php
            endforeach;
        endif;

        /**
         *  RSA keys (alpine) and signify keys (openbsd), by source repository
         */
        if ($section != 'gpg' and $properties['count'] > 0) :
            foreach ($sourcesKeys[$section] as $source) : ?>
                <div class="signing-keys-group">
                    <div class="flex align-item-center column-gap-10 margin-top-20 margin-bottom-10">
                        <h6 class="margin-top-0"><?= htmlspecialchars(strtoupper($source['name'])) ?></h6>
                        <span class="note"><?= count($source['keys']) ?> key<?= count($source['keys']) > 1 ? 's' : '' ?></span>
                    </div>

                    <?php
                    if (empty($source['keys'])) : ?>
                        <p class="note">No key: the signatures of this source repository cannot be verified.</p>
                        <?php
                    endif;

                    foreach ($source['keys'] as $key) :
                        $search = $searchText([$key['name'], $key['link'] ?? '', $source['name'], $key['details']['comment'] ?? '']);
                        $searchFpr = $searchText([$key['details']['fingerprint'] ?? '', $key['details']['keynum'] ?? '']); ?>

                        <div class="signing-keys-item" search="<?= $search ?>" search-fpr="<?= $searchFpr ?>">
                            <div class="table-container grid-fr-4-1 panel-list-item pointer signing-keys-row">
                                <div>
                                    <p class="wordbreakall"><?= htmlspecialchars($key['name']) ?></p>
                                    <p class="note wordbreakall">
                                        <?php
                                        // Short summary: size and start of the fingerprint (RSA) or comment (signify)
                                        if ($section == 'alpine' and !empty($key['details'])) {
                                            echo $key['details']['bits'] . ' bits, SHA256 ' . substr($key['details']['fingerprint'], 0, 16) . '...';
                                        } elseif ($section == 'openbsd' and !empty($key['details'])) {
                                            echo htmlspecialchars($key['details']['comment']);
                                        } else {
                                            echo !empty($key['link']) ? htmlspecialchars($key['link']) : 'Imported from plain text';
                                        } ?>
                                    </p>
                                </div>

                                <div class="flex align-item-center justify-end column-gap-10">
                                    <?php
                                    // These keys do not expire: a label is only printed if the key cannot be read
                                    if (!empty($key['error'])) {
                                        echo '<span class="label-red" title="' . htmlspecialchars($key['error']) . '">Invalid</span>';
                                    } ?>

                                    <input type="checkbox" class="child-checkbox lowopacity" checkbox-id="signing-keys-<?= $section ?>" checkbox-data-attribute="key-ref" key-ref="<?= $source['id'] ?>/<?= htmlspecialchars($key['name']) ?>" title="Select <?= htmlspecialchars($key['name']) ?> key" />
                                </div>
                            </div>

                            <div class="signing-keys-details hide details-div margin-bottom-10">
                                <?php
                                if (!empty($key['error'])) : ?>
                                    <h6 class="margin-top-0">ERROR</h6>
                                    <p class="note"><?= htmlspecialchars($key['error']) ?></p>
                                    <?php
                                endif;

                                if ($section == 'alpine' and !empty($key['details'])) : ?>
                                    <div class="grid grid-fr-1-3 column-gap-10">
                                        <div>
                                            <h6 class="margin-top-0">SIZE</h6>
                                            <p><?= $key['details']['bits'] ?> bits</p>
                                        </div>
                                        <div>
                                            <h6 class="margin-top-0" title="SHA256 of the key in DER format (openssl pkey -pubin -in <key> -outform DER | sha256sum)">SHA256 FINGERPRINT</h6>
                                            <p class="copy wordbreakall"><?= $key['details']['fingerprint'] ?></p>
                                        </div>
                                    </div>
                                    <?php
                                endif;

                                if ($section == 'openbsd' and !empty($key['details'])) : ?>
                                    <div class="grid grid-fr-1-3 column-gap-10">
                                        <div>
                                            <h6 class="margin-top-0" title="Key number, identifies the key verifying a signature">KEY NUMBER</h6>
                                            <p class="copy"><?= $key['details']['keynum'] ?></p>
                                        </div>
                                        <div>
                                            <h6 class="margin-top-0">COMMENT</h6>
                                            <p class="wordbreakall"><?= htmlspecialchars($key['details']['comment']) ?></p>
                                        </div>
                                    </div>
                                    <?php
                                endif;

                                if (!empty($key['link'])) : ?>
                                    <h6>LINK</h6>
                                    <p class="copy wordbreakall"><?= htmlspecialchars($key['link']) ?></p>
                                    <?php
                                endif ?>

                                <h6>CONTENT</h6>
                                <pre class="codeblock copy"><?= htmlspecialchars($key['content'] ?? '') ?></pre>
                            </div>
                        </div>
                        <?php
                    endforeach ?>
                </div>
                <?php
            endforeach;
        endif ?>

        <p class="note signing-keys-no-result hide">No key matches this search.</p>
    </div>
    <?php
endforeach ?>

<script>
    // Restore the active section, filter and search, then filter the keys (see events/repo/source/signing-keys.js)
    signingKeysInit();
</script>

<?php
$content = ob_get_clean();
$slidePanelName = 'repos/sources/keys';
$slidePanelTitle = 'SIGNING KEYS';

include(ROOT . '/views/includes/slide-panel.inc.php');
