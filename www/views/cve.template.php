<section class="section-main">
    <h3><?= strtoupper($nameId) ?></h3>

    <div class="div-generic-blue flex flex-direction-column row-gap-10">
        <div>
            <h6 class="margin-top-0">CVSS3 & CVSS2 SCORE</h6>
            <div class="flex align-item-center column-gap-15">
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
                    echo '<span class="label-white">N/A</span>';
                }

                if (!empty($cveDetails['Cvss2_score'])) {
                    if ($cveDetails['Cvss2_score'] >= 0 and $cveDetails['Cvss2_score'] <= 3.9) {
                        echo '<span class="label-white" title="Severity: low">' . $cveDetails['Cvss2_score'] . '</span>';
                    } elseif ($cveDetails['Cvss2_score'] >= 4 and $cveDetails['Cvss2_score'] <= 6.9) {
                        echo '<span class="label-yellow" title="Severity: medium">' . $cveDetails['Cvss2_score'] . '</span>';
                    } elseif ($cveDetails['Cvss2_score'] >= 7 and $cveDetails['Cvss2_score'] <= 8.9) {
                        echo '<span class="label-red" title="Severity: high">' . $cveDetails['Cvss2_score'] . '</span>';
                    } elseif ($cveDetails['Cvss2_score'] >= 8.9 and $cveDetails['Cvss2_score'] <= 10) {
                        echo '<span class="label-red" title="Severity: critical">' . $cveDetails['Cvss2_score'] . '</span>';
                    }
                } else {
                    echo '<span class="label-white">N/A</span>';
                } ?>
            </div>
        </div>

        <div>
            <h6>PUBLISHED DATE</h6>
            <p><?= $cveDetails['Date'] . ' ' . $cveDetails['Time'] . ' (Last updated ' . $cveDetails['Updated_date'] . ' ' . $cveDetails['Updated_time'] . ')' ?></p>
        </div>

        <div>
            <h6>DESCRIPTION</h6>
            <p><?= $cveDetails['Description'] ?></p>
        </div>
    </div>

    <div class="grid grid-rfr-1-2 column-gap-20 row-gap-20">
        <div class="div-generic-blue">
            <h5 class="margin-top-0">AFFECTED PRODUCTS</h5>

            <?php
            if (!empty($cveDetails['Cpe23Uri'])) : ?>
                <div>
                    <table class="table-generic-blue">
                        <thead>
                            <tr>
                                <td>CPE ver.</td>
                                <td>Part</td>
                                <td>Vendor</td>
                                <td>Product</td>
                                <td>Version</td>
                                <td>Edition</td>
                                <td>Language</td>
                                <td>sw_edition</td>
                                <td>target_sw</td>
                                <td>target_hw</td>
                                <td>Other</td>
                            </tr>
                        </thead>

                        <?php
                        $cpe23Uri = explode(',', $cveDetails['Cpe23Uri']);
                        $cpes = array_unique($cpe23Uri);

                        foreach ($cpes as $cpe) :
                            $cpeExplode = explode(':', $cpe); ?>
                            <tr>
                                <td><?= $cpeExplode[0] . ' ' . $cpeExplode[1] ?></td>
                                <td><?= $cpeExplode[2] ?></td>
                                <td><a href="/cves?vendor=<?= $cpeExplode[3] ?>"><span class="label-white"><?= $cpeExplode[3] ?></span></a></td>
                                <td><a href="/cves?product=<?= $cpeExplode[4] ?>"><span class="label-white"><?= $cpeExplode[4] ?></span></a></td>
                                <td><?= $cpeExplode[5] ?></td>
                                <td><?= $cpeExplode[6] ?></td>
                                <td><?= $cpeExplode[7] ?></td>
                                <td><?= $cpeExplode[8] ?></td>
                                <td><?= $cpeExplode[9] ?></td>
                                <td><?= $cpeExplode[10] ?></td>
                                <td><?= $cpeExplode[11] ?></td>
                            </tr>
                            <?php
                        endforeach ?>
                    </table>
                </div>
                <?php
            else : ?>
                <p>No data.</p>
                <?php
            endif ?>
        </div>

        <div class="div-generic-blue">
            <h5 class="margin-top-0">AFFECTED HOSTS</h5>

            <?php
            if (empty($possibleAffectedHosts) and empty($affectedHosts)) {
                // echo '<p>No affected host.</p>';
                echo '<p>Coming soon.</p>';
            }

            if (!empty($affectedHosts)) : ?>
                <h5>Affected hosts:</h5>

                <table class="table-generic-red">
                    <?php
                    $lastHostId = '';

                    foreach ($affectedHosts as $affectedHost) :
                        $hostDetails = $myhost->getAll($affectedHost['Host_id']);

                        if ($lastHostId != $hostDetails['Id']) : ?>
                            <tr>
                                <td><b><a href="/host/<?= $hostDetails['Id'] ?>" target="_blank" rel="noopener noreferrer"><?= $hostDetails['Hostname'] ?></a></b></td>
                                <td colspan="2"><b><?= ucfirst($hostDetails['Os']) . ' ' . $hostDetails['Os_version'] . ' - ' . $hostDetails['Kernel'] . ' ' . $hostDetails['Arch'] ?></b></td>
                            </tr>
                            <?php
                        endif ?>

                        <tr class="header-light-red">
                            <td></td>
                            <td colspan="2"><b>Product:</b> <?= $affectedHost['Product'] ?> (<?= $affectedHost['Version'] ?>) </td>
                        </tr>
                        <?php
                        $lastHostId = $hostDetails['Id'];
                    endforeach ?>
                </table>
                <?php
            endif;

            if (!empty($possibleAffectedHosts)) : ?>
                <h5>Possible affected hosts:</h5>
                <table class="table-generic-blue">
                    <?php
                    $lastHostId = '';

                    foreach ($possibleAffectedHosts as $possibleAffectedHost) :
                        $hostDetails = $myhost->getAll($possibleAffectedHost['Host_id']);

                        if ($lastHostId != $hostDetails['Id']) : ?>
                            <tr>
                                <td>
                                    <b><a href="/host/<?= $hostDetails['Id'] ?>" target="_blank" rel="noopener noreferrer"><?= $hostDetails['Hostname'] ?></a></b>
                                </td>
                                <td colspan="2">
                                    <b><?= ucfirst($hostDetails['Os']) . ' ' . $hostDetails['Os_version'] . ' - ' . $hostDetails['Kernel'] . ' ' . $hostDetails['Arch'] ?></b>
                                </td>
                            </tr>
                            <?php
                        endif ?>

                        <tr class="header-light-blue">
                            <td></td>
                            <td colspan="2">
                                <b>Product:</b> <?= $possibleAffectedHost['Product'] ?> (<?= $possibleAffectedHost['Version'] ?>)
                            </td>
                        </tr>
                        <?php
                        $lastHostId = $hostDetails['Id'];
                    endforeach ?>
                </table>
                <?php
            endif ?>
        </div>
    </div>


<div class="flex justify-space-between">
    <div class="flex-div-50 div-generic-blue">
                    
        <h4>REFERENCES</h4>

        <?php
        if (!empty($cveReferences)) : ?>
            <table class="table-generic-blue">
                <thead>
                    <tr>
                        <td>Url</td>
                        <td>Source</td>
                        <td>Tags</td>
                    </tr>
                </thead>

                <?php
                foreach ($cveReferences as $cveReference) : ?>    
                    <tr>
                        <td>
                            <a href="<?= $cveReference['Url'] ?>" target="_blank" rel="noopener noreferrer"><?= $cveReference['Url'] ?></a>
                        </td>
                        <td class="td-10">
                            <?php
                            if (!empty($cveReference['Source'])) {
                                echo '<span class="label-white">' .  $cveReference['Source'] . '</span>';
                            } else {
                                echo '<p>N/A</p>';
                            } ?>
                        </td>
                        <td class="td-10">
                            <div class="flex flex-wrap column-gap-10 row-gap-10">
                                <?php
                                if (!empty($cveReference['Tags'])) {
                                    $tags = explode(',', $cveReference['Tags']);
                                    foreach ($tags as $tag) {
                                        echo '<span class="label-white">' . $tag . '</span>';
                                    }
                                } ?>
                            </div>
                        </td>
                    </tr>
                    <?php
                endforeach ?>
            </table>
            <?php
        else :
            echo '<p>No resources</p>';
        endif;
        ?>
    </div>
</section>
