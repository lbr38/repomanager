<section class="section-main">
    
    <a href="/cves"><h3>CVE</h3></a>

    <p>
        <?php
        if (isset($totalImportedCve)) {
            echo '<b>' . $totalImportedCve . '</b> imported CVEs</p>';
        }
        if (!empty($totalCveFound)) {
            echo '<b>' . $totalCveFound . '</b> CVEs found</p>';
        } ?>
    </p>

    <div id="title-button-div">
        <div>
            <p>Search in CVEs:</p>
            <form action="/cves" method="get">
                <input class="input-medium" type="text" name="search" autocomplete="off">
            </form>
        </div>

        <?php
        if (isset($currentPage) and !empty($pagesCount)) : ?>
            <div id="title-button-container" class="flex-direction-column align-item-right row-gap-5">
                <div>
                    <?php
                    if (!empty($currentPage) and !empty($pagesCount)) : ?>
                        <span>Page: <b><?= $currentPage ?></b> / <?= $pagesCount ?></span>
                        <?php
                    endif ?>
                </div>
                <div>
                    <?php
                    if ($previousPage != $currentPage) : ?>
                        <a href="<?= $previousPageLink ?>">
                            <button class="btn-small-green">Previous</button>
                        </a>
                        <?php
                    endif;

                    if ($currentPage != $pagesCount) : ?>
                        <a href="<?= $nextPageLink ?>">
                            <button class="btn-small-green">Next</button>
                        </a>
                        <?php
                    endif; ?>
                </div>
            </div>
            <?php
        endif ?>
    </div>
    
    <br><br>

    <div id="cves-div" class="div-generic-blue">           
        <table id="cve-table" class="table-generic-blue">
            <thead>
                <tr class="first-row">
                    <td class="td-10" title="CVSS3 Score">Score</td>
                    <td class="td-10">CVE</td>
                    <td class="td-10">Published</td>
                    <td class="td-10">Last updated</td>
                    <td class="td-10">Vendor</td>
                    <td class="td-10">Affected product</td>
                    <td class="td-10">Affected hosts</td>
                    <td class="wordbreakall">Description</td>
                </tr>
            </thead>

            <?php
            foreach ($cveIdList as $cveId) :
                $vendors = array();
                $products = array();
                $cveDetails = $mycve->get($cveId);
                $cveCpeDetails = $mycve->getCpe($cveId);
                $possibleAffectedHostsCount = 0;
                $affectedHostsCount = 0;

                /**
                 *  Get affected hosts count
                 */
                $possibleAffectedHosts = $mycve->getAffectedHosts($cveId, 'possible');
                $affectedHosts = $mycve->getAffectedHosts($cveId, 'affected');

                if (!empty($possibleAffectedHosts)) {
                    $possibleAffectedHostsCount = count(array_unique(array_column($possibleAffectedHosts, 'Host_id')));
                }
                if (!empty($affectedHosts)) {
                    $affectedHostsCount = count(array_unique(array_column($affectedHosts, 'Host_id')));
                }

                /**
                 *  Parse cpe details
                 */
                if (!empty($cveCpeDetails)) {
                    $lastVendor = '';
                    $lastProduct = '';

                    foreach ($cveCpeDetails as $cpeDetails) {
                        if ($cpeDetails['Vendor'] != $lastVendor) {
                            $vendors[] = $cpeDetails['Vendor'];
                        }

                        if ($cpeDetails['Product'] != $lastProduct) {
                            $products[] = $cpeDetails['Product'];
                        }

                        $lastVendor = $cpeDetails['Vendor'];
                        $lastProduct = $cpeDetails['Product'];
                    }
                } ?>
                
                <tr>
                    <td class="td-10">
                        <?php
                        if (!empty($cveDetails['Cvss3_score'])) {
                            if ($cveDetails['Cvss3_score'] >= 0 and $cveDetails['Cvss3_score'] <= 3.9) {
                                echo '<span class="label-white" title="Severity: low">' . $cveDetails['Cvss3_score'] . '</span>';
                            } elseif ($cveDetails['Cvss3_score'] >= 4 and $cveDetails['Cvss3_score'] <= 6.9) {
                                echo '<span class="label-yellow" title="Severity: medium">' . $cveDetails['Cvss3_score'] . '</span>';
                            } elseif ($cveDetails['Cvss3_score'] >= 7 and $cveDetails['Cvss3_score'] <= 8.9) {
                                echo '<span class="label-red" title="Severity: high">' . $cveDetails['Cvss3_score'] . '</span>';
                            } elseif ($cveDetails['Cvss3_score'] >= 8.9 and $cveDetails['Cvss3_score'] <= 10) {
                                echo '<span class="label-red" title="Severity: critical">' . $cveDetails['Cvss3_score'] . '</span>';
                            }
                        } else {
                            echo 'N/A';
                        } ?>
                    </td>
                    <td class="td-10">
                        <b><a href="/cve?nameid=<?= $cveDetails['Name'] ?>" target="_blank" rel="noopener noreferrer"><?= $cveDetails['Name'] ?></a></b>
                    </td>
                    <td class="td-10">
                        <?= $cveDetails['Date'] . ' ' . $cveDetails['Time'] ?>
                    </td>
                    <td class="td-10">
                        <?= $cveDetails['Updated_date'] . ' ' . $cveDetails['Updated_time'] ?>
                    </td>
                    <td class="td-10">
                        <?php
                        if (!empty($vendors)) : ?>
                            <div class="flex flex-wrap column-gap-4 row-gap-4">
                                <?php
                                foreach ($vendors as $vendor) {
                                    echo '<a href="/cves?vendor=' . $vendor . '"><span class="label-white">' . $vendor . '</span></a>';
                                } ?>
                            <div>
                            <?php
                        endif ?>
                    </td>
                    <td class="td-10">
                        <?php
                        if (!empty($products)) : ?>
                            <div class="flex flex-wrap column-gap-4 row-gap-4">
                                <?php
                                foreach ($products as $product) {
                                    echo '<a href="/cves?product=' . $product . '"><span class="label-white">' . $product . '</span></a>';
                                } ?>
                            <div>
                            <?php
                        endif ?>
                    </td>
                    <td class="td-10">
                        <div class="flex column-gap-4">
                            <?php
                            if (!empty($affectedHostsCount) and $affectedHostsCount > 0) {
                                echo '<span class="label-red" title="Affected hosts">' . $affectedHostsCount . ' </span>';
                            }
                            if (!empty($possibleAffectedHostsCount) and $possibleAffectedHostsCount > 0) {
                                echo '<span class="label-yellow" title="Possible affected hosts">' . $possibleAffectedHostsCount . '</span>';
                            } ?>
                        </div>
                    </td>
                    <td class="wordbreakall">
                        <?= $cveDetails['Description'] ?>
                    </td>
                </tr>
                <?php
            endforeach ?>
        </table>
    </div>
</section>