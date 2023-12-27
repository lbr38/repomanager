<div class="reloadable-table" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <?php
    if (!empty($reloadableTableContent)) : ?>
        <h5>CURRENT SOURCE REPOSITORIES</h5>

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
                    <img src="/assets/icons/delete.svg" class="source-repo-delete-btn icon-lowopacity" source-id="<?= $item['Id'] ?>" source-name="<?= $item['Name'] ?>" title="Delete <?= $item['Name'] ?> source repo" />
                </div>
            </div>

            <div class="hide source-repo-param-div detailsDiv margin-bottom-5" source-id="<?= $item['Id'] ?>">
                <form class="source-form" source-id="<?= $item['Id'] ?>" autocomplete="off">
                    <div class="grid grid-fr-1-2 align-item-center column-gap-10">
                        <span>Name</span>
                        <span>
                            <input class="source-input-name" type="text" value="<?= $item['Name'] ?>" />
                        </span>

                        <span>URL</span>
                        <span>
                            <input class="source-input-url" type="text" value="<?= $item['Url'] ?>" />
                        </span>
                    </div>

                    <br>

                    <p><b>GPG parameters</b></p>

                    <div class="grid grid-fr-1-2 align-item-center column-gap-10">
                        <span>GPG signing key URL</span>
                        <span>
                            <input class="source-gpgkey-input" type="text" value="<?= $item['Gpgkey'] ?>" placeholder="http://..." />
                        </span>
                    </div>

                    <br>

                    <p><b>SSL parameters</b></p>
                    <p class="lowopacity-cst">Use a SSL certificate and private key to authenticate to the source repository<a href="https://github.com/lbr38/repomanager/wiki/05.-Manage-sources-repositories#edit-a-source-repository" target="_blank" rel="noopener noreferrer" title="See documentation"><img src="/assets/icons/external-link.svg" class="icon" /></a></p>

                    <div class="grid grid-fr-1-2 align-item-center column-gap-10">
                        <span>Path to SSL certificate</span>
                        <span>
                            <input class="source-sslcrt-input" type="text" value="<?= $item['Ssl_certificate_path'] ?>" placeholder="e.g. /var/lib/repomanager/ssl/certificate.crt" />
                        </span>
                    
                        <span>Path to SSL private key</span>
                        <span>
                            <input class="source-sslkey-input" type="text" value="<?= $item['Ssl_private_key_path'] ?>" placeholder="e.g. /var/lib/repomanager/ssl/private.key" />
                        </span>
                    </div>

                    <br>
                    <button type="submit" class="btn-large-green" title="Save">Save</button>
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
