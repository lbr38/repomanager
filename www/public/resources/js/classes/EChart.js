// import * as echarts from 'echarts';

/**
 *  Create charts with ECharts
 *  - A <div> element with id="<chartID>" must exist in the HTML
 *  - The server must return all the data needed to create the chart (.vars.inc.php file must exist)
 *  - (optional) A loading spinner with id="<chartID>-loading" must exist in the HTML
 */
class EChart
{
    // Static registry to store all chart instances for external access
    static instances = {};

    // If on mobile, default to 1 day range, otherwise 3 days
    constructor(type, id, autoUpdate = true, autoUpdateInterval = 15000, days = window.innerWidth < 600 ? 1 : 3)
    {
        this.id                 = id;
        this.type               = type;
        this.autoUpdate         = autoUpdate;
        this.autoUpdateInterval = autoUpdateInterval;
        this.days               = days;
        this.datasets           = [];
        this.labels             = [];
        this.chartOptions       = [];
        this.animate            = '';

        // Default options (will be cloned before use)
        this.baseOptions = {
            title: {
                text: '',
                left: 'left',
                textStyle: {
                    color: '#8A99AA',
                    fontFamily: 'Roboto',
                    fontSize: 16,
                }
            },
            tooltip: {
                show: true,
                trigger: 'item',
                confine: true, // Force tooltip to stay within container boundaries
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                textStyle: {
                    color: '#fff'
                },
                formatter: (params) => {
                    if (!params) return '';
                    
                    // For pie charts
                    if (params.seriesType === 'pie') {
                        return `${params.marker} ${params.name}: ${params.value} (${params.percent}%)`;
                    }
                    
                    // For line charts (existing code)
                    if (params.length === 0) return '';
                    
                    const timestamp = params[0].axisValue;
                    const d = new Date(Number(timestamp));
                    let result = d.toLocaleString(undefined, {
                        year: 'numeric', month: 'short', day: '2-digit',
                        hour: '2-digit', minute: '2-digit', second: '2-digit',
                        hour12: false
                    }) + '<br/>';
                    
                    params.forEach(param => {
                        let value = param.value[1];
                        // Check if this is an active/inactive chart (values are 0 or 1)
                        if ((value === 0 || value === 1) && EChart.formatters.activeState) {
                            value = EChart.formatters.activeState(value);
                        }
                        result += `${param.marker} ${param.seriesName}: ${value}<br/>`;
                    });
                    
                    return result;
                }
            },
            legend: {
                show: false,
                type: 'scroll',
                bottom: '0px',
                icon: 'circle',
                itemWidth: 12,
                itemHeight: 12,
                itemGap: 15,
                textStyle: {
                    fontFamily: 'Roboto',
                    fontSize: 14,
                    color: '#8A99AA'
                },
            },
            grid: {
                show: false,
                top: '60px',
                left: '10px',
                right: '10px',
                bottom: '10px',
                width: 'auto',
                containLabel: true
            },
            toolbox: {
                right: '0px',
                top: '0px',
                showTitle: true,
                feature: {
                    dataZoom: {
                        show: false,
                        yAxisIndex: 'none'
                    },
                    dateView: {
                        show: false,
                    },
                    brush: {
                        show: false
                    },
                    restore: {
                        show: true
                    },
                    saveAsImage: {
                        show: true,
                    },
                    // magicType: {
                    //     show: true,
                    //     type: ['line', 'bar']
                    // }
                },
                iconStyle: {
                    borderColor: '#8A99AA',
                    borderWidth: 1.5,
                    borderCap: 'round'
                },
                emphasis: {
                    iconStyle: {
                        borderColor: '#FFFFFF',
                        textPadding: 10
                    }
                }
            },
            xAxis: {
                type: 'time',
                boundaryGap: false,
                axisLabel: {
                    color: '#8A99AA',
                    formatter: (value) => {
                        const d = new Date(value);
                        return d.toLocaleString(undefined, {
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: false
                        });
                    }
                },
                axisLine: {
                    lineStyle: {
                        color: '#8A99AA'
                    }
                }
            },
            yAxis: {
                type: 'value',
                axisLabel: {
                    color: '#8A99AA'
                },
                axisLine: {
                    lineStyle: {
                        color: '#8A99AA'
                    }
                },
                splitLine: {
                    show: false
                }
            },
            dataZoom: [
                {
                    type: 'inside',
                    start: 80,
                    end: 100
                },
                {
                    type: 'slider',
                    show: false,
                    start: 80,
                    end: 100,
                    height: 40,
                    bottom: 30,
                    showPlayBtn: false,
                    textStyle: {
                        color: '#8A99AA'
                    },
                    borderColor: '#8A99AA',
                    borderRadius: 6,
                    brushSelect: false
                }
            ],
            series: []
        };

        // To store the SetInterval Id for each chart
        this.setIntervalId = {};

        // Register this instance in the static registry
        EChart.instances[this.id] = this;

        // Create or update the chart
        this.createOrUpdateChart(id);

        // Start auto-update
        this.startAutoUpdate();
    }

    /**
     * Start auto-updating the chart
     */
    startAutoUpdate()
    {
        // If autoUpdate is enabled, set an interval to update the chart
        // Default: update every 10 seconds
        if (!this.autoUpdate) return;

        this.setIntervalId[this.id] = setInterval(async () => {
            console.info('EChart.startAutoUpdate: updating chart for id=' + this.id);

            // Get chart data
            await this.get(this.id);

            const chartElement = document.querySelector("#" + this.id);
            const chart = chartElement?._chartInstance;
            if (!chart) return;

            // Get current dataZoom state to preserve zoom/pan
            const currentOption = chart.getOption();
            const currentDataZoom = currentOption.dataZoom;
            
            // Check if chart is in natural state (not zoomed)
            const isNaturalState = this.isInNaturalState(currentDataZoom);

            // Build series data
            const series = this.buildSeries();

            // Update chart with new data
            chart.setOption({
                series: series
            });

            // Only restore zoom state if user has zoomed/panned (not in natural state)
            if (!isNaturalState && currentDataZoom) {
                chart.setOption({
                    dataZoom: currentDataZoom
                });
            }

        }, this.autoUpdateInterval);
    }

    /**
     * Stop auto-updating the chart
     * @param {string} id - Optional chart ID, uses this.id if not provided
     */
    stopAutoUpdate(id = null)
    {
        const chartId = id || this.id;
        // Clear the interval to stop auto-updating
        if (this.setIntervalId[chartId]) {
            console.info('EChart.stopAutoUpdate: stopping auto-update for chart id=' + chartId);
            clearInterval(this.setIntervalId[chartId]);
            delete this.setIntervalId[chartId];
        }
    }

    /**
     * Restart auto-updating the chart
     */
    restartAutoUpdate()
    {
        this.stopAutoUpdate();
        this.startAutoUpdate();
    }

    /**
     * Check if chart is in natural (unzoomed) state
     */
    isInNaturalState(dataZoom)
    {
        if (!dataZoom || dataZoom.length === 0) return true;
        
        // Consider natural if showing most of the data (e.g., > 90%)
        const insideZoom = dataZoom.find(dz => dz.type === 'inside') || dataZoom[0];
        if (!insideZoom) return true;
        
        const range = (insideZoom.end || 100) - (insideZoom.start || 0);
        return range > 90;
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
                        days: this.days,
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
                reject('Failed to get chart data: ' + error);
            }
        });
    }

    /**
     * Build series array formatted for EChart
     */
    buildSeries()
    {
        if (this.type === 'line') {
            return this.datasets.map(dataset => {
                const data = dataset.data.map((v, i) => [this.labels[i], v]);
                // Use dataset color if defined, otherwise use default green color
                const lineColor = dataset.color || '#15bf7f';
                return {
                    name: dataset.name,
                    type: 'line',
                    color: lineColor,
                    smooth: true,
                    symbol: 'none',
                    lineStyle: {
                        width: 2,
                        color: lineColor
                    },
                    areaStyle: {
                        opacity: 0.25,
                        color: lineColor
                    },
                    data: data
                };
            });
        }

        if (this.type === 'bar') {
            return this.datasets.map(dataset => {
                const data = dataset.data.map((v, i) => {
                    const item = [this.labels[i], v];

                    // If we have individual colors defined, apply them
                    if (dataset.colors && dataset.colors[i]) {
                        // For bar charts, we need to return an object with itemStyle
                        return {
                            value: item,
                            itemStyle: {
                                borderRadius: [4, 4, 0, 0],
                                color: dataset.colors[i]
                            }
                        };
                    }
                    
                    return item;
                });

                return {
                    name: dataset.name,
                    type: 'bar',
                    color: dataset.color, // Default color if no individual colors
                    barMaxWidth: 30, // Maximum width of each bar in pixels
                    itemStyle: {
                        borderRadius: [4, 4, 0, 0],
                        color: dataset.color // Default color
                    },
                    emphasis: {
                        itemStyle: {
                            opacity: 0.8
                        }
                    },
                    data: data
                };
            });
        }

        if (this.type === 'barHorizontal') {
            return this.datasets.map(dataset => {
                const data = dataset.data.map((v, i) => {
                    // For horizontal bars, just use the value (not [label, value])
                    // Labels are handled by yAxis.data
                    const item = v;

                    // If we have individual colors defined, apply them
                    if (dataset.colors && dataset.colors[i]) {
                        // For bar charts, we need to return an object with itemStyle
                        return {
                            value: item,
                            itemStyle: {
                                borderRadius: [0, 4, 4, 0], // Right rounded for horizontal
                                color: dataset.colors[i]
                            }
                        };
                    }
                    
                    return item;
                });

                return {
                    name: dataset.name,
                    type: 'bar',
                    color: dataset.color, // Default color if no individual colors
                    barMaxWidth: 30, // Maximum height for horizontal bars
                    itemStyle: {
                        borderRadius: [0, 4, 4, 0], // Right rounded for horizontal
                        color: dataset.color // Default color
                    },
                    emphasis: {
                        itemStyle: {
                            opacity: 0.8
                        }
                    },
                    data: data
                };
            });
        }

        if (this.type === 'pie') {
            return this.datasets.map(dataset => {
                const data = dataset.data.map((v, i) => {
                    const item = {
                        name: this.labels[i],
                        value: v
                    };

                    // Add custom color if defined
                    if (dataset.colors && dataset.colors[i]) {
                        item.itemStyle = {
                            color: dataset.colors[i]
                        };
                    }

                    return item;
                });

                return {
                    name: dataset.name,
                    type: 'pie',
                    radius: ['0%', '70%'],
                    center: ['50%', '50%'],
                    itemStyle: {
                        borderRadius: 3
                    },
                    label: {
                        color: '#8A99AA'
                    },
                    data: data
                };
            });
        }

        if (this.type === 'nightingale') {
            return this.datasets.map(dataset => {
                const data = dataset.data.map((v, i) => {
                    const item = {
                        name: this.labels[i],
                        value: v
                    };

                    // Add custom color if defined
                    if (dataset.colors && dataset.colors[i]) {
                        item.itemStyle = {
                            color: dataset.colors[i]
                        };
                    }

                    return item;
                });

                // Get radius values from chartOptions if defined, otherwise use defaults
                let innerRadius = '0%';
                let outerRadius = '65%';
                
                if (this.chartOptions) {
                    if (this.chartOptions.innerRadius !== undefined) {
                        innerRadius = this.chartOptions.innerRadius;
                    }
                    if (this.chartOptions.outerRadius !== undefined) {
                        outerRadius = this.chartOptions.outerRadius;
                    }
                }

                return {
                    name: dataset.name,
                    type: 'pie',
                    roseType: 'radius',
                    radius: [innerRadius, outerRadius], // Configurable radius values
                    center: ['50%', '50%'],
                    itemStyle: {
                        borderRadius: 2
                    },
                    label: {
                        color: '#8A99AA',
                        position: 'outside'
                    },
                    labelLine: {
                        show: true,
                        lineStyle: {
                            color: '#8A99AA'
                        }
                    },
                    data: data
                };
            });
        }

        if (this.type === 'doughnut') {
            return this.datasets.map(dataset => {
                const data = dataset.data.map((v, i) => {
                    const item = {
                        name: this.labels[i],
                        value: v
                    };

                    // Add custom color if defined
                    if (dataset.colors && dataset.colors[i]) {
                        item.itemStyle = {
                            color: dataset.colors[i]
                        };
                    }

                    return item;
                });

                // Get radius values from chartOptions if defined, otherwise use defaults
                let innerRadius = '40%';
                let outerRadius = '90%';
                
                if (this.chartOptions) {
                    if (this.chartOptions.innerRadius !== undefined) {
                        innerRadius = this.chartOptions.innerRadius;
                    }
                    if (this.chartOptions.outerRadius !== undefined) {
                        outerRadius = this.chartOptions.outerRadius;
                    }
                }

                const seriesConfig = {
                    name: dataset.name,
                    type: 'pie',
                    radius: [innerRadius, outerRadius], // Configurable radius values
                    center: ['50%', '50%'],
                    avoidLabelOverlap: false,
                    itemStyle: {
                        borderRadius: 3
                    },
                    label: {
                        show: false,
                        position: 'center'
                    },
                    labelLine: {
                        show: false
                    },
                    data: data
                };

                // Handle emphasis (hover effects)
                if (this.chartOptions.emphasis?.disabled === true) {
                    // Completely disable hover effects
                    seriesConfig.emphasis = {
                        disabled: true
                    };
                } else {
                    // Default emphasis behavior
                    seriesConfig.emphasis = {
                        label: {
                            show: false
                        }
                    };
                }

                return seriesConfig;
            });
        }
    }

    /**
     * Generic method to create or update a chart
     * @param {string} id - Chart ID
     * @returns {Promise}
     */
    async createOrUpdateChart(id)
    {
        await this.get(id);

        const chartElement = document.querySelector("#" + id);
        const existingChart = chartElement?._chartInstance;

        if (existingChart) {
            return this.updateChart(existingChart);
        }

        return this.createChart(id);
    }

    /**
     * Create the chart for the first time
     */
    createChart(id)
    {
        console.info('EChart.createChart: creating chart for id=' + id);

        // Get the container element and bail out if missing
        const chartElement = document.querySelector("#" + id);
        if (!chartElement) {
            console.warn('EChart.createChart: container not found for id=', id);
            return;
        }

        // Clone base options
        const options = JSON.parse(JSON.stringify(this.baseOptions));

        // Build series data
        const series = this.buildSeries();
        options.series = series;

        // For pie charts, hide axes and grid
        if (this.type === 'pie') {
            options.xAxis.show = false;
            options.yAxis.show = false;
            options.grid.show = false;
            // Disable dataZoom to avoid conflicts with page scroll
            options.dataZoom = [];
        }

        // For nightingale charts, hide axes and grid (same as pie)
        if (this.type === 'nightingale') {
            options.xAxis.show = false;
            options.yAxis.show = false;
            options.grid.show = false;
            // Disable dataZoom to avoid conflicts with page scroll
            options.dataZoom = [];
        }

        // For doughnut charts, hide axes and grid (same as pie)
        if (this.type === 'doughnut') {
            options.xAxis.show = false;
            options.yAxis.show = false;
            options.grid.show = false;
            // Disable dataZoom to avoid conflicts with page scroll
            options.dataZoom = [];
        }

        // For line charts, adjust tooltip trigger
        if (this.type === 'line') {
            options.tooltip.trigger = 'axis';
        }

        // For bar charts, adjust axis configuration
        if (this.type === 'bar') {
            // For bar charts, we usually use categories on the X axis
            options.xAxis.type = 'category';
            options.xAxis.data = this.labels;
            options.xAxis.boundaryGap = true; // Add space around bars
            options.xAxis.axisLabel.rotate = 45;
            options.xAxis.axisLabel.interval = 0; // Show all labels
            options.xAxis.axisLabel.fontSize = 12;
            options.xAxis.axisLabel.textStyle = {
                color: '#8A99AA'
            };
            // Disable dataZoom to avoid conflicts with page scroll
            options.dataZoom = [];
        }

        // For horizontal bar charts, just swap the axes (same as vertical but inverted)
        if (this.type === 'barHorizontal') {
            // Y axis: categories (instead of X for vertical bars)
            options.yAxis.type = 'category';
            options.yAxis.data = this.labels;
            options.yAxis.axisLabel = {
                color: '#8A99AA'
            };

            // X axis: values (instead of Y for vertical bars)
            options.xAxis.type = 'value';
            options.xAxis.boundaryGap = [0, 0.01];
            options.xAxis.axisLabel = {
                color: '#8A99AA'
            };
            options.xAxis.splitLine = {
                show: false  // Hide vertical grid lines
            };
            delete options.xAxis.data;
            
            // Disable dataZoom to avoid conflicts with page scroll
            options.dataZoom = [];
        }

        // Set title
        if (this.chartOptions.title?.text) {
            options.title.text = this.chartOptions.title.text;
            options.title.left = this.chartOptions.title.align || 'left';
        }

        // Y-axis features
        if (this.chartOptions.yaxis) {
            if (this.chartOptions.yaxis.min !== undefined) {
                options.yAxis.min = this.chartOptions.yaxis.min;
            }
            if (this.chartOptions.yaxis.max !== undefined) {
                options.yAxis.max = this.chartOptions.yaxis.max;
            }

            // If server sends a formatterName, use table lookup
            if (this.chartOptions.yaxis.labels?.formatterName) {
                const formatterFn = EChart.formatters[this.chartOptions.yaxis.labels.formatterName];
                if (formatterFn) {
                    options.yAxis.axisLabel.formatter = formatterFn;
                    
                    // Special case for activeState (binary 0/1 charts)
                    if (this.chartOptions.yaxis.labels.formatterName === 'activeState') {
                        options.yAxis.min = 0;
                        options.yAxis.max = 1;
                        options.yAxis.interval = 1;
                        options.yAxis.splitNumber = 1;
                        
                        // Override tooltip formatter for activeState charts
                        options.tooltip.formatter = (params) => {
                            if (params.length === 0) return '';
                            
                            const timestamp = params[0].axisValue;
                            const d = new Date(Number(timestamp));
                            let result = d.toLocaleString(undefined, {
                                year: 'numeric', month: 'short', day: '2-digit',
                                hour: '2-digit', minute: '2-digit', second: '2-digit',
                                hour12: false
                            }) + '<br/>';
                            
                            params.forEach(param => {
                                // Get the actual value - could be param.value[1] or just param.value
                                let rawValue = Array.isArray(param.value) ? param.value[1] : param.value;
                                const value = EChart.formatters.activeState(Number(rawValue));
                                result += `${param.marker} ${param.seriesName}: ${value}<br/>`;
                            });
                            
                            return result;
                        };
                    }
                }
            }
        }

        // Toolbar show / hide
        if (this.chartOptions.toolbox?.show === false) {
            options.toolbox.show = false;
        }

        // Tooltip show / hide
        if (this.chartOptions.tooltip?.show === false) {
            options.tooltip.show = false;
        }

        // Legend
        if (this.chartOptions.legend?.show === true) {
            options.legend.show = true;

            // If legend is show, adjust grid bottom to make room
            options.grid.bottom = '30px';
        }

        // Window size for initial zoom (default 15 points) - only for line charts
        if (this.type === 'line') {
            const visibleCount = this.chartOptions?.['init-zoom'] ?? 15;
            const totalPoints = this.labels.length;
            if (totalPoints > visibleCount) {
                // Find the range that contains the most recent data with actual values
                let hasDataInRange = false;
                let startPercent = Math.max(0, ((totalPoints - visibleCount) / totalPoints) * 100);
                
                // Check if there's actual data in the calculated range
                for (let i = Math.floor(totalPoints * startPercent / 100); i < totalPoints; i++) {
                    for (let dataset of this.datasets) {
                        if (dataset.data[i] && dataset.data[i] !== 0) {
                            hasDataInRange = true;
                            break;
                        }
                    }
                    if (hasDataInRange) break;
                }
                
                // If no data in the default range, show the full range instead
                if (!hasDataInRange) {
                    startPercent = 0;
                    options.dataZoom[0].end = 100;
                    options.dataZoom[1].end = 100;
                }
                
                options.dataZoom[0].start = startPercent;
                options.dataZoom[1].start = startPercent;
            } else {
                // If we have few data points, show everything
                options.dataZoom[0].start = 0;
                options.dataZoom[0].end = 100;
                options.dataZoom[1].start = 0;
                options.dataZoom[1].end = 100;
            }
        }

        // Initialize EChart with Canvas renderer for better performance
        const chart = echarts.init(chartElement, null, {
            renderer: 'canvas'
        });
        
        // Set options and render
        chart.setOption(options);

        // Remove spinner
        $('#' + id + '-loading').hide();

        chartElement._chartInstance = chart;

        // Handle window resize
        window.addEventListener('resize', () => {
            chart.resize();
        });
    }

    // helper local formatter (use user's locale and local timezone)
    _formatDate(val, withDate = false) {
        const d = new Date(Number(val));

        if (withDate) {
            return d.toLocaleString(undefined, {
                year: 'numeric', month: 'short', day: '2-digit',
                hour: '2-digit', minute: '2-digit', second: '2-digit',
                hour12: false
            });
        }

        return d.toLocaleString(undefined, {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        });
    }

    /**
     * Update an existing chart without resetting zoom/pan
     */
    updateChart(chart)
    {
        // Get current dataZoom state to preserve zoom/pan
        const currentOption = chart.getOption();
        const currentDataZoom = currentOption.dataZoom;
        
        // Check if chart is in natural state
        const isNaturalState = this.isInNaturalState(currentDataZoom);

        // Build new series
        const series = this.buildSeries();

        // Update chart with new data
        chart.setOption({
            series: series
        });

        // Only restore zoom if user has zoomed/panned (not in natural state)
        if (!isNaturalState && currentDataZoom) {
            chart.setOption({
                dataZoom: currentDataZoom
            });
        }

        $('#' + this.id + '-loading').hide();
    }
}

/**
 * Static methods for external control of charts
 */

/**
 * Stop auto-update for a specific chart by ID (external access)
 * @param {string} chartId - The chart ID
 */
EChart.stopAutoUpdateById = function(chartId) {
    const instance = EChart.instances[chartId];
    if (instance) {
        instance.stopAutoUpdate();
        return true;
    }
    console.warn('EChart.stopAutoUpdateById: chart not found for id=' + chartId);
    return false;
};

/**
 * Start auto-update for a specific chart by ID (external access)
 * @param {string} chartId - The chart ID
 */
EChart.startAutoUpdateById = function(chartId) {
    const instance = EChart.instances[chartId];
    if (instance) {
        instance.restartAutoUpdate();
        return true;
    }
    console.warn('EChart.startAutoUpdateById: chart not found for id=' + chartId);
    return false;
};

/**
 * Stop auto-update for all charts (external access)
 */
EChart.stopAllAutoUpdates = function() {
    Object.values(EChart.instances).forEach(instance => {
        instance.stopAutoUpdate();
    });
};

/**
 * Start auto-update for all charts (external access)
 */
EChart.startAllAutoUpdates = function() {
    Object.values(EChart.instances).forEach(instance => {
        instance.restartAutoUpdate();
    });
};

/**
 * Get chart instance by ID (external access)
 * @param {string} chartId - The chart ID
 * @returns {EChart|null} - The chart instance or null
 */
EChart.getInstance = function(chartId) {
    return EChart.instances[chartId] || null;
};

/**
 * Clean up chart instance from registry
 * @param {string} chartId - The chart ID
 */
EChart.destroyInstance = function(chartId) {
    console.info('EChart.destroyInstance: destroying chart instance for id=' + chartId);
    const instance = EChart.instances[chartId];
    if (instance) {
        instance.stopAutoUpdate();
        delete EChart.instances[chartId];
        return true;
    }
    return false;
};

/**
 * Recreate a chart by destroying and creating a new instance
 * @param {*} type 
 * @param {*} id 
 * @param {*} autoUpdate 
 * @param {*} autoUpdateInterval 
 * @param {*} days 
 */
EChart.recreate = function(type, id, autoUpdate = true, autoUpdateInterval = 15000, days = window.innerWidth < 600 ? 1 : 3) {
    if (EChart.destroyInstance(id)) {
        // Make spinner visible before creating new chart
        $('#' + id + '-loading').show();

        new EChart(type, id, autoUpdate, autoUpdateInterval, days);
    }
}

/**
 * Register available formatters (to avoid eval)
 */
EChart.formatters = {
    // Example usage: formatterName: "activeState"
    activeState: (val) => val === 1 ? "active" : "inactive",
};
