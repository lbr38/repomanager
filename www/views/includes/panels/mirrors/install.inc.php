<?php ob_start(); ?>
<h6 class="required">SELECT AN ENVIRONMENT</h6>

<select id="mirror-install-select-env" class="margin-bottom-20">
    <option value=""></option>
    <?php
    foreach (ENVS as $env) {
        echo '<option value="' . htmlspecialchars($env['Name']) . '">' . htmlspecialchars($env['Name']) . '</option>';
    } ?>
</select>

<div id="mirror-install-commands-container" class="hide">
    <?php
    // Display a warning message if the user selected mirrors of different package types
    if (count($packagesTypes) > 1) {
        echo '<div class="note-warning margin-bottom-15"><img src="/assets/icons/warning.svg" /><p>You have selected mirrors of different package types. Make sure to install them separately.</p></div>';
    }

    foreach ($installs as $install) :
        $mirrorId = $install['mirror']['Id']; ?>

        <div class="form-block form-block-accent-<?= $install['accent'] ?> mirror-install-block" snap-ids="<?= implode(',', $install['snapIds']) ?>" envs="<?= htmlspecialchars(json_encode($install['envs']), ENT_QUOTES) ?>">
            <div class="flex align-item-center">
                <p class="label-white"><?= htmlspecialchars($install['label']) ?></p>⸺
                <?php
                foreach ($install['dates'] as $date) {
                    echo '<p class="label-white">' . $date . '</p>';
                } ?>
                <p class="mirror-install-env"></p>
            </div>

            <div class="install-env-warning hide">
                <div class="note-warning margin-top-10">
                    <img src="/assets/icons/warning.svg" />
                    <p></p>
                </div>
            </div>

            <?php
            if ($install['mirror']['Package_type'] == 'alpine') : ?>
                <h6>SIGNING KEYS</h6>
                <p class="note">The upstream RSA key(s) must be installed in <code>/etc/apk/keys/</code> on the host. The official Alpine Linux keys are installed by the <code>alpine-keys</code> package.</p>
                <?php
                if (!empty($install['commands']['keys'])) : ?>
                    <p class="note margin-bottom-5">Keys of the source repository, if they are not already installed:</p>
                    <pre class="mirror-install-commands codeblock margin-top-10 margin-bottom-10 copy" template="<?= htmlspecialchars($install['commands']['keys']) ?>"></pre>
                    <?php
                endif;
            elseif ($install['mirror']['Package_type'] == 'openbsd') : ?>
                <h6>SIGNING KEYS</h6>
                <p class="note">The signify keys of the current and next OpenBSD versions are installed in <code>/etc/signify/</code> by the base system, nothing to install.</p>
                <?php
            else : ?>
                <h6>GPG KEY</h6>
                <p class="note">The upstream GPG key(s) must be trusted on the host.</p>
                <?php
            endif ?>

            <h6>INSTALLATION</h6>

            <?php
            if ($install['mirror']['Package_type'] == 'deb') : ?>
                <p class="note margin-bottom-5">Copy and paste the following commands in the shell of the target host<?= $install['multipleDists'] ? ', keeping only the distributions matching the host' : '' ?>.</p>

                <div class="switch-field margin-bottom-10">
                    <input type="radio" id="mirror-<?= $mirrorId ?>-legacy" class="mirror-config-format" name="mirror-<?= $mirrorId ?>-config-format" mirror-id="<?= $mirrorId ?>" value="legacy" checked />
                    <label for="mirror-<?= $mirrorId ?>-legacy">Legacy format</label>
                    <input type="radio" id="mirror-<?= $mirrorId ?>-deb822" class="mirror-config-format" name="mirror-<?= $mirrorId ?>-config-format" mirror-id="<?= $mirrorId ?>" value="deb822" />
                    <label for="mirror-<?= $mirrorId ?>-deb822">deb822 format</label>
                </div>

                <pre class="mirror-install-commands codeblock margin-top-10 margin-bottom-10 copy" mirror-id="<?= $mirrorId ?>" format="legacy" template="<?= htmlspecialchars($install['commands']['legacy']) ?>"></pre>
                <pre class="mirror-install-commands codeblock margin-top-10 margin-bottom-10 copy hide" mirror-id="<?= $mirrorId ?>" format="deb822" template="<?= htmlspecialchars($install['commands']['deb822']) ?>"></pre>
                <?php
            endif;

            if ($install['mirror']['Package_type'] == 'rpm') : ?>
                <p class="note margin-bottom-5">Copy and paste the following commands in the shell of the target host.</p>
                <pre class="mirror-install-commands codeblock margin-top-10 margin-bottom-10 copy" template="<?= htmlspecialchars($install['commands']['rpm']) ?>"></pre>
                <?php
            endif;

            if ($install['mirror']['Package_type'] == 'arch') : ?>
                <p class="note margin-bottom-5">For repositories already declared in <code>/etc/pacman.conf</code> with the mirrorlist (e.g. Arch Linux <code>core</code> and <code>extra</code>), add the following line at the top of <code>/etc/pacman.d/mirrorlist</code>:</p>
                <pre class="mirror-install-commands codeblock margin-top-10 margin-bottom-10 copy" template="<?= htmlspecialchars($install['mirrorlist']) ?>"></pre>

                <p class="note margin-bottom-5">Otherwise, add the following sections to <code>/etc/pacman.conf</code>:</p>
                <pre class="mirror-install-commands codeblock margin-top-10 margin-bottom-10 copy" template="<?= htmlspecialchars($install['commands']['arch']) ?>"></pre>
                <?php
            endif;

            if ($install['mirror']['Package_type'] == 'alpine') : ?>
                <p class="note margin-bottom-5">Copy and paste the following commands in the shell of the target host. The mirror repositories replace the ones declared in <code>/etc/apk/repositories</code>.</p>
                <pre class="mirror-install-commands codeblock margin-top-10 margin-bottom-10 copy" template="<?= htmlspecialchars($install['commands']['alpine']) ?>"></pre>
                <?php
            endif;

            if ($install['mirror']['Package_type'] == 'openbsd') : ?>
                <p class="note margin-bottom-5">Copy and paste the following command in the shell of the target host. The mirror replaces the one declared in <code>/etc/installurl</code>.</p>
                <pre class="mirror-install-commands codeblock margin-top-10 margin-bottom-10 copy" template="<?= htmlspecialchars($install['commands']['openbsd']) ?>"></pre>

                <p class="note">
                    <?php
                    if (!empty($install['commands']['tools'])) {
                        echo 'This mirror can be used by <code>' . implode('</code>, <code>', $install['commands']['tools']) . '</code>. ';
                    }

                    if (in_array('sysupgrade', $install['commands']['tools'])) {
                        echo '<code>sysupgrade</code> looks for the next OpenBSD version in the mirror: add it to the mirror versions beforehand (<code>Edit</code> button of the mirror). ';
                    } ?>
                    Firmwares are not part of OpenBSD mirrors: <code>fw_update</code> retrieves them from <code>firmware.openbsd.org</code>.
                </p>
                <?php
            endif ?>
        </div>
        <?php
    endforeach ?>
</div>
<br><br>

<?php
$content = ob_get_clean();
$slidePanelName = 'mirrors/install';
$slidePanelTitle = 'INSTALL MIRRORS';

include(ROOT . '/views/includes/slide-panel.inc.php');
