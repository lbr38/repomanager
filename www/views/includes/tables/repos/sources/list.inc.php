<div class="reloadable-table" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">   
    <?php
    if (!empty($reloadableTableContent)) :
        foreach ($reloadableTableContent as $item) :
            $distAndComponent = null;
            $sslCertificatePath = '';
            $sslPrivateKeyPath = '';
            $sslCaCertificatePath = '';

            // Decode JSON definition
            $definition = json_decode($item['Definition'], true);
            $name = $definition['name'];
            $url  = $definition['url'];
            $type = $definition['type'];

            // Case it is a deb source repository
            if ($type == 'deb') {
                if (isset($definition['distributions'])) {
                    $distAndComponent = $definition['distributions'];
                }
            }

            // Case it is a rpm source repository
            // TODO

            // SSL authentication
            if (isset($definition['ssl-authentication']['certificate-path'])) {
                $sslCertificatePath = $definition['ssl-authentication']['certificate-path'];
            }
            if (isset($definition['ssl-authentication']['private-key-path'])) {
                $sslPrivateKeyPath = $definition['ssl-authentication']['private-key-path'];
            }
            if (isset($definition['ssl-authentication']['ca-certificate-path'])) {
                $sslCaCertificatePath = $definition['ssl-authentication']['ca-certificate-path'];
            } ?>

            <div class="table-container-3 bck-blue-alt pointer source-repo-edit-param-btn" source-id="<?= $item['Id'] ?>">
                <div>
                    <?php
                    if ($type == 'rpm') {
                        echo ' <span class="label-pkg-rpm">rpm</span>';
                    }
                    if ($type == 'deb') {
                        echo ' <span class="label-pkg-deb">deb</span>';
                    } ?>
                </div>

                <div>
                    <p><?= $name ?></p>
                    <p class="lowopacity-cst wordbreakall"><?= $url ?></p>
                </div>

                <div class="flex justify-end">
                    <img src="/assets/icons/delete.svg" class="source-repo-delete-btn icon-lowopacity" source-id="<?= $item['Id'] ?>" source-name="<?= $name ?>" title="Delete <?= $name ?> source repository" />
                </div>
            </div>

            <div class="hide source-repo-param-div detailsDiv margin-bottom-5" source-id="<?= $item['Id'] ?>">
                <form class="source-repo-form" source-id="<?= $item['Id'] ?>" autocomplete="off">
                    <h6 class="required">NAME</h6>
                    <input type="text" class="source-param" param-name="name" value="<?= $name ?>" />

                    <h6 class="required">URL</h6>
                    <input type="text" class="source-param" param-name="url" value="<?= $url ?>" />

                    <?php
                    if ($type == 'deb') :
                        echo '<h6>DISTRIBUTIONS</h6>';
                        echo '<p class="note">Embedded distributions.</p>';
                        
                        if (!empty($distAndComponent)) :
                            foreach ($distAndComponent as $distributionId => $distributionDetails) : ?>
                                <!-- Distributions -->
                                <div class="table-container grid-2 bck-blue-alt source-repo-distribution-edit-param-btn pointer" source-id="<?= $item['Id'] ?>" distribution-id="<?= $distributionId ?>">
                                    <div>
                                        <p><?= $distributionDetails['name'] ?></p>
                                        <p class="note"><?= $distributionDetails['description'] ?></p>

                                        <div class="flex column-gap-5">
                                            <?php
                                            foreach ($distributionDetails['components'] as $componentId => $componentDetails) {
                                                echo '<p class="label-black">' . $componentDetails['name'] . '</p>';
                                            } ?>
                                        </div>
                                    </div>

                                    <div class="flex justify-end">
                                        <img src="/assets/icons/delete.svg" class="source-repo-delete-distribution-btn icon-lowopacity" source-id="<?= $item['Id'] ?>" distribution-id="<?= $distributionId ?>" title="Remove <?= $distributionDetails['name'] ?> distribution" />
                                    </div>
                                </div>
                                <?php
                            endforeach;
                        endif ?>

                        <div class="flex align-item-center column-gap-5">
                            <input type="text" class="source-repo-add-distribution-input" source-id="<?= $item['Id'] ?>" placeholder="Add distribution">
                            <button type="button" class="source-repo-add-distribution-btn btn-xxsmall-green" source-id="<?= $item['Id'] ?>" title="Add distribution">+</button>
                        </div>
                        <?php
                    endif ?>
                    
                    <h6>SSL AUTHENTICATION</h6>
                    <p class="note">Use a SSL certificate and private key to authenticate to the source repository<a href="https://github.com/lbr38/repomanager/wiki/05.-Manage-sources-repositories#edit-a-source-repository" target="_blank" rel="noopener noreferrer" title="See documentation"><img src="/assets/icons/external-link.svg" class="icon margin-left-5" /></a></p>

                    <h6>PATH TO SSL CERTIFICATE</h6>
                    <input type="text" class="source-param" param-name="ssl-certificate-path" value="<?= $sslCertificatePath ?>" placeholder="e.g. /var/lib/repomanager/ssl/my-editor/certificate.crt" />
    
                    <h6>PATH TO SSL PRIVATE KEY</h6>
                    <input type="text" class="source-param" param-name="ssl-private-key-path" value="<?= $sslPrivateKeyPath ?>" placeholder="e.g. /var/lib/repomanager/ssl/my-editor/private.key" />

                    <h6>PATH TO SSL CA CERTIFICATE</h6>
                    <input type="text" class="source-param" param-name="ssl-ca-certificate-path" value="<?= $sslCaCertificatePath ?>" placeholder="e.g. /var/lib/repomanager/ssl/my-editor/ca-certificate.crt" />

                    <br><br>
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
