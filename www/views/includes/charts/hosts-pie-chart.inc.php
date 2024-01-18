<script>
// Data
var pieChartData = {
    datasets: [{
        data: [<?= $datas ?>],
        backgroundColor: [<?= $backgrounds ?>],
        borderWidth: 0.4,
    }],
    labels: [<?= $labels ?>],
};
// Options
var pieChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
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
window.myPie = new Chart(ctx, {
    type: "pie",
    data: pieChartData,
    options: pieChartOptions
});
</script>