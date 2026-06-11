<?php
$hostController = new \Controllers\Host\Host();
$hostListingController = new \Controllers\Host\Listing();
$datasets = [];
$labels = [];
$options = [];
$totalNotUptodate = 0;
$totalUptodate = 0;

// Getting hosts list
$hosts = $hostListingController->get();

// Loop through the list of hosts to determine the number of hosts up to date and not up to date
foreach ($hosts as $host) {
    if ($hostController->getCompliance($host['Id'])['compliant']) {
        $totalUptodate++;
    } else {
        $totalNotUptodate++;
    }
}

$labels[] = 'Compliant';
$labels[] = 'Not compliant';
$datasets[0]['data'][] = $totalUptodate;
$datasets[0]['data'][] = $totalNotUptodate;
$datasets[0]['colors'] = ['#24d794', '#F32F63'];
$options['title']['text'] = round(($totalUptodate / ($totalUptodate + $totalNotUptodate)) * 100, 2) . '% compliant';
$options['title']['align'] = 'left';
$options['title']['fontSize'] = 12;

unset($hostController, $hostListingController, $hosts, $totalUptodate, $totalNotUptodate);
