<div class="reloadable-table" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <?php
    if (!empty($reloadableTableContent)) : ?>
        <div class="div-generic-blue">
            <h6 class="margin-top-0">ACCESS REQUESTS (<?= $reloadableTableTotalItems ?>)</h6>
            <p class="note">This table shows the details of the access requests to the repository snapshot.</p>

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

                <div class="table-container bck-blue-alt column-gap-15 stats-access-request">
                    <div>
                        <?php
                        if ($item['Request_result'] == '200' or $item['Request_result'] == '304') {
                            echo '<img src="/assets/icons/check.svg" class="icon-np" title="' . $item['Request_result'] . '" />';
                        } else {
                            echo '<img src="/assets/icons/warning-red.svg" class="icon-np" title="' . $item['Request_result'] . '" />';
                        } ?>
                    </div>
                    
                    <div>
                        <p><?= DateTime::createFromFormat('Y-m-d', $item['Date'])->format('d-m-Y') . ' ' . $item['Time'] ?></p>
                        <p class="lowopacity-cst">
                            <?php
                            if ($item['Request_result'] == '200') {
                                echo 'Request OK - Status code 200';
                            } elseif ($item['Request_result'] == '304') {
                                echo 'OK - Status code 304';
                            } else {
                                echo 'KO - Status code ' . $item['Request_result'];
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
        </div>
        
        <div class="flex justify-end">
            <?php \Controllers\Layout\Table\Render::paginationBtn($reloadableTableCurrentPage, $reloadableTableTotalPages); ?>
        </div>

        <?php
    endif ?>
</div>
