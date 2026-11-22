<div class="reloadable-container" container="settings/debug-mode">
    <?php
    if (IS_ADMIN) : ?>
        <div class="settings-section hide" section="debug-mode" save="false">
            <div class="group-header hide">
                <div class="flex align-item-center column-gap-10">
                    <img src="/assets/icons/terminal.svg" class="group-header-icon lowopacity-cst" />
                    <span class="group-header-name">Debug mode</span>
                    <span class="label-count settings-search-count hide"></span>
                </div>
            </div>

            <p class="note settings-section-note">Debug mode is applied immediately.</p>

            <div class="settings-columns">
                <div class="settings-column">
                    <div class="div-generic-blue settings-field">
                        <h5 class="margin-top-0">DEBUG MODE</h5>
                        <h6>ENABLE DEBUG MODE</h6>
                        <p class="note">Debug mode will display additional information on the interface.</p>

                        <label class="onoff-switch-label">
                            <input id="debug-mode-btn" class="onoff-switch-input" type="checkbox" value="true" <?php echo \Controllers\App\DebugMode::enabled() === true ? 'checked' : ''; ?>>
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="settings-column"></div>
            </div>
        </div>
        <?php
    endif ?>

    <script>
        // Re-apply the settings sections display when this container is reloaded (settings.js is not loaded yet on page load)
        if (typeof settingsLayout !== 'undefined') {
            settingsLayout.apply();
        }
    </script>
</div>
