<script>
// Data
var donutChartData = {
    datasets: [{
        data: [<?= $datas ?>],
        backgroundColor: [<?= $backgrounds ?>],
        borderWidth: 0.4,
    }],
    labels: [<?= $labels ?>],
};
// Options
var donutChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: 30,
    plugins: {
        legend: {
            display: true,
            position: 'left'
        },
        title: {
            display: true,
            text: '<?= $title ?>'
        }
    },
}
// Print chart
var ctx = document.getElementById('<?= $chartId ?>').getContext("2d");
window.myDonut = new Chart(ctx, {
    type: "doughnut",
    data: donutChartData,
    options: donutChartOptions
});
</script>