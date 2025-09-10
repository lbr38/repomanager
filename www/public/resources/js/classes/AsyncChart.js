/**
 *  Create charts with ChartJS
 *  - A <canvas> element with the same ID as the chart must exist in the HTML
 *  - The server must return the data with all the data needed to create the chart (.vars.inc.php file must exist)
 *  - (optional) A loading spinner with the ID <chartID>-loading must exist in the HTML
 */
class AsyncChart
{
    constructor(type, id, autoUpdate = true, autoUpdateInterval = 10000)
    {
        this.datasets = [];
        this.labels = [];
        this.chartOptions = [];
        this.animate = '';

        this.id = id;
        this.type = type;
        this.autoUpdate = autoUpdate;

        // Call the appropriate chart creation method based on the type
        if (typeof this[type] === 'function') {
            this[type](id);
        }

        // If autoUpdate is enabled, set an interval to update the chart
        // Default: update every 10 seconds
        if (this.autoUpdate) {
            setInterval(() => {
                // Call the appropriate chart creation method based on the type
                if (typeof this[type] === 'function') {
                    this[type](id);
                }
            }, autoUpdateInterval);
        }
    }

    /**
     * Check if a chart with the given ID already exists
     * @param {*} id 
     */
    exists(id)
    {
        try {
            // Check if the chart already exists and destroy it
            var existing_chart = Chart.getChart(id);
            existing_chart.destroy();

            // Disable animation when the chart already exists
            this.animate = 'none';
        } catch (e) {
            // Chart does not exist, do nothing
        }
    }

    /**
     * Get chart data by ID
     * @param {*} id
     * @returns
     */
    get(id)
    {
        return new Promise((resolve, reject) => {
            try {
                ajaxRequest(
                    // Controller:
                    'chart',
                    // Action:
                    'get',
                    // Data:
                    {
                        id: id,
                        sourceGetParameters: getGetParams()
                    },
                    // Print success alert:
                    false,
                    // Print error alert:
                    true
                ).then(() => {
                    // Parse the response and store it in the class properties
                    this.datasets     = jsonValue.message.datasets;
                    this.labels       = jsonValue.message.labels;
                    this.chartOptions = jsonValue.message.options;

                    // For debugging purposes only
                    // console.log("datasets: " + JSON.stringify(this.datasets));
                    // console.log("labels: " + JSON.stringify(this.labels));
                    // console.log("options: " + JSON.stringify(this.chartOptions));

                    // Resolve promise
                    resolve('Chart data retrieved');
                });
            } catch (error) {
                // Reject promise
                reject('Failed to get chart data');
            }
        });
    }

    /**
     * Create a bar chart from the given ID
     * @param {*} id
     * @returns
     */
    bar(id)
    {
        // Get chart data
        this.get(id).then(() => {
            // Remove loading spinner
            $('#' + id + '-loading').remove();

            // Data
            var barChartData = {
                labels: [],
                datasets: []
            };

            // Options
            var barChartOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    // Title
                    title: {
                        color: '#8A99AA',
                        font: {
                            size: 14,
                            family: 'Roboto'
                        }
                    },

                    // Legend
                    legend: {
                        // Do not display the legend as it is not needed for bar charts
                        display: false
                    }
                },

                elements: {
                    point: {
                        radius: 0
                    }
                },

                // Scales
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

            // Destroy current chart if it exists
            this.exists(id);

            // Initialize chart
            var ctx = document.getElementById(id).getContext("2d");

            // Create a new chart instance
            window.myBar = new Chart(ctx, {
                type: "bar",
                data: barChartData,
                options: barChartOptions
            });

            // Update chart with data from the server

            // Labels
            window.myBar.data.labels = this.labels;

            // Datasets
            window.myBar.data.datasets = this.datasets.map((dataset, index) => {
                return {
                    data: dataset.data,
                    backgroundColor: dataset.backgroundColor,
                    borderColor: dataset.borderColor,
                    borderWidth: 0.4,
                    maxBarThickness: 18,
                    fill: true,
                };
            });

            // Options
            // Title
            window.myBar.options.plugins.title.display = this.chartOptions.title.display || true;
            window.myBar.options.plugins.title.text = this.chartOptions.title.text || '';
            window.myBar.options.plugins.title.position = this.chartOptions.title.position || 'top';

            // Legend
            if (this.chartOptions.legend) {
                window.myBar.options.plugins.legend.display = this.chartOptions.legend.display || true;
                window.myBar.options.plugins.legend.position = this.chartOptions.legend.position || 'bottom';
            }

            // Update the chart
            window.myBar.update(this.animate);
        });
    }

    /**
     * Create a horizontal bar chart from the given ID
     * @param {*} id
     * @returns
     */
    horizontalBar(id)
    {
        // Get chart data
        this.get(id).then(() => {
            // Remove loading spinner
            $('#' + id + '-loading').remove();

            // Data
            var barChartData = {
                labels: [],
                datasets: []
            };

            // Options
            var barChartOptions = {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    // Title
                    title: {
                        color: '#8A99AA',
                        font: {
                            size: 14,
                            family: 'Roboto'
                        }
                    },

                    // Legend
                    legend: {
                        // Do not display the legend as it is not needed for horizontal bar charts
                        display: false
                    }
                },

                elements: {
                    point: {
                        radius: 0
                    },
    
                    bar: {
                        borderWidth: 2,
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

            // Destroy current chart if it exists
            this.exists(id);

            // Initialize chart
            var ctx = document.getElementById(id).getContext("2d");

            // Create a new chart instance
            window.myBar = new Chart(ctx, {
                type: "bar",
                data: barChartData,
                options: barChartOptions
            });

            // Update chart with data from the server

            // Labels
            window.myBar.data.labels = this.labels;

            // Datasets
            window.myBar.data.datasets = this.datasets.map((dataset, index) => {
                return {
                    data: dataset.data,
                    backgroundColor: dataset.backgroundColor,
                    borderColor: dataset.borderColor,
                    borderWidth: 0.4,
                    maxBarThickness: 18,
                    fill: true,
                };
            });

            // Options
            // Title
            window.myBar.options.plugins.title.display = this.chartOptions.title.display || true;
            window.myBar.options.plugins.title.text = this.chartOptions.title.text || '';
            window.myBar.options.plugins.title.position = this.chartOptions.title.position || 'top';

            // Legend
            if (this.chartOptions.legend) {
                window.myBar.options.plugins.legend.display = this.chartOptions.legend.display || true;
                window.myBar.options.plugins.legend.position = this.chartOptions.legend.position || 'bottom';
            }

            // Update the chart
            window.myBar.update(this.animate);
        });
    }

    /**
     * Create a pie chart from the given ID
     * @param {*} id
     * @returns
     */
    pie(id)
    {
        // Get chart data
        this.get(id).then(() => {
            // Remove loading spinner
            $('#' + id + '-loading').remove();

            // Data
            var pieChartData = {
                labels: [],
                datasets: []
            };

            // Options
            var pieChartOptions = {
                responsive: true,
                maintainAspectRatio: false,
                tension: 0.2,
                borderJoinStyle: "round",
                plugins: {
                    // Title
                    title: {
                        color: '#8A99AA',
                        font: {
                            size: 14,
                            family: 'Roboto'
                        }
                    },

                    // Legend
                    legend: {
                        position: 'left',
                        labels: {
                            font: {
                                size: 14,
                                family: 'Roboto',
                            },
                            color: '#8A99AA',
                            usePointStyle: true,
                            useBorderRadius: true,
                            borderRadius: 5,
                        },
                    }
                }
            }

            // Destroy current chart if it exists
            this.exists(id);

            // Initialize chart
            var ctx = document.getElementById(id).getContext("2d");

            // Create a new chart instance
            window.myPie = new Chart(ctx, {
                type: "pie",
                data: pieChartData,
                options: pieChartOptions
            });

            // Update chart with data from the server

            // Labels
            window.myPie.data.labels = this.labels;

            // Datasets
            window.myPie.data.datasets = this.datasets.map((dataset, index) => {
                return {
                    data: dataset.data,
                    backgroundColor: dataset.backgroundColor,
                    borderColor: 'black',
                    borderWidth: 0.2,
                    fill: true,
                };
            });

            // Options
            // Title
            window.myPie.options.plugins.title.display = this.chartOptions.title.display || true;
            window.myPie.options.plugins.title.text = this.chartOptions.title.text || '';
            window.myPie.options.plugins.title.position = this.chartOptions.title.position || 'top';

            // Legend
            if (this.chartOptions.legend) {
                window.myPie.options.plugins.legend.display = this.chartOptions.legend.display || true;
                window.myPie.options.plugins.legend.position = this.chartOptions.legend.position || 'bottom';
            }

            // Update the chart
            window.myPie.update(this.animate);
        });
    }

    /**
     * Create a line chart from the given ID
     * @param {*} id
     * @returns
     */
    line(id)
    {
        // Get chart data
        this.get(id).then(() => {
            // Remove loading spinner
            $('#' + id + '-loading').remove();

            // Data
            var lineChartData = {
                labels: [],
                datasets: []
            };

            // Options
            var lineChartOptions = {
                responsive: true,
                maintainAspectRatio: false,
                tension: this.chartOptions.tension || 0.2,
                borderJoinStyle: "round",
                plugins: {
                    // Title
                    title: {
                        color: '#8A99AA',
                        font: {
                            size: 14,
                            family: 'Roboto',
                        }
                    },

                    // Legend
                    legend: {
                        labels: {
                            font: {
                                size: 14,
                                family: 'Roboto',
                            },
                            color: '#8A99AA',
                            usePointStyle: true,
                            useBorderRadius: true,
                            borderRadius: 5
                        }
                    }
                },

                // Scales
                scales: {
                    x: {
                        display: true,
                        ticks: {
                            color: '#8A99AA',
                            font: {
                                size: 13,
                                family: 'Roboto'
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        display: true,
                        ticks: {
                            color: '#8A99AA',
                            font: {
                                size: 13,
                                family: 'Roboto'
                            },
                            stepSize: 1
                        }
                    }
                },
            }

            // Destroy current chart if it exists
            this.exists(id);

            // Initialize chart
            var ctx = document.getElementById(id).getContext("2d");

            // Create a new chart instance
            window.myLine = new Chart(ctx, {
                type: "line",
                data: lineChartData,
                options: lineChartOptions
            });

            // Update chart with data from the server

            // Labels
            window.myLine.data.labels = this.labels;

            // Datasets
            window.myLine.data.datasets = this.datasets.map((dataset, index) => {
                return {
                    label: dataset.label,
                    data: dataset.data,
                    backgroundColor: dataset.backgroundColor,
                    borderColor: dataset.borderColor,
                    fill: true,
                };
            });

            // Options
            // Title
            window.myLine.options.plugins.title.display = this.chartOptions.title.display || true;
            window.myLine.options.plugins.title.text = this.chartOptions.title.text || '';
            window.myLine.options.plugins.title.position = this.chartOptions.title.position || 'top';

            // Legend
            if (this.chartOptions.legend) {
                window.myLine.options.plugins.legend.display = this.chartOptions.legend.display || true;
                window.myLine.options.plugins.legend.position = this.chartOptions.legend.position || 'bottom';
            }

            // Scales
            if (this.chartOptions.scales) {
                // x axis
                // If a custom ticks callback is provided, convert it to a function
                if (this.chartOptions.scales.x && this.chartOptions.scales.x.ticks && this.chartOptions.scales.x.ticks.callback) {
                    this.chartOptions.scales.x.ticks.callback = new Function(
                        'context',
                        this.chartOptions.scales.x.ticks.callback.replace(/^function\s*\(value\)\s*{/, '').replace(/}$/, '')
                    );
                    window.myLine.options.scales.x.ticks.callback = this.chartOptions.scales.x.ticks.callback;
                }

                // y axis
                // If a custom ticks callback is provided, convert it to a function
                if (this.chartOptions.scales.y && this.chartOptions.scales.y.ticks && this.chartOptions.scales.y.ticks.callback) {
                    this.chartOptions.scales.y.ticks.callback = new Function(
                        'value',
                        this.chartOptions.scales.y.ticks.callback.replace(/^function\s*\(value\)\s*{/, '').replace(/}$/, '')
                    );
                    window.myLine.options.scales.y.ticks.callback = this.chartOptions.scales.y.ticks.callback;
                }
            }

            // Tooltip
            if (this.chartOptions.tooltip) {
                // If a custom label callback is provided, convert it to a function
                if (this.chartOptions.tooltip.callbacks && this.chartOptions.tooltip.callbacks.label) {
                    this.chartOptions.tooltip.callbacks.label = new Function(
                        'context',
                        this.chartOptions.tooltip.callbacks.label.replace(/^function\s*\(context\)\s*{/, '').replace(/}$/, '')
                    );
                    window.myLine.options.plugins.tooltip.callbacks.label = this.chartOptions.tooltip.callbacks.label;
                }
            }

            // Update the chart
            window.myLine.update(this.animate);
        });
    }
}
