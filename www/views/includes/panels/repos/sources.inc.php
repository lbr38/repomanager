<?php ob_start(); ?>

<p>To create mirrors, you must configure sources repositories.</p>

<h5>NEW SOURCE REPOSITORY</h5>

<form id="addSourceForm" autocomplete="off">
    <table>
        <tr>
            <td class="td-30">Repo type</td>
            <td colspan="100%">
                <div class="switch-field">
                    <?php
                    if (RPM_REPO == 'true' and DEB_REPO == 'true') : ?>
                        <input type="radio" id="repoType_rpm" name="addSourceRepoType" value="rpm" checked />
                        <label for="repoType_rpm">rpm</label>
                        <input type="radio" id="repoType_deb" name="addSourceRepoType" value="deb" />
                        <label for="repoType_deb">deb</label>
                        <?php
                    elseif (RPM_REPO == 'true') : ?>
                        <input type="radio" id="repoType_rpm" name="addSourceRepoType" value="rpm" checked />
                        <label for="repoType_rpm">rpm</label>     
                        <?php
                    elseif (DEB_REPO == 'true') : ?>
                        <input type="radio" id="repoType_deb" name="addSourceRepoType" value="deb" checked />
                        <label for="repoType_deb">deb</label> 
                        <?php
                    endif ?>
                </div>
            </td>
        </tr>
        <tr>
            <td>Name</td>
            <td colspan="100%">
                <input type="text" name="addSourceName" required />
            </td>
        </tr>
        <tr>
            <td>URL</td>
            <td>
                <input type="text" name="addSourceUrl" required />
            </td>
        </tr>
        <tr>
            <td colspan="2"><br><b>GPG signing key</b> <span class="lowopacity-cst">(optionnal)</span></td>
        </tr>
        <tr>
            <td colspan="100%">
                <div>
                    <br>
                    <p>Specify URL to the GPG key or import a plain ASCII GPG key.</p>
                    <br>
                    <p>URL to the GPG key:</p>
                    <input type="text" name="gpgKeyURL" placeholder="https://...">
                    
                    <br><br>
                    <p>Import a GPG key:</p>
                    <textarea id="gpgKeyText" class="textarea-100" placeholder="-----BEGIN PGP PUBLIC KEY BLOCK-----"></textarea>
                </div>
            </td>
        </tr>
    </table>
    <br>
    <button type="submit" class="btn-large-green" title="Add">Add source</button>
</form>

<?php
/**
 *  Print current sources repositories
 */
\Controllers\Layout\Table\Render::render('source_repos/list', 0); ?>

<h5>GPG SIGNING KEYS</h5>

<p>Import a GPG key</p>

<br>

<form id="source-repo-add-key-form" autocomplete="off">
    <div class="flex align-content-center">
        <textarea id="source-repo-add-key-textarea" class="textarea-100" placeholder="-----BEGIN PGP PUBLIC KEY BLOCK-----"></textarea>
        <button class="btn-xxsmall-green" title="Import">+</button>
    </div>
</form>

<br>

<?php
/**
 *  Print imported GPG signing keys
 */
\Controllers\Layout\Table\Render::render('source_repos/gpgkeys', 0); ?>

<br><br>

<?php
$content = ob_get_clean();
$slidePanelName = 'repos/sources';
$slidePanelTitle = 'SOURCE REPOSITORIES';

include(ROOT . '/views/includes/slide-panel.inc.php');
