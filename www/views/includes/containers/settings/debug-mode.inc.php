<div class="reloadable-container" container="settings/debug-mode">
    <?php
    if (IS_ADMIN) : ?>
        <div>
            <h3>DEBUG MODE</h3>
            <div class="div-generic-blue">
                <h6 class="margin-top-0">ENABLE DEBUG MODE</h6>
                <p class="note">Debug mode will display additional information on the interface.</p>

                <label class="onoff-switch-label">
                    <input id="debug-mode-btn" class="onoff-switch-input" type="checkbox" value="true" <?php echo DEBUG_MODE === true ? 'checked' : ''; ?>>
                    <span class="onoff-switch-slider"></span>
                </label>
            </div>
        </div>
        <?php
    endif ?>
</div>
