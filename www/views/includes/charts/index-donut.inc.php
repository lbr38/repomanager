<?php

if ($diskUsedSpacePercent > 0 && $diskUsedSpacePercent <= 30) {
    $borderColor = "'#15bf7f','#15bf7f'";
    $donutColor = "'#15bf7f', 'rgba(21, 191, 127, 0.40)',";
}
if ($diskUsedSpacePercent > 30 && $diskUsedSpacePercent <= 50) {
    $borderColor = "'#ffb536','#ffb536'";
    $donutColor = "'#ffb536', 'rgba(255, 181, 54, 0.40)',";
}
if ($diskUsedSpacePercent > 50 && $diskUsedSpacePercent <= 70) {
    $borderColor = "'#ff7c49','#ff7c49'";
    $donutColor = "'#ff7c49', 'rgba(255, 124, 73, 0.40)',";
}
if ($diskUsedSpacePercent > 70 && $diskUsedSpacePercent <= 100) {
    $borderColor = "'#F32F63','#F32F63'";
    $donutColor = "'#F32F63', 'rgba(243, 47, 99, 0.40)',";
} ?>

<canvas id="diskSpaceChart-<?= $donutChartName ?>" class="donut-chart"></canvas>

<script>
$(document).ready(function() {
    var diskUsedSpaceHuman = "<?= $diskUsedSpaceHuman ?>";
    var diskFreeSpaceHuman = "<?= $diskFreeSpaceHuman ?>";

    // Data
    var doughnutChartData = {
        datasets: [{
            labels: ['Used space', 'Free space'],
            data: [<?= "$diskUsedSpacePercent, $diskFreeSpacePercent" ?>],
            backgroundColor: [<?= $donutColor ?>],
            borderColor: [<?= $borderColor ?>],
            borderWidth: 0.4
        }],
        labels: ['Used space', 'Free space']
    };

    // Options
    var doughnutChartOptions = {
        responsive: true,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        let value = context.parsed;
                        let human = context.dataIndex === 0 ? diskUsedSpaceHuman : diskFreeSpaceHuman;
                        return [
                            label + ': ',
                            value + '% (' + human + ')'
                        ];
                    }
                }
            }
        },
        cutout: '90%',
        elements: {
            point: {
                radius: 0
            }
        },
    }

    // Print chart
    var ctx = document.getElementById('diskSpaceChart-<?=$donutChartName?>').getContext("2d");
    window.myDoughnut = new Chart(ctx, {
        type: "doughnut",
        data: doughnutChartData,
        options: doughnutChartOptions
    });
});
</script>