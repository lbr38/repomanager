<div id="displayDiv" class="param-slide-container">
    <div class="param-slide">
        <img id="displayDivCloseButton" title="Close" class="close-btn float-right" src="resources/icons/close.svg" />
        <h3>REPOS LIST DISPLAY SETTINGS</h3>

        <?php
        if (Controllers\Common::isadmin()) : ?>
            <h5>Informations</h5>
                
            <!-- afficher ou non la taille des repos/sections -->
            <label class="onoff-switch-label">
                <input type="hidden" name="printRepoSize" value="off" />
                <input class="onoff-switch-input" type="checkbox" name="printRepoSize" value="on" <?php echo (PRINT_REPO_SIZE == "yes") ? 'checked' : ''; ?>>
                <span class="onoff-switch-slider"></span>
            </label>

            <span> Print repo size</span><br>
            <!-- afficher ou non le type des repos (miroir ou local) -->
            <label class="onoff-switch-label">
                <input type="hidden" name="printRepoType" value="off" />
                <input class="onoff-switch-input" type="checkbox" name="printRepoType" value="on" <?php echo (PRINT_REPO_TYPE == "yes") ? 'checked' : ''; ?>>
                <span class="onoff-switch-slider"></span>
            </label>

            <span> Print repo type (mirror / local)</span><br>
            <!-- afficher ou non la signature gpg des repos -->
            <label class="onoff-switch-label">
                <input type="hidden" name="printRepoSignature" value="off" />
                <input class="onoff-switch-input" type="checkbox" name="printRepoSignature" value="on" <?php echo (PRINT_REPO_SIGNATURE == "yes") ? 'checked' : ''; ?>>
                <span class="onoff-switch-slider"></span>
            </label>

            <span> Print repo or packages GPG signature</span>
            
            <br>
            <br>
            <h5>Cache</h5>
            <p>Use <b>/dev/shm</b> to store repo list in cache (recommended for large repo list)</p>
            <!-- mettre en cache ou non la liste des repos -->
            <label class="onoff-switch-label">
                <input type="hidden" name="cache_repos_list" value="off" />
                <input class="onoff-switch-input" type="checkbox" name="cacheReposList" value="on" <?php echo (CACHE_REPOS_LIST == "yes") ? 'checked' : ''; ?>>
                <span class="onoff-switch-slider"></span>
            </label>
            <span> Use /dev/shm</span><br>
            <br>
            <button id="repos-display-conf-btn" type="submit" class="btn-large-green">Save</button>
            <?php
        endif ?>
    </div>
</div>