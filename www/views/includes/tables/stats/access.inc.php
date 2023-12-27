<div class="reloadable-table" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <?php
    if (!empty($reloadableTableContent)) : ?>
        <h5>ACCESS REQUESTS (<?= $reloadableTableTotalItems ?>)</h5>
        <br>

        <?php
        foreach ($reloadableTableContent as $item) :
            /**
             *  Retrieve the target (package or file) from the request
             *  Here the preg_match allows to retrieve the package or file name from the complete URL
             *  It retrieves an occurence composed of letters, numbers and special characters and which begins with a slash '/' and ends with a space [[:space:]]
             *  e.g:
             *  GET /repo/debian-security/buster/updates/main_test/pool/main/b/bind9/bind9-host_9.11.5.P4%2bdfsg-5.1%2bdeb10u6_amd64.deb HTTP/1.1
             *                                                                      |                                                   |
             *                                                                      |_                                                  |_
             *                                                                        |                                                   |
             *                                                                preg_match retrives the occurence between a slash and a space
             *  It retrieves only an occurence composed of letters, numbers and some special characters like - _ . and %
             */
            preg_match('#/[a-zA-Z0-9\%_\.-]+[[:space:]]#i', $item['Request'], $accessTarget);
            $accessTarget[0] = str_replace('/', '', $accessTarget[0]); ?>

            <div class="table-container column-gap-15 stats-access-request">
                <div>
                    <?php
                    if ($item['Request_result'] == '200' or $item['Request_result'] == '304') {
                        echo '<img src="/assets/icons/greencircle.png" class="icon-small" title="' . $item['Request_result'] . '" />';
                    } else {
                        echo '<img src="/assets/icons/redcircle.png" class="icon-small" title="' . $item['Request_result'] . '" />';
                    } ?>
                </div>
                
                <div>
                    <p><?= DateTime::createFromFormat('Y-m-d', $item['Date'])->format('d-m-Y') . ' ' . $item['Time'] ?></p>
                    <p class="lowopacity-cst">
                        <?php
                        if ($item['Request_result'] == '200') {
                            echo 'Request OK';
                        } elseif ($item['Request_result'] == '304') {
                            echo 'Request OK (not modified)';
                        } else {
                            echo 'Request KO';
                        } ?>
                    </p>
                </div>

                <div>
                    <p class="copy" title="Source host"><?= $item['Source'] ?></p>
                    <p class="lowopacity-cst copy" title="Source IP"><?= $item['IP'] ?></p>
                </div>

                <div>
                    <p class="copy wordbreakall" title="Requested file"><?= $accessTarget[0] ?></p>
                    <p class="lowopacity-cst wordbreakall copy" title="Full request"><?= str_replace('"', '', $item['Request']) ?></p>
                </div>
            </div>
            <?php
        endforeach; ?>
        
        <div class="flex justify-end">
            <?php \Controllers\Layout\Table\Render::paginationBtn($reloadableTableCurrentPage, $reloadableTableTotalPages); ?>
        </div>

        <?php
    endif ?>
</div>
