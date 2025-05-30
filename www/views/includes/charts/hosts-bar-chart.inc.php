<script>
$(document).ready(function() {
    // Remove loading spinner
    $('#<?= $chartId ?>-loading').remove();

    // Data
    var barChartData = {
        datasets: [{
            data: [<?= $datas ?>],
            backgroundColor: [<?= $backgrounds ?>],
            borderWidth: 0.4,
            maxBarThickness: 20,
        }],
        labels: [<?= $labels ?>],
    };
    // Options
    var barChartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            title: {
                display: true,
                text: "<?= $title ?>"
            }
        },
        elements: {
            point: {
                radius: 0
            }
        },
        scales: {
            x: {
                ticks: {
                    color: '#8A99AA',
                    font: {
                        size: 14,
                        family: 'Roboto'
                    },
                    stepSize: 1
                }
            },
            y: {
                ticks: {
                    color: '#8A99AA',
                    font: {
                        size: 12,
                        family: 'Roboto'
                    },
                    stepSize: 1
                }
            }
        }
    }
    // Print chart
    var ctx = document.getElementById('<?= $chartId ?>').getContext("2d");
    window.myBar = new Chart(ctx, {
        type: "bar",
        data: barChartData,
        options: barChartOptions
    });
});
</script>