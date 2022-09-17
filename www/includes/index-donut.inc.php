<?php

if ($diskUsedSpace > 0 && $diskUsedSpace <= 30) {
    $donutColor = "'#15bf7f',";
}
if ($diskUsedSpace > 30 && $diskUsedSpace <= 50) {
    $donutColor = "'#ffb536',";
}
if ($diskUsedSpace > 50 && $diskUsedSpace <= 70) {
    $donutColor = "'rgba(255, 124, 73, 0.8)',";
}
if ($diskUsedSpace > 70 && $diskUsedSpace <= 100) {
    $donutColor = "'#ff0044',";
}
$donutColor .= "'rgb(247, 247, 247, 0)'"; // transparent (opacité 0) (Free space)
?>

<canvas id="diskSpaceChart-<?=$donutChartName?>" class="donut-chart"></canvas>

<script>
// Données
var doughnutChartData = {
    datasets: [{
        labels: ['Used space', 'Free space'],
        borderWidth: 3,
        data: [<?= "$diskUsedSpace, $diskFreeSpace" ?>],
        backgroundColor: [<?=$donutColor?>],
        borderColor: [
            'gray',
            'gray'
        ],
        borderWidth: 0.4
    }],
    labels: ['Used space %', 'Free space %']
};

// Options
var doughnutChartOptions = {
    responsive: true,
    plugins: {
        legend: {
            display: false
        },
    },
    cutout: 55,
    elements: {
        point: {
            radius: 0
        }
    },
}

// Affichage du chart
var ctx = document.getElementById('diskSpaceChart-<?=$donutChartName?>').getContext("2d");
window.myDoughnut = new Chart(ctx, {
    type: "doughnut",
    data: doughnutChartData,
    options: doughnutChartOptions
});
</script>