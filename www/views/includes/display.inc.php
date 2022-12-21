<div id="displayDiv" class="param-slide-container">
    <div class="param-slide">
        <img id="displayDivCloseButton" title="Close" class="close-btn lowopacity float-right" src="resources/icons/close.svg" />
        <h3>REPOS LIST DISPLAY SETTINGS</h3>

        <h5>Informations</h5>
                
        <!-- afficher ou non la taille des repos/sections -->
        <div class="flex align-item-center column-gap-4">
            <label class="onoff-switch-label">
                <input type="hidden" name="printRepoSize" value="off" />
                <input class="onoff-switch-input" type="checkbox" name="printRepoSize" value="on" <?php echo (PRINT_REPO_SIZE == "yes") ? 'checked' : ''; ?>>
                <span class="onoff-switch-slider"></span>
            </label>
            <span>Print repo size</span>
        </div>

        <!-- afficher ou non le type des repos (miroir ou local) -->
        <div class="flex align-item-center column-gap-4">
            <label class="onoff-switch-label">
                <input type="hidden" name="printRepoType" value="off" />
                <input class="onoff-switch-input" type="checkbox" name="printRepoType" value="on" <?php echo (PRINT_REPO_TYPE == "yes") ? 'checked' : ''; ?>>
                <span class="onoff-switch-slider"></span>
            </label>
            <span>Print repo type (mirror / local)</span><br>
        </div>

        <!-- afficher ou non la signature gpg des repos -->
        <div class="flex align-item-center column-gap-4">
            <label class="onoff-switch-label">
                <input type="hidden" name="printRepoSignature" value="off" />
                <input class="onoff-switch-input" type="checkbox" name="printRepoSignature" value="on" <?php echo (PRINT_REPO_SIGNATURE == "yes") ? 'checked' : ''; ?>>
                <span class="onoff-switch-slider"></span>
            </label>
            <span> Print repo or packages GPG signature</span>
        </div>

        <br>

        <h5>Cache</h5>
        <!-- mettre en cache ou non la liste des repos -->
        <div class="flex align-item-center column-gap-4">
            <label class="onoff-switch-label">
                <input type="hidden" name="cache_repos_list" value="off" />
                <input class="onoff-switch-input" type="checkbox" name="cacheReposList" value="on" <?php echo (CACHE_REPOS_LIST == "yes") ? 'checked' : ''; ?>>
                <span class="onoff-switch-slider"></span>
            </label>
            <span>Use <b>/dev/shm</b> to store repo list in cache (recommended for large repo list)</span>
        </div>

        <br>
        <br>

        <button id="repos-display-conf-btn" type="submit" class="btn-large-green">Save</button>
    </div>
</div>