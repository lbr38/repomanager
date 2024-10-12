<div class="reloadable-table" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <?php
    if (!empty($reloadableTableContent)) : ?>
        <h6>CURRENT SOURCE REPOSITORIES</h6>

        <button type="button" class="btn-small-green get-panel-btn" panel="source-repos/new">Manually add</button>
        <button type="button" class="btn-small-green get-panel-btn" panel="source-repos/import">Import</button>
        <br><br>

        <?php
        foreach ($reloadableTableContent as $item) : ?>
            <div class="table-container-3 bck-blue-alt pointer source-repo-edit-param-btn" source-id="<?= $item['Id'] ?>">
                <div>
                    <?php
                    if ($item['Type'] == 'rpm') {
                        echo ' <span class="label-pkg-rpm">rpm</span>';
                    }
                    if ($item['Type'] == 'deb') {
                        echo ' <span class="label-pkg-deb">deb</span>';
                    } ?>
                </div>

                <div>
                    <p><?= $item['Name'] ?></p>
                    <p class="lowopacity-cst wordbreakall"><?= $item['Url'] ?></p>
                </div>

                <div class="flex justify-end">
                    <img src="/assets/icons/delete.svg" class="source-repo-delete-btn icon-lowopacity" source-id="<?= $item['Id'] ?>" source-name="<?= $item['Name'] ?>" title="Delete <?= $item['Name'] ?> source repository" />
                </div>
            </div>

            <div class="hide source-repo-param-div detailsDiv margin-bottom-5" source-id="<?= $item['Id'] ?>">
                <form class="source-form" source-id="<?= $item['Id'] ?>" autocomplete="off">
                    <h6>NAME</h6>
                    <input class="source-input-name" type="text" value="<?= $item['Name'] ?>" />

                    <h6>URL</h6>
                    <input class="source-input-url" type="text" value="<?= $item['Url'] ?>" />

                    <h6>GPG SIGNING KEY URL</h6>
                    <input class="source-gpgkey-input" type="text" value="<?= $item['Gpgkey'] ?>" placeholder="http://..." />

                    <h4>SSL parameters</h4>
                    <p class="note">Use a SSL certificate and private key to authenticate to the source repository<a href="https://github.com/lbr38/repomanager/wiki/05.-Manage-sources-repositories#edit-a-source-repository" target="_blank" rel="noopener noreferrer" title="See documentation"><img src="/assets/icons/external-link.svg" class="icon margin-left-5" /></a></p>

                    <h6>PATH TO SSL CERTIFICATE</h6>
                    <input class="source-ssl-crt-input" type="text" value="<?= $item['Ssl_certificate_path'] ?>" placeholder="e.g. /var/lib/repomanager/ssl/my-editor/certificate.crt" />
    
                    <h6>PATH TO SSL PRIVATE KEY</h6>
                    <input class="source-ssl-key-input" type="text" value="<?= $item['Ssl_private_key_path'] ?>" placeholder="e.g. /var/lib/repomanager/ssl/my-editor/private.key" />

                    <h6>PATH TO SSL CA CERTIFICATE</h6>
                    <input class="source-ssl-cacrt-input" type="text" value="<?= $item['Ssl_ca_certificate_path'] ?>" placeholder="e.g. /var/lib/repomanager/ssl/my-editor/ca-certificate.crt" />

                    <br><br>
                    <button type="submit" class="btn-medium-green" title="Save">Save</button>
                </form>
            </div>
            <?php
        endforeach; ?>
        
        <div class="flex justify-end">
            <?php \Controllers\Layout\Table\Render::paginationBtn($reloadableTableCurrentPage, $reloadableTableTotalPages); ?>
        </div>

        <?php
    endif ?>
</div>
