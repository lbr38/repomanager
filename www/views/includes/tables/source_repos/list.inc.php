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
                <div class="grid grid-fr-1-2 align-item-center column-gap-10">
                    <span>Name</span>
                    <span>
                        <input class="source-input-name" type="text" source-name="<?= $item['Name'] ?>" source-type="<?= $item['Type'] ?>" value="<?= $item['Name'] ?>" />
                    </span>

                    <span>URL</span>
                    <span>
                        <input class="source-input-url" type="text" source-name="<?= $item['Name'] ?>" source-type="<?= $item['Type'] ?>" value="<?= $item['Url'] ?>" />
                    </span>
                </div>

                <br>

                <p><b>GPG parameters</b></p>

                <div class="grid grid-fr-1-2 align-item-center column-gap-10">
                    <span>GPG signing key URL</span>
                    <span>
                        <input class="source-repo-gpgkey-input" source-id="<?= $item['Id'] ?>" type="text" value="<?= $item['Gpgkey'] ?>" placeholder="http://..." />
                    </span>
                </div>

                <br>

                <p><b>SSL parameters</b></p>
                <p class="lowopacity-cst">Use a SSL certificate and private key to authenticate to the source repository</p>

                <div class="grid grid-fr-1-2 align-item-center column-gap-10">
                    <span>Path to SSL certificate</span>
                    <span>
                        <input class="source-repo-crt-input" source-id="<?= $item['Id'] ?>" type="text" value="<?= $item['Ssl_certificate_path'] ?>" placeholder="e.g. /var/lib/repomanager/ssl/certificate.crt" />
                    </span>
                
                    <span>Path to SSL private key</span>
                    <span>
                        <input class="source-repo-key-input" source-id="<?= $item['Id'] ?>" type="text" value="<?= $item['Ssl_private_key_path'] ?>" placeholder="e.g. /var/lib/repomanager/ssl/private.key" />
                    </span>
                </div>
            </div>
            <?php
        endforeach; ?>
        
        <div class="flex column-gap-10 justify-end">
            <?php
            if ($reloadableTableOffset > 0) {
                echo '<div class="reloadable-table-previous-btn btn-small-green">Previous</div>';
            }

            if ($reloadableTableCurrentPage < $reloadableTableTotalPages) {
                echo '<div class="reloadable-table-next-btn btn-small-green">Next</div>';
            } ?>
        </div>

        <?php
    endif ?>
</div>
