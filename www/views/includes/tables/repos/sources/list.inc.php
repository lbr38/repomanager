<div class="reloadable-table" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <h6>CURRENT SOURCE REPOSITORIES</h6>

    <div class="flex column-gap-10">
        <button type="button" class="btn-small-green get-panel-btn" panel="repos/sources/new">Manually add</button>
        <button type="button" class="btn-small-green get-panel-btn" panel="repos/sources/import">Import</button>
    </div>
    <br><br>
    
    <?php
    if (!empty($reloadableTableContent)) :
        foreach ($reloadableTableContent as $item) :
            $distAndComponent = null;

            /**
             *  Decode JSON details
             */
            $details = json_decode($item['Details'], true);
            $url = $details['url'];

            /**
             *  Case it is a deb source repository
             */
            if ($item['Type'] == 'deb') {
                if (isset($details['distributions'])) {
                    $distAndComponent = $details['distributions'];
                }
            }

            
            
            ?>

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
                    <p class="lowopacity-cst wordbreakall"><?= $url ?></p>
                </div>

                <div class="flex justify-end">
                    <img src="/assets/icons/delete.svg" class="source-repo-delete-btn icon-lowopacity" source-id="<?= $item['Id'] ?>" source-name="<?= $item['Name'] ?>" title="Delete <?= $item['Name'] ?> source repository" />
                </div>
            </div>

            <div class="hide source-repo-param-div detailsDiv margin-bottom-5" source-id="<?= $item['Id'] ?>">
                <form class="source-repo-form" source-id="<?= $item['Id'] ?>" autocomplete="off">
                    <h6 class="required">NAME</h6>
                    <input type="text" class="source-param" param-name="name" value="<?= $item['Name'] ?>" />

                    <h6 class="required">URL</h6>
                    <input type="text" class="source-param" param-name="url" value="<?= $url ?>" />

                    <?php
                    if ($item['Type'] == 'deb') :
                        if (!empty($distAndComponent)) :
                            echo '<h6>DISTRIBUTIONS</h6>';
                            echo '<p class="note">Embedded distributions.</p>';

                            foreach ($distAndComponent as $distributionName => $distributionDetails) : ?>
                                <!-- Distribution -->
                                <div class="table-container grid-2 bck-blue-alt source-repo-distribution-edit-param-btn pointer" source-id="<?= $item['Id'] ?>" distribution="<?= $distributionName ?>">
                                    <div>
                                        <p><?= $distributionName ?></p>
                                        <p class="note"><?= $distributionDetails['description'] ?></p>

                                        <div class="flex column-gap-5">
                                            <?php
                                            foreach ($distributionDetails['components'] as $componentName => $componentDetails) {
                                                echo '<p class="label-black">' . $componentName . '</p>';
                                            } ?>
                                        </div>
                                    </div>

                                    <div class="flex justify-end">
                                        <img src="/assets/icons/delete.svg" class="source-repo-delete-distribution-btn icon-lowopacity" source-id="<?= $item['Id'] ?>" distribution="<?= $distributionName ?>" title="Delete <?= $distributionName ?> distribution" />
                                    </div>
                                </div>
                                <?php
                            endforeach;
                        endif;
                    endif ?>

                    <!-- TODO -->

                    <!-- <h6>GPG SIGNING KEY URL</h6>
                    <input class="source-gpgkey-input" type="text" value="<?= $item['Gpgkey'] ?>" placeholder="http://..." />

                    <h4>SSL parameters</h4>
                    <p class="note">Use a SSL certificate and private key to authenticate to the source repository<a href="https://github.com/lbr38/repomanager/wiki/05.-Manage-sources-repositories#edit-a-source-repository" target="_blank" rel="noopener noreferrer" title="See documentation"><img src="/assets/icons/external-link.svg" class="icon margin-left-5" /></a></p>

                    <h6>PATH TO SSL CERTIFICATE</h6>
                    <input class="source-ssl-crt-input" type="text" value="<?= $item['Ssl_certificate_path'] ?>" placeholder="e.g. /var/lib/repomanager/ssl/my-editor/certificate.crt" />
    
                    <h6>PATH TO SSL PRIVATE KEY</h6>
                    <input class="source-ssl-key-input" type="text" value="<?= $item['Ssl_private_key_path'] ?>" placeholder="e.g. /var/lib/repomanager/ssl/my-editor/private.key" />

                    <h6>PATH TO SSL CA CERTIFICATE</h6>
                    <input class="source-ssl-cacrt-input" type="text" value="<?= $item['Ssl_ca_certificate_path'] ?>" placeholder="e.g. /var/lib/repomanager/ssl/my-editor/ca-certificate.crt" /> -->

                    <br>
                    <button type="button" class="source-repo-form-submit-btn btn-medium-green" source-id="<?= $item['Id'] ?>" title="Save">Save</button>
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
