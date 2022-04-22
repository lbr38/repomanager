<?php
if ($diskUsedSpace > 0 && $diskUsedSpace <= 30) {
    $donutColor = "'rgb(92, 184, 92, 0.80)',";
}
if ($diskUsedSpace > 30 && $diskUsedSpace <= 50) {
    $donutColor = "'rgb(240, 173, 78, 0.80)',";
}
if ($diskUsedSpace > 50 && $diskUsedSpace <= 70) {
    $donutColor = "'rgb(240, 116, 78, 0.80)',";
}
if ($diskUsedSpace > 70 && $diskUsedSpace <= 100) {
    $donutColor = "'rgb(217, 83, 79, 0.80)',";
}
$donutColor .= "'rgb(247, 247, 247, 0)'"; // transparent (opacité 0) (espace libre)
?>

<canvas id="diskSpaceChart-<?=$donutChartName?>" class="donut-chart"></canvas>

<script>
// Données
var doughnutChartData = {
    datasets: [{
        labels: ['Espace utilisé', 'Espace libre'],
        borderWidth: 3,
        data: [<?= "$diskUsedSpace, $diskFreeSpace" ?>],
        backgroundColor: [<?=$donutColor?>],
        borderColor: [
            'gray',
            'gray'
        ],
        borderWidth: 0.4
    }],
    labels: ['Espace utilisé %', 'Espace libre %']
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