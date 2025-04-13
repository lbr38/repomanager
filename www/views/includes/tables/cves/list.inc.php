<div class="reloadable-table" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <p class="text-right margin-bottom-15">
        <b><?= $reloadableTableTotalItems ?></b> CVEs
    </p>

    <?php
    if (!empty($reloadableTableContent)) : ?>
        <div class="table-container cve-table bck-blue-alt">
            <p><b>Score</b></p>
            <p><b>CVE</b></p>
            <p><b>Vendors</b></p>
            <p><b>Products</b></p>
            <p><b>Affected hosts</b></p>
            <p><b>Description</b></p>
        </div>

        <?php
        foreach ($reloadableTableContent as $item) :
            $cveCpe = $mycve->getCpe($item['Id']);
            $vendors = array_unique(array_column($cveCpe, 'Vendor'));
            $products = array_unique(array_column($cveCpe, 'Product'));

            /**
             *  Get affected hosts count
             */
            $possibleAffectedHosts = $mycve->getAffectedHosts($item['Id'], 'possible');
            $possibleAffectedHostsCount = count($possibleAffectedHosts);
            $affectedHosts = $mycve->getAffectedHosts($item['Id'], 'affected');
            $affectedHostsCount = count($affectedHosts); ?>

            <div class="table-container cve-table bck-blue-alt">
                <div class="flex column-gap-10">
                    <div class="flex flex-direction-column align-item-center" title="CVSS3 Score">
                        <p><b>CVSS3</b></p>
                        <p>
                            <?php
                            if (!empty($item['Cvss3_score'])) {
                                if ($item['Cvss3_score'] >= 0 and $item['Cvss3_score'] <= 3.9) {
                                    echo '<span class="label-white" title="Severity: low">' . $item['Cvss3_score'] . '</span>';
                                } elseif ($item['Cvss3_score'] >= 4 and $item['Cvss3_score'] <= 6.9) {
                                    echo '<span class="label-yellow" title="Severity: medium">' . $item['Cvss3_score'] . '</span>';
                                } elseif ($item['Cvss3_score'] >= 7 and $item['Cvss3_score'] <= 8.9) {
                                    echo '<span class="label-red" title="Severity: high">' . $item['Cvss3_score'] . '</span>';
                                } elseif ($item['Cvss3_score'] >= 8.9 and $item['Cvss3_score'] <= 10) {
                                    echo '<span class="label-red" title="Severity: critical">' . $item['Cvss3_score'] . '</span>';
                                }
                            } else {
                                echo '<code>N/A</code>';
                            } ?>
                        </p>
                    </div>
                    <div class="flex flex-direction-column align-item-center" title="CVSS2 Score">
                        <p><b>CVSS2</b></p>
                        <p>
                            <?php
                            if (!empty($item['Cvss2_score'])) {
                                if ($item['Cvss2_score'] >= 0 and $item['Cvss2_score'] <= 3.9) {
                                    echo '<span class="label-white" title="Severity: low">' . $item['Cvss2_score'] . '</span>';
                                } elseif ($item['Cvss2_score'] >= 4 and $item['Cvss2_score'] <= 6.9) {
                                    echo '<span class="label-yellow" title="Severity: medium">' . $item['Cvss2_score'] . '</span>';
                                } elseif ($item['Cvss2_score'] >= 7 and $item['Cvss2_score'] <= 8.9) {
                                    echo '<span class="label-red" title="Severity: high">' . $item['Cvss2_score'] . '</span>';
                                } elseif ($item['Cvss2_score'] >= 8.9 and $item['Cvss2_score'] <= 10) {
                                    echo '<span class="label-red" title="Severity: critical">' . $item['Cvss2_score'] . '</span>';
                                }
                            } else {
                                echo '<code>N/A</code>';
                            } ?>
                        </p>
                    </div>
                </div>

                <div>
                    <p>
                        <b><a href="/cve?nameid=<?= $item['Name'] ?>" target="_blank" rel="noopener noreferrer"><?= $item['Name'] ?></a></b>
                    </p>

                    <p class="lowopacity-cst" title="First published on <?= $item['Date'] . ' ' . $item['Time'] ?>">
                        <?= $item['Updated_date'] . ' ' . $item['Updated_time'] ?>
                    </p>
                </div>

                <div>
                    <?php
                    if (!empty($vendors)) : ?>
                        <div class="flex flex-wrap column-gap-4 row-gap-4">
                            <?php
                            foreach ($vendors as $vendor) {
                                echo '<a href="/cves?vendor=' . $vendor . '"><span class="label-white">' . $vendor . '</span></a>';
                            } ?>
                        </div>
                        <?php
                    endif ?>
                </div>

                <div>
                    <?php
                    if (!empty($products)) : ?>
                        <div class="flex flex-wrap column-gap-4 row-gap-4">
                            <?php
                            foreach ($products as $product) {
                                echo '<a href="/cves?product=' . $product . '"><span class="label-white">' . $product . '</span></a>';
                            } ?>
                        </div>
                        <?php
                    endif ?>
                </div>

                <div>
                    <div class="flex column-gap-4">
                        <?php
                        if ($affectedHostsCount > 0) {
                            echo '<span class="label-red" title="Affected hosts">' . $affectedHostsCount . ' </span>';
                        }
                        if ($possibleAffectedHostsCount > 0) {
                            echo '<span class="label-yellow" title="Possible affected hosts">' . $possibleAffectedHostsCount . ' </span>';
                        } ?>
                    </div>
                </div>

                <div>
                    <p class="wordbreakall"><?= $item['Description'] ?></p>
                </div>
            </div>
            <?php
        endforeach; ?>
        
        <div class="flex justify-end margin-top-10">
            <?php \Controllers\Layout\Table\Render::paginationBtn($reloadableTableCurrentPage, $reloadableTableTotalPages); ?>
        </div>

        <?php
    endif ?>
</div>
